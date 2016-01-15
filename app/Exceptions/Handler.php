<?php

namespace FireflyIII\Exceptions;

use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Foundation\Validation\ValidationException;
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
            AuthorizationException::class,
            HttpException::class,
            ModelNotFoundException::class,
            ValidationException::class,
        ];

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
        parent::report($exception);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Exception               $e
     *
     * @return \Illuminate\Http\Response
     */
    public function render($request, Exception $e)
    {
        return parent::render($request, $e);
    }
}
