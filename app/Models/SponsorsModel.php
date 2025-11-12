<?php
    
    
    namespace App\Models;
    
    use CodeIgniter\Model;
    
    class SponsorsModel extends Model
    {
        protected $table = 'sponsors';
        protected $primaryKey = 'id';
        protected $allowedFields = ['content_id', 'name', 'link', 'image_url'];
        protected $useTimestamps = true;
        protected $createdField = 'created_at';
        protected $updatedField = 'updated_at';
    }