<?php

if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/*
 * InvoicePlane
 *
 * @author      InvoicePlane Developers & Contributors
 * @copyright   Copyright (c) 2012 - 2025 InvoicePlane.com
 * @license     https://invoiceplane.com/license.txt
 * @link        https://invoiceplane.com
 */

#[AllowDynamicProperties]
class Ajax extends Admin_Controller
{

    public $ajax_controller = true;

    public function modal_find_supplier()
    {
        $this->load->module('layout');
        $this->load->model('clients/mdl_clients');

        $data = [
            'client' => $this->mdl_clients->get_by_id($this->input->post('client_id')),
            'clients' => $this->mdl_clients->get_latest(),
        ];
            
        $this->layout->load_view('expenses/modal_find_supplier', $data);
    }

}
