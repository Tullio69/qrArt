<?php
    namespace App\Filters;
    
    use CodeIgniter\HTTP\RequestInterface;
    use CodeIgniter\HTTP\ResponseInterface;
    use CodeIgniter\Filters\FilterInterface;
    
    class MobileOnly implements FilterInterface
    {
        public function before(RequestInterface $request, $arguments = null)
        {
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';

            // Se l'utente ha force-ws05, bypassa il controllo mobile (è un admin)
            if (strpos($userAgent, 'force-ws05') !== false) {
                return;
            }

            $isMobile = preg_match('/Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i', $userAgent);

            $currentPath = trim($request->getUri()->getPath(), '/'); // rimuove eventuali slash

            // Escludi le route API dal controllo mobile
            if (strpos($currentPath, 'api/') === 0) {
                return;
            }

            // Se NON è mobile e NON è in home, allora reindirizza
            if (!$isMobile && $currentPath !== '') {
                return redirect()->to('/');
            }

            // Altrimenti lascia proseguire
        }
        
        public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
        {
            // Nessuna azione necessaria
        }
    }
