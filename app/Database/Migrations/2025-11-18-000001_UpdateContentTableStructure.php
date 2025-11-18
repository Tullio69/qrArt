<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class UpdateContentTableStructure extends Migration
{
    public function up()
    {
        // Verifica se i campi esistono già prima di aggiungerli
        $db = \Config\Database::connect();
        $fields = $db->getFieldNames('content');

        // Aggiungi caller_name se non esiste
        if (!in_array('caller_name', $fields)) {
            $this->forge->addColumn('content', [
                'caller_name' => [
                    'type' => 'VARCHAR',
                    'constraint' => 255,
                    'null' => true,
                    'after' => 'caller_id'
                ]
            ]);
        }

        // Aggiungi caller_title se non esiste
        if (!in_array('caller_title', $fields)) {
            $this->forge->addColumn('content', [
                'caller_title' => [
                    'type' => 'VARCHAR',
                    'constraint' => 255,
                    'null' => true,
                    'after' => 'caller_name'
                ]
            ]);
        }

        // Aggiungi content_name se non esiste (rinomina da title se necessario)
        if (!in_array('content_name', $fields)) {
            if (in_array('title', $fields)) {
                // Se esiste 'title', rinominalo in 'content_name'
                $this->forge->modifyColumn('content', [
                    'title' => [
                        'name' => 'content_name',
                        'type' => 'VARCHAR',
                        'constraint' => 255,
                        'null' => true
                    ]
                ]);
            } else {
                // Altrimenti aggiungilo
                $this->forge->addColumn('content', [
                    'content_name' => [
                        'type' => 'VARCHAR',
                        'constraint' => 255,
                        'null' => true,
                        'after' => 'caller_title'
                    ]
                ]);
            }
        }

        // Rimuovi il campo 'title' se esiste ancora (dopo rename)
        if (in_array('title', $fields) && in_array('content_name', $fields)) {
            $this->forge->dropColumn('content', 'title');
        }
    }

    public function down()
    {
        // Ripristina la struttura originale
        $db = \Config\Database::connect();
        $fields = $db->getFieldNames('content');

        if (in_array('caller_name', $fields)) {
            $this->forge->dropColumn('content', 'caller_name');
        }

        if (in_array('caller_title', $fields)) {
            $this->forge->dropColumn('content', 'caller_title');
        }

        if (in_array('content_name', $fields)) {
            // Rinomina content_name in title
            $this->forge->modifyColumn('content', [
                'content_name' => [
                    'name' => 'title',
                    'type' => 'VARCHAR',
                    'constraint' => 255,
                    'null' => false
                ]
            ]);
        }
    }
}
