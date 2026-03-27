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
 * Class Guest
 */
class Employee extends Employee_Controller
{
    public function index()
    {
        $this->layout->buffer('content', 'employee/index');
        $this->layout->render('layout_employee');
    }

}
