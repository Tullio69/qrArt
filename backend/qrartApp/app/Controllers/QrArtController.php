<?php
    
    namespace App\Controllers;
    use App\Models\ShortUrlModel;
    use App\Models\ContentFilesModel;
    use App\Models\ContentMetadataModel;
    use App\Models\ContentModel;
    use App\Models\RelatedArticlesModel;
    use App\Models\SponsorsModel;
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
                $files = $this->request->getFiles();

                // For multipart/form-data, use getVar() directly as getPost() may return empty
                $contentType = $this->request->getHeaderLine('Content-Type');
                $isMultipart = stripos($contentType, 'multipart/form-data') !== false;

                // Get form data using appropriate method
                if ($isMultipart || !empty($files)) {
                    // For multipart requests, use getVar() directly
                    $formData = [
                        'callerName' => $this->request->getVar('callerName'),
                        'callerTitle' => $this->request->getVar('callerTitle'),
                        'contentName' => $this->request->getVar('contentName'),
                        'contentType' => $this->request->getVar('contentType')
                    ];
                } else {
                    // For regular POST, use getPost()
                    $formData = $this->request->getPost();
                }

                // Log received data for debugging
                log_message('debug', 'Content-Type: ' . $contentType);
                log_message('debug', 'Is Multipart: ' . ($isMultipart ? 'yes' : 'no'));
                log_message('debug', 'Form data received: ' . json_encode($formData));
                log_message('debug', 'Files received: ' . json_encode(array_keys($files)));

                // Validate required fields
                $requiredFields = ['callerName', 'callerTitle', 'contentName', 'contentType'];
                $missingFields = [];

                foreach ($requiredFields as $field) {
                    if (!isset($formData[$field]) || $formData[$field] === null || $formData[$field] === '') {
                        $missingFields[] = $field;
                    }
                }

                if (!empty($missingFields)) {
                    throw new Exception('Campi obbligatori mancanti: ' . implode(', ', $missingFields));
                }

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

                // Get language variants - use getVar for multipart requests
                if ($isMultipart || !empty($files)) {
                    $languageVariants = $this->request->getVar('languageVariants');
                } else {
                    $languageVariants = $formData['languageVariants'] ?? $this->request->getVar('languageVariants');
                }

                log_message('debug', 'Language variants received: ' . json_encode($languageVariants));

                if (empty($languageVariants)) {
                    throw new Exception('Nessuna variante linguistica fornita');
                }

                foreach ($languageVariants as $index => $variant) {
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

                // Handle related articles - try both getPost and getVar
                $relatedArticles = $formData['relatedArticles'] ?? $this->request->getVar('relatedArticles') ?? [];
                $this->handleRelatedArticles($relatedArticles, $contentId);

                // Handle sponsors - try both getPost and getVar
                $sponsors = $formData['sponsors'] ?? $this->request->getVar('sponsors') ?? [];
                $this->handleSponsors($sponsors, $files['sponsorImages'] ?? [], $contentId, $contentDir);
                
                // Genera URL breve per il contenuto
                $shortUrlModel = new ShortUrlModel();
                $shortCode = $shortUrlModel->createShortUrl($contentId);
                
                if (!$shortCode) {
                    throw new Exception('Errore nella creazione dello short URL');
                }

                $db->transCommit();
                
                return $this->response->setJSON([
                    'success' => true,
                    'message' => 'Contenuto creato con successo',
                    'contentId' => $contentId,
                    'shortCode' => $shortCode
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
            
            // ====================================================================
            // STEP 1: LOG DETTAGLIATO DELLA STRUTTURA DEI FILE
            // ====================================================================
            log_message('debug', '=== handleFileUploads START ===');
            log_message('debug', 'Parameters:');
            log_message('debug', '  - variantIndex: ' . $variantIndex);
            log_message('debug', '  - contentType: ' . $contentType);
            log_message('debug', '  - contentId: ' . $contentId);
            log_message('debug', '  - metadataId: ' . $metadataId);
            log_message('debug', '  - variant language: ' . ($variant['language'] ?? 'NONE'));
            log_message('debug', '  - languageDir: ' . $languageDir);
            
            // Log la struttura completa di $files
            log_message('debug', 'Files structure top-level keys: ' . json_encode(array_keys($files)));
            
            // Log dettagliato per ogni chiave
            foreach ($files as $topKey => $topValue) {
                if (is_array($topValue)) {
                    log_message('debug', "files['{$topKey}'] is array with " . count($topValue) . " items");
                    
                    // Se è languageVariants, log più dettagliato
                    if ($topKey === 'languageVariants') {
                        log_message('debug', "  languageVariants has indices: " . json_encode(array_keys($topValue)));
                        
                        if (isset($topValue[$variantIndex])) {
                            log_message('debug', "  languageVariants[{$variantIndex}] exists with keys: " . json_encode(array_keys($topValue[$variantIndex])));
                            
                            foreach ($topValue[$variantIndex] as $fileKey => $fileValue) {
                                if ($fileValue instanceof UploadedFile) {
                                    log_message('debug', "    - {$fileKey}: UploadedFile");
                                    log_message('debug', "      * isValid: " . ($fileValue->isValid() ? 'YES' : 'NO'));
                                    log_message('debug', "      * hasMoved: " . ($fileValue->hasMoved() ? 'YES' : 'NO'));
                                    log_message('debug', "      * getName: " . $fileValue->getName());
                                    log_message('debug', "      * getClientName: " . $fileValue->getClientName());
                                    log_message('debug', "      * getSize: " . $fileValue->getSize());
                                    log_message('debug', "      * getExtension: " . $fileValue->getExtension());
                                    log_message('debug', "      * getError: " . $fileValue->getError());
                                } else {
                                    log_message('debug', "    - {$fileKey}: " . gettype($fileValue));
                                }
                            }
                        } else {
                            log_message('debug', "  languageVariants[{$variantIndex}] DOES NOT EXIST!");
                        }
                    }
                } elseif ($topValue instanceof UploadedFile) {
                    log_message('debug', "files['{$topKey}'] is UploadedFile");
                    log_message('debug', "  * isValid: " . ($topValue->isValid() ? 'YES' : 'NO'));
                    log_message('debug', "  * getClientName: " . $topValue->getClientName());
                } else {
                    log_message('debug', "files['{$topKey}'] type: " . gettype($topValue));
                }
            }
            
            // ====================================================================
            // STEP 2: RICERCA FLESSIBILE DEL FILE
            // ====================================================================
            $fileKey = $contentType === 'audio' || $contentType === 'audio_call' ? 'audioFile' : 'videoFile';
            log_message('debug', 'Looking for fileKey: ' . $fileKey);
            
            $foundFile = null;
            $foundLocation = '';
            
            // Strategia 1: Struttura nidificata standard
            if (isset($files['languageVariants'][$variantIndex][$fileKey]) &&
                $files['languageVariants'][$variantIndex][$fileKey] instanceof UploadedFile) {
                $foundFile = $files['languageVariants'][$variantIndex][$fileKey];
                $foundLocation = "files['languageVariants'][{$variantIndex}]['{$fileKey}']";
                log_message('debug', 'Found file using Strategy 1 (nested structure)');
            }
            
            // Strategia 2: Struttura piatta con naming convention
            if (!$foundFile && isset($files[$fileKey]) && $files[$fileKey] instanceof UploadedFile) {
                $foundFile = $files[$fileKey];
                $foundLocation = "files['{$fileKey}']";
                log_message('debug', 'Found file using Strategy 2 (flat structure)');
            }
            
            // Strategia 3: Ricerca con pattern matching (es: audioFile_0, audioFile_1, etc)
            if (!$foundFile) {
                $searchPattern = $fileKey . '_' . $variantIndex;
                if (isset($files[$searchPattern]) && $files[$searchPattern] instanceof UploadedFile) {
                    $foundFile = $files[$searchPattern];
                    $foundLocation = "files['{$searchPattern}']";
                    log_message('debug', 'Found file using Strategy 3 (pattern: ' . $searchPattern . ')');
                }
            }
            
            // Strategia 4: Ricerca per language code
            if (!$foundFile && isset($variant['language'])) {
                $searchPattern = $fileKey . '_' . $variant['language'];
                if (isset($files[$searchPattern]) && $files[$searchPattern] instanceof UploadedFile) {
                    $foundFile = $files[$searchPattern];
                    $foundLocation = "files['{$searchPattern}']";
                    log_message('debug', 'Found file using Strategy 4 (language pattern: ' . $searchPattern . ')');
                }
            }
            
            // ====================================================================
            // STEP 3: PROCESSO IL FILE SE TROVATO
            // ====================================================================
            if ($foundFile) {
                log_message('debug', 'FILE FOUND at: ' . $foundLocation);
                
                if ($foundFile->isValid() && !$foundFile->hasMoved()) {
                    $destinationPath = $languageDir;
                    
                    try {
                        $contentGroup = ($contentType === 'audio' || $contentType === 'audio_call' ? 'audio' : 'video');
                        $newName = $contentId . '_' . $variant['language'] . '_' . $contentGroup . '.' . $foundFile->getExtension();
                        
                        log_message('debug', 'Attempting to move file:');
                        log_message('debug', '  From: ' . $foundFile->getTempName());
                        log_message('debug', '  To: ' . $destinationPath . '/' . $newName);
                        
                        $foundFile->move($destinationPath, $newName);
                        
                        if ($foundFile->hasMoved()) {
                            $filePath = $contentId . '/' . $variant['language'] . '/' . $newName;
                            log_message('debug', 'File moved successfully to: ' . $filePath);
                            
                            $uploadedFiles[] = [
                                'success' => true,
                                'message' => 'File salvato con successo',
                                'filePath' => $filePath,
                                'originalName' => $foundFile->getClientName(),
                                'newName' => $newName
                            ];
                            
                            // Insert into content_files table
                            $insertData = [
                                'content_id' => $contentId,
                                'metadata_id' => $metadataId,
                                'file_type' => $contentGroup,
                                'file_url' => $filePath
                            ];
                            log_message('debug', 'Inserting into content_files: ' . json_encode($insertData));
                            
                            $insertResult = $contentFilesModel->insert($insertData);
                            log_message('debug', 'Insert result: ' . ($insertResult ? 'SUCCESS' : 'FAILED'));
                            
                            if (!$insertResult) {
                                log_message('error', 'Database insert failed. Errors: ' . json_encode($contentFilesModel->errors()));
                            }
                        } else {
                            $errorMsg = 'Impossibile spostare il file';
                            log_message('error', $errorMsg);
                            $uploadedFiles[] = [
                                'success' => false,
                                'message' => $errorMsg
                            ];
                        }
                    } catch (Exception $e) {
                        $errorMsg = 'Errore durante il salvataggio del file: ' . $e->getMessage();
                        log_message('error', $errorMsg);
                        log_message('error', 'Stack trace: ' . $e->getTraceAsString());
                        
                        $uploadedFiles[] = [
                            'success' => false,
                            'message' => $errorMsg
                        ];
                    }
                } else {
                    $reason = !$foundFile->isValid() ? 'File non valido (error code: ' . $foundFile->getError() . ')' : 'File già spostato';
                    log_message('error', $reason);
                    $uploadedFiles[] = [
                        'success' => false,
                        'message' => $reason
                    ];
                }
            } else {
                log_message('error', "FILE NOT FOUND! Searched for: {$fileKey} in variant index {$variantIndex}");
                log_message('error', 'Available files structure: ' . json_encode(array_keys($files)));
            }
            
            // ====================================================================
            // STEP 4: GESTIONE CONTENUTO HTML
            // ====================================================================
            if (isset($variant['htmlContent']) && !empty($variant['htmlContent'])) {
                log_message('debug', 'Processing HTML content for language: ' . $variant['language']);
                
                $htmlContent = $variant['htmlContent'];
                $htmlFileName = $contentId . '_' . $variant['language'] . '_content.html';
                $htmlFilePath = $languageDir . '/' . $htmlFileName;
                
                log_message('debug', 'Writing HTML to: ' . $htmlFilePath);
                
                if (file_put_contents($htmlFilePath, $htmlContent) !== false) {
                    $filePath = $contentId . '/' . $variant['language'] . '/' . $htmlFileName;
                    log_message('debug', 'HTML content saved successfully: ' . $filePath);
                    
                    $uploadedFiles[] = [
                        'success' => true,
                        'message' => 'Contenuto HTML salvato con successo',
                        'filePath' => $filePath,
                        'newName' => $htmlFileName
                    ];
                    
                    // Insert into content_files table
                    $insertData = [
                        'content_id' => $contentId,
                        'metadata_id' => $metadataId,
                        'file_type' => 'html',
                        'file_url' => $filePath
                    ];
                    log_message('debug', 'Inserting HTML into content_files: ' . json_encode($insertData));
                    
                    $insertResult = $contentFilesModel->insert($insertData);
                    log_message('debug', 'HTML insert result: ' . ($insertResult ? 'SUCCESS' : 'FAILED'));
                    
                    if (!$insertResult) {
                        log_message('error', 'HTML database insert failed. Errors: ' . json_encode($contentFilesModel->errors()));
                    }
                } else {
                    $errorMsg = 'Impossibile salvare il contenuto HTML';
                    log_message('error', $errorMsg . ' at path: ' . $htmlFilePath);
                    $uploadedFiles[] = [
                        'success' => false,
                        'message' => $errorMsg
                    ];
                }
            }
            
            log_message('debug', '=== handleFileUploads END ===');
            log_message('debug', 'Total files processed: ' . count($uploadedFiles));
            
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
        
        private function handleRelatedArticles($relatedArticles, $contentId)
        {
            $relatedArticlesModel = new RelatedArticlesModel();
            foreach ($relatedArticles as $article) {
                $relatedArticlesModel->insert([
                    'content_id' => $contentId,
                    'title' => $article['title'],
                    'link' => $article['link']
                ]);
            }
        }
        
        private function handleSponsors($sponsors, $sponsorImages, $contentId, $contentDir)
        {
            $sponsorsModel = new SponsorsModel();
            foreach ($sponsors as $sponsor) {
                $sponsorData = [
                    'content_id' => $contentId,
                    'name' => $sponsor['name'],
                    'link' => $sponsor['link']
                ];
                
                if (isset($sponsorImages[$sponsor['name']]) && $sponsorImages[$sponsor['name']] instanceof UploadedFile) {
                    $file = $sponsorImages[$sponsor['name']];
                    if ($file->isValid() && !$file->hasMoved()) {
                        $newName = $contentId . '_sponsor_' . $sponsor['name'] . '.' . $file->getExtension();
                        $file->move($contentDir, $newName);
                        $sponsorData['image_url'] = 'media/' . $contentId . '/' . $newName;
                    }
                }
                
                $sponsorsModel->insert($sponsorData);
            }
        }
        
        public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
        {
            parent::initController($request, $response, $logger);
            // Inizializzazione del controller
        }
    }