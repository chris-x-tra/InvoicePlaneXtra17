<?php

if ( ! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * Dokumente (Scans/PDFs) zu Einnahme-Belegen.
 * Dateien liegen in UPLOADS_FOLDER/receipts_documents/<xx>/ (Hash-Verzeichnisse, Vorschauen in previews/<xx>/).
 * Ausgeliefert werden sie ueber Payments::receipt_document (nur mit Login).
 */
#[AllowDynamicProperties]
class Mdl_Receipts_Documents extends Response_Model
{
    public $table = 'ip_receipts_documents';
    public $primary_key = 'ip_receipts_documents.document_id';

    public static function dir()
    {
        return UPLOADS_FOLDER . 'receipts_documents/';
    }

    /** Ziel-Pfad der Vorschau im Hash-Verzeichnis (preview_helper) */
    public static function preview_path($filename)
    {
        get_instance()->load->helper('preview');

        return doc_preview_path(self::dir(), $filename);
    }

    /** Dateiname ohne Praefix "R_0001_abcdef1234_" */
    public static function clean_filename($filename)
    {
        $parts = explode('_', $filename, 4);

        return isset($parts[3]) ? $parts[3] : $filename;
    }

    public function get_documents($receipts_id)
    {
        $this->load->helper('preview');

        $docs = $this->db
            ->where('receipts_id', (int) $receipts_id)
            ->where('document_deleted', 0)
            ->order_by('document_created', 'DESC')
            ->get('ip_receipts_documents')
            ->result();

        foreach ($docs as $doc) {
            $doc->has_preview = doc_has_preview(self::dir(), $doc->document_filename);
        }

        return $docs;
    }

    public function get_document($document_id)
    {
        return $this->db
            ->where('document_id', (int) $document_id)
            ->where('document_deleted', 0)
            ->get('ip_receipts_documents')
            ->row();
    }

    public function insert_document($receipts_id, $document_filename, $document_description = '')
    {
        $this->db->insert('ip_receipts_documents', [
            'receipts_id'          => (int) $receipts_id,
            'document_filename'    => $document_filename,
            'document_description' => $document_description,
            'document_created'     => date('Y-m-d H:i:s'),
            'document_deleted'     => 0,
        ]);
    }

    /** Soft delete (Datei bleibt liegen), nur wenn das Dokument zu diesem Beleg gehoert */
    public function delete_document($receipts_id, $document_id)
    {
        $this->db->update(
            'ip_receipts_documents',
            ['document_deleted' => 1],
            ['document_id' => (int) $document_id, 'receipts_id' => (int) $receipts_id]
        );
    }

    /** Beim Loeschen des Belegs: Dateien, Vorschauen und DB-Zeilen entfernen */
    public function hard_delete_documents_by_receipt($receipts_id)
    {
        $this->load->helper('preview');

        $documents = $this->db
            ->select('document_filename')
            ->where('receipts_id', (int) $receipts_id)
            ->get('ip_receipts_documents')
            ->result();

        foreach ($documents as $doc) {
            doc_delete_files(self::dir(), $doc->document_filename);
        }

        $this->db->where('receipts_id', (int) $receipts_id)->delete('ip_receipts_documents');
    }
}
