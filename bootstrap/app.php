<?php

use App\Models\Contact;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // La API no tiene página de login: sin token se responde 401, no se redirige.
        $middleware->redirectGuestsTo(fn () => null);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Todo lo que esté bajo /api responde en JSON, aunque falte la cabecera Accept.
        $exceptions->shouldRenderJsonWhen(function (Request $request, Throwable $e) {
            return $request->is('api/*') || $request->expectsJson();
        });

        // 401: sin sesión iniciada. La respuesta indica a dónde ir: el login.
        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'message' => 'No has iniciado sesión. Inicia sesión para continuar.',
                    'login_url' => route('auth.login'),
                ], 401);
            }
        });

        // 403: el recurso existe pero no es del usuario (mensaje de ContactPolicy).
        $exceptions->render(function (AccessDeniedHttpException $e, Request $request) {
            if ($request->is('api/*')) {
                $mensaje = $e->getMessage() === '' || $e->getMessage() === 'This action is unauthorized.'
                    ? 'No tienes permiso para realizar esta acción.'
                    : $e->getMessage();

                return response()->json(['message' => $mensaje], 403);
            }
        });

        // 404: la ruta no existe, o el registro no existe (o fue eliminado).
        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            if ($request->is('api/*')) {
                $anterior = $e->getPrevious();

                $mensaje = match (true) {
                    $anterior instanceof ModelNotFoundException && $anterior->getModel() === Contact::class => 'El contacto solicitado no existe.',
                    $anterior instanceof ModelNotFoundException => 'El recurso solicitado no existe.',
                    default => 'La ruta solicitada no existe.',
                };

                return response()->json(['message' => $mensaje], 404);
            }
        });

        // 405: la ruta existe pero no con ese método (por ejemplo POST /contacts/5).
        $exceptions->render(function (MethodNotAllowedHttpException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'message' => 'El método '.$request->method().' no está permitido en esta ruta.',
                ], 405, $e->getHeaders());
            }
        });
    })->create();
