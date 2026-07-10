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
class Ajax extends Admin_Controller
{
    public $ajax_controller = true;

    public function name_query($type = 1)
    {
        // Load the model & helper
        $this->load->model('clients/mdl_clients');
        $this->load->model('clients/mdl_client_extended');

        $response = [];

        // Get the post input
        $query                   = $this->input->get('query');
        $permissiveSearchClients = $this->input->get('permissive_search_clients');

        if (empty($query)) {
            echo json_encode($response);
            exit;
        }

        // Search for chars "in the middle" of clients names
        $moreClientsQuery = $permissiveSearchClients ? '%' : '';

        // Search for clients
        $escapedQuery = $this->db->escape_str($query);
        $escapedQuery = str_replace('%', '', $escapedQuery);

        // client or supplier? type 1 or 2
        if ($type == 2) {
            $clients = $this->mdl_clients
                ->where('client_active', 1)
                    ->where('ip_client_extended.client_type', 2)
                ->having("client_name LIKE '" . $moreClientsQuery . $escapedQuery . "%'")
                ->or_having("client_surname LIKE '" . $moreClientsQuery . $escapedQuery . "%'")
                ->or_having("client_fullname LIKE '" . $moreClientsQuery . $escapedQuery . "%'")
                ->order_by('client_name')
                ->get()
                ->result();
        } else {
            $clients = $this->mdl_clients
                ->where('client_active', 1)
                    ->where('ip_client_extended.client_type', 1)
                ->having("client_name LIKE '" . $moreClientsQuery . $escapedQuery . "%'")
                ->or_having("client_surname LIKE '" . $moreClientsQuery . $escapedQuery . "%'")
                ->or_having("client_fullname LIKE '" . $moreClientsQuery . $escapedQuery . "%'")
                ->order_by('client_name')
                ->get()
                ->result();
        }

        foreach ($clients as $client) {
            $response[] = [
                'id'   => $client->client_id,
                'text' => htmlsc(format_client($client, false)),
            ];
        }

        // Return the results
        echo json_encode($response);
    }

    /**
     * Get the latest clients.
     */
    public function get_latest()
    {
        // Load the model & helper
        $this->load->model('clients/mdl_clients');

        $response = [];

        $clients = $this->mdl_clients
            ->where('client_active', 1)
            ->limit(5)
            ->order_by('client_date_created')
            ->get()
            ->result();

        foreach ($clients as $client) {
            $response[] = [
                'id'   => $client->client_id,
                'text' => htmlsc(format_client($client, false)),
            ];
        }

        // Return the results
        echo json_encode($response);
    }

    public function save_preference_permissive_search_clients()
    {
        $this->load->model('mdl_settings');
        $permissiveSearchClients = $this->input->get('permissive_search_clients');

        if ( ! preg_match('!^[0-1]{1}$!', $permissiveSearchClients)) {
            exit;
        }

        $this->mdl_settings->save('enable_permissive_search_clients', $permissiveSearchClients);
    }

    /**
     * Delete client note id.
     */
    public function delete_client_note()
    {
        $success        = 0;
        $client_note_id = $this->input->post('client_note_id');
        $this->load->model('mdl_client_notes');

        // Only continue if the note exists or no item id was provided
        if ($this->mdl_client_notes->get_by_id($client_note_id) || empty($client_note_id)) {
            // Delete invoice item
            $this->load->model('mdl_client_notes');
            $item = $this->mdl_client_notes->delete($client_note_id);

            // Check if deletion was successful
            if ($item) {
                $success = 1;
            }
        }

        // Return the response
        echo json_encode([
            'success' => $success,
        'new_token' => $this->security->get_csrf_hash(),
        ]);
    }

    public function save_client_note()
    {
        $this->load->model('clients/mdl_client_notes');

        if ($this->mdl_client_notes->run_validation()) {
            $this->mdl_client_notes->save();

            $response = [
                'success'   => 1,
                'new_token' => $this->security->get_csrf_hash(),
            ];
        } else {
            $this->load->helper('json_error');
            $response = [
                'success'           => 0,
                'new_token'         => $this->security->get_csrf_hash(),
                'validation_errors' => json_errors(),
            ];
        }

        echo json_encode($response);
    }

    public function load_client_notes()
    {
        $this->load->model('clients/mdl_client_notes');
        $data = [
            'client_notes' => $this->mdl_client_notes->where(
                'client_id',
                $this->input->post('client_id')
            )->get()->result(),
        ];

        $this->layout->load_view('clients/partial_notes', $data);
    }

