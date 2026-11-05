<?php
/**
 * Class Mdl_Expenses_Documents
 *
 */

class Mdl_Expenses_Documents extends Response_Model
{
    public $table = 'ip_expenses_documents';
    public $primary_key = 'ip_expenses_documents.document_id';

    public static function dir()
    {
        return UPLOADS_FOLDER . 'expenses_documents/';
    }

    public static function preview_path($filename)
    {
        return self::dir() . 'previews/' . pathinfo(basename($filename), PATHINFO_FILENAME) . '_preview.jpg';
    }

    /** Dateiname ohne Praefix "E_0001_abcdef_" */
    public static function clean_filename($filename)
    {
        $parts = explode('_', $filename, 4);

        return isset($parts[3]) ? $parts[3] : $filename;
    }

    public function get_document($document_id)
    {
        return $this->db
            ->where('document_id', (int) $document_id)
            ->where('document_deleted', 0)
            ->get('ip_expenses_documents')
            ->row();
    }

    function get_documents($expenses_id )
    {
        $docs =  $this->mdl_expenses_documents
            ->where('expenses_id' , $expenses_id)
            ->where('document_deleted' , 0)
            ->order_by('ip_expenses_documents.document_created', 'DESC')
            ->get()
            ->result();

        foreach ($docs as $doc) {
            $doc->has_preview = is_file(self::preview_path($doc->document_filename));
        }

        return $docs;
    }

    public function insert_document( $expenses_id, $document_filename, $document_description )
    {
        $data = array (
                'expenses_id' => $expenses_id,
                'document_filename' => $document_filename,
                'document_description' => $document_description,
                'document_created' => date('Y-m-d H:i:s'),
                'document_deleted' => 0
                );
        $this->db->insert('ip_expenses_documents', $data);
    }

    public function delete_document( $document_id, $expenses_id = null)
    {
        $where = ['document_id' => $document_id];
        if ($expenses_id !== null) {
            $where['expenses_id'] = $expenses_id;   // nur Dokumente dieses Belegs
        }
        $this->db->update('ip_expenses_documents', ['document_deleted' => 1], $where);
    }

    public function hard_delete_documents_by_expense($expense_id)
    {

    // 1. Alle Dokumente abfragen
    $documents = $this->db
        ->select('document_filename')
        ->where('expenses_id', $expense_id)
        ->get('ip_expenses_documents')
        ->result();

    // 2. Dateien vom Dateisystem löschen
    foreach ($documents as $doc) {
        $source_path = UPLOADS_FOLDER . "expenses_documents/" . basename($doc->document_filename);
        $preview_name = pathinfo($doc->document_filename, PATHINFO_FILENAME) . '_preview.jpg';
        $preview_path = UPLOADS_FOLDER . "expenses_documents/previews/" . $preview_name;

        if (file_exists($source_path)) {
            @unlink($source_path);
        }

        if (file_exists($preview_path)) {
            @unlink($preview_path);
        }
    }

        $this->db
        ->where('expenses_id', $expense_id)
        ->delete('ip_expenses_documents');
    }

}

