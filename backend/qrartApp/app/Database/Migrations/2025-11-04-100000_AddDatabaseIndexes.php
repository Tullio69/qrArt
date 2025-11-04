<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddDatabaseIndexes extends Migration
{
    public function up()
    {
        // Indice su short_urls.short_code
        // Questo è il campo più critico - ogni QR scan fa lookup su questo campo
        if (!$this->db->indexExists('short_urls', 'idx_short_code')) {
            $this->forge->addKey('short_code', false, false, 'idx_short_code');
            $this->forge->processIndexes('short_urls');
        }

        // Indici su content_files
        // Ottimizza il recupero dei file per content_id
        if (!$this->db->indexExists('content_files', 'idx_content_id')) {
            $this->forge->addKey('content_id', false, false, 'idx_content_id');
            $this->forge->processIndexes('content_files');
        }

        // Ottimizza query sui metadati
        if (!$this->db->indexExists('content_files', 'idx_metadata_id')) {
            $this->forge->addKey('metadata_id', false, false, 'idx_metadata_id');
            $this->forge->processIndexes('content_files');
        }

        // Ottimizza filtri per tipo di file
        if (!$this->db->indexExists('content_files', 'idx_file_type')) {
            $this->forge->addKey('file_type', false, false, 'idx_file_type');
            $this->forge->processIndexes('content_files');
        }

        // Indice composito su content_files per query comuni
        // Migliora performance di getContentWithRelations()
        if (!$this->db->indexExists('content_files', 'idx_content_metadata')) {
            $this->db->query('CREATE INDEX idx_content_metadata ON content_files(content_id, metadata_id)');
        }

        // Indici su content_metadata
        // Indice composito per ricerche lingua-specifiche
        if (!$this->db->indexExists('content_metadata', 'idx_content_language')) {
            $this->db->query('CREATE INDEX idx_content_language ON content_metadata(content_id, language)');
        }

        // Ottimizza filtri text_only
        if (!$this->db->indexExists('content_metadata', 'idx_text_only')) {
            $this->forge->addKey('text_only', false, false, 'idx_text_only');
            $this->forge->processIndexes('content_metadata');
        }

        // Indice su content.content_type
        // Ottimizza query filtrate per tipo (audio, video, etc.)
        if (!$this->db->indexExists('content', 'idx_content_type')) {
            $this->forge->addKey('content_type', false, false, 'idx_content_type');
            $this->forge->processIndexes('content');
        }

        // Indice su content.created_at
        // Ottimizza ordinamento per data nella lista contenuti
        if (!$this->db->indexExists('content', 'idx_created_at')) {
            $this->forge->addKey('created_at', false, false, 'idx_created_at');
            $this->forge->processIndexes('content');
        }

        // Indice su content.caller_id per JOIN
        if (!$this->db->indexExists('content', 'idx_caller_id')) {
            $this->forge->addKey('caller_id', false, false, 'idx_caller_id');
            $this->forge->processIndexes('content');
        }

        log_message('info', 'Database indexes created successfully');
    }

    public function down()
    {
        // Rimuovi tutti gli indici creati
        $indexes = [
            'short_urls' => ['idx_short_code'],
            'content_files' => ['idx_content_id', 'idx_metadata_id', 'idx_file_type', 'idx_content_metadata'],
            'content_metadata' => ['idx_content_language', 'idx_text_only'],
            'content' => ['idx_content_type', 'idx_created_at', 'idx_caller_id']
        ];

        foreach ($indexes as $table => $tableIndexes) {
            foreach ($tableIndexes as $index) {
                if ($this->db->indexExists($table, $index)) {
                    $this->forge->dropKey($table, $index);
                }
            }
        }

        log_message('info', 'Database indexes dropped successfully');
    }
}
