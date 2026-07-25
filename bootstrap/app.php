<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
        ]);

        $middleware->statefulApi();

        $middleware->throttleApi('api');

        $middleware->trustProxies(
            at: '*',
            headers: Request::HEADER_X_FORWARDED_FOR
                | Request::HEADER_X_FORWARDED_HOST
                | Request::HEADER_X_FORWARDED_PORT
                | Request::HEADER_X_FORWARDED_PROTO
                | Request::HEADER_X_FORWARDED_AWS_ELB
        );

        $middleware->append([
            \App\Http\Middleware\AddSecurityHeaders::class,
            \App\Http\Middleware\TrackLastActivity::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (\Throwable $throwable, Request $request) {
            if (! $request->expectsJson()) {
                return null;
            }

            $status = match (true) {
                $throwable instanceof \Illuminate\Validation\ValidationException => Response::HTTP_UNPROCESSABLE_ENTITY,
                $throwable instanceof \Illuminate\Auth\AuthenticationException => Response::HTTP_UNAUTHORIZED,
                $throwable instanceof \Illuminate\Auth\Access\AuthorizationException => Response::HTTP_FORBIDDEN,
                $throwable instanceof \Symfony\Component\HttpKernel\Exception\NotFoundHttpException => Response::HTTP_NOT_FOUND,
                default => Response::HTTP_INTERNAL_SERVER_ERROR,
            };

            return response()->json([
                'success' => false,
                'message' => $throwable instanceof \Illuminate\Validation\ValidationException
                    ? 'The given data was invalid.'
                    : ($throwable->getMessage() ?: Response::$statusTexts[$status]),
                'errors' => $throwable instanceof \Illuminate\Validation\ValidationException
                    ? $throwable->errors()
                    : null,
            ], $status);
        });
    })->create();
