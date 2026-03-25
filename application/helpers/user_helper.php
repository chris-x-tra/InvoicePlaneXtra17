<?php

if ( ! defined('BASEPATH')) {
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
/**
 * @param mixed id or object $user - since 1.6.3
 */
function format_user($user): string
{
    // Get an id
    if ($user && is_numeric($user)) {
        $CI = & get_instance();
        if ( ! property_exists($CI, 'mdl_users')) {
            $CI->load->model('users/mdl_users');
        }

        $user = $CI->mdl_users->get_by_id($user);
    }

    // Not exist or find, Stop.
    if (empty($user->user_name)) {
        return '';
    }

    $user_company = empty($user->user_company) ? '' : ' - ' . $user->user_company;
    $contact      = empty($user->user_invoicing_contact) ? '' : ' - ' . $user->user_invoicing_contact;

    return ucfirst($user->user_name) . $user_company . $contact;
}

function show_user($user_name, $user_email, $user_type = false)
{
    echo '<i class="fa fa-user" title=""></i>';
    echo "<span>$user_name ";
    if ($user_email) echo "($user_email) ";
    if ($user_type) {
        echo "(";
        switch ($user_type) {
            case 1:
                _trans('administrator');
                break;
            case 2:
                _trans('guest');
                break;
            case 3:
                _trans('employee');
                break;
            default:
        }
        echo ") ";
    }
    echo '</span>';
}

function get_greeting() {
    $hour = (int) date('H');

    if ($hour >= 5 && $hour < 11) {
        echo "☀️ Guten Morgen";
    } elseif ($hour >= 11 && $hour < 13) {
        echo "🍽️  Mahlzeit";
    } elseif ($hour >= 13 && $hour < 17) {
        echo "👋 Hallo";
    } elseif ($hour >= 17 && $hour < 22) {
        echo "🌆 Guten Abend";
    } else {
        echo "🌙 Gute Nacht";
    }
}

function check_new_notes($ts) {
    if($ts) {
        $CI = &get_instance();
        $CI->load->model('clients/Mdl_client_notes');
        $notes = $CI->Mdl_client_notes->get_notes_ts($ts);
        return $notes;
    } else {
        return NULL;
    }
}

