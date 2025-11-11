<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */

   /*#$routes->get('/', 'Home::index');
   
// Cattura tutte le altre richieste non API
    $routes->get('(:any)', 'AngularController::index'); // Assumi che 'noauth' sia un tuo filtro, se necessario*/
    $routes->get('test-file', 'MediaController::testFileExistence');

    // Content API - Read
    $routes->get('api/content/details/(:num)', 'ContentController::getDetails/$1');
    $routes->get('api/content/getlist', 'ContentController::list');
    $routes->get(from: 'api/content/(:any)', to: 'ContentController::getContentData/$1');

    // Content API - Create/Update/Delete
    $routes->post('api/qrart/process', 'QrArtController::processQrArtContent');
    $routes->post('api/content/update/(:any)', 'ContentController::updateContent/$1');
    $routes->delete('api/content/delete/(:num)', 'ContentController::deleteContent/$1');

    // Content API - File Management
    $routes->post('api/content/replaceFile', 'ContentController::replaceFile');
    $routes->delete('api/content/file/(:num)', 'ContentController::deleteFile/$1');
    $routes->delete('api/content/metadata/(:num)', 'ContentController::deleteMetadata/$1');

    // Media & Analytics
    $routes->get('media/audio/(:any)', 'MediaController::serveAudio/$1');
    $routes->get('content/html/(:num)/(:alpha)', 'ContentController::getHtmlContent/$1/$2');
    $routes->get('analytics/overview', 'AnalyticsController::overview');
// Rotte per l'applicazione principale
    if (!isAllowedUserAgent()) {
        $routes->get('(:any)', 'WipController::index');
    } else {
        // Rotte specifiche per AngularJS
        
        $routes->get('dashboard', 'AngularController::dashboard');
        $routes->get('profile', 'AngularController::profile');
        
        // Cattura le rimanenti richieste per AngularJS
        $routes->get('(:any)', 'AngularController::index');
    }

// Funzione per verificare l'user agent (considera di spostarla in un helper o in un middleware)
    function isAllowedUserAgent()
    {
        $agent = service('request')->getUserAgent();
        $userAgentString = $agent->getAgentString();
        
        #log_message('debug', 'User Agent String: ' . $userAgentString);
        
        $isAllowed = (strpos($userAgentString, 'force-ws05') !== false);
        
        #log_message('debug', 'Is Allowed: ' . ($isAllowed ? 'true' : 'false'));
        
        return true;#$isAllowed;
    }