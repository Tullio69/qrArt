<?php
    
    namespace App\Models;
    
    use CodeIgniter\Model;
    
    class ContentModel extends Model
    {
        protected $DBGroup          = 'default';
        protected $table            = 'content';
        protected $primaryKey       = 'id';
        protected $useAutoIncrement = true;
        protected $returnType       = 'array';
        protected $useSoftDeletes   = false;
        protected $protectFields    = true;
        protected $allowedFields    = ['caller_name', 'caller_title', 'content_name', 'content_type','created_at', 'updated_at'];
        
        // Dates
        protected $useTimestamps = true;
        protected $dateFormat    = 'datetime';
        protected $createdField  = 'created_at';
        protected $updatedField  = 'updated_at';
        
        // Validation
        protected $validationRules      = [];
        protected $validationMessages   = [];
        protected $skipValidation       = false;
        protected $cleanValidationRules = true;
        
        // Callbacks
        protected $allowCallbacks = true;
        protected $beforeInsert   = [];
        protected $afterInsert    = [];
        protected $beforeUpdate   = [];
        protected $afterUpdate    = [];
        protected $beforeFind     = [];
        protected $afterFind      = [];
        protected $beforeDelete   = [];
        protected $afterDelete    = [];
        
        // Custom Methods
        public function getContentWithRelations($contentId)
        {
            $db = \Config\Database::connect();
            
            // 1. Ottieni i dati base del contenuto
            $content = $db->table('content')
                ->where('id', $contentId)
                ->get()
                ->getRowArray();
            
            if (!$content) {
                return [
                    'data_common' => [],
                    'data_meta' => [],
                    'content' => null
                ];
            }
            
            // 2. Ottieni i file comuni (quelli senza metadata_id)
            $commonFiles = $db->table('content_files')
                ->select('file_type, file_url')
                ->where('content_id', $contentId)
                ->where('metadata_id IS NULL')
                ->get()
                ->getResultArray();
            
            // 3. Ottieni i metadata
            $metadataQuery = $db->table('content_metadata cm')
                ->select('cm.id as metadata_id, cm.language, cm.content_name, cm.description, cm.text_only, cm.html_content')
                ->where('cm.content_id', $contentId)
                ->get()
                ->getResultArray();
            
            // 4. ✅ Per ogni metadata, ottieni IL file associato (uno solo)
            //    e aggiungilo come proprietà diretta del metadata
            $metadataWithFiles = [];
            foreach ($metadataQuery as $metadata) {
                // Prendi UN SOLO file per questo metadata
                $file = $db->table('content_files')
                    ->select('file_type, file_url')
                    ->where('content_id', $contentId)
                    ->where('metadata_id', $metadata['metadata_id'])
                    ->get()
                    ->getRowArray(); // ✅ getRowArray() prende solo 1 record
                
                // ✅ Aggiungi file_type e file_url come proprietà dirette
                if ($file) {
                    $metadata['file_type'] = $file['file_type'];
                    $metadata['file_url'] = $file['file_url'];
                } else {
                    // Se non c'è file (es: solo html_content), metti null
                    $metadata['file_type'] = null;
                    $metadata['file_url'] = null;
                }
                
                $metadataWithFiles[] = $metadata;
            }
            
            // Log per debug
            log_message('debug', 'Content Data: ' . print_r($content, true));
            log_message('debug', 'Content ID: ' . $contentId);
            log_message('debug', 'Common files count: ' . count($commonFiles));
            log_message('debug', 'Metadata count: ' . count($metadataWithFiles));
            
            return [
                'content' => $content,
                'data_common' => $commonFiles,
                'data_meta' => $metadataWithFiles
            ];
        }
        
        public function getContentWithVariants($contentId)
        {
            $content = $this->find($contentId);
            if ($content) {
                $variantModel = new LanguageVariantModel();
                $content['variants'] = $variantModel->where('content_id', $contentId)->findAll();
            }
            return $content;
        }
        
        public function createContentWithVariants($contentData, $variantsData)
        {
            $contentId = $this->insert($contentData);
            
            if ($contentId) {
                $variantModel = new LanguageVariantModel();
                foreach ($variantsData as $variant) {
                    $variant['content_id'] = $contentId;
                    $variantModel->insert($variant);
                }
            }
            
            return $contentId;
        }
        
        public function getHtmlContent($contentId, $language)
        {
            $builder = $this->db->table('content c');
            $builder->select('cm.html_content, cm.content_name')
                ->join('content_metadata cm', 'c.id = cm.content_id')
                ->where('c.id', $contentId)
                ->where('cm.language', $language)
                ->where('cm.text_only', '1');
            
            $query = $builder->get();
            return $query->getRowArray();
        }
        
        public function getAllContents()
        {
            $contents = $this->select("content.id, content.caller_name, content.caller_title, content.content_name, content.content_type, content.created_at, content.updated_at, short_urls.short_code,
        GROUP_CONCAT(DISTINCT content_metadata.language ORDER BY content_metadata.language ASC) AS languages")
                ->join('short_urls', 'short_urls.content_id = content.id', 'left')
                ->join('content_metadata', 'content_metadata.content_id = content.id', 'left')
                ->groupBy('content.id')
                ->orderBy('content.created_at', 'DESC')
                ->findAll();
            
            // Convertire la stringa di lingue in array
            foreach ($contents as &$content) {
                $content['languages'] = $content['languages'] ? explode(',', $content['languages']) : [];
            }
            
            return $contents;
        }
        
        
        
        
    }