<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');

/*
 * Generischer Dokumenten-Upload für Clients und Users (inkl. Profilbild).
 *
 * Routen:
 *   documents/upload/<client|user>/<id>[/avatar]   Formular
 *   documents/do_upload/<client|user>/<id>[/avatar] Speichern (POST)
 *   documents/delete/<document_id>                  Löschen (POST)
 *   documents/download/<document_id>                Download (geschützt)
 *   documents/avatar/<user_id>                      Profilbild ausliefern
 */
#[AllowDynamicProperties]
class Documents extends Admin_Controller
{
    /** Pro Besitzer-Typ: Dateipräfix + Rücksprung-URL */
    private const OWNERS = [
        'client' => ['prefix' => 'D', 'back' => 'clients/view/%d/documents'],
        'user'   => ['prefix' => 'U', 'back' => 'users/view/%d/documents'],
    ];

    private const ALLOWED = [
        'document' => 'odt|ods|pdf|doc|docx|xls|xlsx|jpeg|jpg|png|gif|tiff|txt',
        'avatar'   => 'jpg|jpeg|png|gif|webp',
    ];

    private const MAX_KB = 15360; // 15 MB (CI erwartet KB! der alte Wert war 15 GB)

    public function __construct()
    {
        parent::__construct();
        $this->load->model('documents/mdl_documents');
    }

    private function folder(): string
    {
        return rtrim(UPLOADS_FOLDER, '/') . '/documents/';
    }

    private function check_owner($type, $kind): void
    {
        if (!isset(self::OWNERS[$type]) || !isset(self::ALLOWED[$kind])) {
            show_404();
        }
    }

    /** Darf der aktuelle Benutzer ändern? (User-Dokumente: nur Admin, Profilbild: Admin oder man selbst) */
    private function can_write($type, $id, $kind): bool
    {
        if ($type === 'client') {
            return true; // wie bisher: jeder angemeldete Mitarbeiter
        }
        $is_admin = (int) $this->session->userdata('user_type') === 1;
        $is_self  = (int) $this->session->userdata('user_id') === (int) $id;

        return $is_admin || ($kind === 'avatar' && $is_self);
    }

    /** Darf der aktuelle Benutzer lesen? */
    private function can_read($doc): bool
    {
        if ($doc->owner_type === 'client' || $doc->document_kind === 'avatar') {
            return true;
        }

        return (int) $this->session->userdata('user_type') === 1
            || (int) $this->session->userdata('user_id') === (int) $doc->owner_id;
    }

    private function back_url($type, $id): string
    {
        return sprintf(self::OWNERS[$type]['back'], (int) $id);
    }

    public function upload($type = 'client', $id = 0, $kind = 'document'): void
    {
        $this->check_owner($type, $kind);
        if (!$this->can_write($type, $id, $kind)) {
            show_error('Forbidden', 403);
        }

        $this->layout->set([
            'owner_type' => $type,
            'owner_id'   => (int) $id,
            'kind'       => $kind,
            'back_url'   => $this->back_url($type, $id),
            'accept'     => '.' . str_replace('|', ',.', self::ALLOWED[$kind]),
        ]);
        $this->layout->buffer('content', 'documents/upload_form');
        $this->layout->render();
    }

    public function do_upload($type = 'client', $id = 0, $kind = 'document'): void
    {
        $this->check_owner($type, $kind);
        if (!$this->can_write($type, $id, $kind)) {
            show_error('Forbidden', 403);
        }

        $id     = (int) $id;
        $folder = $this->folder();
        if (!is_dir($folder)) {
            mkdir($folder, 0755, true);
        }

        $orig = $_FILES['document']['name'] ?? '';
        $orig = preg_replace('/[^\p{L}\p{N}\s\-_.]/u', '', basename($orig));

        $this->load->library('upload', [
            'file_name'     => self::OWNERS[$type]['prefix'] . sprintf('%04d', $id) . '_' . substr(md5(uniqid('', true)), 0, 6) . '_' . $orig,
            'upload_path'   => $folder,
            'allowed_types' => self::ALLOWED[$kind],
            'max_size'      => self::MAX_KB,
        ]);

        if (!$this->upload->do_upload('document')) {
            $this->session->set_flashdata('alert_error', $this->upload->display_errors('', ''));
            redirect('documents/upload/' . $type . '/' . $id . '/' . $kind);
        }

        $this->mdl_documents->insert_document($type, $id, $kind, $this->upload->data('file_name'));
        $this->session->set_flashdata('alert_success', trans('record_successfully_created'));
        redirect($this->back_url($type, $id));
    }

    public function delete($document_id): void
    {
        $doc = $this->mdl_documents->get_document($document_id);
        if (!$doc || $this->input->method() !== 'post') {
            show_404();
        }
        if (!$this->can_write($doc->owner_type, $doc->owner_id, $doc->document_kind)) {
            show_error('Forbidden', 403);
        }

        $this->mdl_documents->delete_document($doc->document_id);
        redirect($this->back_url($doc->owner_type, $doc->owner_id));
    }

    public function download($document_id): void
    {
        $doc = $this->mdl_documents->get_document($document_id);
        if (!$doc || !$this->can_read($doc)) {
            show_404();
        }
        $this->send_file($doc, $doc->document_kind === 'avatar');
    }

    public function avatar($user_id = 0): void
    {
        $doc = $this->mdl_documents->get_avatar($user_id);
        if (!$doc) {
            show_404();
        }
        $this->send_file($doc, true);
    }

    private function send_file($doc, bool $inline): void
    {
        $base = realpath($this->folder());
        $path = realpath($this->folder() . $doc->document_filename);

        // Path-Traversal-Schutz + Existenz
        if (!$base || !$path || strpos($path, $base) !== 0 || !is_file($path)) {
            show_404();
        }

        $mime = function_exists('mime_content_type') ? mime_content_type($path) : 'application/octet-stream';
        if ($inline && strpos($mime, 'image/') !== 0) {
            $inline = false;
        }
        $shown = preg_replace('/^[A-Z]\d{4}_[a-f0-9]{6}_/', '', $doc->document_filename);

        header('Content-Type: ' . $mime);
        header('Content-Length: ' . filesize($path));
        header('X-Content-Type-Options: nosniff');
        header('Content-Disposition: ' . ($inline ? 'inline' : 'attachment') . '; filename="' . str_replace('"', '', $shown) . '"');
        readfile($path);
        exit;
    }
}
