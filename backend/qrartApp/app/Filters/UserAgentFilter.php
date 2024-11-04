<?php
    
    namespace Filters;
    
    class UserAgentFilter
    {
    
    }
    
    namespace App\Filters;
    
    use CodeIgniter\HTTP\RequestInterface;
    use CodeIgniter\HTTP\ResponseInterface;
    use CodeIgniter\Filters\FilterInterface;
    
    class UserAgentFilter implements FilterInterface
    {
        public function before(RequestInterface $request, $arguments = null)
        {
            $userAgent = $request->getUserAgent();
            $allowedUserAgent = 'Your-Development-User-Agent'; // Sostituisci con il tuo user-agent di sviluppo
            
            if ($userAgent->getAgentString() !== $allowedUserAgent) {
                return redirect()->to('/wip');
            }
        }
        
        public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
        {
            // Do nothing
        }
    }