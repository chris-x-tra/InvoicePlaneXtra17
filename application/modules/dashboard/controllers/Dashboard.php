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
class Dashboard extends Admin_Controller
{
    public function index()
    {
        $this->load->model('invoices/mdl_invoice_amounts');
        $this->load->model('quotes/mdl_quote_amounts');
        $this->load->model('invoices/mdl_invoices');
        $this->load->model('quotes/mdl_quotes');
        $this->load->model('projects/mdl_projects');
        $this->load->model('tasks/mdl_tasks');

        $quote_overview_period   = get_setting('quote_overview_period');
        $invoice_overview_period = get_setting('invoice_overview_period');

        if ($this->input->post()) {
            $this->load->model('mdl_settings');
            if($quote_overview_period = $this->input->post('quote_overview_period')) {
                $this->mdl_settings->save('quote_overview_period', $quote_overview_period);
            } 
            if($invoice_overview_period = $this->input->post('invoice_overview_period')) {
                $this->mdl_settings->save('invoice_overview_period', $invoice_overview_period);
            }
            redirect('dashboard/index');
        }

        $this->layout->set(
            [
                'invoice_status_totals' => $this->mdl_invoice_amounts->get_status_totals($invoice_overview_period),
                'quote_status_totals'   => $this->mdl_quote_amounts->get_status_totals($quote_overview_period),
                'invoice_status_period' => str_replace('-', '_', $invoice_overview_period),
                'quote_status_period'   => str_replace('-', '_', $quote_overview_period),
                'invoices'              => $this->mdl_invoices->limit(10)->get()->result(),
                'quotes'                => $this->mdl_quotes->limit(10)->get()->result(),
                'invoice_statuses'      => $this->mdl_invoices->statuses(),
                'quote_statuses'        => $this->mdl_quotes->statuses(),
                'overdue_invoices'      => $this->mdl_invoices->is_overdue()->get()->result(),
                'projects'              => $this->mdl_projects->get_latest()->get()->result(),
                'tasks'                 => $this->mdl_tasks->get_latest()->get()->result(),
                'task_statuses'         => $this->mdl_tasks->statuses(),

                // easy template choose by chrissie TODO
                'invoice_pdf_templates' => [],
            ]
        );

        $this->layout->buffer('content', 'dashboard/index');
        $this->layout->render();
    }

    //
    // see modules/filter/controllers/Ajax.php
    //
    public function filter_invoices()
    {
        $this->load->model('invoices/mdl_invoices');

        $query = $this->input->post('search-i');
        if (!empty($query) && !ctype_space($query)) {
            $keywords = explode(' ', $query);
            foreach ($keywords as $keyword) {
                if ($keyword) {
                    $keyword = strtolower($keyword);
                    $this->mdl_invoices->like("(CONCAT_WS('^',LOWER(invoice_number),invoice_date_created,invoice_date_due,
                        LOWER(client_name),invoice_total,invoice_balance) COLLATE utf8mb3_general_ci)", $keyword);
                }
            }

            $invoices = $this->mdl_invoices->get()->result();
            $status = $this->mdl_invoices->statuses();
        } else {
            $invoices = [];
            $status = [];
        }

        $this->layout->set(
             [
                'invoices' => $invoices,
                'status' => $status,

                'filter_display' => true,
                'filter_placeholder' => trans('filter_invoices'),
                'filter_method' => 'filter_invoices',
                'invoice_statuses' => $this->mdl_invoices->statuses(),
                'filter_value' => $query,

                // easy template choose by chrissie
                //'invoice_pdf_templates' => $this->mdl_templates->get_invoice_templates('pdf'),
                'invoice_pdf_templates' => [],

                'invoice_custom_fields' => [],
                'invoice_custom_values' => [],
                'invoice_custom' => [],

                'current_records' => -1,
                'offset' => -1,

            ]
        );
        $this->layout->buffer('content', 'invoices/index');
        $this->layout->render();
    }


    //
    // see modules/filter/controllers/Ajax.php
    //
    public function filter_clients()
    {
        $this->load->model('clients/mdl_clients');

        $query = $this->input->post('search');

        // leeren query nicht an db geben - macht 100 % cpu last
        if (!empty($query) && !ctype_space($query)) {
            $keywords = explode(' ', $query);

            foreach ($keywords as $keyword) {
                if ($keyword) {
                    $keyword = strtolower($keyword);
                    $this->mdl_clients->like("CONCAT_WS('^',LOWER(client_name),LOWER(client_surname),LOWER(client_email),client_phone,client_active)", $keyword);
                }
            }

            $clients = $this->mdl_clients->with_total_balance()->get()->result();
        } else {
            $clients =[];
        }

        $this->layout->set(
                array(
                    'sort' => 0,
                    'records' => $clients,
                    'einvoicing' => get_setting('einvoicing'),

                    'filter_display' => true,
                    'filter_placeholder' => trans('filter_clients'),
                    'filter_method' => 'filter_clients',
                    'filter_value' => $query
                 )
                );

        $this->layout->buffer('content', 'clients/index');
        $this->layout->render();
    }

}
