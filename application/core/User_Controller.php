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
class User_Controller extends Base_Controller
{
    // ajax whitelisting by Chrissie - for APP API
    protected $jwt_whitelist = [
        'clients/get_ajax_clients',
        'sessions/api_login',
        'timesheets/api_upload',
        'timesheets/api_create',
        'timesheets/api_update',
        'timesheets/api_remove',
        'timesheets/api_fetch_dirty',

// TODO dies vorubergehend - in Zukunft mit check versehen 
// nur angemeldete aber Admin, Manager, Supervisor, Employee durfen
'timesheets/ajax/check',
'timesheets/ajax/update_by_uuid',
'timesheets/ajax/set_delete',

    ];


    /**
     * User_Controller constructor.
     *
     * @param string $required_key
     * @param int    $required_val
     */
    public function __construct($required_key, $required_val)
    {
        parent::__construct();

        $current_uri = $this->uri->uri_string();

        if (!in_array($current_uri, $this->jwt_whitelist)) {
            if ($this->session->userdata($required_key) != $required_val) {
                redirect('sessions/login');
            }
	}
    }
}
