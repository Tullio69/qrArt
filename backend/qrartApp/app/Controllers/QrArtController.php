<?php
    
    namespace App\Controllers;
    
    use App\Models\ContentFilesModel;
    use App\Models\ContentMetadataModel;
    use App\Models\ContentModel;
    use CodeIgniter\HTTP\Files\UploadedFile;
    use CodeIgniter\HTTP\RequestInterface;
    use CodeIgniter\HTTP\ResponseInterface;
    use Config\Database;
    use Exception;
    use Psr\Log\LoggerInterface;
    
    class QrArtController extends BaseController
    {
        public function processQrArtContent()
        {
            $db = Database::connect();
            $db->transStart();
            $contentDir = null;
            
            try {
                $formData = $this->request->getPost();
                $files = $this->request->getFiles();
                
                $contentModel = new ContentModel();
                $contentData = [
                    'caller_name' => $formData['callerName'],
                    'caller_title' => $formData['callerTitle'],
                    'content_name' => $formData['contentName'],
                    'content_type' => $formData['contentType']
                ];
                
                $contentId = $contentModel->insert($contentData);
                
                $contentDir = $this->createContentDirectory($contentId);
                
                if (!$contentId) {
                    throw new Exception('Errore nella creazione del nuovo content');
                }
                
                // Handle common files first
                $this->handleCommonFiles($files, $contentDir, $contentId);
                
                foreach ($formData['languageVariants'] as $index => $variant) {
                    $metadataData = [
                        'content_id' => $contentId,
                        'language' => $variant['language'],
                        'content_name' => $variant['contentName'],
                        'text_only' => $variant['textOnly'],
                        'description' => $variant['description'] ?? '',
                        'html_content' => $variant['htmlContent'] ?? null  // Salva sempre l'HTML content se presente
                    ];
                    
                  
                    $contentMetadataModel = new ContentMetadataModel();
                    $metadataId = $contentMetadataModel->insert($metadataData);
                   
                    if (!$metadataId) {
                        throw new Exception('Errore nel salvataggio dei metadati della variante linguistica');
                    }
                    
                    $languageDir = $this->createLanguageDirectory($contentDir, $variant['language']);
                    
                    $uploadedFiles = $this->handleFileUploads($files, $variant, $contentId, $languageDir, $formData['contentType'], $index, $metadataId);
                    
                    if (!$uploadedFiles['success']) {
                        throw new Exception($uploadedFiles['message']);
                    }
                }
                
                $db->transCommit();
                
                return $this->response->setJSON([
                    'success' => true,
                    'message' => 'Contenuto creato con successo',
                    'contentId' => $contentId
                ]);
                
            } catch (Exception $e) {
                if ($contentDir !== null && is_dir($contentDir)) {
                    $this->removeDirectory($contentDir);
                }
                
                $db->transRollback();
                $this->resetContentCounter();
                
                log_message('error', 'Errore in processQrArtContent: ' . $e->getMessage());
                
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Si è verificato un errore durante l\'elaborazione: ' . $e->getMessage()
                ])->setStatusCode(500);
            }
        }
        
        private function handleFileUploads($files, $variant, $contentId, $languageDir, $contentType, $variantIndex, $metadataId)
        {
            $uploadedFiles = [];
            $contentFilesModel = new ContentFilesModel();
            
            // Handle audio/video file
            $fileKey = $contentType === 'audio' || $contentType === 'audio_call' ? 'audioFile' : 'videoFile';
            if (isset($files['languageVariants'][$variantIndex][$fileKey]) &&
                $files['languageVariants'][$variantIndex][$fileKey] instanceof UploadedFile) {
                
                $file = $files['languageVariants'][$variantIndex][$fileKey];
                
                if ($file->isValid() && !$file->hasMoved()) {
                    $destinationPath = $languageDir;
                    
                    try {
                        $contentGroup = ($contentType === 'audio' || $contentType === 'audio_call' ? 'audio' : 'video');
                        $newName = $contentId . '_' . $variant['language'] . '_' . $contentGroup . '.' . $file->getExtension();
                        $file->move($destinationPath, $newName);
                        
                        if ($file->hasMoved()) {
                            $filePath = $contentId . '/' . $variant['language'] . '/' . $newName;
                            $uploadedFiles[] = [
                                'success' => true,
                                'message' => 'File salvato con successo',
                                'filePath' => $filePath,
                                'originalName' => $file->getClientName(),
                                'newName' => $newName
                            ];
                            
                            // Insert into content_files table
                            $contentFilesModel->insert([
                                'content_id' => $contentId,
                                'metadata_id' => $metadataId,
                                'file_type' => $contentGroup,
                                'file_url' => $filePath
                            ]);
                        } else {
                            $uploadedFiles[] = [
                                'success' => false,
                                'message' => 'Impossibile spostare il file'
                            ];
                        }
                    } catch (Exception $e) {
                        $uploadedFiles[] = [
                            'success' => false,
                            'message' => 'Errore durante il salvataggio del file: ' . $e->getMessage()
                        ];
                    }
                } else {
                    $uploadedFiles[] = [
                        'success' => false,
                        'message' => 'File non valido o già spostato'
                    ];
                }
            }
            
            // Handle HTML content for both text-only and non-text-only variants
            if (isset($variant['htmlContent']) && !empty($variant['htmlContent'])) {
                $htmlContent = $variant['htmlContent'];
                $htmlFilePath = $languageDir . '/'.$contentId.'_'.$variant['language'].'_'.'content.html';
                if (file_put_contents($htmlFilePath, $htmlContent) !== false) {
                    $filePath = $contentId . '/' . $variant['language'] . '/'.$contentId.'_'.$variant['language'].'_'.'content.html';
                    $uploadedFiles[] = [
                        'success' => true,
                        'message' => 'Contenuto HTML salvato con successo',
                        'filePath' => $filePath,
                        'newName' => 'content.html'
                    ];
                    
                    // Insert into content_files table
                    $contentFilesModel->insert([
                        'content_id' => $contentId,
                        'metadata_id' => $metadataId,
                        'file_type' => 'html',
                        'file_url' => $filePath
                    ]);
                } else {
                    $uploadedFiles[] = [
                        'success' => false,
                        'message' => 'Impossibile salvare il contenuto HTML'
                    ];
                }
            }
            
            return ['success' => !empty($uploadedFiles), 'files' => $uploadedFiles];
        }
        
        
        private function createContentDirectory($contentId)
        {
            $mediaDir = FCPATH . 'media';
            $contentDir = $mediaDir . DIRECTORY_SEPARATOR . $contentId;
            
            if (!is_dir($mediaDir)) {
                if (!mkdir($mediaDir, 0755)) {
                    log_message('error', "Failed to create media directory: {$mediaDir}");
                    throw new \RuntimeException("Unable to create media directory {$mediaDir}");
                }
            }
            
            if (!is_dir($contentDir)) {
                if (!mkdir($contentDir, 0755, true)) {
                    log_message('error', "Failed to create content directory: {$contentDir}");
                    throw new \RuntimeException("Unable to create content directory {$contentDir}");
                }
            }
            
            return $contentDir;
        }
        
        private function handleCommonFiles($files, $contentDir, $contentId)
        {
            $commonFiles = ['callerBackground', 'callerAvatar'];
            $contentFilesModel = new ContentFilesModel();
            
            foreach ($commonFiles as $fileKey) {
                if (isset($files[$fileKey]) && $files[$fileKey] instanceof \CodeIgniter\HTTP\Files\UploadedFile) {
                    $file = $files[$fileKey];
                    log_message('info', "Tentativo di caricamento del file {$fileKey}");
                    
                    if ($file->isValid() && !$file->hasMoved()) {
                        $newName = $contentId . '_' . $fileKey . "." . $file->getExtension();
                        try {
                            log_message('info', "Tentativo di spostamento del file {$fileKey} in {$contentDir}/{$newName}");
                            
                            if (!is_dir($contentDir)) {
                                log_message('error', "La directory di destinazione non esiste: {$contentDir}");
                                mkdir($contentDir, 0755, true);
                                log_message('info', "Creata directory: {$contentDir}");
                            }
                            
                            if (!is_writable($contentDir)) {
                                log_message('error', "La directory di destinazione non è scrivibile: {$contentDir}");
                                throw new \Exception("La directory di destinazione non è scrivibile");
                            }
                            
                            $file->move($contentDir, $newName);
                            $filePath = $contentId . '/' . $newName;
                            log_message('info', "File comune caricato con successo: {$contentDir}/{$newName}");
                            
                            // Insert into content_files table
                            $data = [
                                'content_id' => $contentId,
                                'metadata_id' => null,
                                'file_type' => $fileKey,
                                'file_url' => $filePath
                            ];
                            
                            $db = \Config\Database::connect();
                            $builder = $db->table('content_files');
                            
                            $sql = $builder->set($data)->getCompiledInsert();
                            $db->query($sql);
                            log_message('info', "Query Eseguita: " . $sql);
                            
                        } catch (\Exception $e) {
                            log_message('error', "Errore dettagliato nel caricamento del file comune {$fileKey}: " . $e->getMessage());
                            log_message('error', "Stack trace: " . $e->getTraceAsString());
                            throw new \Exception("Errore nel caricamento del file comune {$fileKey}: " . $e->getMessage());
                        }
                    } else {
                        log_message('error', "File comune non valido o già spostato: {$fileKey}");
                        throw new \Exception("File comune non valido o già spostato: {$fileKey}");
                    }
                } else {
                    log_message('info', "File comune non fornito: {$fileKey}");
                }
            }
        }
        
        private function createLanguageDirectory($contentDir, $language)
        {
            $languageDir = $contentDir . '/' . $language;
            if (!is_dir($languageDir)) {
                mkdir($languageDir, 0755, true);
            }
            return $languageDir;
        }
        
    
        
        private function removeDirectory($dir)
        {
            if (is_dir($dir)) {
                $objects = scandir($dir);
                foreach ($objects as $object) {
                    if ($object != "." && $object != "..") {
                        if (is_dir($dir . "/" . $object)) {
                            $this->removeDirectory($dir . "/" . $object);
                        } else {
                            unlink($dir . "/" . $object);
                        }
                    }
                }
                rmdir($dir);
            }
        }
        
        public function resetContentCounter()
        {
            $db = Database::connect();
            
            try {
                $db->transStart();
                
                $maxId = $db->table('content')->selectMax('id')->get()->getRow()->id ?? 0;
                
                $db->query("ALTER TABLE content AUTO_INCREMENT = " . ($maxId + 1));
                
                $db->transComplete();
                
                if ($db->transStatus() === false) {
                    throw new DatabaseException('Errore durante il reset del contatore');
                }
                
                return $this->response->setJSON([
                    'success' => true,
                    'message' => 'Contatore della tabella content resettato con successo',
                    'next_id' => $maxId + 1
                ]);
                
            } catch (Exception $e) {
                log_message('error', 'Errore nel reset del contatore: ' . $e->getMessage());
                
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Si è verificato un errore durante il reset del contatore: ' . $e->getMessage()
                ])->setStatusCode(500);
            }
        }
        
        public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
        {
            parent::initController($request, $response, $logger);
            // Inizializzazione del controller
        }
    }