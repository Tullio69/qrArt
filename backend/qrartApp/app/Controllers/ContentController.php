<?php
    
    namespace App\Controllers;
    
    use CodeIgniter\Controller;
    use CodeIgniter\HTTP\ResponseInterface;
   use App\Models\ContentModel;
    
    class ContentController extends Controller
    {
        protected $shortUrlModel;
        protected $contentModel;
        
        protected $ContentMetadataModel;
        public function __construct()
        {
           
            $this->contentModel = new ContentModel();
         
        }
        
        public function handleShortCode($shortCode): ResponseInterface
        {
            
            $contentId = $this->shortUrlModel->getContentIdByShortCode($shortCode);
            
            if (!$contentId) {
                return $this->response->setStatusCode(404)->setJSON([
                    'error' => 'Content not found'
                ]);
            }
            
            $content = $this->contentModel->find($contentId);
            
            if (!$content) {
                return $this->response->setStatusCode(404)->setJSON([
                    'error' => 'Content not available'
                ]);
            }
            
            // Assuming you want to render a view with the content
            return $this->response->setJSON([
                'contentId' => $contentId,
                'content' => $content
            ]);
        }
        
        
        
        public function getContentData($contentId): ResponseInterface
        {
            $rawData = $this->contentModel->getContentWithRelations($contentId);
            
            if (empty($rawData)) {
                return $this->failNotFound('Content not found');
            }
            
            // Organize the data
            $content = [
                'id' => $rawData[0]['id'],
                'caller_id' => $rawData[0]['caller_id'],
                'caller_name' => $rawData[0]['caller_name'],
                'caller_title' => $rawData[0]['caller_title'],
                'content_name' => $rawData[0]['content_name'],
                'content_type' => $rawData[0]['content_type'],
                'created_at' => $rawData[0]['created_at'],
                'updated_at' => $rawData[0]['updated_at'],
            ];
            
            $metadata = [];
            $files = [];
            
            foreach ($rawData as $row) {
                if (!empty($row['language'])) {
                    $metadata[] = [
                        'id' => $row['id'],
                        'content_id' => $row['content_id'],
                        'language' => $row['language'],
                        'text_only' => $row['text_only'],
                        'content_name' => $row['content_name'],
                        'description' => $row['description'],
                        'created_at' => $row['created_at'],
                        'updated_at' => $row['updated_at'],
                    ];
                }
                
                if (!empty($row['file_name'])) {
                    $files[] = [
                        'id' => $row['id'],
                        'content_id' => $row['content_id'],
                        'file_name' => $row['file_name'],
                        'file_type' => $row['file_type'],
                        'file_path' => $row['file_path'],
                        'created_at' => $row['created_at'],
                        'updated_at' => $row['updated_at'],
                    ];
                }
            }
            
            $response = [
                'content' => $content,
                'metadata' => $metadata,
                'files' => $files
            ];
            
            return $this->response->setJSON($response);
        }
        
        public function createShortUrl(): ResponseInterface
        {
            $contentId = $this->request->getPost('content_id');
            
            if (!$contentId) {
                return $this->response->setStatusCode(400)->setJSON([
                    'error' => 'Content ID is required'
                ]);
            }
            
            $shortCode = $this->shortUrlModel->createShortUrl($contentId);
            
            if (!$shortCode) {
                return $this->response->setStatusCode(500)->setJSON([
                    'error' => 'Failed to create short URL'
                ]);
            }
            
            return $this->response->setJSON([
                'short_code' => $shortCode,
                'full_url' => site_url($shortCode)
            ]);
        }
    }