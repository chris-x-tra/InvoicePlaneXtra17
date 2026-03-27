<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');

/*
 * InvoicePlane
 *
 * @author		InvoicePlane Developers & Contributors
 * @copyright	Copyright (c) 2012 - 2018 InvoicePlane.com
 * @license		https://invoiceplane.com/license.txt
 * @link		https://invoiceplane.com
 */

/**
 * Class Clients
 */


class Clients extends Employee_Controller
{
    /**
     * Clients constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->load->model('clients/mdl_clients');
        $this->load->model('clients/mdl_client_extended');
    }

  // -- -- //
    public function index()
    {
        // Display active clients by default
        redirect('employee/clients/status/active');
    }

    /**
     * @param string $status
     * @param int $page
     */
    public function status($status = 'active', $page = 0)
    {

        $this->load->helper('date_helper');

        if (is_numeric(array_search($status, array('active', 'inactive')))) {
            $function = 'is_' . $status;
            $this->mdl_clients->$function();
        }

        // original query
        //$this->mdl_clients->with_total_balance()->paginate(site_url('clients/status/' . $status), $page);

        // sort asc desc by chrissie
        $sort  = $this->input->get('sort') ?? 'name'; // Standard-Spalte
        $order = $this->input->get('order') ?? 'asc';  // Standard-Reihenfolge

        if ($sort == 'name' && $order =='asc')
            $this->mdl_clients->with_total_balance()->order_by('client_name','ASC') ->paginate(site_url('employee/clients/status/' . $status), $page);
        if ($sort == 'name' && $order =='desc')
            $this->mdl_clients->with_total_balance()->order_by('client_name','DESC') ->paginate(site_url('employee/clients/status/' . $status), $page);
        if ($sort == 'id' && $order =='asc')
            $this->mdl_clients->with_total_balance()->order_by('ip_clients.client_id','ASC') ->paginate(site_url('employee/clients/status/' . $status), $page);
        if ($sort == 'id' && $order =='desc')
            $this->mdl_clients->with_total_balance()->order_by('ip_clients.client_id','DESC') ->paginate(site_url('employee/clients/status/' . $status), $page);
        if ($sort == 'amount' && $order =='asc')
            $this->mdl_clients->with_total_balance()->order_by('client_invoice_balance','ASC') ->paginate(site_url('employee/clients/status/' . $status), $page);
        if ($sort == 'amount' && $order =='desc')
            $this->mdl_clients->with_total_balance()->order_by('client_invoice_balance','DESC') ->paginate(site_url('employee/clients/status/' . $status), $page);
        if ($sort == 'carelevel' && $order =='asc')
            $this->mdl_clients->with_total_balance()->order_by('ip_client_extended.carelevel','ASC') ->paginate(site_url('employee/clients/status/' . $status), $page);
        if ($sort == 'carelevel' && $order =='desc')
            $this->mdl_clients->with_total_balance()->order_by('ip_client_extended.carelevel','DESC') ->paginate(site_url('employee/clients/status/' . $status), $page);
        // end sort

        $clients = $this->mdl_clients->result();
        // ^chrissie

        $this->layout->set(
                array(
                    'sort' => $sort,
                    'order' => $order,
                    'page' => $page,
                    'records' => $clients,
                    'filter_display' => true,
                    'filter_placeholder' => trans('filter_clients'),
                    'filter_method' => 'filter_clients',
                    'filter_value' => ''
                    )
                );

        $this->layout->buffer('content', 'employee/client_index');
$this->layout->render('layout_employee');
    }

