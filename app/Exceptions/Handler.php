<?php
declare(strict_types = 1);
namespace FireflyIII\Exceptions;

use ErrorException;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Mail\Message;
use Log;
use Mail;
use Swift_TransportException;
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

            // log
            Log::error($exception->getMessage());

            // mail?
            try {
                $email = env('SITE_OWNER');

                $args = [
                    'errorMessage' => $exception->getMessage(),
                    'stacktrace'   => $exception->getTraceAsString(),
                    'file'         => $exception->getFile(),
                    'line'         => $exception->getLine(),
                    'code'         => $exception->getCode(),
                ];

                Mail::send(
                    ['emails.error-html', 'emails.error'], $args,
                    function (Message $message) use ($email) {
                        if ($email != 'mail@example.com') {
                            $message->to($email, $email)->subject('Caught an error in Firely III.');
                        }
                    }
                );
            } catch (Swift_TransportException $e) {
                // could also not mail! :o
                Log::error($e->getMessage());
            }
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
        parent::report($exception);
    }
}
