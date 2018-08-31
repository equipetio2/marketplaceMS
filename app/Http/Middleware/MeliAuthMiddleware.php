<?php

namespace App\Http\Middleware;

use Closure;
use Dsc\MercadoLivre\Meli;
use Dsc\MercadoLivre\Resources\Authorization\AuthorizationService;

class MeliAuthMiddleware
{

    public static $meli;
    public static $token;
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if (!$request->isMethod('GET')) {
            static::$meli = new Meli($request->appId, $request->appSecretKey);
            $service = new AuthorizationService(static::$meli);
            static::$token = $service->getAccessToken();
        }
        return $next($request);
    }
}
