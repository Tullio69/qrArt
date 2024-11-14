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
        protected $allowedFields    = ['caller_name', 'caller_title', 'content_name', 'content_type'];
        
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
            return $this->select('content.*, content_metadata.*, content_files.*')
                ->join('content_metadata', 'content_metadata.content_id = content.id', 'left')
                ->join('content_files', 'content_files.content_id = content.id', 'left')
                ->where('content.id', $contentId)
                ->findAll();
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
    }