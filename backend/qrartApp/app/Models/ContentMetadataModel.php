<?php
    
    namespace App\Models;
    
    use CodeIgniter\Model;
    
    class ContentMetadataModel extends Model
    {
        protected $table            = 'content_metadata';
        protected $primaryKey       = 'id';
        protected $useAutoIncrement = true;
        protected $returnType       = 'array';
        protected $useSoftDeletes   = false;
        protected $protectFields    = true;
        protected $allowedFields    = [
            'content_id',
            'language',
            'text_only',
            'title',
            'description'
        ];
        
        // Dates
        protected $useTimestamps = true;
        protected $dateFormat    = 'datetime';
        protected $createdField  = 'created_at';
        protected $updatedField  = 'updated_at';
        
        // Validation
        protected $validationRules      = [
            'content_id'  => 'required|integer',
            'language'    => 'required|alpha|max_length[5]',
            'text_only'   => 'permit_empty|integer|in_list[0,1]',
            'title'       => 'required|max_length[255]',
            'description' => 'permit_empty'
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