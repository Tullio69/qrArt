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
            $isMobile = preg_match('/Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i', $userAgent);
            
            $currentPath = trim($request->getUri()->getPath(), '/'); // rimuove eventuali slash
            
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
