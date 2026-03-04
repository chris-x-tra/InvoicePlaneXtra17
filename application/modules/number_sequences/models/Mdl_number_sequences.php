<?php

if ( ! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/*
 * InvoicePlane
 *
 * @author		InvoicePlane Developers & Contributors
 * @copyright	Copyright (c) 2012 - 2026 InvoicePlane.com
 * @license		https://invoiceplane.com/license.txt
 * @link		https://invoiceplane.com
 *
 * number sequences aka Nummernkreise module by chrissie ^ x-tra-designs.
 */

#[AllowDynamicProperties]
class Mdl_Number_Sequences extends Response_Model
{
    public $table = 'ip_number_sequences';

    public $primary_key = 'ip_number_sequences.number_sequence_id';

    public function default_select()
    {
        $this->db->select('SQL_CALC_FOUND_ROWS *', false);
    }

    public function default_order_by()
    {
        $this->db->order_by('ip_number_sequences.number_sequence_name');
    }

    /**
     * @return array
     */
    public function validation_rules()
    {
        return [
            'number_sequence_name' => [
                'field' => 'number_sequence_name',
                'label' => trans('name'),
                'rules' => 'required',
            ],
            'number_sequence_identifier_format' => [
                'field' => 'number_sequence_identifier_format',
                'label' => trans('identifier_format'),
                'rules' => 'required',
            ],
            'number_sequence_next_id' => [
                'field' => 'number_sequence_next_id',
                'label' => trans('next_id'),
                'rules' => 'required',
            ],
            'number_sequence_left_pad' => [
                'field' => 'number_sequence_left_pad',
                'label' => trans('left_pad'),
                'rules' => 'required',
            ],
        ];
    }

    /**
     * @param      $number_sequence_id
     * fix: 1 = clients / 2 = suppliers, see mysql table, cannot be changed
     *
     * @param bool $set_next
     *
     * @return mixed
     */
    public function generate_sequence_number($number_sequence_id, $set_next = true)
    {
        $number_sequence = $this->get_by_id($number_sequence_id);

        $sequence_identifier = $this->parse_identifier_format(
            $number_sequence->number_sequence_identifier_format,
            $number_sequence->number_sequence_next_id,
            $number_sequence->number_sequence_left_pad
        );

        if ($set_next) {
            $this->set_next_sequence_number($number_sequence_id);
        }

        return $sequence_identifier;
    }

    /**
     * @param $number_sequence_id
     * fix: 1 = clients / 2 = suppliers, see mysql table, cannot be changed
     */
    public function set_next_sequence_number($number_sequence_id)
    {
        $this->db->where($this->primary_key, $number_sequence_id);
        $this->db->set('number_sequence_next_id', 'number_sequence_next_id+1', false);
        $this->db->update($this->table);
    }

    /**
     * @param $identifier_format
     * @param $next_id
     * @param $left_pad
     *
     * @return mixed
     */
    private function parse_identifier_format($identifier_format, string $next_id, int $left_pad)
    {
        if (preg_match_all('/{{{([^{|}]*)}}}/', $identifier_format, $template_vars)) {
            foreach ($template_vars[1] as $var) {
                switch ($var) {
                    case 'year':
                        $replace = date('Y');
                        break;
                    case 'yy':
                        $replace = date('y');
                        break;
                    case 'month':
                        $replace = date('m');
                        break;
                    case 'day':
                        $replace = date('d');
                        break;
                    case 'id':
                        $replace = mb_str_pad($next_id, $left_pad, '0', STR_PAD_LEFT);
                        break;
                    default:
                        $replace = '';
                }

                $identifier_format = str_replace('{{{' . $var . '}}}', $replace, $identifier_format);
            }
        }

        return $identifier_format;
    }
}
