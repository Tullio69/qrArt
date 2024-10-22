<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
    $routes->get('(:any)', 'WipController::index'); // Assumi che 'noauth' sia un tuo filtro, se necessario
    #$routes->get('/', 'Home::index');
    $routes->get('api/content/(:num)', 'ContentController::getContent/$1');
    $routes->post('api/qrart/process', 'QrArtController::processForm');
// Cattura tutte le altre richieste non API
    $routes->get('(:any)', 'AngularController::index'); // Assumi che 'noauth' sia un tuo filtro, se necessario
