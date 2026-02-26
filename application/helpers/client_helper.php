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

function get_best_salutation($client) 
{
        if ($client->client_salutation){
                return $client->client_salutation;
        } elseif ($client->client_gender != NULL) {
                switch ($client->client_gender) {
                case 0:
                        return trans('mr');
                                break;
                case 1:
                        return trans('mrs');
                                break;
                case 2:
                        return trans('mrx');
                                break;
                }
        } else {
                return "Alien";
        }
}

function get_best_invoice_salutation_line($client) 
{
	$ret = "";
        if ($client->client_salutation){
		if($invoice->client_salutation == "Frau") {
		    $ret = "Sehr geehrte Frau";
		} elseif ($invoice->client_salutation == "Herr") {
		    $ret = "Sehr geehrter Herr";
		} else {
		    $ret = "Guten Tag";
		}
        } elseif ($client->client_gender != NULL) {
                switch ($client->client_gender) {
                case 0:
                        $ret = "Sehr geehrter " . trans('mr');	// Herr
                                break;
                case 1:
                        $ret = "Sehr geehrte " . trans('mrs');	// Frau
                                break;
                case 2:
                        $ret = "Guten Tag " . trans('mrx');	// Xier
                                break;
                }
        } else {
                $ret = "Hallo Alien ";
        }

	$ret .=" ";
	if($client->client_contact_person)
	    $ret .= $client->client_contact_person;
	else
	    $ret .= $client->client_name;

	$ret .= ", ";
	return $ret;
}

/**
 * @param obj|int $client     (or id - since 1.6.3)
 * @param bool    $show_title - since 1.6.3
 * show_title default false by chrissie!!!
 */
function format_client($client, $show_title = false): string
{
    // Get an id
    if ($client && is_numeric($client)) {
        $CI = & get_instance();
        if ( ! property_exists($CI, 'mdl_clients')) {
            $CI->load->model('clients/mdl_clients');
        }

        $client = $CI->mdl_clients->get_by_id($client);
    }

    // Not exist or find, Stop.
    if (empty($client->client_name)) {
        return '';
    }

    $client_title = '';
    if ($show_title && ! empty($client->client_title)) {
        $client_title = ucfirst(in_array($client->client_title, ClientTitleEnum::VALUES, true) ? trans($client->client_title) : $client->client_title) . ' ';
    }

    return $client_title . $client->client_name . (empty($client->client_surname) ? '' : ' ' . $client->client_surname);
}

/**
 * @param string $gender
 *
 * @return string
 */
function format_gender($gender)
{
    if ($gender == 0) {
        return trans('gender_male');
    }

    if ($gender == 1) {
        return trans('gender_female');
    }

    return trans('gender_other');
}

function customer_satisfaction_smileys($code = 0)
{
    // 0 = undefined
    // 1 = angry
    // 2 = neutral
    // 3 = happy

    return '
    <div class="cs-smileys" data-code="'.$code.'">
        <span class="smiley angry">😠</span>
        <span class="smiley neutral">😐</span>
        <span class="smiley happy">😄</span>
    </div>';
}

function client_data_processing_agreement($flag=0)
{
         switch ($flag) {
                case 0:
                    echo _trans('open');
                    break;
                case 1:
                    echo _trans('no');
                    break;
                case 2:
                    echo _trans('yes');
                    break;
	} 
}
