<?php
    
    namespace App\Database\Migrations;
    
    use CodeIgniter\Database\Migration;
    
    class CreateContentTables extends Migration
    {
        public function up()
        {
            // Creazione della tabella languages
            $this->forge->addField([
                'id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                    'auto_increment' => true,
                ],
                'code' => [
                    'type' => 'VARCHAR',
                    'constraint' => 5, // Es: 'en', 'it', 'fr'
                ],
                'name' => [
                    'type' => 'VARCHAR',
                    'constraint' => 100, // Nome completo della lingua
                ],
                'flag_url' => [
                    'type' => 'TEXT', // URL dell'immagine della bandiera
                    'null' => true,
                ],
                'created_at' => [
                    'type' => 'TIMESTAMP',
                    'null' => true,
                    'default' => null,
                ],
                'updated_at' => [
                    'type' => 'TIMESTAMP',
                    'null' => true,
                    'default' => null,
                    'on_update' => 'CURRENT_TIMESTAMP',
                ]
            ]);
            $this->forge->addKey('id', true);
            $this->forge->createTable('languages',true);
            
            // Creazione della tabella callers (chiamanti)
            $this->forge->addField([
                'id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                    'auto_increment' => true,
                ],
                'name' => [
                    'type' => 'VARCHAR',
                    'constraint' => 255, // Nome del chiamante
                ],
                'number' => [
                    'type' => 'VARCHAR',
                    'constraint' => 20,  // Numero di telefono
                ],
                'avatar' => [
                    'type' => 'TEXT',     // URL dell'avatar
                    'null' => true,
                ],
                'created_at' => [
                    'type' => 'TIMESTAMP',
                    'null' => true,
                    'default' => null,
                ],
                'updated_at' => [
                    'type' => 'TIMESTAMP',
                    'null' => true,
                    'default' => null,
                    'on_update' => 'CURRENT_TIMESTAMP',
                ]
            ]);
            $this->forge->addKey('id', true);
            $this->forge->createTable('callers',true);
            
            // Creazione della tabella content
            $this->forge->addField([
                'id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                    'auto_increment' => true,
                ],
                'caller_id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                    'null' => true,  // Chiamante associato (se applicabile)
                ],
                'content_type' => [
                    'type' => 'VARCHAR',
                    'constraint' => 50, // Tipo di contenuto (audio, video, text, etc.)
                ],
                'title' => [
                    'type' => 'VARCHAR',
                    'constraint' => 255, // Titolo del contenuto
                ],
                'description' => [
                    'type' => 'TEXT',    // Descrizione del contenuto
                    'null' => true,
                ],
                'created_at' => [
                    'type' => 'TIMESTAMP',
                    'null' => true,
                    'default' => null,
                ],
                'updated_at' => [
                    'type' => 'TIMESTAMP',
                    'null' => true,
                    'default' => null,
                    'on_update' => 'CURRENT_TIMESTAMP',
                ]
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addForeignKey('caller_id', 'callers', 'id', 'CASCADE', 'SET NULL');  // Foreign key collegata alla tabella dei chiamanti
            $this->forge->createTable('content',true);
            
            // Creazione della tabella content_files
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
                    'unsigned' => true,  // Foreign key per la tabella content
                ],
                'language' => [
                    'type' => 'VARCHAR',
                    'constraint' => 5,   // Codice della lingua (es: 'en', 'it', 'fr')
                ],
                'file_type' => [
                    'type' => 'VARCHAR',
                    'constraint' => 50,  // Tipo di file (audio, video, text, image)
                ],
                'file_url' => [
                    'type' => 'TEXT',    // URL del file
                ],
                'created_at' => [
                    'type' => 'TIMESTAMP',
                    'null' => true,
                    'default' => null,
                ],
                'updated_at' => [
                    'type' => 'TIMESTAMP',
                    'null' => true,
                    'default' => null,
                    'on_update' => 'CURRENT_TIMESTAMP',
                ]
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addForeignKey('content_id', 'content', 'id', 'CASCADE', 'CASCADE');  // Foreign key collegata alla tabella content
            $this->forge->createTable('content_files',true);
        }
        
        public function down()
        {
            // Elimina le tabelle create
            $this->forge->dropTable('content_files');
            $this->forge->dropTable('content');
            $this->forge->dropTable('callers');
            $this->forge->dropTable('languages');
        }
    }
