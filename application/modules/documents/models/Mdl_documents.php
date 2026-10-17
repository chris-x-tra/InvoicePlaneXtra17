<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');

/*
 * Generisches Dokumenten-Modell (Clients, Users, ...)
 * Tabelle ip_documents: document_id, owner_type, document_kind, owner_id,
 *                       document_filename, document_description, document_created, document_deleted
 * owner_type:    'client' | 'user'
 * document_kind: 'document' | 'avatar'
 */
class Mdl_Documents extends Response_Model
{
    public $table       = 'ip_documents';
    public $primary_key = 'ip_documents.document_id';

    public function get_documents($owner_type, $owner_id, $kind = 'document')
    {
        return $this->db
            ->where('owner_type', $owner_type)
            ->where('owner_id', (int) $owner_id)
            ->where('document_kind', $kind)
            ->where('document_deleted', 0)
            ->order_by('document_created', 'DESC')
            ->get($this->table)
            ->result();
    }

    public function get_document($document_id)
    {
        return $this->db
            ->where('document_id', (int) $document_id)
            ->where('document_deleted', 0)
            ->get($this->table)
            ->row();
    }

    public function get_avatar($user_id)
    {
        return $this->db
            ->where('owner_type', 'user')
            ->where('owner_id', (int) $user_id)
            ->where('document_kind', 'avatar')
            ->where('document_deleted', 0)
            ->order_by('document_created', 'DESC')
            ->limit(1)
            ->get($this->table)
            ->row();
    }

    public function insert_document($owner_type, $owner_id, $kind, $filename, $description = '')
    {
        // Es gibt immer nur ein aktives Profilbild
        if ($kind === 'avatar') {
            $this->db->update(
                $this->table,
                ['document_deleted' => 1],
                ['owner_type' => $owner_type, 'owner_id' => (int) $owner_id, 'document_kind' => 'avatar']
            );
        }

        $this->db->insert($this->table, [
            'owner_type'           => $owner_type,
            'owner_id'             => (int) $owner_id,
            'document_kind'        => $kind,
            'document_filename'    => $filename,
            'document_description' => $description,
            'document_created'     => date('Y-m-d H:i:s'),
            'document_deleted'     => 0,
        ]);

        return $this->db->insert_id();
    }

    /** Soft delete (Datei bleibt auf der Platte, wie bisher) */
    public function delete_document($document_id)
    {
        $this->db->update($this->table, ['document_deleted' => 1], ['document_id' => (int) $document_id]);
    }
}
