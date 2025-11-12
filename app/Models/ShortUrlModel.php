<?php
    
    namespace App\Models;
    
    use CodeIgniter\Model;
    
    class ShortUrlModel extends Model
    {
        protected $table            = 'short_urls';
        protected $primaryKey       = 'id';
        protected $useAutoIncrement = true;
        protected $returnType       = 'array';
        protected $useSoftDeletes   = false;
        protected $protectFields    = true;
        protected $allowedFields    = ['content_id', 'short_code', 'created_at', 'updated_at'];
        
        // Dates
        protected $useTimestamps = true;
        protected $dateFormat    = 'datetime';
        protected $createdField  = 'created_at';
        protected $updatedField  = 'updated_at';
        
        // Validation
        protected $validationRules      = [
            'content_id' => 'required|integer',
            'short_code' => 'required|alpha_numeric|min_length[6]|max_length[10]|is_unique[short_urls.short_code]',
        ];
        protected $validationMessages   = [];
        protected $skipValidation       = false;
        protected $cleanValidationRules = true;
        
        /**
         * Get content ID by short code
         *
         * @param string $shortCode
         * @return int|null
         */
        public function getContentIdByShortCode(string $shortCode): ?int
        {
            $result = $this->where('short_code', $shortCode)->first();
            return $result ? $result['content_id'] : null;
        }
        
        /**
         * Generate a unique short code
         *
         * @param int $length
         * @return string
         */
        public function generateUniqueShortCode(int $length = 6): string
        {
            $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
            do {
                $shortCode = '';
                for ($i = 0; $i < $length; $i++) {
                    $shortCode .= $characters[rand(0, strlen($characters) - 1)];
                }
            } while ($this->where('short_code', $shortCode)->countAllResults() > 0);
            
            return $shortCode;
        }
        
        /**
         * Create a new short URL
         *
         * @param int $contentId
         * @return string|null
         */
        public function createShortUrl(int $contentId): ?string
        {
            $shortCode = $this->generateUniqueShortCode();
            $data = [
                'content_id' => $contentId,
                'short_code' => $shortCode,
            ];
            
            if ($this->insert($data)) {
                return $shortCode;
            }
            
            return null;
        }
    }