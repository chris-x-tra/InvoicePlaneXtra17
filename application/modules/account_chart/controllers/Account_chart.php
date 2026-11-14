<?php

if ( ! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/*
 * Kontenrahmen (SKR03) - Konten fuer Ausgaben und Einnahme-Belege verwalten.
 * Aufbau wie das Modul families. Erreichbar ueber Einstellungen (Zahnrad) > Kontenrahmen.
 */

#[AllowDynamicProperties]
class Account_Chart extends Admin_Controller
{
    public function __construct()
    {
        parent::__construct();

        $this->load->model('mdl_account_chart');
    }

    /** Liste aller Konten (ohne Paginierung - Filter im Browser) */
    public function index()
    {
        $this->layout->set([
            'accounts'      => $this->mdl_account_chart->get()->result(),
            'account_types' => $this->mdl_account_chart->account_types(),
        ]);
        $this->layout->buffer('content', 'account_chart/index');
        $this->layout->render();
    }

    public function form($id = null)
    {
        if ($this->input->post('btn_cancel')) {
            redirect('account_chart');
        }

        $this->filter_input();  // filtert $_POST

        $id = $id ? (int) $id : null;

        if ($this->input->post('btn_submit')) {
            $type   = (int) $this->input->post('account_type');
            $number = trim((string) $this->input->post('account_number'));
            $name   = trim((string) $this->input->post('account_name'));

            // gleiche Art + Nummer + Bezeichnung nur einmal
            $check = $this->db->where([
                'account_type'   => $type,
                'account_number' => $number,
                'account_name'   => $name,
            ]);
            if ($id) {
                $check->where('account_id !=', $id);
            }
            if ($number !== '' && $name !== '' && $check->count_all_results('ip_account_chart') > 0) {
                $this->session->set_flashdata('alert_error', trans('account_already_exists'));
                redirect('account_chart/form' . ($id ? '/' . $id : ''));
            }

            // Art eines Kontos mit Buchungen darf nicht mehr geaendert werden
            if ($id) {
                $old = $this->db->get_where('ip_account_chart', ['account_id' => $id])->row();
                if ($old && (int) $old->account_type !== $type && $this->mdl_account_chart->usage_count($id) > 0) {
                    $this->session->set_flashdata('alert_error', trans('account_type_locked'));
                    redirect('account_chart/form/' . $id);
                }
            }
        }

        if ($this->mdl_account_chart->run_validation()) {
            $this->mdl_account_chart->save($id);
            $this->session->set_flashdata('alert_success', trans('record_successfully_updated'));
            redirect('account_chart');
        }

        if ($id && ! $this->input->post('btn_submit')) {
            if ( ! $this->mdl_account_chart->prep_form($id)) {
                show_404();
            }
            $this->mdl_account_chart->set_form_value('is_update', true);
        } elseif ( ! $id && ! $this->input->post('btn_submit')) {
            // neues Konto: Art/Gruppe aus der Liste vorbelegen
            $this->mdl_account_chart->set_form_value('account_type', (int) $this->input->get('type') === 1 ? 1 : 2);
        }

        $this->layout->set([
            'account_types'  => $this->mdl_account_chart->account_types(),
            'account_groups' => $this->mdl_account_chart->get_groups(),
            'usage_count'    => $id ? $this->mdl_account_chart->usage_count($id) : 0,
        ]);
        $this->layout->buffer('content', 'account_chart/form');
        $this->layout->render();
    }

    /** nur per POST (Formular mit CSRF-Token); Konten mit Buchungen werden nicht geloescht */
    public function delete($id = null)
    {
        if ($this->input->method() !== 'post' || ! $id) {
            redirect('account_chart');
        }

        $id = (int) $id;

        if ($this->mdl_account_chart->usage_count($id) > 0) {
            $this->session->set_flashdata('alert_error', trans('account_in_use'));
            redirect('account_chart');
        }

        $this->mdl_account_chart->delete($id);
        $this->session->set_flashdata('alert_success', trans('record_successfully_deleted'));
        redirect('account_chart');
    }
}
