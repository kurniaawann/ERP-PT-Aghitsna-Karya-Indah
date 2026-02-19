<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;
use Illuminate\Database\QueryException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;

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
            // Log error untuk monitoring
            \Log::error('Application Error: ' . $e->getMessage(), [
                'exception' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
        });

        // Handle specific exceptions
        $this->renderable(function (QueryException $e, $request) {
            // Jika bukan mode debug, tampilkan halaman error ramah pengguna
            if (!config('app.debug')) {
                return response()->view('errors.500', ['exception' => $e], 500);
            }
        });

        $this->renderable(function (NotFoundHttpException $e, $request) {
            if (!config('app.debug')) {
                return response()->view('errors.404', [], 404);
            }
        });

        $this->renderable(function (HttpException $e, $request) {
            if (!config('app.debug')) {
                $statusCode = $e->getStatusCode();

                // Cek apakah ada view khusus untuk status code ini
                if (view()->exists("errors.{$statusCode}")) {
                    return response()->view("errors.{$statusCode}", ['exception' => $e], $statusCode);
                }

                // Default ke 500 jika tidak ada view khusus
                return response()->view('errors.500', ['exception' => $e], $statusCode);
            }
        });

        // Catch all other exceptions
        $this->renderable(function (Throwable $e, $request) {
            if (!config('app.debug')) {
                return response()->view('errors.500', ['exception' => $e], 500);
            }
        });
    }
}
