<?php

namespace App\Filters;

use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Filters\FilterInterface;

class AdminOnly implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';

        // Verifica se lo user-agent contiene 'force-ws05'
        $isAllowed = (strpos($userAgent, 'force-ws05') !== false);

        if (!$isAllowed) {
            // Redirect alla homepage se non autorizzato
            log_message('warning', 'Tentativo di accesso non autorizzato a: ' . $request->getUri()->getPath() . ' - User Agent: ' . $userAgent);
            return redirect()->to('/');
        }

        // Altrimenti lascia proseguire
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Nessuna azione necessaria
    }
}
