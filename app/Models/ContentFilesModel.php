<?php
    
    namespace App\Models;
    
    use CodeIgniter\Model;
    
    class ContentFilesModel extends Model
    {
        protected $table            = 'content_files';
        protected $primaryKey       = 'id';
        protected $useAutoIncrement = true;
        protected $returnType       = 'array';
        protected $useSoftDeletes   = false;
        protected $protectFields    = true;
        protected $allowedFields    = [
            'content_id',
            'metadata_id',
            'file_type',
            'file_url'
        ];
        
        // Dates
        protected $useTimestamps = true;
        protected $dateFormat    = 'datetime';
        protected $createdField  = 'created_at';
        protected $updatedField  = 'updated_at';
        
        // Validation
        protected $validationRules      = [
            'content_id'  => 'required|integer',
            'metadata_id' => 'required|integer',
            'file_type'   => 'required|max_length[50]',
            'file_url'    => 'required|valid_url'
        ];
        protected $validationMessages   = [
            'content_id' => [
                'required' => 'Content ID is required.',
                'integer'  => 'Content ID must be an integer.'
            ],
            'metadata_id' => [
                'required' => 'Metadata ID is required.',
                'integer'  => 'Metadata ID must be an integer.'
            ],
            'file_type' => [
                'required'   => 'File type is required.',
                'max_length' => 'File type must not exceed 50 characters.'
            ],
            'file_url' => [
                'required'  => 'File URL is required.',
                'valid_url' => 'Please enter a valid URL.'
            ]
        ];
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
        
        public function metadata()
        {
            return $this->belongsTo('App\Models\ContentMetadataModel', 'metadata_id', 'id');
        }
        
        // Custom Methods
        public function getFilesByContentId($contentId)
        {
            return $this->where('content_id', $contentId)->findAll();
        }
        
        public function getFilesByMetadataId($metadataId)
        {
            return $this->where('metadata_id', $metadataId)->findAll();
        }
        
        public function addFile($data)
        {
            return $this->insert($data);
        }
        
        public function updateFile($id, $data)
        {
            return $this->update($id, $data);
        }
        
        public function deleteFile($id)
        {
            return $this->delete($id);
        }
    }