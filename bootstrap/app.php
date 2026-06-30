<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->api(append: [
            \App\Http\Middleware\LogAPI::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e, \Illuminate\Http\Request $request) {
            if ($request->is('api/*')) {
                // Log 404 manually because middleware doesn't run on route not found
                $user = null;
                try {
                    $user = \PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth::parseToken()->authenticate();
                } catch (\Exception $ex) {
                    $user = null;
                }

                $filteredRequest = \App\Helpers\ApiFormatter::filterSensitiveData($request->all());
                $responseJson = \App\Helpers\ApiFormatter::createJson(404, 'Not Found', 'Route not found.');

                \App\Models\LogModel::create([
                    'user_id' => $user ? $user->id : null,
                    'log_method' => $request->method(),
                    'log_url' => $request->fullUrl(),
                    'log_ip' => $request->ip(),
                    'log_request' => json_encode($filteredRequest),
                    'log_response' => json_encode($responseJson->getData())
                ]);

                return $responseJson;
            }
        });
    })->create();
