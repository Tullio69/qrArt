<?php
    
    namespace App\Controllers;
    
    use CodeIgniter\Controller;
    
    class ContentController extends Controller
    {
        public function getContent($contentId)
        {
            $db = \Config\Database::connect();
            
            // Recupera il contenuto principale
            $content = $db->table('content')
                ->where('id', $contentId)
                ->get()
                ->getRowArray();
            
            // Recupera i metadati localizzati
            $metadata = $db->table('content_metadata')
                ->where('content_id', $contentId)
                ->get()
                ->getResultArray();
            
            // Recupera i file associati
            $files = $db->table('content_files')
                ->where('content_id', $contentId)
                ->get()
                ->getResultArray();
            
            // Restituisce i dati in formato JSON
            return $this->response->setJSON([
                'content' => $content,
                'metadata' => $metadata,
                'files' => $files
            ]);
        }
    }
