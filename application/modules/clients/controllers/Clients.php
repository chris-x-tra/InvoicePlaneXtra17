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
class Clients extends Admin_Controller
{
    private const CLIENT_TITLE = 'client_title';

    /**
     * Clients constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->page_title = trans('clients');
        $this->layout->set(['page_title' => $this->page_title]);

        $this->load->model('mdl_clients');
    }
    
    /**
     * APP API: Get Clients Data
     * despite of the name, will be fetched with a post request
     */
    function get_ajax_clients()
    {
        $this->load->helper('cors_helper');
        x_cors_helper();

        //log_message('debug', '### before jwt ' );
        $user = $this->verifyJWT();     // check JWT Token
        //log_message('debug', '### user ' . $user);

        $this->load->helper('ajax_helper');

        // fur DEBUG nur erst mal 5 um Zeit zu sparen
        // $cl = get_ajax_clients(0, 5);
      
        // alle holen
        $cl = get_ajax_clients(0, 0);
        echo($cl);
    }

    /**
     * view the last 100 or new notes 
     */
    public function view_notes($new = 1)
    {
        $this->load->model('mdl_client_notes');

        // neue oder alle bzw letzte 100
        if($new == 1) {
                $ts = $this->session->userdata('last_notes_read_ts');
                $notes = $this->mdl_client_notes->get_notes_ts($ts);
        } else {
                $notes = $this->mdl_client_notes->get_notes();
        }

        // TODO improve this via model, aber besser als nix!
        $n_clients=[];
        foreach ($notes as $n) {
            $c = $this->mdl_clients
                    ->where('ip_clients.client_id', $n["client_id"])
                    ->get()->row();

            $n_clients[$n["client_id"]] =
            $c->client_fullname.", ".
            $c->client_zip." ".
            $c->client_city." (".
            $c->customer_no.")";
        }

        $this->layout->set([
                'notes' => $notes,
                'n_clients' => $n_clients,
                'new' => $new
            ]
        );

        $this->layout->buffer('content', 'clients/new_notes');
        $this->layout->render();
    }

    public function new_notes_mark_read()
    {
        // set new timestamp to mark as read
        $this->session->set_userdata('last_notes_read_ts', date('Y-m-d H:i:s'));
        redirect('dashboard');
    }


    // documents
    // https://www.buildwithphp.com/how-to-upload-image-in-codeigniter-with-database-example
    public function do_upload_document($client_id=1)
    {
                $this->load->model('clients/mdl_documents');

                // generate unique name - YMMV
                $sid = sprintf("%1$04d", $client_id);
                $new_name = "D" . $sid . "_" . substr(md5(time()),0,6) . "_" . $_FILES['document']['name'];

                $config = array(
                       'file_name'  => $new_name,
                        'upload_path' => UPLOADS_FOLDER . "documents/",
                        'allowed_types' => "odt|ods|pdf|doc|docx|xls|xlsx|jpeg|jpg|png|gif|tiff",
                        'max_size' => "15728640" 		// your max file size , here it is 15 MB
                );
                $this->load->library('upload', $config);
                if ($this->upload->do_upload('document')) {

                        $document_filename = $this->upload->data('file_name');
                        $document_description = ""; // TODO
                        // instert into database
                        $this->mdl_documents->insert_document( $client_id, $document_filename, $document_description );
                        $this->session->set_flashdata('alert_success','Record has been saved successfully.');
                        redirect('clients/view/' . $client_id . '/documents');
                } else {
                        $this->session->set_flashdata('alert_error', $this->upload->display_errors());
                        redirect('clients/upload_document/' . $client_id);
                }
        }

    public function upload_document($client_id=1)
    {
        $client = $this->mdl_clients
            ->where('ip_clients.client_id', $client_id)
            ->get()->row();

        $this->layout->set(
            array(
                'client' => $client,
                'client_id' => $client_id,
            )
        );

        $this->layout->buffer('content', 'clients/upload_document');
        $this->layout->render();
    }

