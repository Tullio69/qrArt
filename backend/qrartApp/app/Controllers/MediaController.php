<?php
    
    namespace App\Controllers;
    
    use CodeIgniter\Controller;
    
    class MediaController extends Controller
    {
        public function serveAudio(...$segments)
        {
            // Ricomponiamo il percorso del file
            $filename = implode('/', $segments);
            
            // Costruiamo il percorso completo del file
            $path = WRITEPATH . 'media/' . $filename;
            
            // Verifichiamo che il file esista e sia all'interno della directory consentita
            $realPath = realpath($path);
            $mediaPath = realpath(WRITEPATH . 'media/');
            
            if ($realPath === false || strpos($realPath, $mediaPath) !== 0) {
                log_message('error', "Tentativo di accesso non autorizzato o file non trovato: {$path}");
                return $this->response->setStatusCode(404, 'File not found');
            }
            
            if (!is_readable($realPath)) {
                log_message('error', "File non leggibile: {$realPath}");
                return $this->response->setStatusCode(403, 'File not readable');
            }
            
            $mime = mime_content_type($realPath);
            $size = filesize($realPath);
            
            $this->response->setHeader('Content-Type', $mime);
            $this->response->setHeader('Content-Length', $size);
            $this->response->setHeader('Accept-Ranges', 'bytes');
            
            // Gestione delle richieste di range per lo streaming
            $range = $this->request->getHeaderLine('Range');
            if (!empty($range)) {
                $range = str_replace('bytes=', '', $range);
                $range = explode('-', $range);
                $start = intval($range[0]);
                $end = (isset($range[1]) && is_numeric($range[1])) ? intval($range[1]) : $size - 1;
                
                $this->response->setStatusCode(206);
                $this->response->setHeader('Content-Range', "bytes {$start}-{$end}/{$size}");
                $this->response->setHeader('Content-Length', $end - $start + 1);
                
                $handle = fopen($realPath, 'rb');
                fseek($handle, $start);
                $buffer = 1024 * 8;
                while (!feof($handle) && ($pos = ftell($handle)) <= $end) {
                    if ($pos + $buffer > $end) {
                        $buffer = $end - $pos + 1;
                    }
                    echo fread($handle, $buffer);
                    flush();
                }
                fclose($handle);
                return;
            }
            
            // Se non c'è una richiesta di range, invia l'intero file
            readfile($realPath);
        }
        
        public function testFileExistence()
        {
            $filename = '23/it/23_it_audio.mp3';
            $path = WRITEPATH . 'media/' . $filename;
            
            $result = [
                'filename' => $filename,
                'full_path' => $path,
                'exists' => file_exists($path),
                'is_readable' => is_readable($path),
                'file_size' => file_exists($path) ? filesize($path) : 'N/A',
                'mime_type' => file_exists($path) ? mime_content_type($path) : 'N/A'
            ];
            
            log_message('info', 'Test file existence: ' . json_encode($result));
            
            return $this->response->setJSON($result);
        }
    }

