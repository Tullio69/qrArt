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
            echo "<pre>DEBUG: Inizio processQrArtContent()</pre>";
            
            // 1. Ricezione dei dati del form e dei file
            $formData = $this->request->getPost();
            $files = $this->request->getFiles();
            
            echo "<pre>DEBUG: Dati del form ricevuti: " . print_r($formData, true) . "</pre>";
            echo "<pre>DEBUG: File ricevuti: " . print_r($files, true) . "</pre>";
           
            // 2. Validazione dei dati del form
          
            
            echo "<pre>DEBUG: Validazione dei dati del form completata con successo</pre>";
            
            // 3. Elaborazione e controllo dei dati
            $processedData = $this->processData($formData);
            echo "<pre>DEBUG: Dati elaborati: " . print_r($processedData, true) . "</pre>";
            
            // 4. Gestione del caricamento dei file e salvataggio dei dati per ogni variante linguistica
            $results = [];
            foreach ($processedData['languageVariants'] as $index => $variant) {
                echo "<pre>DEBUG: Elaborazione della variante linguistica {$variant['language']}</pre>";
                
                // 4.1 Gestione del caricamento dei file per la variante corrente
                $variantFiles = $this->extractVariantFiles($files, $index);
                echo "<pre>DEBUG: File estratti per la variante {$variant['language']}: " . print_r($variantFiles, true) . "</pre>";
                
                $fileUploadResult = $this->handleFileUploads($variantFiles, $variant);
                echo "<pre>DEBUG: Risultato caricamento file per {$variant['language']}: " . print_r($fileUploadResult, true) . "</pre>";
                
                if (!$fileUploadResult['success']) {
                    $results[$variant['language']] = [
                        'success' => false,
                        'message' => "Errore nel caricamento dei file per la lingua {$variant['language']}: " . $fileUploadResult['message']
                    ];
                    echo "<pre>DEBUG: Errore nel caricamento dei file per {$variant['language']}</pre>";
                    continue;
                }
                
                // 4.2 Salvataggio dei dati per la variante corrente
                $saveResult = $this->saveVariantData($processedData, $variant, $fileUploadResult['files']);
                echo "<pre>DEBUG: Risultato salvataggio dati per {$variant['language']}: " . print_r($saveResult, true) . "</pre>";
                
                if (!$saveResult['success']) {
                    $results[$variant['language']] = [
                        'success' => false,
                        'message' => "Errore nel salvataggio dei dati per la lingua {$variant['language']}: " . $saveResult['message']
                    ];
                } else {
                    $results[$variant['language']] = [
                        'success' => true,
                        'message' => "Dati salvati con successo per la lingua {$variant['language']}",
                        'variantId' => $saveResult['variantId']
                    ];
                }
            }
            
            // 5. Verifica dei risultati e preparazione della risposta
            $allSuccess = !in_array(false, array_column($results, 'success'));
            $responseMessage = $allSuccess ? 'Tutte le varianti linguistiche sono state elaborate con successo' : 'Alcune varianti linguistiche hanno riscontrato errori';
            
            echo "<pre>DEBUG: Risultati finali: " . print_r($results, true) . "</pre>";
            echo "<pre>DEBUG: Tutti i processi completati con successo? " . ($allSuccess ? 'Sì' : 'No') . "</pre>";
            
            // 6. Restituzione della risposta
            echo "<pre>DEBUG: Fine processQrArtContent()</pre>";
            return $this->response->setJSON([
                'success' => $allSuccess,
                'message' => $responseMessage,
                'results' => $results
            ])->setStatusCode($allSuccess ? 200 : 500);
        }
        
        private function handleFileUploads($files, &$variant)
        {
            echo "<pre>DEBUG: Inizio handleFileUploads per la variante {$variant['language']}</pre>";
            
            $uploadPath = WRITEPATH . 'uploads/';
            $allowedTypes = [
                'audio' => 'audio/mpeg,audio/wav',
                'video' => 'video/mp4,video/mpeg',
                'image' => 'image/jpeg,image/png,image/gif'
            ];
            
            $uploadedFiles = [];
            
            foreach ($files as $fileType => $file) {
                echo "<pre>DEBUG: Elaborazione file di tipo {$fileType}</pre>";
                
                if ($file instanceof UploadedFile && $file->isValid() && !$file->hasMoved()) {
                    $type = strpos($fileType, 'audio') !== false ? 'audio' : (strpos($fileType, 'video') !== false ? 'video' : 'image');
                    if (!in_array($file->getMimeType(), explode(',', $allowedTypes[$type]))) {
                        echo "<pre>DEBUG: Tipo di file non valido per {$fileType}</pre>";
                        return ['success' => false, 'message' => "Tipo di file non valido per {$fileType}"];
                    }
                    
                    $newName = $file->getRandomName();
                    $file->move($uploadPath, $newName);
                    $uploadedFiles[$fileType] = $uploadPath . $newName;
                    
                    echo "<pre>DEBUG: File {$fileType} caricato con successo: {$uploadedFiles[$fileType]}</pre>";
                } else {
                    echo "<pre>DEBUG: Errore nel caricamento del file {$fileType}</pre>";
                    return ['success' => false, 'message' => "Errore nel caricamento del file {$fileType}: " . $file->getErrorString()];
                }
            }
            
            echo "<pre>DEBUG: Fine handleFileUploads, files caricati: " . print_r($uploadedFiles, true) . "</pre>";
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