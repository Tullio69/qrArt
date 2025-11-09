<?php
    
    namespace App\Controllers;
    
    use CodeIgniter\Controller;
    use CodeIgniter\HTTP\ResponseInterface;
    use App\Models\ContentModel;
    use App\Models\ShortUrlModel; // Aggiunto import mancante
    use App\Models\ContentMetadataModel;
    use App\Models\ContentFilesModel;  // ✅ Import corretto
    
    class ContentController extends Controller
    {
        protected $shortUrlModel;
        protected $contentModel;
        protected $contentMetadataModel;
        
        public function __construct()
        {
            $this->contentModel = new ContentModel();
            $this->shortUrlModel = new ShortUrlModel(); // Aggiunta inizializzazione mancante
            $this->contentMetadataModel = new ContentMetadataModel();
        }
        
        public function handleShortCode($shortCode): ResponseInterface
        {
            // Validazione dello shortcode
            if (empty($shortCode)) {
                return $this->response->setStatusCode(400)->setJSON([
                    'success' => false,
                    'error' => 'Short code non valido'
                ]);
            }
            
            try {
                // Recupero del contentId dallo shortcode
                $contentId = $this->shortUrlModel->getContentIdByShortCode($shortCode);
                
                if (!$contentId) {
                    return $this->response->setStatusCode(404)->setJSON([
                        'success' => false,
                        'error' => 'Contenuto non trovato'
                    ]);
                }
                
                // Recupero del contenuto completo
                $content = $this->contentModel->find($contentId);
                
                if (!$content) {
                    return $this->response->setStatusCode(404)->setJSON([
                        'success' => false,
                        'error' => 'Contenuto non disponibile'
                    ]);
                }
                
                // Recupero dei metadati e delle informazioni aggiuntive
                $contentData = $this->contentModel->getContentWithRelations($contentId);
                
                return $this->response->setJSON([
                    'success' => true,
                    'contentId' => $contentId,
                    'content' => $content,
                    'metadata' => $contentData['data'],
                    'shortCode' => $shortCode,
                    'fullUrl' => site_url($shortCode)
                ]);
                
            } catch (\Exception $e) {
                log_message('error', 'Errore in handleShortCode: ' . $e->getMessage());
                return $this->response->setStatusCode(500)->setJSON([
                    'success' => false,
                    'error' => 'Si è verificato un errore durante l\'elaborazione della richiesta'
                ]);
            }
        }
        
        public function getHtmlContent($contentId, $language): ResponseInterface
        {
            try {
                $htmlContent = $this->contentModel->getHtmlContent($contentId, $language);
                
                if (empty($htmlContent)) {
                    return $this->response->setStatusCode(404)->setJSON([
                        'status' => 404,
                        'error' => 'Contenuto HTML non trovato'
                    ]);
                }
                
                return $this->response->setJSON([
                    'status' => 200,
                    'content_name' => $htmlContent['content_name'],
                    'html_content' => $htmlContent['html_content']
                ]);
            } catch (\Exception $e) {
                log_message('error', 'Errore nel recupero del contenuto HTML: ' . $e->getMessage());
                return $this->response->setStatusCode(500)->setJSON([
                    'status' => 500,
                    'error' => 'Si è verificato un errore durante il recupero del contenuto HTML'
                ]);
            }
        }
        
        public function getContentData($shortCode): ResponseInterface
        {
            try {
                // Recupera il contentId dallo shortcode
                $contentId = $this->shortUrlModel->getContentIdByShortCode($shortCode);
                
                if (!$contentId) {
                    return $this->response->setStatusCode(404)->setJSON([
                        'status' => 404,
                        'error' => 'Content not found'
                    ]);
                }
                $result = $this->contentModel->getContentWithRelations($contentId);
              
                $rawData = $result['content'];
                $commonFiles = $result['data_common'];
                $metadata = $result['data_meta'];
                
                
                if (empty($rawData)) {
                    return $this->response->setStatusCode(404)->setJSON([
                        'status' => 404,
                        'error' => 'Content not found'
                    ]);
                }
                
                // Initialize content structure
                $content = [
                    'id' => $contentId,
                    'caller_name' => $rawData['caller_name'],
                    'caller_title' => $rawData['caller_title'],
                    'content_type' => $rawData['content_type'],
                    'common_files' => [],
                    'metadata' => $metadata
                ];
                
               
                
                foreach ($commonFiles as $row) {
                    if ($row['file_type'] === 'callerBackground' || $row['file_type'] === 'callerAvatar') {
                        $content['common_files'][] = [
                            'file_type' => $row['file_type'],
                            'file_url' => $row['file_url']
                        ];
                    } elseif ($row['language'] !== null) {
                        $metadataKey = $row['language'] . '_' . ($row['text_only'] ? 'text_only' : 'audio');
                        
                       
                    }
                }
                
                $content['metadata'] = array_values($metadata);
                
                $response = [
                    'status' => 200,
                    'content' => $content
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
        
        public function getDetails($contentId): ResponseInterface
        {
            $contentFilesModel = new ContentFilesModel();
            $contentMetadataModel = new ContentMetadataModel();
            
            $files = $contentFilesModel->where('content_id', $contentId)->findAll();
            $metadata = $contentMetadataModel->where('content_id', $contentId)->findAll();
            
            return $this->response->setJSON([
                'status' => 200,
                'files' => $files,
                'metadata' => $metadata
            ]);
        }
        
        
        public function list(): ResponseInterface
        {
            try {
                $contents = $this->contentModel->getAllContents(); // Chiama il metodo del modello
                
                return $this->response->setJSON([
                    'status' => 200,
                    'message' => 'Lista contenuti recuperata con successo.',
                    'data' => $contents
                ]);
            } catch (\Exception $e) {
                return $this->response->setStatusCode(500)->setJSON([
                    'success' => false,
                    'error' => 'Errore durante il recupero dei contenuti.',
                    'details' => $e->getMessage()
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
        
        public function updateContent($shortCode): ResponseInterface
        {
            $db = \Config\Database::connect();
            $db->transStart();
            
            try {
                // Recupera il contentId dallo shortcode
                $contentId = $this->shortUrlModel->getContentIdByShortCode($shortCode);
                
                if (!$contentId) {
                    return $this->response->setStatusCode(404)->setJSON([
                        'success' => false,
                        'error' => 'Contenuto non trovato'
                    ]);
                }
                
                $formData = $this->request->getPost();
                $files = $this->request->getFiles();
                
                // Aggiorna i dati principali del contenuto
                $contentData = [
                    'caller_name' => $formData['callerName'],
                    'caller_title' => $formData['callerTitle'],
                    'content_name' => $formData['contentName'],
                    'content_type' => $formData['contentType']
                ];
                
                $this->contentModel->update($contentId, $contentData);
                
                // Gestione dei file comuni (avatar e background)
                $this->handleCommonFilesUpdate($files, $contentId);
                
                // Gestione delle varianti linguistiche
                foreach ($formData['languageVariants'] as $variant) {
                    $this->handleLanguageVariantUpdate($variant, $contentId, $files);
                }
                
                // Commit della transazione
                $db->transCommit();
                
                // Recupera i dati aggiornati
                $updatedContent = $this->getContentData($shortCode);
                
                return $this->response->setJSON([
                    'success' => true,
                    'message' => 'Contenuto aggiornato con successo',
                    'content' => $updatedContent->getJSON()
                ]);
                
            } catch (Exception $e) {
                $db->transRollback();
                log_message('error', 'Errore in updateContent: ' . $e->getMessage());
                
                return $this->response->setStatusCode(500)->setJSON([
                    'success' => false,
                    'error' => 'Si è verificato un errore durante l\'aggiornamento del contenuto'
                ]);
            }
        }
        
        private function handleCommonFilesUpdate($files, $contentId)
        {
            $commonFiles = ['callerBackground', 'callerAvatar'];
            
            foreach ($commonFiles as $fileType) {
                if (isset($files[$fileType]) && $files[$fileType]->isValid()) {
                    // Elimina il file esistente se presente
                    $existingFile = $this->contentFilesModel
                        ->where('content_id', $contentId)
                        ->where('file_type', $fileType)
                        ->first();
                    
                    if ($existingFile && file_exists(FCPATH . 'media/' . $existingFile['file_url'])) {
                        unlink(FCPATH . 'media/' . $existingFile['file_url']);
                    }
                    
                    // Carica il nuovo file
                    $file = $files[$fileType];
                    $newName = $contentId . '_' . $fileType . '.' . $file->getExtension();
                    $file->move(FCPATH . 'media/' . $contentId, $newName);
                    
                    // Aggiorna o inserisce il record nel database
                    $fileData = [
                        'content_id' => $contentId,
                        'file_type' => $fileType,
                        'file_url' => $contentId . '/' . $newName
                    ];
                    
                    if ($existingFile) {
                        $this->contentFilesModel->update($existingFile['id'], $fileData);
                    } else {
                        $this->contentFilesModel->insert($fileData);
                    }
                }
            }
        }
        
        private function handleLanguageVariantUpdate($variant, $contentId, $files)
        {
            // Aggiorna o crea i metadati della variante linguistica
            $metadataData = [
                'content_id' => $contentId,
                'language' => $variant['language'],
                'content_name' => $variant['contentName'],
                'text_only' => $variant['textOnly'],
                'description' => $variant['description'] ?? '',
                'html_content' => $variant['htmlContent'] ?? null
            ];
            
            $existingMetadata = $this->contentMetadataModel
                ->where('content_id', $contentId)
                ->where('language', $variant['language'])
                ->first();
            
            if ($existingMetadata) {
                $this->contentMetadataModel->update($existingMetadata['id'], $metadataData);
                $metadataId = $existingMetadata['id'];
            } else {
                $metadataId = $this->contentMetadataModel->insert($metadataData);
            }
            
            // Gestione dei file della variante
            $this->handleVariantFilesUpdate($variant, $contentId, $metadataId, $files);
        }
        
        private function handleVariantFilesUpdate($variant, $contentId, $metadataId, $files)
        {
            $fileKey = $variant['textOnly'] ? null :
                ($variant['contentType'] === 'audio' || $variant['contentType'] === 'audio_call' ? 'audioFile' : 'videoFile');
            
            if ($fileKey && isset($files['languageVariants'][$variant['language']][$fileKey])) {
                $file = $files['languageVariants'][$variant['language']][$fileKey];
                
                if ($file->isValid()) {
                    // Elimina il file esistente
                    $existingFile = $this->contentFilesModel
                        ->where('content_id', $contentId)
                        ->where('metadata_id', $metadataId)
                        ->first();
                    
                    if ($existingFile && file_exists(FCPATH . 'media/' . $existingFile['file_url'])) {
                        unlink(FCPATH . 'media/' . $existingFile['file_url']);
                    }
                    
                    // Carica il nuovo file
                    $newName = $contentId . '_' . $variant['language'] . '_' .
                        ($variant['contentType'] === 'audio' || $variant['contentType'] === 'audio_call' ? 'audio' : 'video') .
                        '.' . $file->getExtension();
                    
                    $file->move(FCPATH . 'media/' . $contentId . '/' . $variant['language'], $newName);
                    
                    // Aggiorna o inserisce il record nel database
                    $fileData = [
                        'content_id' => $contentId,
                        'metadata_id' => $metadataId,
                        'file_type' => $variant['contentType'],
                        'file_url' => $contentId . '/' . $variant['language'] . '/' . $newName
                    ];
                    
                    if ($existingFile) {
                        $this->contentFilesModel->update($existingFile['id'], $fileData);
                    } else {
                        $this->contentFilesModel->insert($fileData);
                    }
                }
            }
            
            // Gestione del contenuto HTML
            if (isset($variant['htmlContent']) && !empty($variant['htmlContent'])) {
                $htmlFilePath = FCPATH . 'media/' . $contentId . '/' . $variant['language'] . '/' .
                    $contentId . '_' . $variant['language'] . '_content.html';
                
                file_put_contents($htmlFilePath, $variant['htmlContent']);
            }
        }
    }