    public function show_documents($client_id=1)
    {
        $data = get_documents($client_id );
    }

   public function document_del($client_id, $document_id)
   {
        $this->load->model('clients/mdl_documents');
        if ($this->input->post('del')) {
                $this->mdl_documents->delete_document($document_id);
            redirect('clients/view/' . $client_id . '/documents');
        }

        $this->layout->set(
            array(
                'client_id' => $client_id,
                'document_id' => $document_id
            )
        );

        $this->layout->buffer('content', 'clients/delete_document');
        $this->layout->render();
   }

    public function index(): void
    {
        // Display active clients by default
        redirect('clients/status/active');
    }

    /**
     * @param int $page
     */
    public function status(string $status = 'active', $page = 0): void
    {
        $this->load->model('clients/mdl_client_extended');

        // sql profiler debug by chrissie
        //$this->output->enable_profiler(TRUE);

        if (is_numeric(array_search($status, ['active', 'inactive', 'supplier'], true))) {
            $function = 'is_' . $status;
            $this->mdl_clients->{$function}();
        }

	// original query - unmodified
        //$this->mdl_clients->with_total_balance()->paginate(site_url('clients/status/' . $status), $page);

        // sort asc desc by chrissie
        $sort = $this->input->get('sort') ?? 'name'; // Standard-Spalte
        $order = $this->input->get('order') ?? 'asc';  // Standard-Reihenfolge

	if ($sort == 'name' && $order =='asc')
		$this->mdl_clients->with_total_balance()->order_by('ip_clients.client_name','ASC') ->paginate(site_url('clients/status/' . $status), $page);
	if ($sort == 'name' && $order =='desc')
                $this->mdl_clients->with_total_balance()->order_by('ip_clients.client_name','DESC') ->paginate(site_url('clients/status/' . $status), $page);
	if ($sort == 'id' && $order =='asc')
                $this->mdl_clients->with_total_balance()->order_by('ip_clients.client_id','ASC') ->paginate(site_url('clients/status/' . $status), $page);
	if ($sort == 'id' && $order =='desc')
                $this->mdl_clients->with_total_balance()->order_by('ip_clients.client_id','DESC') ->paginate(site_url('clients/status/' . $status), $page);
	if ($sort == 'amount' && $order =='asc')
                $this->mdl_clients->with_total_balance()->order_by('client_invoice_balance','ASC') ->paginate(site_url('clients/status/' . $status), $page);
	if ($sort == 'amount' && $order =='desc')
                $this->mdl_clients->with_total_balance()->order_by('client_invoice_balance','DESC') ->paginate(site_url('clients/status/' . $status), $page);
        if ($sort == 'carelevel' && $order =='asc')
            $this->mdl_clients->with_total_balance()->order_by('ip_client_extended.carelevel','ASC') ->paginate(site_url('clients/status/' . $status), $page);
        if ($sort == 'carelevel' && $order =='desc')
            $this->mdl_clients->with_total_balance()->order_by('ip_client_extended.carelevel','DESC') ->paginate(site_url('clients/status/' . $status), $page);
        // end sort

        $clients = $this->mdl_clients->result();

        $req_einvoicing = get_setting('einvoicing');
        if ($req_einvoicing) {
            $this->load->helper('e-invoice'); // eInvoicing++

            foreach ($clients as &$client) {
                // Get a check of filled Required (client and users) fields for eInvoicing
                $req_einvoicing = get_req_fields_einvoice($client);

                $client = $this->check_client_einvoice_active($client, $req_einvoicing);
            }
            unset($client);
        }

        $this->layout->set(
            [
            'page' => $page,
                'sort' => $sort,
                'order' => $order,
                'records'            => $clients,
                'filter_display'     => true,
                'filter_placeholder' => trans('filter_clients'),
                'filter_method'      => 'filter_clients',
                'einvoicing'         => get_setting('einvoicing'),
                'client_types' => $this->mdl_client_extended->client_types(),
            ]
        );

        $this->layout->buffer('content', 'clients/index');
        $this->layout->render();
    }

