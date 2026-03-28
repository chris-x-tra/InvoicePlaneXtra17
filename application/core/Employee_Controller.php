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
class Employee_Controller extends User_Controller
{
    /** @var array */
    public $user_clients = [];

    /**
     * Guest_Controller constructor.
     */
    public function __construct()
    {
        parent::__construct('user_type', 3);
    }
}
