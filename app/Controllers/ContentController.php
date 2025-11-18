<?php
    
    namespace App\Controllers;

    use CodeIgniter\Controller;
    use CodeIgniter\HTTP\ResponseInterface;
    use App\Models\ContentModel;
    use App\Models\ShortUrlModel; // Aggiunto import mancante
    use App\Models\ContentMetadataModel;
    use App\Models\ContentFilesModel;  // ✅ Import corretto
    use App\Libraries\AnalyticsEventService;
    
    class ContentController extends Controller
    {
        protected $shortUrlModel;
        protected $contentModel;
        protected $contentMetadataModel;
        protected $analyticsService;

        public function __construct()
        {
            $this->contentModel = new ContentModel();
            $this->shortUrlModel = new ShortUrlModel(); // Aggiunta inizializzazione mancante
            $this->contentMetadataModel = new ContentMetadataModel();
            $this->analyticsService = new AnalyticsEventService();
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

                // Traccia accesso al contenuto HTML
                $this->analyticsService->trackEvent('content_view', [
                    'content_id' => $contentId,
                    'language' => $language,
                    'metadata' => ['content_type' => 'html']
                ]);

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

                // Traccia evento di scansione QR code
                $this->analyticsService->trackEvent('qr_scan', [
                    'content_id' => $contentId,
                    'short_code' => $shortCode
                ]);

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

            // Get content data
            $content = $this->contentModel->find($contentId);

            // Get short code
            $shortUrlModel = new ShortUrlModel();
            $shortUrlData = $shortUrlModel->where('content_id', $contentId)->first();

            // Add short_code to content if exists
            if ($content && $shortUrlData) {
                $content['short_code'] = $shortUrlData['short_code'];
            }

            $files = $contentFilesModel->where('content_id', $contentId)->findAll();
            $metadata = $contentMetadataModel->where('content_id', $contentId)->findAll();

            return $this->response->setJSON([
                'status' => 200,
                'content' => $content,
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
                
                $db->transCommit();
                
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
        
        public function updateHtmlContent($contentId): ResponseInterface
        {
            $db = \Config\Database::connect();
            $db->transStart();
            
            try {
                $data = $this->request->getJSON(true);
                
                // Aggiorna content
                $this->contentModel->update($contentId, [
                    'caller_name' => $data['caller_name'],
                    'caller_title' => $data['caller_title']
                ]);
                
                // Aggiorna metadata
                if (isset($data['metadata'])) {
                    foreach ($data['metadata'] as $meta) {
                        $this->contentMetadataModel->update($meta['id'], [
                            'content_name' => $meta['content_name'],
                            'description' => $meta['description'],
                            'html_content' => $meta['html_content'],
                            'text_only' => $meta['text_only']
                        ]);
                    }
                }
                
                $db->transCommit();
                
                return $this->response->setJSON([
                    'success' => true,
                    'message' => 'Contenuto aggiornato con successo'
                ]);
                
            } catch (\Exception $e) {
                $db->transRollback();
                log_message('error', 'Errore update: ' . $e->getMessage());
                
                return $this->response->setJSON([
                    'success' => false,
                    'error' => $e->getMessage()
                ])->setStatusCode(500);
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

        /**
         * Elimina un contenuto e tutti i suoi dati associati
         */
        public function deleteContent($contentId): ResponseInterface
        {
            $db = \Config\Database::connect();
            $db->transStart();

            try {
                // Verifica che il contenuto esista
                $content = $this->contentModel->find($contentId);
                if (!$content) {
                    return $this->response->setStatusCode(404)->setJSON([
                        'success' => false,
                        'error' => 'Contenuto non trovato'
                    ]);
                }

                // Recupera tutti i file associati per eliminarli fisicamente
                $contentFilesModel = new ContentFilesModel();
                $files = $contentFilesModel->where('content_id', $contentId)->findAll();

                // Elimina i file dal filesystem
                foreach ($files as $file) {
                    $filePath = FCPATH . 'media/' . $file['file_url'];
                    if (file_exists($filePath)) {
                        unlink($filePath);
                    }
                }

                // Elimina la directory del contenuto se esiste
                $contentDir = FCPATH . 'media/' . $contentId;
                if (is_dir($contentDir)) {
                    $this->deleteDirectory($contentDir);
                }

                // Elimina i record dal database (rispettando l'ordine per le foreign key)
                $contentFilesModel->where('content_id', $contentId)->delete();
                $this->contentMetadataModel->where('content_id', $contentId)->delete();
                $this->shortUrlModel->where('content_id', $contentId)->delete();
                $this->contentModel->delete($contentId);

                $db->transCommit();

                return $this->response->setJSON([
                    'success' => true,
                    'message' => 'Contenuto eliminato con successo'
                ]);

            } catch (\Exception $e) {
                $db->transRollback();
                log_message('error', 'Errore in deleteContent: ' . $e->getMessage());

                return $this->response->setStatusCode(500)->setJSON([
                    'success' => false,
                    'error' => 'Si è verificato un errore durante l\'eliminazione del contenuto',
                    'details' => $e->getMessage()
                ]);
            }
        }

        /**
         * Elimina una directory ricorsivamente
         */
        private function deleteDirectory($dir): bool
        {
            if (!is_dir($dir)) {
                return false;
            }

            $items = array_diff(scandir($dir), ['.', '..']);
            foreach ($items as $item) {
                $path = $dir . DIRECTORY_SEPARATOR . $item;
                is_dir($path) ? $this->deleteDirectory($path) : unlink($path);
            }

            return rmdir($dir);
        }

        /**
         * Sostituisce un file esistente
         */
        public function replaceFile(): ResponseInterface
        {
            try {
                $fileId = $this->request->getPost('file_id');
                $files = $this->request->getFiles();

                if (!$fileId || empty($files['file'])) {
                    return $this->response->setStatusCode(400)->setJSON([
                        'success' => false,
                        'error' => 'File ID e file sono richiesti'
                    ]);
                }

                $contentFilesModel = new ContentFilesModel();
                $existingFile = $contentFilesModel->find($fileId);

                if (!$existingFile) {
                    return $this->response->setStatusCode(404)->setJSON([
                        'success' => false,
                        'error' => 'File non trovato'
                    ]);
                }

                $newFile = $files['file'];

                if (!$newFile->isValid()) {
                    return $this->response->setStatusCode(400)->setJSON([
                        'success' => false,
                        'error' => 'Il file caricato non è valido'
                    ]);
                }

                // Elimina il vecchio file
                $oldFilePath = FCPATH . 'media/' . $existingFile['file_url'];
                if (file_exists($oldFilePath)) {
                    unlink($oldFilePath);
                }

                // Carica il nuovo file con lo stesso nome
                $pathInfo = pathinfo($existingFile['file_url']);
                $newName = $pathInfo['basename'];
                $directory = $pathInfo['dirname'];

                $newFile->move(FCPATH . 'media/' . $directory, $newName, true);

                return $this->response->setJSON([
                    'success' => true,
                    'message' => 'File sostituito con successo',
                    'file_url' => $existingFile['file_url']
                ]);

            } catch (\Exception $e) {
                log_message('error', 'Errore in replaceFile: ' . $e->getMessage());

                return $this->response->setStatusCode(500)->setJSON([
                    'success' => false,
                    'error' => 'Si è verificato un errore durante la sostituzione del file',
                    'details' => $e->getMessage()
                ]);
            }
        }

        /**
         * Elimina un singolo file
         */
        public function deleteFile($fileId): ResponseInterface
        {
            try {
                $contentFilesModel = new ContentFilesModel();
                $file = $contentFilesModel->find($fileId);

                if (!$file) {
                    return $this->response->setStatusCode(404)->setJSON([
                        'success' => false,
                        'error' => 'File non trovato'
                    ]);
                }

                // Elimina il file dal filesystem
                $filePath = FCPATH . 'media/' . $file['file_url'];
                if (file_exists($filePath)) {
                    unlink($filePath);
                }

                // Elimina il record dal database
                $contentFilesModel->delete($fileId);

                return $this->response->setJSON([
                    'success' => true,
                    'message' => 'File eliminato con successo'
                ]);

            } catch (\Exception $e) {
                log_message('error', 'Errore in deleteFile: ' . $e->getMessage());

                return $this->response->setStatusCode(500)->setJSON([
                    'success' => false,
                    'error' => 'Si è verificato un errore durante l\'eliminazione del file',
                    'details' => $e->getMessage()
                ]);
            }
        }

        /**
         * Elimina i metadati di una specifica lingua
         */
        public function deleteMetadata($metadataId): ResponseInterface
        {
            $db = \Config\Database::connect();
            $db->transStart();

            try {
                $metadata = $this->contentMetadataModel->find($metadataId);

                if (!$metadata) {
                    return $this->response->setStatusCode(404)->setJSON([
                        'success' => false,
                        'error' => 'Metadati non trovati'
                    ]);
                }

                // Elimina i file associati a questi metadati
                $contentFilesModel = new ContentFilesModel();
                $files = $contentFilesModel->where('metadata_id', $metadataId)->findAll();

                foreach ($files as $file) {
                    $filePath = FCPATH . 'media/' . $file['file_url'];
                    if (file_exists($filePath)) {
                        unlink($filePath);
                    }
                    $contentFilesModel->delete($file['id']);
                }

                // Elimina il file HTML se esiste
                $contentId = $metadata['content_id'];
                $language = $metadata['language'];
                $htmlFilePath = FCPATH . 'media/' . $contentId . '/' . $language . '/' .
                    $contentId . '_' . $language . '_content.html';

                if (file_exists($htmlFilePath)) {
                    unlink($htmlFilePath);
                }

                // Elimina i metadati
                $this->contentMetadataModel->delete($metadataId);

                $db->transCommit();

                return $this->response->setJSON([
                    'success' => true,
                    'message' => 'Variante linguistica eliminata con successo'
                ]);

            } catch (\Exception $e) {
                $db->transRollback();
                log_message('error', 'Errore in deleteMetadata: ' . $e->getMessage());

                return $this->response->setStatusCode(500)->setJSON([
                    'success' => false,
                    'error' => 'Si è verificato un errore durante l\'eliminazione dei metadati',
                    'details' => $e->getMessage()
                ]);
            }
        }
    }