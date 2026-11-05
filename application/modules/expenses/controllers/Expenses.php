<?php

if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/*
 * InvoicePlane
 *
 * @author		InvoicePlane Developers & Contributors
 * @copyright	Copyright (c) 2012 - 2018 InvoicePlane.com
 * @license		https://invoiceplane.com/license.txt
 * @link		https://invoiceplane.com
 */

// expenses by chrissie ^ x-tra-designs 12.2024

#[AllowDynamicProperties]
class Expenses extends Admin_Controller
{

    /**
     * Invoices constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->page_title = trans('expenses');
        $this->layout->set(['page_title' => $this->page_title]);

        $this->load->model('mdl_expenses');
    }

    public function index()
    {

        // profiler for debug by chrissie
        //$this->output->enable_profiler(TRUE);

        // Display all expenses by default
        redirect('expenses/status/all');
    }

    /**
     * @param string $status
     * @param int $page
     */
    public function status($status = 'all', $page = 0)
    {
        // Determine which group of expenses to load
        switch ($status) {
            case 'paid':
                $this->mdl_expenses->is_paid();
                break;
            case 'open':
                $this->mdl_expenses->is_open();
                break;
            case 'overdue':
                $this->mdl_expenses->is_overdue();
                break;
        }

        $this->mdl_expenses->paginate(site_url('expenses/status/' . $status), $page);
        $expenses = $this->mdl_expenses->result();

        $this->layout->set([
                'expenses' => $expenses,
                'status' => $status,
                'expense_types' => $this->mdl_expenses->expense_types(),
                ]);
        $this->layout->buffer('content', 'expenses/index');
        $this->layout->render();
    }

    function dump_post()
    {
        $post = array();
        foreach ( array_keys($_POST) as $key ) {
            $post[$key] = $this->input->post($key);
        }
        echo '<pre>'; print_r($post); echo '</pre>';
    }

    public function form($id = NULL)
    {
        $this->load->model('expenses/mdl_expenses_documents');

        // profiler, post debug by chrissie
        //$this->output->enable_profiler(TRUE);
        //if ( $this->input->post('btn_submit')) $this->dump_post();

        if ($this->input->post('btn_cancel')) {
            redirect('expenses/index');
        }

        $this->filter_input();  // <<<--- filters _POST array for nastiness

        if ($this->mdl_expenses->run_validation()) {
            if ($this->input->post('expense_id'))           // new -> save
                $id = $this->input->post('expense_id');

            $id = $this->mdl_expenses->save($id);

            if (!$id) {        // save error
                $this->session->set_flashdata('alert_error', 'Die Ausgabe konnte nicht gespeichert werden.');
                $this->session->set_flashdata('alert_success', null);
                redirect('expenses/form');
            }

            if ($this->mdl_expenses->bank_date_defaulted) {
                $this->session->set_flashdata('alert_info', 'WARNUNG: Es wurde kein Bank-Buchungsdatum angegeben. Es wurde auf das Datum der Ausgabe gesetzt - bitte prüfen und nachtragen!');
            }

            // is a file uploaded?
            if (!empty($_FILES['document']['name'])) {
                $this->do_upload_document($id);
            }

            redirect('expenses/view/' . $id);
        }

        if ($id && ! $this->input->post('btn_submit')) {
            // wenn  id gesetzt, form mit vorhandenen daten versorgen
            if ( ! $this->mdl_expenses->prep_form($id)) {
                show_404();
            }

            // extra documents
            $expenses_documents = $this->mdl_expenses_documents->get_documents($id);
            $this->layout->set([ 'expenses_documents' => $expenses_documents ]);

            // extra suppliers
            $expense = $this->mdl_expenses->get_by_id($id);
            $supplier_name = $expense->client_name." ".$expense->client_surname;
            $supplier_id = $expense->expense_supplier_id;
            $this->layout->set(
                [ 'supplier_name' => $supplier_name ,
                 'supplier_id' => $supplier_id ]
            );
        }

        $this->layout->set([ 
            'expense_types' => $this->mdl_expenses->expense_types(),
        ]);
        $this->layout->buffer('content', 'expenses/form');
        $this->layout->render();
    }

