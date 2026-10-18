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

/* functions for showing flags */
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

/**
 * Flag-Badges (Paragraphen o. a".) fu"r die Kundenliste, Definitionen aus config/flags.php.
 * Das Bit 128 (Pflegegrad-Bestätigung) ist kein Eintrag in der Config und wird nich angezeigt.
 */
function show_flags($flags): string
{
    $flags = (int) $flags; // NULL aus dem Left Join wird zu 0
    $html  = '';

    foreach (flag_items() as $flag) {
        if ($flags & $flag['bit']) {
            $html .= '<span class="flags" style="border: 2px solid ' . htmlsc($flag['color'] ?? '#999999')
                   . '; margin: 2px;">' . htmlsc($flag['short']) . '</span>';
        }
    }

    return $html;
}

/**
 * Flag-Checkboxen fu"r Kunden- und Rechnungsformular (Definitionen aus config/flags.php).
 * Feldname und id = flag_<key>, value 1, vorbelegt aus der Bitmaske $mask.
 * Gegenstu"ck beim Speichern: flag_mask_from_post().
 */
function show_flag_inputs(string $for, int $mask, bool $disabled = false, string $class = 'checkbox-inline'): string
{
    $html = '';

    foreach (flag_items($for) as $flag) {
        $field = flag_field($flag);

        $html .= '<label class="' . htmlsc($class) . '">'
               . '<input type="checkbox" id="' . $field . '" name="' . $field . '" value="1"'
               . (($mask & $flag['bit']) ? ' checked="checked"' : '')
               . ($disabled ? ' disabled="disabled"' : '')
               . '> ' . htmlsc($flag['label'])
               . '</label>&nbsp;&nbsp;&nbsp;';
    }

    return $html;
}

/**
 * Flag-Checkboxen fu"r den Kundenfiler (GET: flags[]=<bit>), vorbelegt aus dem GET.
 * Gegenstu"ck: Mdl_clients::list_params().
 */
function show_flag_filter(string $for = 'client'): string
{
    $selected = array_map('intval', (array) get_instance()->input->get('flags'));
    $html     = '';

    foreach (flag_items($for) as $flag) {
        $html .= '<label class="checkbox-inline">'
               . '<input type="checkbox" name="flags[]" value="' . $flag['bit'] . '"'
               . (in_array($flag['bit'], $selected, true) ? ' checked="checked"' : '')
               . '> ' . htmlsc($flag['short'])
               . '</label>&nbsp;&nbsp;';
    }

    return $html;
}


/**
 * Rechnungsadresse für die Kundenliste als fertiges HTML (escaped).
 * Erste Rechnungsadresse (ia_*, kommt aus Mdl_clients::default_select);
 * ohne Rechnungsadresse: Fallback auf Name + Adresse des Kunden, grau dargestellt.
 */
function client_billing_html(object $client): string
{
    $lines = array_filter([
        trim(($client->ia_name2 ?? '') . ' ' . ($client->ia_name ?? '')),
        $client->ia_address_1 ?? '',
        $client->ia_address_2 ?? '',
        trim(($client->ia_zip ?? '') . ' ' . ($client->ia_city ?? '')),
    ]);
    $class = '';

    if (!$lines) {
        $class = ' class="text-muted"';
        $lines = array_filter([
            format_client($client),
            trim(($client->client_address_1 ?? '') . ' ' . ($client->client_address_2 ?? '')),
            trim(($client->client_zip ?? '') . ' ' . ($client->client_city ?? '')),
        ]);
    }

    return '<div' . $class . '>' . implode("<br>\n", array_map('htmlsc', $lines)) . '</div>';
}