    /* ajax note update by chrissie */
    public function update_client_note()
    {
        $this->load->model('clients/mdl_client_notes');
        $note_id = $this->input->post('client_note_id');
        $note = $this->input->post('client_note');

        $this->db->where('client_note_id', $note_id);
        $success = $this->db->update('ip_client_notes', [
            'client_note' => $note,
            'client_note_timestamp' => date('Y-m-d H-i-s')
        ]);

        echo json_encode([
            'success' => $success ? 1 : 0,
            'new_token' => $this->security->get_csrf_hash(),
        ]);
    }

    /* 
     *  infinite scroll and stuff by chrissie 
     */
    //public function get_ajax($offset = 0)
    public function get_ajax(string $status = 'active', $offset = 0)
    {
        $this->load->model('clients/mdl_clients');
        $this->load->model('clients/mdl_client_extended');
        //$this->mdl_clients->with_total_balance();
        
        $this->load->helper('date_helper');

        // status
        if (is_numeric(array_search($status, ['active', 'inactive', 'supplier'], true))) {
            $function = 'is_' . $status;
            $this->mdl_clients->{$function}();
        }

        // limit
        $this->db->limit(5, $offset);       // limit, start : immer 5 holen ab ajax-offset

        // sort
        $sort  = $this->input->get('sort')  ?? 'id';    // Standard-Spalte
        $order = $this->input->get('order') ?? 'asc';   // Standard-Reihenfolge
        $sort=trim($sort); $order=trim($order);

        if ($sort == 'name' && $order =='asc')
        $this->mdl_clients->with_total_balance()->order_by('ip_clients.client_name','ASC');
        if ($sort == 'name' && $order =='desc')
        $this->mdl_clients->with_total_balance()->order_by('ip_clients.client_name','DESC');
        if ($sort == 'id' && $order =='asc')
        $this->mdl_clients->with_total_balance()->order_by('ip_clients.client_id','ASC');
        if ($sort == 'id' && $order =='desc')
        $this->mdl_clients->with_total_balance()->order_by('ip_clients.client_id','DESC');
        if ($sort == 'amount' && $order =='asc')
        $this->mdl_clients->with_total_balance()->order_by('client_invoice_balance','ASC');
        if ($sort == 'amount' && $order =='desc')
        $this->mdl_clients->with_total_balance()->order_by('client_invoice_balance','DESC');

        $clients = $this->mdl_clients
            ->limit(5)
            ->get()
            ->result();

        $response = [];

        /*
        // debug A
        $response[]=[
            'id' => 'Offset',
            'text' => $offset
        ];
        */

        foreach ($clients as $client) {
            $client->client_invoice_balance = format_currency($client->client_invoice_balance );

            if(ip_mari()) {
                $client_birthdate='';
                // TODO use date helper here
                if ($client->client_birthdate && $client->client_birthdate !='0000-00-00') {
                        $client_birthdate = date_create($client->client_birthdate);
                        if($client_birthdate)
                                $client_birthdate = date_format($client_birthdate, 'd.m.Y');
                }

                $carelevel_since='';
                if ($client->carelevel_since && $client->carelevel_since !='0000-00-00') {
                        $carelevel_since = date_create($client->carelevel_since);
                        if ($carelevel_since)
                                $carelevel_since = date_format($carelevel_since, 'd.m.Y');
                } 

                $carelevel_confirmation='<input title="carelevel_confirmation" type="checkbox" disabled readonly ';
                if ($client->client_flags & 128) $carelevel_confirmation.=  ' checked="checked" ';
                $carelevel_confirmation.=' >';

            } 
            $htmlsc_addr =  "<br>\n " . $client->client_address_1 . " " . $client->client_address_2 
            . "<br>\n " . $client->client_zip . " " .  $client->client_city;
            $htmlsc_name = htmlsc(format_client($client)) ;
            $response[] = [
                'id' => $client->client_id,
                'htmlsc_name' => $htmlsc_name,
                'htmlsc_addr' => $htmlsc_addr,
                'customerno_joined'=> join_dash($client->customer_no),
                'html_flags' => show_paragraphs($client->client_flags) ,
                'client_birthdate' => $client_birthdate,
                'carelevel_since' => $carelevel_since,
                'carelevel_confirmation' => $carelevel_confirmation,
                $client
            ];
        }

        /*
        // debug B
        $filePath = "/tmp/d.txt";
        $fp = fopen($filePath, "a");
        $objData= "status:".$status."; sort: ".$sort."; order: " . $order . "; offset:" . $offset . "\n";
        fwrite($fp, $objData);
        $objData = serialize($response);
        fwrite($fp, $objData);
        fwrite($fp, "\n");
        fclose($fp);
        */

        // Return the results
        echo json_encode($response);
    }

    /* invoice adress helper search modal by chrissie 
     */
    public function search_addresses() {
        $q = $this->input->get('q');
        $this->load->model('clients/mdl_clients');
        $results = $this->mdl_clients->search_addresses($q);
        echo json_encode($results);
    }
}