    private function do_upload_document($id = 0)
    {
        $this->load->model('expenses/mdl_expenses_documents');

        // generate unique name
        $sid = sprintf("%1$04d", $id);
        $new_name = "E_" . $sid . "_" . bin2hex(random_bytes(4)) . "_" . $_FILES['document']['name'];

        $config = array(
                'file_name'  => $new_name,
                'upload_path' => UPLOADS_FOLDER . "expenses_documents/",
                'allowed_types' => "odt|ods|pdf|doc|docx|xls|xlsx|jpeg|jpg|png|gif|tiff",
                'max_size' => 15360             // KB! = 15 MB (vorher 15728640 = 15 GB)
                );
        if (!is_dir(UPLOADS_FOLDER . "expenses_documents/previews")) {
            @mkdir(UPLOADS_FOLDER . "expenses_documents/previews", 0755, true);
        }

        $this->load->library('upload');
        $this->upload->initialize($config, false);

        if ($this->upload->do_upload('document')) {
            $document_filename = $this->upload->data('file_name');
            $document_description = ""; // TODO

            // Save original
            $this->mdl_expenses_documents->insert_document($id, $document_filename, $document_description);

            // Versuch Vorschau zu erzeugen
            $source_path = UPLOADS_FOLDER . "expenses_documents/" . $document_filename;
            $preview_name = pathinfo($document_filename, PATHINFO_FILENAME) . '_preview.jpg';
            $preview_path = UPLOADS_FOLDER . "expenses_documents/previews/" . $preview_name;

            $this->generate_preview($source_path, $preview_path);

            $this->session->set_flashdata('alert_success','Record has been saved successfully.');
        } else {
            $this->session->set_flashdata('alert_error', $this->upload->display_errors());
        }
    }

    /**
     * generate preview from all image formats and PDF
     */
    private function generate_preview($source_path, $preview_path)
    {
        if (!class_exists('Imagick')) {
            return false;
        }

        try {
            $imagick = new Imagick();

            // Bei PDFs nur die erste Seite
            if (strtolower(pathinfo($source_path, PATHINFO_EXTENSION)) === 'pdf') {
                $imagick->setResolution(150, 150);
                $imagick->readImage($source_path . '[0]'); // nur erste Seite
            } else {
                $imagick->readImage($source_path);
            }

            $imagick->setImageFormat('jpeg');
            $imagick->setImageCompressionQuality(80);
            $imagick->writeImage($preview_path);
            $imagick->clear();
            $imagick->destroy();

            return true;
        } catch (Throwable $e) {
            log_message('error', 'Preview generation failed: ' . $e->getMessage());
            return false;
        }
    }

    public function view($id = 0)
    {
        $this->db->reset_query();
        $this->load->model('expenses/mdl_expenses_documents');
        $expense = $this->mdl_expenses->get_by_id($id);

        if (!$expense) {
            show_404();
        }

        $expenses_documents = $this->mdl_expenses_documents->get_documents($id);

        $this->layout->set( [
                'expense' => $expense,
                'expenses_documents' => $expenses_documents,
                'expense_types' => $this->mdl_expenses->expense_types(),
                ] );

        $this->layout->buffer('content', 'expenses/view');
        $this->layout->render();
    }

    /**
     * Liefert ein Ausgaben-Dokument bzw. dessen Vorschau aus (nur mit Login).
     * /expenses/document/<document_id>[/preview]
     */
    public function document($document_id = 0, $preview = '')
    {
        $this->load->model('expenses/mdl_expenses_documents');
        $this->load->helper('file');

        $doc = $this->mdl_expenses_documents->get_document($document_id);
        if (!$doc) {
            show_404();
        }

        $name  = basename($doc->document_filename);
        $clean = Mdl_Expenses_Documents::clean_filename($name);

        if ($preview === 'preview') {
            $path  = Mdl_Expenses_Documents::preview_path($name);
            $mime  = 'image/jpeg';
            $clean = pathinfo($clean, PATHINFO_FILENAME) . '_preview.jpg';
        } else {
            $path = Mdl_Expenses_Documents::dir() . $name;
            $mime = get_mime_by_extension($name) ?: 'application/octet-stream';
        }

        if (!is_file($path)) {
            show_404();
        }

        $inline = in_array($mime, ['application/pdf', 'image/jpeg', 'image/png', 'image/gif'], true);
        $ascii  = addcslashes(preg_replace('/[^\x20-\x7E]/', '_', $clean), '"\\');

        $this->output
            ->set_content_type($mime)
            ->set_header('X-Content-Type-Options: nosniff')
            ->set_header('Content-Disposition: ' . ($inline ? 'inline' : 'attachment')
                . '; filename="' . $ascii . '"; filename*=UTF-8\'\'' . rawurlencode($clean))
            ->set_output(file_get_contents($path));
    }

    public function delete_document ($expense_id, $document_id = 0) 
    {
        if ($this->input->method() !== 'post') {
            redirect('expenses/view/' . (int) $expense_id);
        }
        $this->load->model('expenses/mdl_expenses_documents');
        $this->mdl_expenses_documents->delete_document($document_id, $expense_id);
        $this->session->set_flashdata('alert_success','Record has been DELETED successfully.');
        redirect('expenses/view/' . $expense_id );
    }

    /**
     * @param $id
     */
    public function delete($id)
    {
        if ($this->input->method() !== 'post') {
            redirect('expenses');
        }
        $this->load->model('expenses/mdl_expenses_documents');
        $this->mdl_expenses_documents->hard_delete_documents_by_expense($id);
        $this->mdl_expenses->delete($id);
        $this->session->set_flashdata('alert_success','Record has been DELETED successfully.');
        redirect('expenses');
    }
}

