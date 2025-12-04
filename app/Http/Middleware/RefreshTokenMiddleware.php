<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use PHPOpenSourceSaver\JWTAuth\Exceptions\JWTException;
use PHPOpenSourceSaver\JWTAuth\Exceptions\TokenExpiredException;
use PHPOpenSourceSaver\JWTAuth\Exceptions\TokenInvalidException;

class RefreshTokenMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        try {
            // Coba parse token dari request
            $user = JWTAuth::parseToken()->authenticate();

            if (!$user) {
                return response()->json([
                    'status' => false,
                    'message' => 'User not found'
                ], 404);
            }

            // Cek apakah token akan expire dalam 10 menit ke depan
            $payload = JWTAuth::payload();
            $exp = $payload->get('exp');
            $now = time();
            $timeUntilExpiry = $exp - $now;

            // Jika token akan expire dalam 10 menit (600 detik), refresh token
            if ($timeUntilExpiry <= 600) {
                try {
                    $newToken = JWTAuth::refresh();

                    // Set header baru untuk token yang sudah di-refresh
                    $response = $next($request);
                    $response->headers->set('Authorization', 'Bearer ' . $newToken);
                    $response->headers->set('X-Token-Refreshed', 'true');

                    return $response;
                } catch (JWTException $e) {
                    return response()->json([
                        'status' => false,
                        'message' => 'Could not refresh token',
                        'error' => $e->getMessage()
                    ], 401);
                }
            }
        } catch (TokenExpiredException $e) {
            // Token sudah expire, coba refresh
            try {
                $newToken = JWTAuth::refresh();

                // Set token baru untuk request ini
                JWTAuth::setToken($newToken);
                $user = JWTAuth::authenticate();

                if (!$user) {
                    return response()->json([
                        'status' => false,
                        'message' => 'User not found after refresh'
                    ], 404);
                }

                $response = $next($request);
                $response->headers->set('Authorization', 'Bearer ' . $newToken);
                $response->headers->set('X-Token-Refreshed', 'true');

                return $response;
            } catch (JWTException $refreshException) {
                return response()->json([
                    'status' => false,
                    'message' => 'Token expired and could not be refreshed. Please login again.',
                    'error' => $refreshException->getMessage()
                ], 401);
            }
        } catch (TokenInvalidException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Token is invalid',
                'error' => $e->getMessage()
            ], 401);
        } catch (JWTException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Token is required',
                'error' => $e->getMessage()
            ], 401);
        }

        return $next($request);
    }
}
