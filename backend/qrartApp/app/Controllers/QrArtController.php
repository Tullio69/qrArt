<?php
    
    namespace App\Controllers;
    
    use CodeIgniter\Controller;
    use CodeIgniter\HTTP\RequestInterface;
    use CodeIgniter\HTTP\ResponseInterface;
    use Psr\Log\LoggerInterface;
    
    class QrArtController extends BaseController
    {
        public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
        {
            parent::initController($request, $response, $logger);
            // Inizializzazione del controller
        }
        
        public function processQrArtContent()
        {
            // Inizia la transazione del database
            $db = \Config\Database::connect();
            $db->transStart();
            
            try {
                // 1. Ricezione dei dati del form e dei file
                $formData = $this->request->getPost();
                $files = $this->request->getFiles();
                log_message('debug', 'Dati del form: ' . print_r($formData, true));
                log_message('debug', 'File ricevuti: ' . print_r($files, true));
                // 2. Validazione dei dati del form
               /* if (!$this->validateFormData($formData)) {
                    throw new \Exception('Errore di validazione dei dati del form');
                }*/
                
                // 3. Elaborazione e controllo dei dati
                $processedData = $this->processData($formData);
                
                // 4. Creazione del nuovo content nel database
                $contentModel = new \App\Models\ContentModel();
                $contentId = $contentModel->insert([
                    'caller_name' => $processedData['callerName'],
                    'caller_title' => $processedData['callerTitle'],
                    'content_name' => $processedData['contentName'],
                    'content_type' => $processedData['contentType'],
                    // Aggiungi altri campi necessari per il content
                ]);
                
                if (!$contentId) {
                    throw new \Exception('Errore nella creazione del nuovo content');
                }
                
                // 5. Creazione della directory per il content
                $contentDir = FCPATH . 'media/' . $contentId;
                
                if (!mkdir($contentDir, 0755, true)) {
                    throw new \Exception('Errore nella creazione della directory del content');
                }
                
                // 6. Gestione del caricamento dei file e salvataggio dei dati per ogni variante linguistica
                $results = [];
                foreach ($processedData['languageVariants'] as $index => $variant) {
                    $variantFiles = $this->extractVariantFiles($files, $index);
                    $fileUploadResult = $this->handleFileUploads($variantFiles, $variant, $contentDir);
                    
                    if (!$fileUploadResult['success']) {
                        throw new \Exception("Errore nel caricamento dei file per la lingua {$variant['language']}: " . $fileUploadResult['message']);
                    }
                    
                    $saveResult = $this->saveVariantData($contentId, $variant, $fileUploadResult['files']);
                    
                    if (!$saveResult['success']) {
                        throw new \Exception("Errore nel salvataggio dei dati per la lingua {$variant['language']}: " . $saveResult['message']);
                    }
                    
                    $results[$variant['language']] = [
                        'success' => true,
                        'message' => "Dati salvati con successo per la lingua {$variant['language']}",
                        'variantId' => $saveResult['variantId']
                    ];
                }
                
                // Se siamo arrivati qui, tutto è andato bene. Confermiamo la transazione.
                $db->transCommit();
                
                return $this->response->setJSON([
                    'success' => true,
                    'message' => 'Tutte le varianti linguistiche sono state elaborate con successo',
                    'contentId' => $contentId,
                    'results' => $results
                ])->setStatusCode(200);
                
            } catch (\Exception $e) {
                // In caso di errore, annulliamo la transazione e eliminiamo la directory del content se è stata creata
                $db->transRollback();
                if (isset($contentDir) && is_dir($contentDir)) {
                    $this->removeDirectory($contentDir);
                }
                
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Si è verificato un errore durante l\'elaborazione: ' . $e->getMessage()
        ])->setStatusCode(500);
    }
}

