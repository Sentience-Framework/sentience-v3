<?php

use Sentience\Routers\Route;
use Sentience\Routers\RouteGroup;
use Sentience\Sentience\Request;
use Sentience\Sentience\Response;
use Src\Controllers\ExampleController;
use Src\Middleware\CORSMiddleware;
use Src\Middleware\ExampleMiddleware;

return [
    Route::any(
        '/healthcheck',
        function (): void {
            Response::ok(['status' => 'available']);
        }
    )->setMiddleware([
                [CORSMiddleware::class, 'returnOrigin']
            ]),

    RouteGroup::route('/response')
        ->setMiddleware([
            [CORSMiddleware::class, 'returnOrigin']
        ])
        ->bind(Route::any('/json', [ExampleController::class, 'jsonResponse']))
        ->bind(Route::any('/xml', [ExampleController::class, 'xmlResponse']))
        ->bind(Route::any('/url', [ExampleController::class, 'urlResponse'])),

    RouteGroup::route('/users/{userId}')
        ->setMiddleware([
            [CORSMiddleware::class, 'returnOrigin'],
            [ExampleMiddleware::class, 'killSwitch']
        ])
        ->bind(Route::get('/', [ExampleController::class, 'getUser']))
        ->bind(
            RouteGroup::route('/contacts')
                ->bind(Route::get('/', [ExampleController::class, 'getContacts']))
                ->bind(Route::post('/', [ExampleController::class, 'createContact']))
                ->bind(
                    RouteGroup::route('/{contactId:int}')
                        ->bind(Route::get('/', [ExampleController::class, 'getContact']))
                        ->bind(Route::put('/', [ExampleController::class, 'updateContact']))
                )
        ),

    Route::any('/{country}-{language}', function (Request $request, ...$pathVars): void {
        Response::ok($pathVars);
    })
];
