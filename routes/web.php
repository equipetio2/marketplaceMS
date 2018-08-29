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

$router->group(['prefix' => 'ml'], function () use ($router) {
    $router->post('/createtoken',
        [
            'middleware' => 'meliAuth',
            'uses' => 'MercadoLivreIntegrationController@createToken'
        ]
    );
    $router->post('/createproduct',
        [
            'middleware' => 'meliAuth',
            'uses' => 'MercadoLivreIntegrationController@createProduct'
        ]
    );
    $router->post('/changeproduct/{productId}',
        [
            'middleware' => 'meliAuth',
            'uses' => 'MercadoLivreIntegrationController@changeProduct'
        ]
    );
    $router->put('/delete/{productId}',
        [
            'middleware' => 'meliAuth',
            'uses' => 'MercadoLivreIntegrationController@deleteProduct'
        ]
    );
    $router->put('/changestatus/{productId}/{status}',
        [
            'middleware' => 'meliAuth',
            'uses' => 'MercadoLivreIntegrationController@changeStatus'
        ]
    );
    $router->get('/categories', 'MercadoLivreIntegrationController@getCategories');
    $router->get('/categoryinfo/{category}', 'MercadoLivreIntegrationController@getCategoryData');
    $router->get('/status', 'MercadoLivreIntegrationController@getStatus');
});