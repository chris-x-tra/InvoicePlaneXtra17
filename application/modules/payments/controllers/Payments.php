<?php

if ( ! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/*
 * InvoicePlane
 *
 * @author      InvoicePlane Developers & Contributors
 * @copyright   Copyright (c) 2012 - 2018 InvoicePlane.com
 * @license     https://invoiceplane.com/license.txt
 * @link        https://invoiceplane.com
 */

#[AllowDynamicProperties]
class Payments extends Admin_Controller
{
    /**
     * Payments constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->load->model('mdl_payments');
    }

    /* Einnahme Uberschuss Rechnung by chrissie ^ x-tra-designs and ChatGPT */
    public function eur_details($year = null)
    {
        $year = (int) ($year ?: date('Y'));
        $this->load->model('payments/mdl_payment_eur');

        $this->layout->set( [
            'year'     => $year,
            'expenses' => $this->mdl_payment_eur->get_expenses_details($year),
            'income'   => $this->mdl_payment_eur->get_income_details($year)
        ]);
        $this->layout->buffer('content', 'payments/eur_details');
        $this->layout->render();
    }

    public function eur($year = null)
    {
        $year = (int) ($year ?: date('Y'));

        $this->load->model('payments/mdl_payment_eur');

        $expenses = $this->mdl_payment_eur->get_expenses_by_year($year);
        $income = $this->mdl_payment_eur->get_income_by_year($year);

        $this->layout->set([
            'year'     => $year,
            'expenses' => $this->_to_month_array($expenses),
            'income'   => $this->_to_month_array($income),
        ]);
        $this->layout->buffer('content', 'payments/eur');
        $this->layout->render();
    }

    private function _to_month_array($results)
    {
        $out = array_fill(1, 12, 0.00); // Month 1-12
        foreach ($results as $row) {
            $out[(int) $row->month] = (float) $row->total;
        }
        return $out;
    }
/* End EUR */

    /**
     * @param int $page
     */
    public function index($page = 0)
    {
        $this->mdl_payments->paginate(site_url('payments/index'), $page);
        $payments = $this->mdl_payments->result();

        $this->layout->set(
            [
                'filter_display'     => true,
                'filter_placeholder' => trans('filter_payments'),
                'filter_method'      => 'filter_payments',
                'payments'           => $payments,
            ]
        );

        $this->layout->buffer('content', 'payments/index');
        $this->layout->render();
    }

    public function form($id = null)
    {
        if ($this->input->post('btn_cancel')) {
            redirect('payments');
        }

        $this->filter_input();  // <<<--- filters _POST array for nastiness

        $this->load->model('custom_fields/mdl_payment_custom');

        if ($this->mdl_payments->run_validation()) {
            $id = $this->mdl_payments->save($id);

            $this->mdl_payment_custom->save_custom($id, $this->input->post('custom'));

            redirect('payments');
        }

        if ( ! $this->input->post('btn_submit')) {
            $prep_form = $this->mdl_payments->prep_form($id);
            if ($id && ! $prep_form) {
                show_404();
            }

            $this->load->model('custom_values/mdl_custom_values');
            $payment_custom = $this->mdl_payment_custom->where('payment_id', $id)->get();
            if ($payment_custom->num_rows()) {
                $payment_custom = $payment_custom->row();

                unset($payment_custom->payment_id, $payment_custom->payment_custom_id);

                foreach ($payment_custom as $key => $val) {
                    $this->mdl_payments->set_form_value('custom[' . $key . ']', $val);
                }
            }
        } elseif ($this->input->post('custom')) {
            foreach ($this->input->post('custom') as $key => $val) {
                $this->mdl_payments->set_form_value('custom[' . $key . ']', $val);
            }
        }

        $this->load->helper('custom_values');
        $this->load->model([
            'invoices/mdl_invoices',
            'payment_methods/mdl_payment_methods',
            'custom_fields/mdl_custom_fields',
            'custom_values/mdl_custom_values',
        ]);

        $open_invoices = $this->mdl_invoices->is_open()->get()->result();

        $custom_fields = $this->mdl_custom_fields->by_table('ip_payment_custom')->get()->result();
        $custom_values = [];

        foreach ($custom_fields as $custom_field) {
            if (in_array($custom_field->custom_field_type, $this->mdl_custom_values->custom_value_fields())) {
                $values                                        = $this->mdl_custom_values->get_by_fid($custom_field->custom_field_id)->result();
                $custom_values[$custom_field->custom_field_id] = $values;
            }
        }

        $fields = $this->mdl_payment_custom->get_by_payid($id);

        foreach ($custom_fields as $cfield) {
            foreach ($fields as $fvalue) {
                if ($fvalue->payment_custom_fieldid == $cfield->custom_field_id) {
                    // TODO: Hackish, may need a better optimization
                    $this->mdl_payments->set_form_value(
                        'custom[' . $cfield->custom_field_id . ']',
                        $fvalue->payment_custom_fieldvalue
                    );
                    break;
                }
            }
        }

        $amounts                 = [];
        $invoice_payment_methods = [];
        foreach ($open_invoices as $open_invoice) {
            $amounts['invoice' . $open_invoice->invoice_id]                 = format_amount($open_invoice->invoice_balance);
            $invoice_payment_methods['invoice' . $open_invoice->invoice_id] = $open_invoice->payment_method;
        }

        $this->layout->set(
            [
                'payment_id'              => $id,
                'payment_methods'         => $this->mdl_payment_methods->get()->result(),
                'open_invoices'           => $open_invoices,
                'custom_fields'           => $custom_fields,
                'custom_values'           => $custom_values,
                'amounts'                 => json_encode($amounts),
                'invoice_payment_methods' => json_encode($invoice_payment_methods),
            ]
        );

        if ($id) {
            $this->layout->set('payment', $this->mdl_payments->where('ip_payments.payment_id', $id)->get()->row());
        }

        $this->layout->buffer('content', 'payments/form');
        $this->layout->render();
    }


    /**
     * @param int $page
     */
    public function online_logs($page = 0)
    {
        $this->load->model('payments/mdl_payment_logs');

        $this->mdl_payment_logs->paginate(site_url('payments/online_logs'), $page);
        $payment_logs = $this->mdl_payment_logs->result();

        $this->layout->set(
            [
                'filter_display'     => true,
                'filter_placeholder' => trans('filter_online_logs'),
                'filter_method'      => 'filter_online_logs',
                'payment_logs'       => $payment_logs,
            ]
        );

        $this->layout->buffer('content', 'payments/online_logs');
        $this->layout->render();
    }

    /**
     * @param $id
     */
    public function delete($id)
    {
        $this->mdl_payments->delete($id);
        redirect('payments');
    }

    /* ------------------------------------------------------------------
     * Einnahme-Belege: Einnahmen ohne Rechnung in InvoicePlane
     * ------------------------------------------------------------------ */

    public function receipt_index($page = 0)
    {
        $this->load->model('payments/mdl_receipts');

        $this->mdl_receipts->paginate(site_url('payments/receipt_index'), $page);
        $receipts = $this->mdl_receipts->result();

        $this->layout->set([
            'receipts'      => $receipts,
            'receipt_types' => $this->mdl_receipts->receipt_types(),
        ]);
        $this->layout->buffer('content', 'payments/receipt_index');
        $this->layout->render();
    }

    public function receipt_form($id = null)
    {
        $this->load->model('payments/mdl_receipts');
        $this->load->model('payments/mdl_receipts_documents');
        $this->load->model('clients/mdl_clients');

        $id = $id ? (int) $id : null;

        if ($this->input->post('btn_cancel')) {
            redirect('payments/receipt_index');
        }

        $this->filter_input();  // <<<--- filters _POST array for nastiness

        if ($this->mdl_receipts->run_validation()) {
            $id = $this->mdl_receipts->save($id);

            if ( ! $id) {
                $this->session->set_flashdata('alert_error', trans('receipt_save_error'));
                redirect('payments/receipt_form');
            }

            if ($this->mdl_receipts->bank_date_defaulted) {
                $this->session->set_flashdata('alert_info', 'WARNUNG: Es wurde kein Bank-Buchungsdatum angegeben. Es wurde auf das Belegdatum gesetzt - bitte prüfen und nachtragen!');
            }

            if ( ! empty($_FILES['document']['name'])) {
                $this->_receipt_upload($id);
            }

            redirect('payments/receipt_view/' . $id);
        }

        if ($id && ! $this->input->post('btn_submit')) {
            if ( ! $this->mdl_receipts->prep_form($id)) {
                show_404();
            }
        }

        // Zahler-Name fuer das Readonly-Feld (auch nach fehlgeschlagener Validierung)
        $payer_name = '';
        $payer_id   = $this->mdl_receipts->form_value('receipt_client_id');
        if ($payer_id) {
            $client = $this->mdl_clients->get_by_id($payer_id);
            if ($client) {
                $payer_name = format_client($client);
            }
        }

        $this->layout->set([
            'receipt_id'        => $id,
            'receipt_types'     => $this->mdl_receipts->receipt_types(),
            'account_options'   => $this->mdl_receipts->account_options(),
            'payer_name'        => $payer_name,
            'receipt_documents' => $id ? $this->mdl_receipts_documents->get_documents($id) : [],
        ]);
        $this->layout->buffer('content', 'payments/receipt_form');
        $this->layout->render();
    }

    public function receipt_view($id = 0)
    {
        $this->db->reset_query();
        $this->load->model('payments/mdl_receipts');
        $this->load->model('payments/mdl_receipts_documents');

        $receipt = $this->mdl_receipts->get_by_id((int) $id);

        if ( ! $receipt) {
            show_404();
        }

        $this->layout->set([
            'receipt'           => $receipt,
            'receipt_documents' => $this->mdl_receipts_documents->get_documents($receipt->receipt_id),
            'receipt_types'     => $this->mdl_receipts->receipt_types(),
        ]);
        $this->layout->buffer('content', 'payments/receipt_view');
        $this->layout->render();
    }

    public function receipt_delete($id = 0)
    {
        if ($this->input->method() !== 'post') {
            redirect('payments/receipt_index');
        }

        $this->load->model('payments/mdl_receipts');
        $this->load->model('payments/mdl_receipts_documents');

        $this->mdl_receipts_documents->hard_delete_documents_by_receipt((int) $id);
        $this->mdl_receipts->delete((int) $id);

        $this->session->set_flashdata('alert_success', trans('record_successfully_deleted'));
        redirect('payments/receipt_index');
    }

    public function receipt_delete_document($receipt_id = 0, $document_id = 0)
    {
        if ($this->input->method() === 'post') {
            $this->load->model('payments/mdl_receipts_documents');
            $this->mdl_receipts_documents->delete_document((int) $receipt_id, (int) $document_id);
            $this->session->set_flashdata('alert_success', trans('record_successfully_deleted'));
        }

        redirect('payments/receipt_view/' . (int) $receipt_id);
    }

    /**
     * Liefert ein Beleg-Dokument bzw. dessen Vorschau aus (nur mit Login).
     * /payments/receipt_document/<document_id>[/preview]
     */
    public function receipt_document($document_id = 0, $preview = '')
    {
        $this->load->model('payments/mdl_receipts_documents');
        $this->load->helper(['file', 'preview']);

        $doc = $this->mdl_receipts_documents->get_document($document_id);
        if ( ! $doc) {
            show_404();
        }

        $name  = basename($doc->document_filename);
        $clean = Mdl_Receipts_Documents::clean_filename($name);

        if ($preview === 'preview') {
            $path = doc_ensure_preview(Mdl_Receipts_Documents::dir(), $name, 480);  // erzeugt bei Bedarf nach
            $mime = 'image/jpeg';
            $clean = pathinfo($clean, PATHINFO_FILENAME) . '_preview.jpg';
        } else {
            $path = doc_source_path(Mdl_Receipts_Documents::dir(), $name);  // Hash-Verzeichnis, sonst alter flacher Pfad
            $mime = get_mime_by_extension($name) ?: 'application/octet-stream';
        }

        if ($path === null || ! is_file($path)) {
            show_404();
        }

        $inline   = in_array($mime, ['application/pdf', 'image/jpeg', 'image/png', 'image/gif'], true);
        $ascii    = addcslashes(preg_replace('/[^\x20-\x7E]/', '_', $clean), '"\\');

        $this->output
            ->set_content_type($mime)
            ->set_header('X-Content-Type-Options: nosniff')
            ->set_header('Content-Disposition: ' . ($inline ? 'inline' : 'attachment')
                . '; filename="' . $ascii . '"; filename*=UTF-8\'\'' . rawurlencode($clean))
            ->set_output(file_get_contents($path));
    }

    /**
     * Upload eines Beleg-Dokuments, setzt Flash-Meldungen. Gibt true/false zurueck.
     */
    private function _receipt_upload($id)
    {
        $this->load->model('payments/mdl_receipts_documents');

        $this->load->helper('preview');

        $dir = Mdl_Receipts_Documents::dir();

        // eindeutiger Name: R_<id>_<zufall>_<original>
        $new_name = 'R_' . sprintf('%04d', $id) . '_' . bin2hex(random_bytes(4)) . '_' . $_FILES['document']['name'];

        $this->load->library('upload');
        $this->upload->initialize([
            'file_name'     => $new_name,
            'upload_path'   => $dir,
            'allowed_types' => 'odt|ods|pdf|doc|docx|xls|xlsx|jpeg|jpg|png|gif|tiff',
            'max_size'      => 15360,   // KB = 15 MB
        ], false);

        if ( ! $this->upload->do_upload('document')) {
            $this->session->set_flashdata('alert_error', $this->upload->display_errors());

            return false;
        }

        $filename = $this->upload->data('file_name');

        // Original ins Hash-Verzeichnis verschieben (do_hash_dir)
        if (doc_store_uploaded($this->upload->data('full_path'), $filename, $dir) === null) {
            @unlink($this->upload->data('full_path'));
            $this->session->set_flashdata('alert_error', 'Datei konnte nicht gespeichert werden.');

            return false;
        }

        $this->mdl_receipts_documents->insert_document($id, $filename, '');

        // Vorschau (max. 480x480) - Fehler sind nicht kritisch, sie wird beim ersten Abruf nachgeholt
        doc_ensure_preview($dir, $filename, 480);
        $this->session->set_flashdata('alert_success', trans('record_successfully_updated'));

        return true;
    }
}
