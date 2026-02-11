<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');



/**
 * Class Mdl_Client_Extended
 */
class Mdl_Client_Extended extends Response_Model
{
    public $table = 'ip_client_extended';
    public $primary_key = 'ip_client_extended.client_extended_id';

    public function client_types()
    {
        return array(
            '1' => trans('client'),
            '2' => trans('supplier'),  
        );
    }

    public function insert_entry(
            $client_id,
            $customer_no,
            $client_flags,
            $contract,
            $direct_debit,
            $bank_name,
            $bank_bic,
            $bank_iban,
            $payment_terms,
            $delivery_terms,
            $client_type,
        $carelevel,
        $carelevel_since,             
        $health_insurance_number,
        $memo
            )
    {
        $data = array (
                'client_id' => $client_id,
                'customer_no' => $customer_no,
                'client_flags' => $client_flags,
                'contract' => $contract,
                'direct_debit' => $direct_debit,
                'bank_name' => $bank_name,
                'bank_bic' => $bank_bic,
                'bank_iban' => $bank_iban,
                'payment_terms' => $payment_terms,
                'delivery_terms' => $delivery_terms,
                'client_type' => $client_type,
            'carelevel' => $carelevel,
            'carelevel_since' => $this->convert_date($carelevel_since),             
            'health_insurance_number' => $health_insurance_number,
            'memo' => $memo

                );
        $this->db->insert('ip_client_extended', $data);
    }

        public function get_by_clientid($client_id)
        {
		$this->db->where('client_id', $client_id);
		$query = $this->db->get('ip_client_extended');
		$ret = $query->row();
		return ($ret);
	}

        public function update_entry(
                $client_id,
                $customer_no,
                $client_flags,
                $contract,
                $direct_debit,
                $bank_name,
                $bank_bic,
                $bank_iban,
                $payment_terms,
                $delivery_terms,
                $client_type,
        $carelevel,
        $carelevel_since,             
        $health_insurance_number,
        $memo
                )
        {
            $data = array (
                    'customer_no' => $customer_no,
                    'client_flags' => $client_flags,
                    'contract' => $contract,
                    'direct_debit' => $direct_debit,
                    'bank_name' => $bank_name,
                    'bank_bic' => $bank_bic,
                    'bank_iban' => $bank_iban,
                    'payment_terms' => $payment_terms,
                    'delivery_terms' => $delivery_terms,
                    'client_type' => $client_type ,
            'carelevel' => $carelevel,
            'carelevel_since' => $this->convert_date($carelevel_since),             
            'health_insurance_number' => $health_insurance_number,
            'memo' => $memo
                    );

            $this->db->update('ip_client_extended', $data, array('client_id' => $client_id));
        }


    public function default_order_by()
    {
        $this->db->order_by('ip_client_extended.client_id ASC');
    }

    public function validation_rules()
    {
        return array(
            'client_id' => array(
                'field' => 'client_id',
                'label' => trans('client'),
                'rules' => 'required'
            )
        );
    }

public function delete_by_client($clientId): void
{
    $row = $this->db->select('client_extended_id')
                 ->where('client_id', $clientId)
                 ->get('ip_client_extended')
                 ->row();
    if ($row) {
        parent::delete($row->client_extended_id);

        $this->load->helper('orphan');
        delete_orphans();
    }
}



    /**
     * @param $input
     * @return string
     */
    // by chrissie diese validation regeln jetzt auch hier weil der callback jetzt in diesem model sucht statt im anderen clients model
    function fix_avs($input)
    {
        if ($input != "") {
            if (preg_match('/(\d{3})\.(\d{4})\.(\d{4})\.(\d{2})/', $input, $matches)) {
                return $matches[1] . $matches[2] . $matches[3] . $matches[4];
            } else if (preg_match('/^\d{13}$/', $input)) {
                return $input;
            }
        }
        return "";
    }
                
    function convert_date($input)
    {
        $this->load->helper('date_helper');
        if ($input == '') {
            return '';
        }
        return date_to_mysql($input);
    }

    public function db_array()
    {
        $db_array = parent::db_array();
        return $db_array;
    }

}
