<?php

use App\Services\Bureau\Exceptions\BureauIndisponivelException;
use App\Services\Credito\Exceptions\AnaliseNaoContratavelException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $esperaJson = fn (Request $request) => $request->is('api/*') || $request->expectsJson();

        $exceptions->shouldRenderJsonWhen($esperaJson);

        // sem isso a API responderia com o nome da classe do model no corpo
        $exceptions->render(function (NotFoundHttpException $e, Request $request) use ($esperaJson) {
            if ($e->getPrevious() instanceof ModelNotFoundException && $esperaJson($request)) {
                return response()->json(
                    ['message' => 'Registro não encontrado.'],
                    Response::HTTP_NOT_FOUND,
                );
            }
        });

        // O Bureau é um terceiro, indisponibilidade dele não é erro da aplicação.
        $exceptions->render(function (BureauIndisponivelException $e, Request $request) use ($esperaJson) {
            if ($esperaJson($request)) {
                return response()->json(
                    ['message' => 'Não foi possível consultar o Bureau de Crédito no momento. Tente novamente em instantes.'],
                    Response::HTTP_SERVICE_UNAVAILABLE,
                );
            }
        });

        $exceptions->render(function (AnaliseNaoContratavelException $e, Request $request) use ($esperaJson) {
            if ($esperaJson($request)) {
                return response()->json(
                    ['message' => $e->getMessage()],
                    Response::HTTP_UNPROCESSABLE_ENTITY,
                );
            }
        });
    })->create();
