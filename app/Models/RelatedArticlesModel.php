<?php
    
    namespace App\Models;
    
    use CodeIgniter\Model;
    
    class RelatedArticlesModel extends Model
    {
        protected $table = 'related_articles';
        protected $primaryKey = 'id';
        protected $allowedFields = ['content_id', 'title', 'link'];
        protected $useTimestamps = true;
        protected $createdField  = 'created_at';
        protected $updatedField  = 'updated_at';
    }