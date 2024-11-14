<?php
    
    namespace App\Controllers;
    
    use CodeIgniter\Controller;
    use CodeIgniter\HTTP\RequestInterface;
    use CodeIgniter\HTTP\ResponseInterface;
    use Psr\Log\LoggerInterface;
    use App\Models\ContentModel;
    use App\Models\ContentMetadataModel;
    use App\Models\ContentFilesModel;
    class QrArtController extends BaseController
    {
        public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
        {
            parent::initController($request, $response, $logger);
            // Inizializzazione del controller
        }
        
        public function processQrArtContent()
        {
            $db = \Config\Database::connect();
            $db->transStart();
            $contentDir = null;  // Dichiariamo contentDir fuori dal blocco try
            
            try {
                $formData = $this->request->getPost();
                $files = $this->request->getFiles();
                
                $contentModel = new ContentModel();
                $contentData = [
                    'caller_name' => $formData['callerName'],
                    'caller_title' => $formData['callerTitle'],
                    'content_name' => $formData['contentName'],
                    'content_type' => $formData['contentType'],
                ];
                $contentId = $contentModel->insert($contentData);
                
                $contentDir = $this->createContentDirectory($contentId);
                
                if (!$contentId) {
                    throw new \Exception('Errore nella creazione del nuovo content');
                }
                
                foreach ($formData['languageVariants'] as $index => $variant) {
                    $metadataData = [
                        'content_id' => $contentId,
                        'language' => $variant['language'],
                        'content_name' => $variant['contentName'],
                        'text_only' => $variant['textOnly'] === 'true' ? 1 : 0,
                        'description' => $variant['description'] ?? null,
                    ];
                    $contentMetadataModel = new ContentMetadataModel();
                    $metadataId = $contentMetadataModel->insert($metadataData);
                    
                    if (!$metadataId) {
                        throw new \Exception('Errore nel salvataggio dei metadati della variante linguistica');
                    }
                    
                    
                    $languageDir = $this->createLanguageDirectory($contentDir, $variant['language']);
                   
                    $uploadedFiles = $this->handleFileUploads($files, $variant, $contentId, $languageDir, $formData['contentType'], $index);
                    log_message('info', '66 Dati Variante: ' . print_r($files['languageVariants'],true));
                  
                 /*   if (!$uploadedFiles['success']) {
                        throw new \Exception($uploadedFiles['message']);
                    }*/
                    
                   
                }
                #throw new \Exception('Errore forzato per il test');
                $this->handleCommonFiles($files, $contentDir,$contentId);
                
                $db->transCommit();
                
                return $this->response->setJSON([
                    'success' => true,
                    'message' => 'Contenuto creato con successo',
                    'contentId' => $contentId
                ]);
                
            } catch (\Exception $e) {
                
                // Elimina la directory creata se esiste
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
        
        private function createContentDirectory($contentId)
        {
            $contentDir = WRITEPATH . 'media/' . $contentId;
            if (!is_dir($contentDir)) {
                mkdir($contentDir, 0755, true);
            }
            return $contentDir;
        }
        
        private function createLanguageDirectory($contentDir, $language)
        {
            $languageDir = $contentDir . '/' . $language;
            if (!is_dir($languageDir)) {
                mkdir($languageDir, 0755, true);
            }
            return $languageDir;
        }
        
        private function handleFileUploads($files, $variant, $contentId, $languageDir, $contentType, $variantIndex)
        {
            $uploadedFiles = [];
          
            if ($variant['textOnly'] === 'false') {
                $fileKey = $contentType === 'audio' || $contentType === 'audio_call' ? 'audioFile' : 'videoFile';
             
                if (isset($files['languageVariants'][$variantIndex][$fileKey]) &&
                    $files['languageVariants'][$variantIndex][$fileKey] instanceof \CodeIgniter\HTTP\Files\UploadedFile) {
                    
                    $file = $files['languageVariants'][$variantIndex][$fileKey];
                    
                    if ($file->isValid() && !$file->hasMoved()) {
                        $destinationPath = $languageDir;
                        
                        $newName = ($contentType === 'audio' || $contentType === 'audio_call' ? 'audio' : 'video') . '.' . $file->getExtension();
                        
                        try {
                            // Genera il nuovo nome del file con contentId_
                            $contentGroup= ($contentType === 'audio_call' ? 'audio' : 'video');
                            $originalName = $file->getClientName();
                            $newName = $contentId . '_' .$variant['language'] . '_' . $contentGroup . '.' . $file->getExtension() ;
                            $file->move($destinationPath, $newName);
                            
                            if ($file->hasMoved()) {
                                $uploadedFiles[] = [
                                    'success' => true,
                                    'message' => 'File salvato con successo',
                                    'filePath' => $contentId . '/' . $languageDir . '/' . $newName,
                                    'originalName' => $file->getClientName(),
                                    'newName' => $newName
                                ];
                            } else {
                                $uploadedFiles[] = [
                                    'success' => false,
                                    'message' => 'Impossibile spostare il file'
                                ];
                            }
                        } catch (\Exception $e) {
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
                } else {
                    $uploadedFiles[] = [
                        'success' => false,
                        'message' => 'File non trovato per la variante linguistica ' . $variantIndex . ', chiave: ' . $fileKey
                    ];
                }
            } else {
                $htmlContent = $variant['description'] ?? '';
                $htmlFilePath = $languageDir . '/text_only_content.html';
                if (file_put_contents($htmlFilePath, $htmlContent) !== false) {
                    $uploadedFiles[] = [
                        'success' => true,
                        'message' => 'Contenuto HTML salvato con successo',
                        'filePath' => $contentId . '/' . $languageDir . '/text_only_content.html',
                        'newName' => 'text_only_content.html'
                    ];
                } else {
                    $uploadedFiles[] = [
                        'success' => false,
                        'message' => 'Impossibile salvare il contenuto HTML'
                    ];
                }
            }
            
            return ['success' => !empty($uploadedFiles), 'files' => $uploadedFiles];
        }
       
        private function handleCommonFiles($files, $contentDir, $contentId)
        {
            $commonFiles = ['callerBackground', 'callerAvatar'];
            foreach ($commonFiles as $fileKey) {
                if (isset($files[$fileKey]) && $files[$fileKey] instanceof \CodeIgniter\HTTP\Files\UploadedFile) {
                    $file = $files[$fileKey];
                    if ($file->isValid() && !$file->hasMoved()) {
                        $newName = $contentId . '_' .$fileKey .".". $file->getExtension();
                        try {
                            $file->move($contentDir, $newName);
                            log_message('info', "File comune caricato con successo: {$contentDir}/{$newName}");
                        } catch (\Exception $e) {
                            log_message('error', "Errore nel caricamento del file comune {$fileKey}: " . $e->getMessage());
                            throw new \Exception("Errore nel caricamento del file comune {$fileKey}");
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
        
        public function resetContentCounter()
        {
            $db = \Config\Database::connect();
            
            try {
                // Inizia una transazione
                $db->transStart();
                
                // Ottieni il valore massimo dell'ID attuale
                $maxId = $db->table('content')->selectMax('id')->get()->getRow()->id ?? 0;
                
                // Resetta l'auto-increment al valore massimo + 1
                $db->query("ALTER TABLE content AUTO_INCREMENT = " . ($maxId + 1));
                
                // Completa la transazione
                $db->transComplete();
                
                if ($db->transStatus() === false) {
                    // Se la transazione è fallita, lancia un'eccezione
                    throw new DatabaseException('Errore durante il reset del contatore');
                }
                
                return $this->response->setJSON([
                    'success' => true,
                    'message' => 'Contatore della tabella content resettato con successo',
                    'next_id' => $maxId + 1
                ]);
                
            } catch (\Exception $e) {
                log_message('error', 'Errore nel reset del contatore: ' . $e->getMessage());
                
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Si è verificato un errore durante il reset del contatore: ' . $e->getMessage()
                ])->setStatusCode(500);
            }
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
        
       
        
    }