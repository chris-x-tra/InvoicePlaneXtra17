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

/**
 * Returns a printable pagination.
 *
 * @param $base_url
 * @param $model
 */

function do_sort_caret($cond, $order) {
  if(!$cond) return;
  if ($order == 'desc') echo ' <i class="fa fa-caret-down"></i> ';
  if ($order == 'asc') echo ' <i class="fa fa-caret-up"></i>';
}


/**
 * Baut den Query-String für alle Pager-Links.
 * sort/order haben immer einen Default, search/carelevel/flags[] nur, wenn gesetzt.
 */
function pager_query(): string
{
    $CI = &get_instance();

    // Defaults by chrissie - invoices neueste zuerst, sonst aufsteigend
    $params = [
        'sort'  => 'id',
        'order' => ($CI->uri->segment(1) === 'invoices') ? 'desc' : 'asc',
    ];

    // sort/order: nur übernehmen, wenn sie plausibel sind (landen später im SQL)
    $sort = (string) $CI->input->get('sort');
    if ($sort !== '' && preg_match('/^[a-z0-9_.]+$/i', $sort)) {
        $params['sort'] = $sort;
    }

    $order = strtolower((string) $CI->input->get('order'));
    if (in_array($order, ['asc', 'desc'], true)) {
        $params['order'] = $order;
    }

    // optionale Filter: kein Default, nur wenn gesetzt
    $search = trim((string) $CI->input->get('search'));
    if ($search !== '') {
        $params['search'] = $search;
    }

    $carelevel = (int) $CI->input->get('carelevel');
    if ($carelevel >= 1 && $carelevel <= 6) {
        $params['carelevel'] = $carelevel;
    }

    $flags = array_values(array_intersect([1, 2, 4, 8, 16], array_map('intval', (array) $CI->input->get('flags'))));
    if ($flags) {
        $params['flags'] = $flags;
    }

    // &amp; weil der String direkt in href="" landet
    return http_build_query($params, '', '&amp;');
}

/**
 * Ein Pager-Button; ohne $href wird er deaktiviert dargestellt.
 */
function pager_button(?string $href, string $title, string $icon): string
{
    $class = 'btn btn-default' . ($href === null ? ' disabled' : '');

    return '<a class="' . $class . '" href="' . ($href ?? '#') . '" title="' . trans($title) . '">'
         . '<i class="fa fa-' . $icon . ' no-margin"></i></a>';
}

function pager(string $base_url, $model): string
{
    $CI    = &get_instance();
    $query = pager_query();

    $link = function ($offset) use ($base_url, $query) {
        return $base_url . '/' . $offset . '?' . $query;
    };

    $has_prev = $CI->$model->previous_offset >= 0;
    $has_next = $CI->$model->next_offset <= $CI->$model->last_offset;

    return '<div class="model-pager btn-group btn-group-sm">'
        . pager_button($has_prev ? $link(0) : null,                              'first', 'fast-backward')
        . pager_button($has_prev ? $link($CI->$model->previous_offset) : null,   'prev',  'backward')
        . pager_button($has_next ? $link($CI->$model->next_offset) : null,       'next',  'forward')
        . pager_button($has_next ? $link($CI->$model->last_offset) : null,       'last',  'fast-forward')
        . '</div>';
}
