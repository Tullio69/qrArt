<?php
    
    namespace App\Models;
    
    use CodeIgniter\Model;
    
    class ContentModel extends Model
    {
        protected $table = 'content';
        protected $primaryKey = 'id';
        protected $useAutoIncrement = true;
        protected $returnType = 'array';
        protected $useSoftDeletes = false;
        protected $allowedFields = ['caller_id', 'caller_name', 'caller_subtitle', 'content_type'];
        protected $useTimestamps = true;
        protected $createdField  = 'created_at';
        protected $updatedField  = 'updated_at';
        
        protected $validationRules = [
            'caller_name' => 'required|max_length[255]',
            'content_type' => 'required|in_list[audio,video,audio_call,video_call,html]',
        ];
        
        protected $validationMessages = [
            'caller_name' => [
                'required' => 'Il nome del chiamante è obbligatorio.',
                'max_length' => 'Il nome del chiamante non può superare i 255 caratteri.',
            ],
            'content_type' => [
                'required' => 'Il tipo di contenuto è obbligatorio.',
                'in_list' => 'Il tipo di contenuto deve essere uno tra: audio, video, audio_call, video_call, html.',
            ],
        ];
        
        protected $skipValidation = false;
        
        // Relazione con la tabella callers
        public function caller()
        {
            return $this->belongsTo('App\Models\CallerModel', 'caller_id', 'id');
        }
        
        // Relazione con la tabella content_metadata
        public function metadata()
        {
            return $this->hasMany('App\Models\ContentMetadataModel', 'content_id', 'id');
        }
        
        // Relazione con la tabella content_files
        public function files()
        {
            return $this->hasMany('App\Models\ContentFileModel', 'content_id', 'id');
        }
        
        // Metodo per ottenere il contenuto con tutti i dati correlati
        public function getContentWithRelations($id)
        {
            return $this->select('content.*, callers.name as caller_name, callers.number as caller_number, callers.avatar as caller_avatar')
                ->join('callers', 'callers.id = content.caller_id', 'left')
                ->where('content.id', $id)
                ->first();
        }
        
        // Metodo per salvare il contenuto con i metadati e i file
        public function saveContentWithRelations($data)
        {
            $this->db->transStart();
            
            // Salva il contenuto principale
            $contentId = $this->insert($data['content']);
            
            // Salva i metadati
            $metadataModel = new ContentMetadataModel();
            foreach ($data['metadata'] as $metadata) {
                $metadata['content_id'] = $contentId;
                $metadataModel->insert($metadata);
            }
            
            // Salva i file
            $fileModel = new ContentFileModel();
            foreach ($data['files'] as $file) {
                $file['content_id'] = $contentId;
                $fileModel->insert($file);
            }
            
            $this->db->transComplete();
            
            return $this->db->transStatus() ? $contentId : false;
        }
        
        // Metodo per aggiornare il contenuto con i metadati e i file
        public function updateContentWithRelations($id, $data)
        {
            $this->db->transStart();
            
            // Aggiorna il contenuto principale
            $this->update($id, $data['content']);
            
            // Aggiorna i metadati
            $metadataModel = new ContentMetadataModel();
            $metadataModel->where('content_id', $id)->delete();
            foreach ($data['metadata'] as $metadata) {
                $metadata['content_id'] = $id;
                $metadataModel->insert($metadata);
            }
            
            // Aggiorna i file
            $fileModel = new ContentFileModel();
            $fileModel->where('content_id', $id)->delete();
            foreach ($data['files'] as $file) {
                $file['content_id'] = $id;
                $fileModel->insert($file);
            }
            
            $this->db->transComplete();
            
            return $this->db->transStatus();
        }
        
        // Metodo per eliminare il contenuto e tutti i dati correlati
        public function deleteContentWithRelations($id)
        {
            $this->db->transStart();
            
            $metadataModel = new ContentMetadataModel();
            $metadataModel->where('content_id', $id)->delete();
            
            $fileModel = new ContentFileModel();
            $fileModel->where('content_id', $id)->delete();
            
            $this->delete($id);
            
            $this->db->transComplete();
            
            return $this->db->transStatus();
        }
    }