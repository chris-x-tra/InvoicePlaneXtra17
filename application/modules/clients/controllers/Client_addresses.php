<?php

if ( ! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

#[AllowDynamicProperties]
class Client_addresses extends Admin_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('clients/mdl_client_addresses');
        $this->load->helper('country');
    }

    /** HTML des Adressbereichs, wird nach jeder A"nderung neu geladen */
    public function section($client_id = 0)
    {
        $this->load->view('clients/partial_client_address_section', ['client_id' => (int) $client_id]);
    }

    public function save()
    {
        $addr  = $this->input->post('addr');
        $error = $this->mdl_client_addresses->save_one(
            (int) $this->input->post('client_id'),
            (int) $this->input->post('address_type'),
            is_array($addr) ? $addr : [],
            (int) $this->input->post('address_id') ?: null
        );

        exit(json_encode($error === null ? ['success' => 1] : ['success' => 0, 'error' => $error]));
    }

    public function delete()
    {
        $ok = $this->mdl_client_addresses->delete_one(
            (int) $this->input->post('address_id'),
            (int) $this->input->post('client_id')
        );

        exit(json_encode(['success' => $ok ? 1 : 0]));
    }
}
