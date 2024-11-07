<?php
    
    namespace App\Models;
    
    use CodeIgniter\Model;
    
    class ContentMetadataModel extends Model
    {
        protected $DBGroup          = 'default';
        protected $table            = 'content_metadata';
        protected $primaryKey       = 'id';
        protected $useAutoIncrement = true;
        protected $returnType       = 'array';
        protected $useSoftDeletes   = false;
        protected $protectFields    = true;
        protected $allowedFields    = ['content_id', 'language', 'text_only', 'description','content_name'];
        
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
        
        // Relationships
        public function content()
        {
            return $this->belongsTo('App\Models\ContentModel', 'content_id', 'id');
        }
        
        // Custom Methods
        public function getMetadataByContentId($contentId)
        {
            return $this->where('content_id', $contentId)->findAll();
        }
        
        public function createOrUpdateMetadata($contentId, $data)
        {
            $existingMetadata = $this->where('content_id', $contentId)
                ->where('language', $data['language'])
                ->first();
            
            if ($existingMetadata) {
                return $this->update($existingMetadata['id'], $data);
            } else {
                return $this->insert($data);
            }
        }
    }