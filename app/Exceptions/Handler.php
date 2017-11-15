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
 * along with Firefly III.  If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);


/**
 * Handler.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

namespace FireflyIII\Exceptions;

use ErrorException;
use Exception;
use FireflyIII\Jobs\MailError;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Request;

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
            //
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
     * @SuppressWarnings(PHPMD.CyclomaticComplexity) // it's five its fine.
     * @param  \Exception $exception
     *
     * @return void
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
