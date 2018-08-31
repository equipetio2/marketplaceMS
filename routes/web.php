<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

    /**
     * Route created to generate a key of application
     */
$router->get('/key', function() {
    return str_random(32);
});

$router->get('/', function () use ($router) {
    return $router->app->version();
});

$router->group(['prefix' => 'ml', 'middleware' => 'meliAuth'], function () use ($router) {
    $router->post('/createtoken', 'MercadoLivreIntegrationController@createToken');
    $router->post('/createproduct', 'MercadoLivreIntegrationController@createProduct');
    $router->post('/changeproduct/{productId}', 'MercadoLivreIntegrationController@changeProduct');
    $router->post('/delete/{productId}', 'MercadoLivreIntegrationController@deleteProduct');
    $router->post('/changestatus/{productId}/{status}', 'MercadoLivreIntegrationController@changeStatus');
    $router->post('/orders', 'MercadoLivreIntegrationController@getOrders');
    $router->post('/lastorders', 'MercadoLivreIntegrationController@getLastOrders');

    $router->get('/categories', 'MercadoLivreIntegrationController@getCategories');
    $router->get('/categoryinfo/{category}', 'MercadoLivreIntegrationController@getCategoryData');
    $router->get('/status', 'MercadoLivreIntegrationController@getStatus');
    $router->get('/ordersstatus', 'MercadoLivreIntegrationController@getStatusOrders');

});