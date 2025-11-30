<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Auth\AuthenticationException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    /**
     * Convert an authentication exception into a response.
     */
    protected function unauthenticated($request, AuthenticationException $exception)
    {
        // Selalu return JSON untuk semua request
        return response()->json([
            'error' => 'Unauthenticated',
            'message' => 'Authentication required'
        ], 401);
    }

    /**
     * Render an exception into an HTTP response.
     */
    public function render($request, Throwable $exception)
    {
        // Handle RouteNotFoundException khusus untuk route login
        if ($exception instanceof \Symfony\Component\Routing\Exception\RouteNotFoundException) {
            if (str_contains($exception->getMessage(), 'login')) {
                return response()->json([
                    'error' => 'Route not found',
                    'message' => 'Login route not configured'
                ], 404);
            }
        }

        return parent::render($request, $exception);
    }
}
