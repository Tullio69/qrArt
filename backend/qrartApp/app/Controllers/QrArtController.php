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
                // Ricezione dei dati del form e dei file
                $formData = $this->request->getPost();
                $files = $this->request->getFiles();
                
                // Validazione dei dati del form
               /* if (!$this->validateFormData($formData)) {
                    throw new \Exception('Errore di validazione dei dati del form');
                }*/
                
                // Creazione del nuovo content nel database
                $contentModel = new ContentModel();
                $contentData = [
                    'caller_name' => $formData['callerName'],
                    'caller_title' => $formData['callerTitle'],
                    'content_name' => $formData['contentName'],
                    'content_type' => $formData['contentType'],
                ];
                $contentId = $contentModel->insert($contentData);
                
                if (!$contentId) {
                    throw new \Exception('Errore nella creazione del nuovo content');
                }
                
                log_message('info', 'Query di inserimento content: ' . $db->getLastQuery());
                
                // Gestione del caricamento dei file e salvataggio dei dati per ogni variante linguistica
                $contentMetadataModel = new ContentMetadataModel();
                foreach ($formData['languageVariants'] as $variant) {
                    $metadataData = [
                        'content_id' => $contentId,
                        'language' => $variant['language'],
                        'text_only' => $variant['textOnly']=='true' ? 1 : 0,
                        'description' => $variant['description'] ?? null,
                    ];
                    $metadataId = $contentMetadataModel->insert($metadataData);
                    
                    if (!$metadataId) {
                        throw new \Exception('Errore nel salvataggio dei metadati della variante linguistica');
                    }
                    
                    log_message('info', 'Query di inserimento metadata: ' . $db->getLastQuery());
                    
                    // Gestione del caricamento dei file
                    if ($variant['textOnly'] == '0') {
                        $uploadedFiles = $this->handleFileUploads($files, $variant, $contentId);
                        if (!$uploadedFiles['success']) {
                            throw new \Exception($uploadedFiles['message']);
                        }
                        
                        // Qui puoi aggiungere la logica per salvare le informazioni sui file caricati
                    }
                }
                
                // Se siamo arrivati qui, tutto è andato bene. Confermiamo la transazione.
                $db->transCommit();
                
                return $this->response->setJSON([
                    'success' => true,
                    'message' => 'Contenuto creato con successo',
                    'contentId' => $contentId
                ]);
                
            } catch (\Exception $e) {
                // In caso di errore, annulliamo la transazione
                $db->transRollback();
                
                log_message('error', 'Errore in processQrArtContent: ' . $e->getMessage());
                
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Si è verificato un errore durante l\'elaborazione: ' . $e->getMessage()
                ])->setStatusCode(500);
            }
        }
        private function removeDirectory($dir)
        {
            if (is_dir($dir)) {
                $objects = scandir($dir);
                foreach ($objects as $object) {
                    if ($object != "." && $object != "..") {
                        if (is_dir($dir . "/" . $object))
                            $this->removeDirectory($dir . "/" . $object);
                        else
                            unlink($dir . "/" . $object);
                    }
                }
                rmdir($dir);
            }
        }
        
        private function createContentDirectory($contentId)
        {
            $contentDir = WRITEPATH . 'media/' . $contentId;
            if (!mkdir($contentDir, 0755, true)) {
                throw new \Exception('Errore nella creazione della directory del content');
            }
            return $contentDir;
        }
        
        private function processLanguageVariants($variants, $files, $contentId, $contentDir)
        {
            $processedVariants = [];
            foreach ($variants as $index => $variant) {
                if ($variant['textOnly']) {
                    $processedVariants[$index] = $this->processTextOnlyVariant($variant);
                } else {
                    $processedVariants[$index] = $this->processFileVariant($variant, $files['languageVariants'][$index] ?? [], $contentId, $contentDir);
                }
            }
            return $processedVariants;
        }
        
        private function processTextOnlyVariant($variant)
        {
            return [
                'language' => $variant['language'],
                'textOnly' => true,
                'description' => $variant['description']
            ];
        }
        
        private function processFileVariant($variant, $files, $contentId, $contentDir)
        {
            $processedFiles = [];
            foreach ($files as $fileType => $file) {
                $uploadResult = $this->uploadFile($file, $fileType, $contentDir);
                if ($uploadResult['success']) {
                    $processedFiles[$fileType] = $uploadResult['path'];
                } else {
                    throw new \Exception($uploadResult['message']);
                }
            }
            return [
                'language' => $variant['language'],
                'textOnly' => false,
                'files' => $processedFiles
            ];
        }
        
        private function uploadFile($file, $fileType, $uploadPath)
        {
            if ($file->isValid() && !$file->hasMoved()) {
                $newName = $file->getRandomName();
                $file->move($uploadPath, $newName);
                return [
                    'success' => true,
                    'path' => 'media/' . basename($uploadPath) . '/' . $newName
                ];
            }
            return [
                'success' => false,
                'message' => 'Errore nel caricamento del file ' . $fileType
            ];
        }
        
        private function saveVariantsData($contentId, $processedVariants)
        {
            $variantModel = new \App\Models\VariantModel();
            foreach ($processedVariants as $variant) {
                $variantModel->insert([
                    'content_id' => $contentId,
                    'language' => $variant['language'],
                    'text_only' => $variant['textOnly']== "true" ? 1 : 0 ,
                    'description' => $variant['textOnly'] ? $variant['description'] : null,
                    'files' => $variant['textOnly'] ? null : json_encode($variant['files'])
                ]);
            }
        }
        
      

    private function validateFormData($formData)
    {
        $validation = \Config\Services::validation();
        
        $rules = [
            `callerName` => `required|max_length[255]`,
            `callerTitle` => `required|max_length[255]`,
            `contentName` => `required|max_length[255]`,
            `contentType` => `required|in_list[audio,video,audio_call,video_call]`,
            `languageVariants` => `required|is_array`,
            `languageVariants.*.language` => `required|alpha|max_length[5]`,
            `languageVariants.*.textOnly` => `required|in_list[0,1]`,
            `languageVariants.*.description` => `permit_empty|string`
        ];
        
        if (!$validation->setRules($rules)->run($formData)) {
            log_message('error', 'Errori di validazione: ' . print_r($validation->getErrors(), true));
            return false;
        }
        
        return true;
    }

    private function handleFileUploads($files, $variant, $contentId)
    {
        // Implementa qui la logica per il caricamento dei file
        // Questa è una versione semplificata, assicurati di gestire correttamente i file e gli errori
        $uploadedFiles = [];
        $uploadPath = WRITEPATH . 'uploads/' . $contentId . '/';

        if (!is_dir($uploadPath)) {
            mkdir($uploadPath, 0755, true);
        }

        foreach ($files as $file) {
            if ($file->isValid() && !$file->hasMoved()) {
                $newName = $file->getRandomName();
                $file->move($uploadPath, $newName);
                $uploadedFiles[] = $uploadPath . $newName;
            }
        }

        return ['success' => true, 'files' => $uploadedFiles];
    }
        
    }