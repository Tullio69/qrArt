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
        
        public function processForm()
        {
            // 1. Ricezione dei dati
            $formData = $this->request->getJSON(true);
            
            // 2. Validazione dei dati
            if (!$this->validateData($formData)) {
                return $this->response->setJSON(['success' => false, 'message' => 'Dati non validi'])->setStatusCode(400);
            }
            
            // 3. Elaborazione e controllo dei dati
            $processedData = $this->processData($formData);
            
            // 4. Scrittura dei file caricati
            $fileUploadResult = $this->handleFileUploads($formData);
            if (!$fileUploadResult['success']) {
                return $this->response->setJSON(['success' => false, 'message' => $fileUploadResult['message']])->setStatusCode(500);
            }
            
            // 5. Scrittura dei dati nel database
            $dbWriteResult = $this->writeToDatabase($processedData);
            if (!$dbWriteResult['success']) {
                return $this->response->setJSON(['success' => false, 'message' => $dbWriteResult['message']])->setStatusCode(500);
            }
            
            // 6. Risposta di successo
            return $this->response->setJSON(['success' => true, 'message' => 'Procedura completata con successo']);
        }
        
        private function validateData($data)
        {
            // Implementa qui la logica di validazione
            return true; // Placeholder
        }
        
        private function processData($data)
        {
            // Implementa qui la logica di elaborazione
            return $data; // Placeholder
        }
        
        private function handleFileUploads($data)
        {
            // Implementa qui la logica per il caricamento dei file
            return ['success' => true]; // Placeholder
        }
        
        private function writeToDatabase($processedData)
        {
            // Inizia una transazione del database
            $this->db->transStart();
            
            try {
                // Inserisci nella tabella content
                $contentModel = new \App\Models\ContentModel();
                $contentId = $contentModel->insert([
                    'caller_name' => $processedData['callerTitle'],
                    'caller_subtitle' => $processedData['callerSubtitle'],
                    'content_type' => $processedData['contentType']
                ]);
                
                $contentFilesModel = new \App\Models\ContentFilesModel();
                $contentMetadataModel = new \App\Models\ContentMetadataModel();
                
                // Scrivi i file comuni
                $commonFilePaths = $this->writeCommonFiles($contentId, $processedData);
                
                foreach ($processedData['languageVariants'] as $variant) {
                    // Scrivi i file specifici per la lingua
                    $filePath = $this->writeFiles($contentId, $variant, $processedData['contentType']);
                    
                    // Inserisci nella tabella content_files
                    $contentFilesModel->insert([
                        'content_id' => $contentId,
                        'language' => $variant['language'],
                        'file_path' => $filePath,
                        'phone_background' => $commonFilePaths['background'],
                        'caller_avatar' => $commonFilePaths['avatar']
                    ]);
                    
                    // Inserisci nella tabella content_metadata
                    $contentMetadataModel->insert([
                        'content_id' => $contentId,
                        'language' => $variant['language'],
                        'text_only' => $variant['textOnly'],
                        'description' => $variant['description']
                    ]);
                }
                
                // Completa la transazione
                $this->db->transComplete();
                
                if ($this->db->transStatus() === false) {
                    // Se la transazione fallisce, genera un'eccezione
                    throw new \Exception('Errore durante il salvataggio dei dati e dei file');
                }
                
                return ['success' => true];
            } catch (\Exception $e) {
                // In caso di errore, annulla la transazione
                $this->db->transRollback();
                // Elimina eventuali file scritti
                $this->cleanupFiles($contentId);
                return ['success' => false, 'message' => $e->getMessage()];
            }
        }
        
        private function writeCommonFiles($contentId, $processedData)
        {
            $baseDir = WRITEPATH . 'uploads/' . $contentId;
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