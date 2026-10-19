<?php

namespace App\Exceptions;

use Throwable;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Routing\Exceptions\InvalidSignatureException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array
     */
    protected $dontFlash = [
        'password',
        'password_confirmation',
    ];

    /**
     * Report or log an exception.
     *
     * @param  \Exception  $exception
     * @return void
     */
    public function report(\Throwable $exception)
    {
        parent::report($exception);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Exception  $exception
     * @return \Illuminate\Http\Response
     */
    public function render($request, Throwable $exception)
    {
        if ($request->is('login') && $request->isMethod('POST') && $exception instanceof \Illuminate\Validation\ValidationException && !$request->expectsJson()) {
            return redirect()->route('login')->withErrors($exception->errors())->withInput($request->only('email'));
        }
        if (config('saas.enabled') && $exception instanceof \Illuminate\Auth\AuthenticationException && !$request->expectsJson()) {
            return redirect()->guest($request->is('superadmin*') ? route('superadmin.login') : tenant_route('login'));
        }
        if ($request->routeIs('client-reviews.*') && (
            $exception instanceof InvalidSignatureException
            || $exception instanceof ModelNotFoundException
            || ($exception instanceof HttpExceptionInterface && in_array($exception->getStatusCode(), [404, 410], true))
        )) {
            // Signed-link expiry can fail in middleware before the controller runs.
            // Render the same private page for unavailable links, even in debug mode.
            $status = $exception instanceof HttpExceptionInterface ? $exception->getStatusCode() : 404;
            return response()->view('client-reviews.unavailable', [], $status)->withHeaders([
                'Cache-Control' => 'private, no-store',
                'Referrer-Policy' => 'no-referrer',
                'X-Robots-Tag' => 'noindex, nofollow, noarchive',
                'X-Content-Type-Options' => 'nosniff',
                'Content-Security-Policy' => "default-src 'none'; style-src 'unsafe-inline'; frame-ancestors 'self'; base-uri 'none'",
            ]);
        }

        return parent::render($request, $exception);
    }
}
