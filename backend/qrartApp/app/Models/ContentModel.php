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
            $builder = $db->table('content c');
            
            $builder->select('c.caller_name,c.caller_title,c.content_type,cf.file_url,cf.file_type,cm.content_name,cm.language,cm.text_only')
                ->join('content_files cf', 'c.id = cf.content_id', 'left')
                ->join('content_metadata cm', 'cf.metadata_id = cm.id', 'left')
                ->where('c.id', $contentId);
            
            // Get the compiled SQL
            $sql = $builder->getCompiledSelect();
        
            // Log the SQL query
            log_message('debug', 'Query executed: ' . $sql);
            
            // Execute the query
            $query = $db->query($sql);
            
            // Return both the result and the SQL for debugging
            return [
                'data' => $query->getResultArray(),
                'sql' => $sql
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