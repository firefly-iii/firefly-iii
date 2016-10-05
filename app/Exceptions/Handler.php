<?php
/**
 * Handler.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types = 1);
namespace FireflyIII\Exceptions;

use ErrorException;
use Exception;
use FireflyIII\Jobs\MailError;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Validation\ValidationException as ValException;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Class Handler
 *
 * @package FireflyIII\Exceptions
 */
class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that should not be reported.
     *
     * @var array
     */
    protected $dontReport
        = [
            AuthenticationException::class,
            AuthorizationException::class,
            HttpException::class,
            ModelNotFoundException::class,
            TokenMismatchException::class,
            ValException::class,
        ];

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Exception               $exception
     *
     * @return \Illuminate\Http\Response
     */
    public function render($request, Exception $exception)
    {
        if ($exception instanceof FireflyException || $exception instanceof ErrorException) {

            $isDebug = env('APP_DEBUG', false);

            return response()->view('errors.FireflyException', ['exception' => $exception, 'debug' => $isDebug], 500);
        }

        return parent::render($request, $exception);
    }


    /**
     * Report or log an exception.
     *
     * This is a great spot to send exceptions to Sentry, Bugsnag, etc.
     *
     * @param  Exception $exception
     *
     * @return void
     */
    public function report(Exception $exception)
    {
        if ($exception instanceof FireflyException || $exception instanceof ErrorException) {
            $userData = [
                'id'    => 0,
                'email' => 'unknown@example.com',
            ];
            if (auth()->check()) {
                $userData['id']    = auth()->user()->id;
                $userData['email'] = auth()->user()->email;
            }
            $data = [
                'class'        => get_class($exception),
                'errorMessage' => $exception->getMessage(),
                'time'         => date('r'),
                'stackTrace'   => $exception->getTraceAsString(),
                'file'         => $exception->getFile(),
                'line'         => $exception->getLine(),
                'code'         => $exception->getCode(),
            ];

            // create job that will mail.
            $ip  = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
            $job = new MailError($userData, env('SITE_OWNER', ''), $ip, $data);
            dispatch($job);
        }

        parent::report($exception);
    }

    /**
     * Convert an authentication exception into an unauthenticated response.
     *
     * @param  \Illuminate\Http\Request                 $request
     * @return \Illuminate\Http\Response
     */
    protected function unauthenticated($request)
    {
        if ($request->expectsJson()) {
            return response()->json(['error' => 'Unauthenticated.'], 401);
        }

        return redirect()->guest('login');
    }
}
