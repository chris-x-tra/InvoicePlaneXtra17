<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');

/*
 * Generischer Dokumenten-Upload für Clients und Users (inkl. Profilbild).
 *
 * Routen:
 *   documents/upload/<client|user>/<id>[/avatar]    Formular
 *   documents/do_upload/<client|user>/<id>[/avatar] Speichern (POST)
 *   documents/delete/<document_id>                  Löschen (POST)
 *   documents/download/<document_id>                Download (geschützt)
 *   documents/preview/<document_id>                 Vorschaubild (JPEG)
 *   documents/avatar/<user_id>                      Profilbild (Vorschau 240x240, sonst Original)
 *
 * Ablage: uploads/documents/<xx>/<datei>         xx = erste 2 Zeichen von md5(dateiname)  (do_hash_dir)
 * Preview: uploads/documents/previews/<xx>/<datei-ohne-ext>_preview.jpg   (preview_helper)
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

    private const MAX_KB = 15360; // 15 MB (CI erwartet KB)

    private const AVATAR_ORIGINAL_W     = 1536; // gespeichertes Profilbild-Original: max. Breite
    private const AVATAR_ORIGINAL_MAX_H = 4096; // und Höhe (Schutz bei sehr hohen Bildern)

    private const PREVIEW_AVATAR = 240; // max. Kantenlänge Profilbild-Vorschau
    private const PREVIEW_DOC    = 480; // max. Kantenlänge Dokument-Vorschau

    public function __construct()
    {
        parent::__construct();
        $this->load->model('documents/mdl_documents');
        $this->load->helper(['file_security', 'preview']);
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

    // ------------------------------------------------------------------ Pfade

    private function preview_size($doc): int
    {
        return $doc->document_kind === 'avatar' ? self::PREVIEW_AVATAR : self::PREVIEW_DOC;
    }

    /** Pfad der Vorschau; erzeugt sie bei Bedarf (preview_helper) */
    private function preview_path($doc): ?string
    {
        return doc_ensure_preview($this->folder(), $doc->document_filename, $this->preview_size($doc));
    }

    // ------------------------------------------------------------------ Aktionen

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
            mkdir($folder, 0775, true);
        }

        $orig = $_FILES['document']['name'] ?? '';
        $orig = preg_replace('/[^\p{L}\p{N}\s\-_.]/u', '', basename($orig));

        // CI speichert zunächst flach in $folder; danach wird die Datei ins Hash-Verzeichnis verschoben.
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

        $final = $this->upload->data('file_name');   // endgültiger Name (CI kann ihn anpassen)
        $flat  = $this->upload->data('full_path');

        // Profilbild: Original auf max. 1536 px Breite verkleinern und als JPEG speichern (Name bekommt .jpg).
        // Klappt es nicht (kein Bild-Engine), bleibt das Original unverändert.
        if ($kind === 'avatar') {
            $shrunk = shrink_image_to_jpg($flat, self::AVATAR_ORIGINAL_W, self::AVATAR_ORIGINAL_MAX_H, 85);
            if ($shrunk !== null) {
                $flat  = $shrunk;
                $final = basename($shrunk);
            }
        }

        if (doc_store_uploaded($flat, $final, $folder) === null) {
            @unlink($flat);
            $this->session->set_flashdata('alert_error', 'Datei konnte nicht gespeichert werden.');
            redirect('documents/upload/' . $type . '/' . $id . '/' . $kind);
        }

        $doc_id = $this->mdl_documents->insert_document($type, $id, $kind, $final);

        // Vorschau sofort erzeugen (Bilder, PDF: erste Seite). Fehler sind nicht kritisch, sie wird sonst beim ersten Abruf nachgeholt.
        $doc = $this->mdl_documents->get_document($doc_id);
        if ($doc) {
            $this->preview_path($doc);
        }

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

    /** Vorschaubild; ohne mögliche Vorschau ein schlankes 404 (die Liste blendet das Bild dann aus) */
    public function preview($document_id): void
    {
        $doc = $this->mdl_documents->get_document($document_id);
        if (!$doc || !$this->can_read($doc)) {
            http_response_code(404);
            exit;
        }

        $path = $this->preview_path($doc);
        if ($path === null) {
            http_response_code(404);
            exit;
        }

        $this->send_image($path);
    }

    /**
     * documents/avatar/<user_id>        Vorschau 240x240
     * documents/avatar/<user_id>/full   großes Bild (gespeichertes Original, max. 1536 px breit)
     */
    public function avatar($user_id = 0, $variant = ''): void
    {
        $doc = $this->mdl_documents->get_avatar($user_id);
        if (!$doc) {
            http_response_code(404);
            exit;
        }

        if ($variant === 'full') {
            $full = doc_source_path($this->folder(), $doc->document_filename);
            $mime = $full !== null && function_exists('mime_content_type') ? mime_content_type($full) : '';
            if ($full === null || strpos((string) $mime, 'image/') !== 0) {
                http_response_code(404);
                exit;
            }
            $this->send_image($full, $mime);
        }

        $path = $this->preview_path($doc);
        if ($path !== null) {
            $this->send_image($path);
        }

        $this->send_file($doc, true); // Fallback: Original
    }

    // ------------------------------------------------------------------ Ausgabe

    /** Bild (Standard: JPEG-Vorschau) mit ETag (neues Profilbild = neue Datei = neuer ETag, kein veralteter Cache) */
    private function send_image(string $path, string $mime = 'image/jpeg'): void
    {
        $etag = '"' . md5($path . filemtime($path)) . '"';

        header('Content-Type: ' . $mime);
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: private, no-cache'); // immer revalidieren, 304 spart die Übertragung
        header('ETag: ' . $etag);

        if (($_SERVER['HTTP_IF_NONE_MATCH'] ?? '') === $etag) {
            http_response_code(304);
            exit;
        }

        header('Content-Length: ' . filesize($path));
        readfile($path);
        exit;
    }

    private function send_file($doc, bool $inline): void
    {
        $path = doc_source_path($this->folder(), $doc->document_filename);
        if ($path === null) {
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
