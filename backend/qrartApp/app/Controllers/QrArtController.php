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
            
            try {
                $formData = $this->request->getPost();
                $files = $this->request->getFiles();
                
                log_message('info', 'Received form data: ' . print_r($formData, true));
                log_message('info', 'Received files: ' . print_r($files, true));
                
                $contentModel = new ContentModel();
                $contentData = [
                    'caller_name' => $formData['callerName'],
                    'caller_title' => $formData['callerTitle'],
                    'content_name' => $formData['contentName'],
                    'content_type' => $formData['contentType'],
                ];
                $contentId = $contentModel->insert($contentData);
                log_message('info', 'Query di inserimento content: ' . $db->getLastQuery());
                
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
                    
                  
                 /*   if (!$uploadedFiles['success']) {
                        throw new \Exception($uploadedFiles['message']);
                    }*/
                    
                   
                }
                
                #$this->handleCommonFiles($files, $contentDir);
                
               /* $db->transCommit();
                
                return $this->response->setJSON([
                    'success' => true,
                    'message' => 'Contenuto creato con successo',
                    'contentId' => $contentId
                ]);*/
                
            } catch (\Exception $e) {
                
               
               
                $db->transRollback();
                
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
        
        private function handleFileUploads($files, $variant, $contentId,  $languageDir, $contentType, $variantIndex)
        {
     
// Otteniamo l'oggetto file dalla variabile
            $file = $files['languageVariants'][0]['audioFile'];

// Verifichiamo se il file è un'istanza valida di UploadedFile
            if ($file instanceof \CodeIgniter\HTTP\Files\UploadedFile) {
                // Verifichiamo se il file è valido e non è stato già spostato
                if ($file->isValid() && !$file->hasMoved()) {
                    // Definiamo la directory di destinazione
                    $destinationPath = $languageDir;
                    
                    // Generiamo un nome per il file
                    $newName = 'audio.' . $file->getExtension();
                    
                    try {
                        // Spostiamo il file nella directory di destinazione
                        $file->move($destinationPath, $newName);
                        
                        if ($file->hasMoved()) {
                            $risultato = [
                                'success' => true,
                                'message' => 'File audio salvato con successo',
                                'filePath' => $contentId . '/' . $languageDir . '/' . $newName,
                                'originalName' => $file->getClientName(),
                                'newName' => $newName
                            ];
                        } else {
                            $risultato = [
                                'success' => false,
                                'message' => 'Impossibile spostare il file audio'
                            ];
                        }
                    } catch (\Exception $e) {
                        $risultato = [
                            'success' => false,
                            'message' => 'Errore durante il salvataggio del file audio: ' . $e->getMessage()
                        ];
                    }
                } else {
                    $risultato = [
                        'success' => false,
                        'message' => 'File audio non valido o già spostato'
                    ];
                }
            } else {
                $risultato = [
                    'success' => false,
                    'message' => 'Il file fornito non è un\'istanza valida di UploadedFile'
                ];
            }

// Puoi utilizzare $risultato come necessario, ad esempio:
// return $this->response->setJSON($risultato);
        }
        private function handleCommonFiles($files, $contentDir)
        {
            $commonFiles = ['callerBackground', 'callerAvatar'];
            foreach ($commonFiles as $fileKey) {
                if (isset($files[$fileKey]) && $files[$fileKey] instanceof \CodeIgniter\Files\UploadedFile) {
                    $file = $files[$fileKey];
                    if ($file->isValid() && !$file->hasMoved()) {
                        $newName = $fileKey . '.' . $file->getExtension();
                        $file->move($contentDir, $newName);
                        log_message('info', "File comune caricato con successo: {$contentDir}/{$newName}");
                    } else {
                        log_message('error', "Errore nel caricamento del file comune: {$fileKey}");
                    }
                } else {
                    log_message('warning', "File comune non trovato: {$fileKey}");
                }
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