    // debug
    function dump_post()
    {
        $post = array();
        foreach ( array_keys($_POST) as $key ) {
            $post[$key] = $this->input->post($key);
        }
        echo '<pre>'; print_r($post); echo '</pre>';
    }

    public function form($id = null): void
    {
   	// profiler for debug by chrissie
    	//$this->output->enable_profiler(TRUE);

	$this->load->model('clients/mdl_client_extended');

        if ($this->input->post('btn_cancel')) {
            redirect('clients');
        }

        /*
    	// debug by chrissie
    	if ( $this->input->post('btn_submit')) {
            $this->dump_post();
            die("btn_submit");
        }
        */

        $new_client = false;
        $this->filter_input();  // <<<--- filters _POST array for nastiness

        // Set validation rule based on is_update
        if ($this->input->post('is_update') == 0 && $this->input->post('client_name') != '') {
            $check = $this->db->get_where('ip_clients', [
                'client_name'    => $this->input->post('client_name'),
                'client_surname' => $this->input->post('client_surname'),
            ])->result();

            if ( ! empty($check)) {
                $this->session->set_flashdata('alert_error', trans('client_already_exists'));
                redirect('clients/form');
            } else {
                $new_client = true;
            }
        }

        if ($this->mdl_clients->run_validation()) {
            $client_title_custom = $this->input->post('client_title_custom');
            // Custom title selected
            if ($_POST[self::CLIENT_TITLE] == ClientTitleEnum::CUSTOM) {
                $_POST[self::CLIENT_TITLE] = $client_title_custom;
                $this->mdl_clients->set_form_value(self::CLIENT_TITLE, $client_title_custom);
            }

            // fix e-invoice reset
            if ($this->input->post('client_start_einvoicing') == '0') {
                $_POST['client_einvoicing_version'] = '';
                $this->mdl_clients->set_form_value('client_einvoicing_version', '');
            }

            $id = $this->mdl_clients->save($id);

            if ($new_client) {
                $this->load->model('user_clients/mdl_user_clients');
                $this->mdl_user_clients->get_users_all_clients();
            }

        //
        // handle extended by chrissie: flags, customer no, ..
        //
        if(ip_mari()) {
                // flags as checkboxes 
                $my_client_flags = 0;
                if ($this->input->post('flag_private')) $my_client_flags |=1;
                if ($this->input->post('flag_39'))      $my_client_flags |=2;
                if ($this->input->post('flag_45a'))     $my_client_flags |=4;
                if ($this->input->post('flag_45b'))     $my_client_flags |=8;
                if ($this->input->post('flag_125'))     $my_client_flags |=16;
                if ($this->input->post('flag_carelevel_confirmation'))  $my_client_flags |=128;
        } else {
                // flags as form field
                $my_client_flags = $this->input->post('client_flags');
        }

	// auto customer number by chrissie for new customer if none entered
        $my_clienttype = intval($this->input->post('client_type'));
	if ($my_clienttype == 0) $my_clienttype = 1; // always be on the safe side!
	$my_customerno = $this->input->post('customer_no');
        if (empty($my_customerno)) {
                // generate correct new customer number from sequence number module - YeeHa!
		// 1 client - 2 supplier
	        $this->load->model('number_sequences/mdl_number_sequences');
                $my_customerno = $this->mdl_number_sequences->generate_sequence_number($my_clienttype);
        }

	// insert or update?
	$exists_extended = $this->mdl_client_extended->get_by_clientid($id);
	if ($exists_extended == NULL) {
		// extended save by chrissie - how to do this in a simpler way?
		$a = $this->mdl_client_extended->insert_entry(
		$id,
		$my_customerno,
		$my_client_flags,
		$this->input->post('contract'),
		$this->input->post('direct_debit'),
		$this->input->post('bank_name'),
		$this->input->post('bank_bic'),
		$this->input->post('bank_iban'),
		$this->input->post('payment_terms'),
		$this->input->post('delivery_terms'),
		$my_clienttype,

		(int)$this->input->post('carelevel'),
		$this->input->post('carelevel_since'),
		$this->input->post('health_insurance_number'),
		$this->input->post('memo'),
		);
	} else {
		$a = $this->mdl_client_extended->update_entry($id,
		$my_customerno,
		$my_client_flags,
		$this->input->post('contract'),
		$this->input->post('direct_debit'),
		$this->input->post('bank_name'),
		$this->input->post('bank_bic'),
		$this->input->post('bank_iban'),
		$this->input->post('payment_terms'),
		$this->input->post('delivery_terms'),
		$my_clienttype,

		(int)$this->input->post('carelevel'),
		$this->input->post('carelevel_since'),
		$this->input->post('health_insurance_number'),
		$this->input->post('memo'),
		);
	}
        //

            $this->load->model('custom_fields/mdl_client_custom');
            $result = $this->mdl_client_custom->save_custom($id, $this->input->post('custom'));

            $where = 'view';
            if ($result !== true) {
                $this->session->set_flashdata('alert_error', $result);
                $this->session->set_flashdata('alert_success', null);
                $where = 'form';
            }

            redirect('clients/' . $where . '/' . $id);
        }

        $req_einvoicing = get_setting('einvoicing');
        if ($req_einvoicing) {
            $this->load->helper('e-invoice'); // eInvoicing++
            // Get a check of filled Required (client and users) fields for eInvoicing
            $req_einvoicing = get_req_fields_einvoice(($new_client || ! $id) ? null : $this->db->from('ip_clients')->where('client_id', $id)->get()->row());
        }

        if ($id && ! $this->input->post('btn_submit')) {
            if ( ! $this->mdl_clients->prep_form($id)) {
                show_404();
            }

            $this->load->model('custom_fields/mdl_client_custom');
            $this->mdl_clients->set_form_value('is_update', true);

            $client_custom = $this->mdl_client_custom->where('client_id', $id)->get();

            if ($client_custom->num_rows()) {
                $client_custom = $client_custom->row();

                unset($client_custom->client_id, $client_custom->client_custom_id);

                foreach ($client_custom as $key => $val) {
                    $this->mdl_clients->set_form_value('custom[' . $key . ']', $val);
                }
            }
        } elseif ($this->input->post('btn_submit')) {
            if ($this->input->post('custom')) {
                foreach ($this->input->post('custom') as $key => $val) {
                    $this->mdl_clients->set_form_value('custom[' . $key . ']', $val);
                }
            }
        }

        $this->load->model([
            'custom_fields/mdl_custom_fields',
            'custom_values/mdl_custom_values',
            'custom_fields/mdl_client_custom',
        ]);

        $custom_fields = $this->mdl_custom_fields->by_table('ip_client_custom')->get()->result();
        $custom_values = [];
        foreach ($custom_fields as $custom_field) {
            if (in_array($custom_field->custom_field_type, $this->mdl_custom_values->custom_value_fields())) {
                $values                                        = $this->mdl_custom_values->get_by_fid($custom_field->custom_field_id)->result();
                $custom_values[$custom_field->custom_field_id] = $values;
            }
        }

        $fields = $this->mdl_client_custom->get_by_clid($id);

        foreach ($custom_fields as $cfield) {
            foreach ($fields as $fvalue) {
                if ($fvalue->client_custom_fieldid == $cfield->custom_field_id) {
                    // TODO: Hackish, may need a better optimization
                    $this->mdl_clients->set_form_value(
                        'custom[' . $cfield->custom_field_id . ']',
                        $fvalue->client_custom_fieldvalue
                    );
                    break;
                }
            }
        }

        $this->load->helper(['custom_values', 'e-invoice']); // e-invoice - since 1.6.3

        $client_extended = $this->mdl_client_extended->get_by_clientid($id);
        if ($client_extended) {
                $this->mdl_client_extended->set_form_value('customer_no', $client_extended->customer_no) ;
                $this->mdl_client_extended->set_form_value('client_flags', $client_extended->client_flags) ;
                $this->mdl_client_extended->set_form_value('contract', $client_extended->contract) ;
                $this->mdl_client_extended->set_form_value('direct_debit', $client_extended->direct_debit) ;
                $this->mdl_client_extended->set_form_value('bank_name', $client_extended->bank_name) ;
                $this->mdl_client_extended->set_form_value('bank_bic', $client_extended->bank_bic) ;
                $this->mdl_client_extended->set_form_value('bank_iban', $client_extended->bank_iban) ;
                $this->mdl_client_extended->set_form_value('payment_terms', $client_extended->payment_terms) ;
                $this->mdl_client_extended->set_form_value('delivery_terms', $client_extended->delivery_terms) ;
                $this->mdl_client_extended->set_form_value('client_type', $client_extended->client_type) ;
                $this->mdl_client_extended->set_form_value('carelevel', $client_extended->carelevel);
                $this->mdl_client_extended->set_form_value('carelevel_since', $client_extended->carelevel_since);
                $this->mdl_client_extended->set_form_value('health_insurance_number', $client_extended->health_insurance_number);
                $this->mdl_client_extended->set_form_value('memo', $client_extended->memo);
        }
        // end

        $this->layout->set(
            [
                'client_extended'      => $client_extended,
                'client_id'            => $id,
                'custom_fields'        => $custom_fields,
                'custom_values'        => $custom_values,
                'countries'            => get_country_list(trans('cldr')),
                'selected_country'     => $this->mdl_clients->form_value('client_country') ?: get_setting('default_country'),
                'languages'            => get_available_languages(),
                'client_title_choices' => $this->get_client_title_choices(),
                'xml_templates'        => get_xml_template_files(), // eInvoicing
                'req_einvoicing'       => $req_einvoicing,
                'client_types'         => $this->mdl_client_extended->client_types(),
            ]
        );

        $this->layout->buffer('content', 'clients/form');
        $this->layout->render();
    }

