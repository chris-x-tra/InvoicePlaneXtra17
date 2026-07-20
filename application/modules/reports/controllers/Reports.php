<?php

if ( ! defined('BASEPATH')) {
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

#[AllowDynamicProperties]
class Reports extends Admin_Controller
{
    /**
     * Reports constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->load->model('mdl_reports');
    }

    public function sales_by_client()
    {
        if ($this->input->post('btn_submit')) {
            $data = [
                'results'   => $this->mdl_reports->sales_by_client($this->input->post('from_date'), $this->input->post('to_date')),
                'from_date' => $this->input->post('from_date'),
                'to_date'   => $this->input->post('to_date'),
            ];

            $html = $this->load->view('reports/sales_by_client', $data, true);

            $this->load->helper('mpdf');

            pdf_create($html, trans('sales_by_client'), true);
        }

        $this->layout->buffer('content', 'reports/sales_by_client_index')->render();
    }

    public function invoices_per_client()
    {
        if ($this->input->post('btn_submit')) {
            $data = [
                'results'   => $this->mdl_reports->invoices_per_client($this->input->post('from_date'), $this->input->post('to_date')),
                'from_date' => $this->input->post('from_date'),
                'to_date'   => $this->input->post('to_date'),
            ];

            $html = $this->load->view('reports/invoices_per_client', $data, true);

            $this->load->helper('mpdf');

            pdf_create($html, trans('invoices_per_client'), true);
        }

        $this->layout->buffer('content', 'reports/invoices_per_client_index')->render();
    }

    public function payment_history()
    {
        if ($this->input->post('btn_submit')) {
            $data = [
                'results'   => $this->mdl_reports->payment_history($this->input->post('from_date'), $this->input->post('to_date')),
                'from_date' => $this->input->post('from_date'),
                'to_date'   => $this->input->post('to_date'),
            ];

            $html = $this->load->view('reports/payment_history', $data, true);

            $this->load->helper('mpdf');

            pdf_create($html, trans('payment_history'), true);
        }

        $this->layout->buffer('content', 'reports/payment_history_index')->render();
    }

    public function invoice_aging()
    {
        if ($this->input->post('btn_submit')) {
            $data = [
                'results' => $this->mdl_reports->invoice_aging(),
            ];

            $html = $this->load->view('reports/invoice_aging', $data, true);

            $this->load->helper('mpdf');

            pdf_create($html, trans('invoice_aging'), true);
        }

        $this->layout->buffer('content', 'reports/invoice_aging_index')->render();
    }

    public function sales_by_year()
    {
        if ($this->input->post('btn_submit')) {
            $data = [
                'results'   => $this->mdl_reports->sales_by_year($this->input->post('from_date'), $this->input->post('to_date'), 
                        $this->input->post('minQuantity'), $this->input->post('maxQuantity'), $this->input->post('checkboxTax')),
                'from_date' => $this->input->post('from_date'),
                'to_date'   => $this->input->post('to_date'),
            ];

            $html = $this->load->view('reports/sales_by_year', $data, true);

            $this->load->helper('mpdf');

            pdf_create($html, trans('sales_by_date'), true);
        }
        $this->layout->buffer('content', 'reports/sales_by_year_index')->render();
    }

    /**
     * Fortytools-compatible csv export 
     */
    private function herrfrau($g) {
        if ($g==0) return "Herr";
        if ($g==1) return "Frau";
        return "";
    }

    private function geehrte($g) {
        if ($g==0) return "Sehr geehrter";
        if ($g==1) return "Sehr geehrte";
        return "";
    }

    public function  customer_export($only_active=1)
    {
        $this->load->model('clients/mdl_clients');
        $this->load->model('clients/mdl_client_extended');

        if ($only_active == 1) {
            $this->mdl_clients->with_total_balance()
                ->where('client_active','1')
                ->order_by('ip_clients.client_id','ASC')->get();
        } else {
            $this->mdl_clients->with_total_balance()
                ->order_by('ip_clients.client_id','ASC')->get();
        }
        $clients = $this->mdl_clients->result();

            $csv_clients=[];
            $csv_clients[] =
                 "client_id;client_date_created;active;pre_salutation;client_salutation;client_surname;"
                ."client_name;client_name_combined;client_address_1;client_city;client_zip;client_phone;"
                ."client_mobile;client_birthdate;customer_no;customer_insurance_number;care_level;care_level_since";

            foreach ($clients as $c) {
                $str = '"'. $c->client_id           . '";'.
                       '"'. $c->client_date_created . '";' ;

                if ( $c->client_active == 1) {
                    $str .='"Kunde";';
                } else {
                    $str .='"Ehemaliger Kunde";';
                }

               $str .= '"'.$this->geehrte($c->client_gender)   .'";';
                // wenn salutation leer ist, aus gender ableiten
                if (!empty($c->salutation)){
                    $str .= '"'.$c->salutation   .'";';
                } else {
                    $str .= '"'.$this->herrfrau($c->client_gender)  .'";';
                }

                $str .=
                    '"'.$c->client_surname  .'";'.
                    '"'.$c->client_name     .'";'.
                    '"'.$c->client_surname   .' '. $c->client_name       .'";'.
                    '"'.$c->client_address_1 .' '. $c->client_address_2  .'";'.
                    '"'.$c->client_city     .'";'.
                    '"'.$c->client_zip      .'";'.

                    '"'.$c->client_phone    .'";'.
                    '"'.$c->client_mobile   .'";'.
                    '"'.$c->client_birthdate.'";'.
                    '"'.$c->customer_no     .'";'.
                    '"'.$c->health_insurance_number .'";'.
                    '"'.$c->carelevel       .'";'.
                    '"'.$c->carelevel_since .'";'.

                    '"'.$c->invoice_salutation .'";'.
                    '"'.$c->invoice_contact_person .'";'.
                    '"'.$c->invoice_name    .'";'.
                    '"'.$c->invoice_name2   .'";'.
                    '"'.$c->invoice_address_1 .' '. $c->invoice_address_2 .'";'.
                    '"'.$c->invoice_zip     .'";'.
                    '"'.$c->invoice_city    .'";'
                ;
                $csv_clients[]=$str;
            }

            if ($this->input->post('btn_submit') ) {
                // write csv to file, download
                $csvfile = UPLOADS_TEMP_FOLDER . 'invoiceplane-export-' . uniqid() . '.csv';

                $fp = fopen($csvfile, 'w');
                if ($fp === false) {
                    log_message('error', 'CSV export: konnte Datei nicht öffnen: ' . $csvfile);
                    show_error('CSV-Export fehlgeschlagen.', 500);
                }

                // write BOM first
                fwrite($fp, "\xEF\xBB\xBF");

                foreach ($csv_clients as $fields) {
                    fwrite($fp, $fields . "\n");
                }
                fclose($fp);

                $filename = 'Customer-Export-' . date('Y-m-d') . '.csv';

                header('Content-Type: text/csv; charset=utf-8');
                header('Content-Disposition: attachment; filename="' . $filename . '"');
                header('Content-Transfer-Encoding: binary');
                header('Content-Length: ' . filesize($csvfile));
                header('Accept-Ranges: bytes');

                readfile($csvfile);

                // cleanup temp
                unlink($csvfile);
                exit;
            } else {
                $this->layout->set(
                        [ 'csv_clients' => $csv_clients 
                        ] );
                $this->layout->buffer('content', 'reports/customer_export');
                $this->layout->render();
            }
    }

    /* Report of Invoice Types for Maricare for health insurance companies 
    */
    public function  invoice_type($year=null)
    {
        //$this->load->model('clients/mdl_clients');
        //$this->load->model('clients/mdl_client_extended');
        //$this->load->model('invoices/mdl_items');
        $this->load->helper('date_helper');

        $all_year = date("Y");
        if (empty($year)) $year = date("Y")-1;    // default dieser report letztes jahr

        if ($this->input->post('btn_submit') ) {
            if ($this->input->post('my_year')) {
                $year = $this->input->post('my_year');
                redirect('reports/invoice_type/'.$year);
            }
        }

        // count per type and all together
        $types = $this->mdl_reports->invoice_type($year);

        // 45a, 45b -> count carelevel of customers
        $types_carelevel = $this->mdl_reports->invoice_type_45_by_carelevel($year);

        // 45a, 45b: count hours per carelevel
        $data['rows'] = $this->mdl_reports->invoice_type_45_hours_by_carelevel($year);
        // Gesamtzeile in PHP aufsummieren
        $data['total'] = (object) array(
            'anzahl_kunden'     => 0,
            'anzahl_rechnungen' => 0,
            'stunden'           => 0,
        );
        foreach ($data['rows'] as $row) {
            $data['total']->anzahl_kunden     += $row->anzahl_kunden;
            $data['total']->anzahl_rechnungen += $row->anzahl_rechnungen;
            $data['total']->stunden           += $row->stunden;
        }

        $this->layout->set([
                'year'              => $year,
                'types'             => $types,
                'types_carelevel'   => $types_carelevel,
                'rows'              => $data['rows'],
                'total'             => $data['total'],
                'all_year' => range(2020, date('Y') + 1)
        ]);
        $this->layout->buffer('content', 'reports/invoice_types');
        $this->layout->render();
    }
}
