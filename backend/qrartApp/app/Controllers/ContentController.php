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
            try {
                $result = $this->contentModel->getContentWithRelations($contentId);
                $rawData = $result['data'];
                $sql = $result['sql'];
                
                if (empty($rawData)) {
                    return $this->response->setStatusCode(404)->setJSON([
                        'status' => 404,
                        'error' => 'Content not found'
                    ]);
                }
                
                // Initialize content structure
                $content = [
                    'id' => $contentId,
                    'caller_name' => $rawData[0]['caller_name'],
                    'caller_title' => $rawData[0]['caller_title'],
                    'content_type' => $rawData[0]['content_type'],
                    'common_files' => [],
                    'metadata' => []
                ];
                
                $metadata = [];
                
                foreach ($rawData as $row) {
                    if ($row['file_type'] === 'callerBackground' || $row['file_type'] === 'callerAvatar') {
                        $content['common_files'][] = [
                            'file_type' => $row['file_type'],
                            'file_url' => $row['file_url']
                        ];
                    } elseif ($row['language'] !== null) {
                        $metadataKey = $row['language'] . '_' . ($row['text_only'] ? 'text_only' : 'audio');
                        
                        if (!isset($metadata[$metadataKey])) {
                            $metadata[$metadataKey] = [
                                'language' => $row['language'],
                                'content_name' => $row['content_name'],
                                'text_only' => (bool)$row['text_only'],
                                'file_type' => $row['file_type'],
                                'file_url' => $row['file_url']
                            ];
                        }
                    }
                }
                
                $content['metadata'] = array_values($metadata);
                
                $response = [
                    'status' => 200,
                    'content' => $content,
                    'debug' => [
                        'sql' => $sql
                    ]
                ];
                
                return $this->response->setJSON($response);
            } catch (\Exception $e) {
                log_message('error', 'Error in getContentData: ' . $e->getMessage());
                return $this->response->setStatusCode(500)->setJSON([
                    'status' => 500,
                    'error' => 'An error occurred while processing your request'
                ]);
            }
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