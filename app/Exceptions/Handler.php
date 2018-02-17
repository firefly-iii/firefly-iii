<?php
/**
 * Handler.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 *
 * This file is part of Firefly III.
 *
 * Firefly III is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Firefly III is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Firefly III. If not, see <http://www.gnu.org/licenses/>.
 */
declare(strict_types=1);

namespace FireflyIII\Exceptions;

use ErrorException;
use Exception;
use FireflyIII\Jobs\MailError;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Validation\ValidationException;
use Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class Handler
 */
class Handler extends ExceptionHandler
{
    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array
     */
    protected $dontFlash
        = [
            'password',
            'password_confirmation',
        ];
    /**
     * A list of the exception types that are not reported.
     *
     * @var array
     */
    protected $dontReport
        = [
        ];

    /**
     * Render an exception into an HTTP response.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Exception               $exception
     *
     * @return \Illuminate\Http\Response
     */
    public function render($request, Exception $exception)
    {
        if ($exception instanceof ValidationException && $request->expectsJson()) {
            // ignore it: controller will handle it.
            return parent::render($request, $exception);
        }
        if ($exception instanceof NotFoundHttpException && $request->expectsJson()) {
            return response()->json(['message' => 'Resource not found', 'exception' => 'NotFoundHttpException'], 404);
        }

        if ($exception instanceof AuthenticationException && $request->expectsJson()) {
            return response()->json(['message' => 'Unauthenticated', 'exception' => 'AuthenticationException'], 401);
        }

        if ($request->expectsJson()) {
            $isDebug = env('APP_DEBUG', false);
            if ($isDebug) {
                return response()->json(
                    [
                        'message'   => $exception->getMessage(),
                        'exception' => get_class($exception),
                        'line'      => $exception->getLine(),
                        'file'      => $exception->getFile(),
                        'trace'     => $exception->getTrace(),
                    ], 500
                );
            }

            return response()->json(['message' => 'Internal Firefly III Exception. See log files.', 'exception' => get_class($exception)], 500);
        }

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
     * @SuppressWarnings(PHPMD.CyclomaticComplexity) // it's five its fine.
     *
     * @param \Exception $exception
     *
     * @return mixed|void
     *
     * @throws Exception
     */
    public function report(Exception $exception)
    {
        $doMailError = env('SEND_ERROR_MESSAGE', true);
        if (($exception instanceof FireflyException || $exception instanceof ErrorException) && $doMailError) {
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
                'version'      => config('firefly.version'),
            ];

            // create job that will mail.
            $ipAddress = Request::ip() ?? '0.0.0.0';
            $job       = new MailError($userData, env('SITE_OWNER', ''), $ipAddress, $data);
            dispatch($job);
        }

        parent::report($exception);
    }
}
