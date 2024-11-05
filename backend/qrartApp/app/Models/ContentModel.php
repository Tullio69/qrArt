<?php
    
    namespace App\Models;
    
    use CodeIgniter\Model;
    
    class ContentModel extends Model
    {
        protected $table            = 'content';
        protected $primaryKey       = 'id';
        protected $useAutoIncrement = true;
        protected $returnType       = 'array';
        protected $useSoftDeletes   = false;
        protected $protectFields    = true;
        protected $allowedFields    = [
            'caller_id',
            'caller_name',
            'caller_title',
            'content_name',
            'content_type'
        ];
        
        // Dates
        protected $useTimestamps = true;
        protected $dateFormat    = 'datetime';
        protected $createdField  = 'created_at';
        protected $updatedField  = 'updated_at';
        
        // Validation
        protected $validationRules      = [
            'caller_name'     => 'required|max_length[255]',
            'content_name'     => 'required|max_length[255]',
            'caller_title' => 'permit_empty|max_length[255]',
            'content_type'    => 'required|in_list[audio,video,audio_call,video_call,html]',
            'caller_id'       => 'permit_empty|integer'
        ];
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