// Funzione helper per rimuovere una directory e il suo contenuto
private function removeDirectory($dir) {
    if (is_dir($dir)) {
        $objects = scandir($dir);
        foreach ($objects as $object) {
            if ($object != "." && $object != "..") {
                if (is_dir($dir. DIRECTORY_SEPARATOR .$object) && !is_link($dir."/".$object))
                    $this->removeDirectory($dir. DIRECTORY_SEPARATOR .$object);
                else
                    unlink($dir. DIRECTORY_SEPARATOR .$object);
            }
        }
        rmdir($dir);
    }
}
        
        private function handleFileUploads($files, &$variant, $contentId)
        {
            $logger = service('logger');
            $logger->info("Inizio handleFileUploads per la variante {$variant['language']} del content {$contentId}");
            
            $uploadPath = WRITEPATH . 'media/' . $contentId . '/';
            if (!is_dir($uploadPath)) {
                if (!mkdir($uploadPath, 0755, true)) {
                    $logger->error("Impossibile creare la directory: {$uploadPath}");
                    return ['success' => false, 'message' => "Errore nella creazione della directory per i file"];
                }
            }
            
            $allowedTypes = [
                'audio' => 'audio/mpeg,audio/wav',
                'video' => 'video/mp4,video/mpeg',
                'image' => 'image/jpeg,image/png,image/gif'
            ];
            
            $uploadedFiles = [];
            
            foreach ($files as $fileType => $file) {
                $logger->info("Elaborazione file di tipo {$fileType}");
                
                if ($file instanceof \CodeIgniter\HTTP\Files\UploadedFile && $file->isValid() && !$file->hasMoved()) {
                    $type = strpos($fileType, 'audio') !== false ? 'audio' : (strpos($fileType, 'video') !== false ? 'video' : 'image');
                    if (!in_array($file->getMimeType(), explode(',', $allowedTypes[$type]))) {
                        $logger->error("Tipo di file non valido per {$fileType}: {$file->getMimeType()}");
                        return ['success' => false, 'message' => "Tipo di file non valido per {$fileType}"];
                    }
                    
                    $newName = $file->getRandomName();
                    try {
                        $file->move($uploadPath, $newName);
                        $uploadedFiles[$fileType] = 'media/' . $contentId . '/' . $newName;
                        $logger->info("File {$fileType} caricato con successo: {$uploadedFiles[$fileType]}");
                    } catch (\Exception $e) {
                        $logger->error("Errore nel caricamento del file {$fileType}: " . $e->getMessage());
                        return ['success' => false, 'message' => "Errore nel caricamento del file {$fileType}: " . $e->getMessage()];
                    }
                } else {
                    $errorMessage = $file instanceof \CodeIgniter\HTTP\Files\UploadedFile ? $file->getErrorString() : "File non valido";
                    $logger->error("Errore nel caricamento del file {$fileType}: {$errorMessage}");
                    return ['success' => false, 'message' => "Errore nel caricamento del file {$fileType}: {$errorMessage}"];
                }
            }
            
            $logger->info("Fine handleFileUploads, files caricati: " . json_encode($uploadedFiles));
            return ['success' => true, 'files' => $uploadedFiles];
        }
     
        private function extractVariantFiles($files, $index)
        {
            $variantFiles = [];
            $fileTypes = ['audioFile', 'videoFile', 'callerBackground', 'callerAvatar'];
            
            foreach ($fileTypes as $fileType) {
                $fileKey = "languageVariants.{$index}.{$fileType}";
                if (isset($files[$fileKey])) {
                    $variantFiles[$fileType] = $files[$fileKey];
                }
            }
            
            return $variantFiles;
        }
        
        private function saveVariantData($processedData, $variant, $uploadedFiles)
        {
            // Implementa qui la logica per salvare i dati della variante nel database
            // Questo è un esempio semplificato, adattalo alle tue esigenze specifiche
            print_r($processedData);
            print_r($variant);
            print_r($uploadedFiles);
            try {
                $db = \Config\Database::connect();
                
                $variantData = [
                    'language' => $variant['language'],
                    'text_only' => $variant['textOnly'],
                    'description' => $variant['description'],
                    // Aggiungi qui altri campi necessari
                ];
                
                $db->table('language_variants')->insert($variantData);
                $variantId = $db->insertID();
                
                foreach ($uploadedFiles as $fileType => $filePath) {
                    $fileData = [
                        'variant_id' => $variantId,
                        'file_type' => $fileType,
                        'file_path' => $filePath
                    ];
                    $db->table('variant_files')->insert($fileData);
                }
                
                return ['success' => true, 'variantId' => $variantId];
            } catch (\Exception $e) {
                log_message('error', 'Errore nel salvataggio dei dati della variante: ' . $e->getMessage());
                return ['success' => false, 'message' => $e->getMessage()];
            }
        }
        
        
        private function processData($data)
        {
            // Implementa qui la logica di elaborazione
            return $data; // Placeholder
        }
        
        
       
        private function writeCommonFiles($contentId, $processedData)
        {
            $baseDir = WRITEPATH . 'media/' . $contentId;
            if (!is_dir($baseDir)) {
                mkdir($baseDir, 0777, true);
            }
            
            $backgroundPath = null;
            $avatarPath = null;
            
            if (isset($processedData['callerBackground'])) {
                $backgroundPath = $baseDir . '/caller_background.jpg';
                move_uploaded_file($processedData['callerBackground'], $backgroundPath);
            }
            
            if (isset($processedData['callerAvatar'])) {
                $avatarPath = $baseDir . '/caller_avatar.jpg';
                move_uploaded_file($processedData['callerAvatar'], $avatarPath);
            }
            
            return [
                'background' => $backgroundPath,
                'avatar' => $avatarPath
            ];
        }
        
        private function writeFiles($contentId, $variant, $contentType)
        {
            $baseDir = WRITEPATH . 'uploads/' . $contentId . '/' . $variant['language'];
            if (!is_dir($baseDir)) {
                mkdir($baseDir, 0777, true);
            }
            
            $filePath = null;
            
            if ($variant['textOnly']) {
                // Se è solo testo, salva il contenuto in un file HTML
                $filePath = $baseDir . '/content.html';
                file_put_contents($filePath, $variant['description']);
            } else {
                // Altrimenti, sposta il file audio o video
                $extension = ($contentType === 'audio_call' || $contentType === 'audio') ? '.mp3' : '.mp4';
                $filePath = $baseDir . '/audio' . $extension;
                $sourceFile = $variant['audioFile'] ?? $variant['videoFile'];
                move_uploaded_file($sourceFile, $filePath);
            }
            
            return $filePath;
        }
        
        private function cleanupFiles($contentId)
        {
            $baseDir = WRITEPATH . 'uploads/' . $contentId;
            if (is_dir($baseDir)) {
                $this->rrmdir($baseDir);
            }
        }
        
        private function rrmdir($dir)
        {
            if (is_dir($dir)) {
                $objects = scandir($dir);
                foreach ($objects as $object) {
                    if ($object != "." && $object != "..") {
                        if (is_dir($dir . "/" . $object)) {
                            $this->rrmdir($dir . "/" . $object);
                        } else {
                            unlink($dir . "/" . $object);
                        }
                    }
                }
                rmdir($dir);
            }
        }
        
    }