<?php

/**
 * Handler.php
 * Copyright (c) 2018 thegrumpydictator@gmail.com
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

/** @noinspection MultipleReturnStatementsInspection */

declare(strict_types=1);

namespace FireflyIII\Exceptions;

use ErrorException;
use Exception;
use FireflyIII\Jobs\MailError;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Validation\ValidationException as LaravelValidationException;
use League\OAuth2\Server\Exception\OAuthServerException;
use Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class Handler
 *
 * @codeCoverageIgnore
 */
class Handler extends ExceptionHandler
{
    /**
     * Render an exception into an HTTP response.
     *
     * @param Request   $request
     * @param Exception $exception
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     *
     * @return mixed
     */
    public function render($request, Exception $exception)
    {
        if ($exception instanceof LaravelValidationException && $request->expectsJson()) {
            // ignore it: controller will handle it.
            return parent::render($request, $exception);
        }
        if ($exception instanceof NotFoundHttpException && $request->expectsJson()) {
            // JSON error:
            return response()->json(['message' => 'Resource not found', 'exception' => 'NotFoundHttpException'], 404);
        }

        if ($exception instanceof AuthenticationException && $request->expectsJson()) {
            // somehow Laravel handler does not catch this:
            return response()->json(['message' => 'Unauthenticated', 'exception' => 'AuthenticationException'], 401);
        }

        if ($exception instanceof OAuthServerException && $request->expectsJson()) {
            // somehow Laravel handler does not catch this:
            return response()->json(['message' => $exception->getMessage(), 'exception' => 'OAuthServerException'], 401);
        }

        if ($request->expectsJson()) {
            $isDebug = config('app.debug', false);
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

        if($exception instanceof NotFoundHttpException) {
            $handler = app(GracefulNotFoundHandler::class);
            return $handler->render($request, $exception);
        }


        if ($exception instanceof FireflyException || $exception instanceof ErrorException || $exception instanceof OAuthServerException) {
            $isDebug = config('app.debug');

            return response()->view('errors.FireflyException', ['exception' => $exception, 'debug' => $isDebug], 500);
        }

        return parent::render($request, $exception);
    }

    /**
     * Report or log an exception.
     *
     * This is a great spot to send exceptions to Sentry etc.
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity) // it's five its fine.
     *
     * @param Exception $exception
     *
     * @return mixed|void
     *
     * @throws Exception
     */
    public function report(Exception $exception)
    {

        $doMailError = config('firefly.send_error_message');
        // if the user wants us to mail:
        if (true === $doMailError
            // and if is one of these error instances
            && ($exception instanceof FireflyException || $exception instanceof ErrorException)) {
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
                'url'          => Request::fullUrl(),
                'userAgent'    => Request::userAgent(),
                'json'         => Request::acceptsJson(),
            ];

            // create job that will mail.
            $ipAddress = Request::ip() ?? '0.0.0.0';
            $job       = new MailError($userData, (string)config('firefly.site_owner'), $ipAddress, $data);
            dispatch($job);
        }

        parent::report($exception);
    }
}
