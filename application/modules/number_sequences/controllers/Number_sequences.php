<?php

if ( ! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/*
 * InvoicePlane
 *
 * @author		InvoicePlane Developers & Contributors
 * @copyright	Copyright (c) 2012 - 2015 InvoicePlane.com
 * @license		https://invoiceplane.com/license.txt
 * @link		https://invoiceplane.com
 *
 * number sequences aka Nummernkreise module by chrissie ^ x-tra-designs.
 */

#[AllowDynamicProperties]
class Number_Sequences extends Admin_Controller
{
    /**
     * Invoice_Groups constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->load->model('mdl_number_sequences');
    }

    /**
     * @param int $page
     */
    public function index($page = 0)
    {
        $this->mdl_number_sequences->paginate(site_url('number_sequences/index'), $page);
        $number_sequences = $this->mdl_number_sequences->result();

        $this->layout->set('number_sequences', $number_sequences);
        $this->layout->buffer('content', 'number_sequences/index');
        $this->layout->render();
    }

    public function form($id = null)
    {
        if ($this->input->post('btn_cancel')) {
            redirect('number_sequences');
        }

        $this->filter_input();  // <<<--- filters _POST array for nastiness

        if ($this->mdl_number_sequences->run_validation()) {
            $this->mdl_number_sequences->save($id);
            redirect('number_sequences');
        }

        if ($id && ! $this->input->post('btn_submit')) {
            if ( ! $this->mdl_number_sequences->prep_form($id)) {
                show_404();
            }
        } elseif ( ! $id) {
            $this->mdl_number_sequences->set_form_value('number_sequence_left_pad', 0);
            $this->mdl_number_sequences->set_form_value('number_sequence_next_id', 1);
        }

        $this->layout->buffer('content', 'number_sequences/form');
        $this->layout->render();
    }
}