    /**
     * @param int $client_id
     */
    public function view($client_id, $activeTab = 'detail', $page = 0): void
    {
        //$this->db->db_debug = TRUE;     // debug by chrissie

        $client = $this->mdl_clients
            ->with_total()
            ->with_total_balance()
            ->with_total_paid()
            ->where('ip_clients.client_id', $client_id)
            ->get()->row();

        if ( ! $client) {
            show_404();
        }

        $this->load->model(
            [
                'clients/mdl_client_notes',
                'clients/mdl_documents',
                'clients/mdl_client_extended',
                'invoices/mdl_invoices',
                'quotes/mdl_quotes',
                'payments/mdl_payments',
                'custom_fields/mdl_custom_fields',
                'custom_fields/mdl_client_custom',
            ]
        );

        $req_einvoicing = get_setting('einvoicing');
        if ($req_einvoicing) {
            $this->load->helper('e-invoice'); // eInvoicing++

            // Get a check of filled Required (client and users) fields for eInvoicing
            $req_einvoicing = get_req_fields_einvoice($client);

            $client = $this->check_client_einvoice_active($client, $req_einvoicing);
        }

        // Change page only for one url (tab) system
        $p = ['invoices' => 0, 'quotes' => 0, 'payments' => 0]; // Default
        // Session key
        $key = 'clientview';
        // When detail (from menu)
        if ($activeTab == 'detail') {
            // Clear temp + session
            $this->session->unmark_temp($key);
            unset($_SESSION[$key]);
        } else {
            // Set pages saved in session
            if (isset($_SESSION[$key])) {
                $p = $_SESSION[$key];
            }

            // Up Actual page num
            $p[$activeTab] = $page;
            // Save in session
            $_SESSION[$key] = $p;
            // For 300 seconds
            $this->session->mark_as_temp($key);
        }


        // calculate used budget per year and invoice type by chrissie for marishine
        // Bitmasks in invoice_type
        // flag_private  1
        // flag_39       2
        // flag_45a      4
        // flag_45b      8
        // flag_125      16

        $this->load->model('reports/mdl_reports');
        $year = date("Y");
        if ($this->input->post('year')) $year = $this->input->post('year');
        $budget_39   = $this->mdl_reports->invoice_type_client_amount       ($client_id,  2,  2, $year);
        $budget_45a  = $this->mdl_reports->invoice_type_client_amount       ($client_id, 12,  4, $year);
        $budget_45b  = $this->mdl_reports->invoice_type_client_amount       ($client_id, 12,  8, $year);
        $budget_45a_45b = $this->mdl_reports->invoice_type_client_amount    ($client_id, 12, 12, $year);
        $budget_125  = $this->mdl_reports->invoice_type_client_amount       ($client_id, 16, 16, $year);

        $old_budget_39  = $this->mdl_reports->invoice_type_client_amount    ($client_id,  2,  2, $year-1);
        $old_budget_45a = $this->mdl_reports->invoice_type_client_amount    ($client_id, 12,  4, $year-1);
        $old_budget_45b = $this->mdl_reports->invoice_type_client_amount    ($client_id, 12,  8, $year-1);
        $old_budget_45a_45b = $this->mdl_reports->invoice_type_client_amount($client_id, 12, 12, $year-1);
        $old_budget_125  = $this->mdl_reports->invoice_type_client_amount   ($client_id, 16, 16, $year-1);

        $base_url = site_url('clients/view/' . $client_id);
        $this->mdl_invoices->by_client($client_id)->paginate($base_url . '/invoices', $p['invoices'], 5);
        $this->mdl_quotes->by_client($client_id)->paginate($base_url . '/quotes', $p['quotes'], 5);
        $this->mdl_payments->by_client($client_id)->paginate($base_url . '/payments', $p['payments'], 5);
        $client_extended = $this->mdl_client_extended->get_by_clientid($client_id);
        $custom_fields = $this->mdl_client_custom->get_by_client($client_id)->result();
        $this->mdl_client_custom->prep_form($client_id);

        $this->layout->set( [
                'client'           => $client,
                'client_extended'  => $client_extended,
                'client_types'     => $this->mdl_client_extended->client_types(),
                'documents'        => $this->mdl_documents->get_documents($client_id),
                'client_notes'     => $this->mdl_client_notes->where('client_id', $client_id)->get()->result(),
                'invoices'         => $this->mdl_invoices->result(),
                'quotes'           => $this->mdl_quotes->result(),
                'payments'         => $this->mdl_payments->result(),
                'custom_fields'    => $custom_fields,
                'quote_statuses'   => $this->mdl_quotes->statuses(),
                'invoice_statuses' => $this->mdl_invoices->statuses(),
                'activeTab'        => $activeTab,
                'req_einvoicing'   => $req_einvoicing,

                'year'              => $year,
                'budget_39'         => $budget_39,   
                'budget_45a'        => $budget_45a,
                'budget_45b'        => $budget_45b, 
                'budget_45a_45b'    => $budget_45a_45b,
                'budget_125'        => $budget_125,
                'old_budget_39'     => $old_budget_39,
                'old_budget_45a'    => $old_budget_45a,
                'old_budget_45b'    => $old_budget_45b, 
                'old_budget_45a_45b'=> $old_budget_45a_45b,
                'old_budget_125'    => $old_budget_125,
            ]);

        $this->layout->buffer(
            [
                [
                    'invoice_table',
                    'invoices/partial_invoice_table',
                ],
                [
                    'quote_table',
                    'quotes/partial_quote_table',
                ],
                [
                    'payment_table',
                    'payments/partial_payments_table',
                ],
                [
                    'document_table',
                    'clients/partial_document_table'
                ],
                [
                    'partial_notes',
                    'clients/partial_notes',
                ],
                [
                    'content',
                    'clients/view',
                ],
            ]
        );

        $this->page_title = trans('clients');                  // because sometimes overridden
        $this->layout->set(['page_title' => $this->page_title]);
        $this->layout->render();
    }


