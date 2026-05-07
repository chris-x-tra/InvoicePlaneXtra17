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
 * Class User_Clients
 */
class User_Clients extends Admin_Controller
{
    /**
     * Custom_Values constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->load->model('users/mdl_users');
        $this->load->model('clients/mdl_clients');
        $this->load->model('user_clients/mdl_user_clients');
    }

    public function index()
    {
        redirect('user_clients/user');
    }

    /**
     * @param null $id
     */
    public function user($id = null)
    {
        if ($this->input->post('btn_cancel')) {
            redirect('users');
        }

	// chrissie get from session
	if (!$id) {
		$id = $this->session->userdata('user_id');
	}
        $user = $this->mdl_users->get_by_id($id);

        if (empty($user)) {
            redirect('users');
        }

        $user_clients = $this->mdl_user_clients->assigned_to($id)->get()->result();

        $this->layout->set('user', $user);
        $this->layout->set('user_clients', $user_clients);
        $this->layout->set('id', $id);
        $this->layout->buffer('content', 'user_clients/field');
        $this->layout->render();
    }

    public function get_data ($id = null) 
    {
            $this->load->helper('custom_values_helper');
            // chrissie get from session
            if (!$id) {
                    $id = $this->session->userdata('user_id');
            }
            $user = $this->mdl_users->get_by_id($id);
            $user_clients = $this->mdl_user_clients->assigned_to($id)->get()->result();

            // ip_client_extended.customer_insurance_number
            // ip_clients.client_surname
            // ip_clients.client_name
            // ip_clients.client_birthdate
            // ip_clients.client_zip
            // ip_clients.client_city
            // ip_client_extended.invoice_addr_name

            $allcards="";
            $allcards .= "
                <style>
                table, th, td {
                  border: 1px solid black;
                  border-collapse: collapse;
                  margin: 5px;
                }
                th, td {
                        padding: 10px;
                }
                </style>
                ";
            foreach ($user_clients as $u ) {
                    $card = "";
                    $card.= "<table><tr>";
                    $card.= "<td>";
                    $card.= $u->customer_insurance_number;
                    $card.= "<br />\n";
                    $card.= $u->client_surname;
                    $card.= "&nbsp;";
                    $card.= $u->client_name;
                    $card.=",&nbsp;";
                    $card.= format_date($u->client_birthdate);
                    $card.= "<br />\n";
                    $card.= $u->client_zip;
                    $card.= "&nbsp;";
                    $card.= $u->client_city;
                    $card.= ", ";
                    $card.= $u->client_address_1;
                    $card.= "<br />\n";
                    $card.= $u->invoice_addr_name;
                    $card.= "</td>\n";

                    $card.= "<td>\n";
                    $card.= $u->customerno;
                    $card.= "<br />\n";
                    $card.= $u->client_phone;
                    $card.= "<br />\n";
                    $card.= $u->client_mobile;
                    $card.= "<br />\n";
                    $card.= $u->client_email;
                    $card.= "</td>\n";
                    $card.= "</tr>\n";
                    $card.="</table >";
                    $allcards .= $card;
            }

            $this->load->helper('mpdf');
            $c = pdf_create($allcards, "customers-of-id-".$id,
                    false, null, null, null, false, null, false, false, 1);

            header('Content-type: application/pdf');
            header('Content-Disposition: inline; filename="' . "customers-of-id-".$id );
            header('Content-Transfer-Encoding: binary');
            header('Accept-Ranges: bytes');
            @readfile ($c);

            redirect('user_clients/index');
    }

    /**
     * @param null $user_id
     */
    public function create($user_id = null)
    {
        if (!$user_id) {
            redirect('custom_values');
        }

        if ($this->input->post('btn_cancel')) {
            redirect('user_clients/field/' . $user_id);
        }

        if ($this->mdl_user_clients->run_validation()) {
            
            if ($this->input->post('user_all_clients')) {
                $users_id = array($user_id);
                
                $this->mdl_user_clients->set_all_clients_user($users_id);
                
                $user_update = array(
                    'user_all_clients' => 1
                );
                
            } else {
                $user_update = array(
                    'user_all_clients' => 0
                );
                
               $this->mdl_user_clients->save(); 
            }
            
            $this->db->where('user_id',$user_id);
            $this->db->update('ip_users',$user_update);
            
            redirect('user_clients/user/' . $user_id);
        }

        $user = $this->mdl_users->get_by_id($user_id);
        $clients = $this->mdl_clients->get_not_assigned_to_user($user_id);

        $this->layout->set('id', $user_id);
        $this->layout->set('user', $user);
        $this->layout->set('clients', $clients);
        $this->layout->buffer('content', 'user_clients/new');
        $this->layout->render();
    }

    /**
     * @param integer $user_client_id
     */
    public function delete($user_client_id)
    {
        $ref = $this->mdl_user_clients->get_by_id($user_client_id);

        $this->mdl_user_clients->delete($user_client_id);
        redirect('user_clients/user/' . $ref->user_id);
    }

}
