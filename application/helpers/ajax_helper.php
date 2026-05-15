<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');

if (!function_exists('get_ajax_clients')) 
{

    /**
     * Get much clients info via ajax for infinite scroll by chrissie
     * TODO duplication in clients/controllers/client_controller.php - fix someday
     */
    function get_ajax_clients($offset = 0, $limit=5)
    {
        $CI =& get_instance();

	    // Load the model & helper
	    $CI->load->model('clients/mdl_clients');
	    $CI->load->model('clients/mdl_client_extended');

	    // helpers in dieser reihenfolge laden - wtf
	    $CI->load->helper('date_helper');
	    $CI->load->helper('custom_values_helper');

	    $sort  = $CI->input->get('sort')  ?? 'id';	// Standard-Spalte
	    $order = $CI->input->get('order') ?? 'asc';	// Standard-Reihenfolge
	    $sort=trim($sort); $order=trim($order);

	    // if limit = 0, then no limit and offset, get all
	    if($limit > 0)
		    $CI->db->limit($limit, $offset);		// limit, start

	    if ($sort == 'name' && $order =='asc')
		    $CI->mdl_clients->with_total_balance()->order_by('client_name','ASC');
	    if ($sort == 'name' && $order =='desc')
		    $CI->mdl_clients->with_total_balance()->order_by('client_name','DESC');
	    if ($sort == 'id' && $order =='asc')
		    $CI->mdl_clients->with_total_balance()->order_by('ip_clients.client_id','ASC');
	    if ($sort == 'id' && $order =='desc')
		    $CI->mdl_clients->with_total_balance()->order_by('ip_clients.client_id','DESC');
	    if ($sort == 'amount' && $order =='asc')
		    $CI->mdl_clients->with_total_balance()->order_by('client_invoice_balance','ASC');
	    if ($sort == 'amount' && $order =='desc')
		    $CI->mdl_clients->with_total_balance()->order_by('client_invoice_balance','DESC');
	    $clients = $CI->mdl_clients
		    ->where('client_active', 1)
		    ->order_by('client_date_created')
		    ->get()
		    ->result();

	    $response = [];
	    foreach ($clients as $client) {
		    $client->client_invoice_balance = format_currency($client->client_invoice_balance );

		    // leider geht hier i-wie der date helper nicht - erforschen und auf date helper umbauen
		    if ($client->client_birthdate && $client->client_birthdate !='0000-00-00') {
			    $client_birthdate = date_create($client->client_birthdate);
			    if($client_birthdate)
				    $client_birthdate = date_format($client_birthdate, 'd.m.Y');
			    else
				    $client_birthdate='';
		    } else { $client_birthdate='';}

		    if ($client->carelevel_since && $client->carelevel_since !='0000-00-00') {
			    $carelevel_since = date_create($client->carelevel_since);
			    if ($carelevel_since)
				    $carelevel_since = date_format($carelevel_since, 'd.m.Y');
			    else
				    $carelevel_since='';
		    } else { $carelevel_since='';}


		    $carelevel_confirmation='<input title="Bestatigung" type="checkbox" disabled readonly ';
		    if ($client->flags & 128)$carelevel_confirmation.=  ' checked="checked" ';
		    $carelevel_confirmation.=' >';


		    $response[] = [
/*
			    'id' => $client->client_id,
			    'htmlsc_name' => htmlsc(format_client($client)),
			    'customerno_joined'=> join_dash($client->customerno),
// serst mal keine flags weil hier kein html!
//			    'flags' => show_flags($client->flags), 
//			    'carelevel_confirmation' => $carelevel_confirmation,
			    'client_birthdate' => $client_birthdate,
			    'carelevel_since' => $carelevel_since,
*/
			    $client
		    ];
	    }
	    // Return the results
	    //echo json_encode($response);
//TODO das doppel array aussenrum weg!!!
return json_encode($clients);
    }
}
