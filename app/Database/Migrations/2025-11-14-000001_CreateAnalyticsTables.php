<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateAnalyticsTables extends Migration
{
    public function up()
    {
        // Tabella per gli eventi di analytics
        $this->forge->addField([
            'id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'event_type' => [
                'type' => 'VARCHAR',
                'constraint' => 50, // qr_scan, content_view, playback_start, playback_complete, language_change, etc.
            ],
            'content_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => true,
            ],
            'short_code' => [
                'type' => 'VARCHAR',
                'constraint' => 20,
                'null' => true,
            ],
            'session_id' => [
                'type' => 'VARCHAR',
                'constraint' => 64, // UUID per tracciare sessioni utente
            ],
            'language' => [
                'type' => 'VARCHAR',
                'constraint' => 5,
                'null' => true,
            ],
            'ip_address' => [
                'type' => 'VARCHAR',
                'constraint' => 45, // IPv6 compatible
                'null' => true,
            ],
            'user_agent' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'device_type' => [
                'type' => 'VARCHAR',
                'constraint' => 20, // mobile, tablet, desktop
                'null' => true,
            ],
            'browser' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'null' => true,
            ],
            'os' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'null' => true,
            ],
            'referrer' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'metadata' => [
                'type' => 'JSON',
                'null' => true, // Dati aggiuntivi specifici per tipo di evento
            ],
            'created_at' => [
                'type' => 'TIMESTAMP',
                'null' => false,
                'default' => 'CURRENT_TIMESTAMP',
            ]
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('event_type');
        $this->forge->addKey('content_id');
        $this->forge->addKey('session_id');
        $this->forge->addKey('created_at');
        $this->forge->addForeignKey('content_id', 'content', 'id', 'CASCADE', 'SET NULL');
        $this->forge->createTable('analytics_events');

        // Tabella per metriche aggregate per contenuto
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'content_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
            ],
            'total_scans' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'default' => 0,
            ],
            'unique_visitors' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'default' => 0,
            ],
            'total_views' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'default' => 0,
            ],
            'playback_starts' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'default' => 0,
            ],
            'playback_completes' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'default' => 0,
            ],
            'avg_completion_rate' => [
                'type' => 'DECIMAL',
                'constraint' => '5,2',
                'default' => 0.00, // Percentuale 0-100
            ],
            'language_stats' => [
                'type' => 'JSON',
                'null' => true, // {"it": 120, "en": 45, "fr": 30}
            ],
            'device_stats' => [
                'type' => 'JSON',
                'null' => true, // {"mobile": 150, "desktop": 45}
            ],
            'last_scan_at' => [
                'type' => 'TIMESTAMP',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'TIMESTAMP',
                'null' => true,
                'default' => null,
                'on_update' => 'CURRENT_TIMESTAMP',
            ]
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('content_id');
        $this->forge->addForeignKey('content_id', 'content', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('content_metrics');

        // Tabella per sessioni utente
        $this->forge->addField([
            'id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'session_id' => [
                'type' => 'VARCHAR',
                'constraint' => 64,
            ],
            'first_seen' => [
                'type' => 'TIMESTAMP',
                'null' => false,
                'default' => 'CURRENT_TIMESTAMP',
            ],
            'last_seen' => [
                'type' => 'TIMESTAMP',
                'null' => false,
                'default' => 'CURRENT_TIMESTAMP',
                'on_update' => 'CURRENT_TIMESTAMP',
            ],
            'ip_address' => [
                'type' => 'VARCHAR',
                'constraint' => 45,
                'null' => true,
            ],
            'user_agent' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'device_type' => [
                'type' => 'VARCHAR',
                'constraint' => 20,
                'null' => true,
            ],
            'total_events' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'default' => 0,
            ],
            'contents_viewed' => [
                'type' => 'JSON',
                'null' => true, // Array di content_id visualizzati
            ]
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('session_id');
        $this->forge->addKey('first_seen');
        $this->forge->createTable('user_sessions');
    }

    public function down()
    {
        $this->forge->dropTable('user_sessions');
        $this->forge->dropTable('content_metrics');
        $this->forge->dropTable('analytics_events');
    }
}