    /**
     * @param int $client_id
     */
    public function view($client_id)
    {
        $this->load->model('clients/mdl_client_notes');
        $this->load->model('clients/mdl_documents');
        $this->load->model('invoices/mdl_invoices');
        $this->load->model('quotes/mdl_quotes');
        $this->load->model('payments/mdl_payments');

        $this->load->model('custom_fields/mdl_custom_fields');
        $this->load->model('custom_fields/mdl_client_custom');
        $this->load->model('custom_values/mdl_custom_values');
        $this->load->model('custom_fields/mdl_invoice_custom');

        $this->load->model('reports/mdl_reports');

        // alle custom felder
        $invoice_custom_fields = $this->mdl_custom_fields->by_table('ip_invoice_custom')->get()->result();

        $client = $this->mdl_clients
            ->with_total()
            ->with_total_balance()
            ->with_total_paid()
            ->where('ip_clients.client_id', $client_id)
            ->get()->row();

        // order is important!
        $custom_fields = $this->mdl_client_custom->get_by_client($client_id)->result();

        $this->mdl_client_custom->prep_form($client_id);

        if (!$client) {
            die("Aiee! client was not saved"); 
            show_404();
        }

        // rechnungen jetzt alle by chrissie
        //$invoices = $this->mdl_invoices->by_client($client_id)->limit(20)->get()->result();
        $invoices = $this->mdl_invoices->by_client($client_id)->get()->result();

        // alle invoice custom felder
        // alle custom werte pro custom feld
        $invoice_custom_values = [];
        foreach ($invoice_custom_fields as $custom_field) {
            if (in_array($custom_field->custom_field_type, $this->mdl_custom_values->custom_value_fields())) {
                $values = $this->mdl_custom_values->get_by_fid($custom_field->custom_field_id)->result();
                $invoice_custom_values[$custom_field->custom_field_id] = $values;
            }
        }

        // custom werte pro rechnung holen
        $invoice_custom =[];
        foreach ($invoices as $i) {
            $fields = $this->mdl_invoice_custom->by_id($i->invoice_id)->get()->result();
            $invoice_custom[]=$fields;
        }

        //
        //
        // verbrauchtes budget nach rechnungstyp und jahr by chrissie
        $type_1_total = 0;

        $year = date("Y");
        if ($this->input->post('year')) $year = $this->input->post('year');

        $customer = $client_id;
        $value = '1';
        $the_invoices = $this->mdl_reports->invoice_type_customers($year, $customer, $value);
        foreach ($the_invoices as $t) {
            $invoice_id = $t->invoice_id;
            $invoice = $this->mdl_invoices->get_by_id($invoice_id);
            $type_1_total += $invoice->invoice_total;
        }

        // verbrauchtes budget nach rechnungstyp und jahr by chrissie
        $type_2_total = 0;
        $year = date("Y");
        if ($this->input->post('year')) $year = $this->input->post('year');
        $customer = $client_id;
        $value = '2';
        $the_invoices = $this->mdl_reports->invoice_type_customers($year, $customer, $value);
        foreach ($the_invoices as $t) {
            $invoice_id = $t->invoice_id;
            $invoice = $this->mdl_invoices->get_by_id($invoice_id);
            $type_2_total += $invoice->invoice_total;
        }

        // verbrauchtes budget nach rechnungstyp und jahr by chrissie
        $type_3_total = 0;
        $year = date("Y");
        if ($this->input->post('year')) $year = $this->input->post('year');
        $customer = $client_id;
        $value = '3';
        $the_invoices = $this->mdl_reports->invoice_type_customers($year, $customer, $value);
        foreach ($the_invoices as $t) {
            $invoice_id = $t->invoice_id;
            $invoice = $this->mdl_invoices->get_by_id($invoice_id);
            $type_3_total += $invoice->invoice_total;
        }

        // verbrauchtes budget nach rechnungstyp und jahr by chrissie
        $type_23_total = 0;
        $year = date("Y");
        if ($this->input->post('year')) $year = $this->input->post('year');
        $customer = $client_id;
        $value = '2,3';
        $the_invoices = $this->mdl_reports->invoice_type_customers($year, $customer, $value);
        foreach ($the_invoices as $t) {
            $invoice_id = $t->invoice_id;
            $invoice = $this->mdl_invoices->get_by_id($invoice_id);
            $type_23_total += $invoice->invoice_total;
        }
        $value = '3,2';
        $the_invoices = $this->mdl_reports->invoice_type_customers($year, $customer, $value);
        foreach ($the_invoices as $t) {
            $invoice_id = $t->invoice_id;
            $invoice = $this->mdl_invoices->get_by_id($invoice_id);
            $type_23_total += $invoice->invoice_total;
        }

        //
        // ----------
        //
        // verbrauchtes budget nach rechnungstyp und jahr by chrissie bei jahr -1 - refactor
        $type_1_total_old = 0;
        $year = date("Y")-1;
        if ($this->input->post('year')) $year = $this->input->post('year')-1;
        $customer = $client_id;
        $value = '1';
        $the_invoices = $this->mdl_reports->invoice_type_customers($year, $customer, $value);
        foreach ($the_invoices as $t) {
            $invoice_id = $t->invoice_id;
            $invoice = $this->mdl_invoices->get_by_id($invoice_id);
            $type_1_total_old += $invoice->invoice_total;
        }

        // verbrauchtes budget nach rechnungstyp und jahr by chrissie
        $type_2_total_old = 0;
        $year = date("Y")-1;
        if ($this->input->post('year')) $year = $this->input->post('year')-1;
        $customer = $client_id;
        $value = '2';
        $the_invoices = $this->mdl_reports->invoice_type_customers($year, $customer, $value);
        foreach ($the_invoices as $t) {
            $invoice_id = $t->invoice_id;
            $invoice = $this->mdl_invoices->get_by_id($invoice_id);
            $type_2_total_old += $invoice->invoice_total;
        }

        // verbrauchtes budget nach rechnungstyp und jahr by chrissie
        $type_3_total_old = 0;
        $year = date("Y")-1;
        if ($this->input->post('year')) $year = $this->input->post('year')-1;
        $customer = $client_id;
        $value = '3';
        $the_invoices = $this->mdl_reports->invoice_type_customers($year, $customer, $value);
        foreach ($the_invoices as $t) {
            $invoice_id = $t->invoice_id;
            $invoice = $this->mdl_invoices->get_by_id($invoice_id);
            $type_3_total_old += $invoice->invoice_total;
        }

        // verbrauchtes budget nach rechnungstyp und jahr by chrissie
        $type_23_total_old = 0;
        $year = date("Y")-1;
        if ($this->input->post('year')) $year = $this->input->post('year')-1;
        $customer = $client_id;
        $value = '2,3';
        $the_invoices = $this->mdl_reports->invoice_type_customers($year, $customer, $value);
        foreach ($the_invoices as $t) {
            $invoice_id = $t->invoice_id;
            $invoice = $this->mdl_invoices->get_by_id($invoice_id);
            $type_23_total_old += $invoice->invoice_total;
        }
        $value = '3,2';
        $the_invoices = $this->mdl_reports->invoice_type_customers($year, $customer, $value);
        foreach ($the_invoices as $t) {
            $invoice_id = $t->invoice_id;
            $invoice = $this->mdl_invoices->get_by_id($invoice_id);
            $type_23_total_old += $invoice->invoice_total;
        }
        $year = date("Y");	// beware
        if ($this->input->post('year')) $year = $this->input->post('year');
        //
        // ----------
        //

        $this->layout->set(
                array(
                    'client' => $client,
                    'client_notes' => $this->mdl_client_notes->where('client_id', $client_id)->get()->result(),
                    'documents' => $this->mdl_documents->get_documents($client_id),
                    'invoices' => $invoices,
                    'quotes' => $this->mdl_quotes->by_client($client_id)->limit(20)->get()->result(),
                    'payments' => $this->mdl_payments->by_client($client_id)->limit(20)->get()->result(),
                    'custom_fields' => $custom_fields,
                    'quote_statuses' => $this->mdl_quotes->statuses(),
                    'invoice_statuses' => $this->mdl_invoices->statuses(),
                    'invoice_custom_fields' => $invoice_custom_fields,
                    'invoice_custom_values' => $invoice_custom_values,
                    'invoice_custom'=>$invoice_custom,
                    'type_1_total' => $type_1_total,
                    'type_2_total' => $type_2_total,
                    'type_3_total' => $type_3_total,
                    'type_23_total' => $type_23_total,
                    'type_1_total_old' => $type_1_total_old,
                    'type_2_total_old' => $type_2_total_old,
                    'type_3_total_old' => $type_3_total_old,
                    'type_23_total_old' => $type_23_total_old,
                    'year' => $year
                        )
                        );

        $this->layout->buffer(
                array(
                    array(
                        'document_table', 'clients/partial_document_table'
                        ),
                    array(
                        'partial_notes', 'clients/partial_notes'
                        ),
                    array(
                            'content', 'clients/view'
                         )
                        )
                        );

$this->layout->render('layout_employee');
    }

}
