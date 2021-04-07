<?php

/**
 * Handler.php
 * Copyright (c) 2019 james@firefly-iii.org
 *
 * This file is part of Firefly III (https://github.com/firefly-iii).
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/** @noinspection MultipleReturnStatementsInspection */

declare(strict_types=1);

namespace FireflyIII\Exceptions;

use ErrorException;
use FireflyIII\Jobs\MailError;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException as LaravelValidationException;
use League\OAuth2\Server\Exception\OAuthServerException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

/**
 * Class Handler
 *
 * @codeCoverageIgnore
 */
class Handler extends ExceptionHandler
{
    /**
     * @var array
     */
    protected $dontReport
        = [
            AuthenticationException::class,
            LaravelValidationException::class,
            NotFoundHttpException::class,
            OAuthServerException::class,
            TokenMismatchException::class,
        ];

    /**
     * Render an exception into an HTTP response.
     *
     * @param Request   $request
     * @param Throwable $e
     *
     * @return mixed
     */
    public function render($request, Throwable $e)
    {
        if ($e instanceof LaravelValidationException && $request->expectsJson()) {
            // ignore it: controller will handle it.
            return parent::render($request, $e);
        }
        if ($e instanceof NotFoundHttpException && $request->expectsJson()) {
            // JSON error:
            return response()->json(['message' => 'Resource not found', 'exception' => 'NotFoundHttpException'], 404);
        }

        if ($e instanceof AuthenticationException && $request->expectsJson()) {
            // somehow Laravel handler does not catch this:
            return response()->json(['message' => 'Unauthenticated', 'exception' => 'AuthenticationException'], 401);
        }

        if ($e instanceof OAuthServerException && $request->expectsJson()) {
            // somehow Laravel handler does not catch this:
            return response()->json(['message' => $e->getMessage(), 'exception' => 'OAuthServerException'], 401);
        }

        if ($request->expectsJson()) {
            $isDebug = config('app.debug', false);
            if ($isDebug) {
                return response()->json(
                    [
                        'message'   => $e->getMessage(),
                        'exception' => get_class($e),
                        'line'      => $e->getLine(),
                        'file'      => $e->getFile(),
                        'trace'     => $e->getTrace(),
                    ],
                    500
                );
            }

            return response()->json(
                ['message' => sprintf('Internal Firefly III Exception: %s', $e->getMessage()), 'exception' => get_class($e)], 500
            );
        }

        if ($e instanceof NotFoundHttpException) {
            $handler = app(GracefulNotFoundHandler::class);

            return $handler->render($request, $e);
        }
        if ($e instanceof FireflyException || $e instanceof ErrorException || $e instanceof OAuthServerException) {
            $isDebug = config('app.debug');

            return response()->view('errors.FireflyException', ['exception' => $e, 'debug' => $isDebug], 500);
        }

        return parent::render($request, $e);
    }

    /**
     * Report or log an exception.
     *
     * This is a great spot to send exceptions to Sentry etc.
     *
     * @param Throwable $e
     *
     * @return void
     * @throws Throwable
     *
     */
    public function report(Throwable $e)
    {
        $doMailError = config('firefly.send_error_message');
        if ($this->shouldntReportLocal($e) || !$doMailError) {
            parent::report($e);

            return;
        }
        $userData = [
            'id'    => 0,
            'email' => 'unknown@example.com',
        ];
        if (auth()->check()) {
            $userData['id']    = auth()->user()->id;
            $userData['email'] = auth()->user()->email;
        }
        $data = [
            'class'        => get_class($e),
            'errorMessage' => $e->getMessage(),
            'time'         => date('r'),
            'stackTrace'   => $e->getTraceAsString(),
            'file'         => $e->getFile(),
            'line'         => $e->getLine(),
            'code'         => $e->getCode(),
            'version'      => config('firefly.version'),
            'url'          => request()->fullUrl(),
            'userAgent'    => request()->userAgent(),
            'json'         => request()->acceptsJson(),
        ];

        // create job that will mail.
        $ipAddress = request()->ip() ?? '0.0.0.0';
        $job       = new MailError($userData, (string)config('firefly.site_owner'), $ipAddress, $data);
        dispatch($job);

        parent::report($e);
    }

    /**
     * @param Throwable $e
     *
     * @return bool
     */
    private function shouldntReportLocal(Throwable $e): bool
    {
        return !is_null(
            Arr::first(
                $this->dontReport, function ($type) use ($e) {
                return $e instanceof $type;
            }
            )
        );
    }
}