    /**
     * @param int $client_id
     */
    public function delete($client_id): void
    {
        $this->load->model('clients/mdl_client_extended');
        $this->mdl_clients->delete($client_id);
        $this->mdl_client_extended->delete_by_client($client_id);
        redirect('clients');
    }

    private function get_client_title_choices(): array
    {
        return array_map(
            fn ($clientTitleEnum) => $clientTitleEnum->value,
            ClientTitleEnum::cases()
        );
    }

    /**
     * Sanitize a value for safe inclusion in log messages.
     *
     * Removes newline and carriage-return characters and casts to string
     * to prevent log injection.
     */
    private function sanitize_for_log($value): string
    {
        $sanitized = (string) $value;
        $sanitized = str_replace(["\r", "\n"], ' ', $sanitized);

        return $sanitized;
    }

    private function check_client_einvoice_active($client, $req_einvoicing)
    {
        // Update active eInvoicing client
        // Check if database has been migrated to 1.6.3+ (where einvoicing fields were added)

        $clientIdForLog = $this->sanitize_for_log($client->client_id);

        if ( ! property_exists($client, 'client_einvoicing_active') || ! property_exists($client, 'client_einvoicing_version')) {
            // Fields don't exist - database hasn't been migrated to 1.6.3+
            $this->load->model('settings/mdl_versions');
            $current_version      = $this->mdl_versions->get_current_version();
            $current_version      = $current_version ?: 'unknown';
            $currentVersionForLog = $this->sanitize_for_log($current_version);

            log_message('warning', '[eInvoicing] Database version mismatch detected in check_client_einvoice_active: Running source code 1.6.3+ with database version ' . $currentVersionForLog);
            log_message('warning', '[eInvoicing] Missing fields: client_einvoicing_active and client_einvoicing_version not found in client object (client_id=' . $clientIdForLog . ')');
            log_message('warning', '[eInvoicing] Please run database migration 039_1.6.3.sql to add these fields');

            // Set default values on the client object to prevent further errors
            $client->client_einvoicing_active  = 0;
            $client->client_einvoicing_version = '';

            return $client;
        }

        $o                             = $client->client_einvoicing_active;
        $clientEinvoicingVersionForLog = $this->sanitize_for_log($client->client_einvoicing_version);
        log_message('debug', '[eInvoicing] check_client_einvoice_active: client_id=' . $clientIdForLog . ', current_active=' . $o . ', version=' . $clientEinvoicingVersionForLog);

        if ( ! empty($client->client_einvoicing_version) && $req_einvoicing->clients[$client->client_id]->einvoicing_empty_fields == 0) {
            $client->client_einvoicing_active = 1; // update view
            log_message('debug', '[eInvoicing] Setting client_einvoicing_active=1 for client_id=' . $clientIdForLog);
        } else {
            $client->client_einvoicing_active = 0; // update view
            log_message('debug', '[eInvoicing] Setting client_einvoicing_active=0 for client_id=' . $clientIdForLog);
        }

        // Update db if need
        if ($o != $client->client_einvoicing_active) {
            log_message('info', '[eInvoicing] Updating database: client_id=' . $clientIdForLog . ', client_einvoicing_active changed from ' . $o . ' to ' . $client->client_einvoicing_active);
            $this->db->where('client_id', $client->client_id);
            $this->db->set('client_einvoicing_active', $client->client_einvoicing_active);
            $this->db->update('ip_clients');
        }

        return $client;
    }
}

