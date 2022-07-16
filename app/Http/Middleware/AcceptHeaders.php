<?php
declare(strict_types=1);

namespace FireflyIII\Http\Middleware;

use FireflyIII\Exceptions\BadHttpHeaderException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Log;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 *
 */
class AcceptHeaders
{
    /**
     * Handle the incoming requests.
     *
     * @param Request $request
     * @param callable $next
     * @return Response
     * @throws BadHttpHeaderException
     */
    public function handle($request, $next): mixed
    {
        $method = $request->getMethod();

        if ('GET' === $method && !$request->accepts(['application/json', 'application/vdn.api+json'])) {
            throw new BadHttpHeaderException('Your request must accept either application/json or application/vdn.api+json.');
        }
        if (('POST' === $method || 'PUT' === $method) && 'application/json' !== (string)$request->header('Content-Type')) {
            $error             = new BadHttpHeaderException('B');
            $error->statusCode = 415;
            throw $error;
        }

        // throw bad request if trace id is not a UUID
        $uuid = $request->header('X-Trace-Id', null);
        if (is_string($uuid) && '' !== trim($uuid) && (preg_match('/^[a-f\d]{8}(-[a-f\d]{4}){4}[a-f\d]{8}$/i', trim($uuid)) !== 1)) {
            throw new BadRequestHttpException('Bad X-Trace-Id header.');
        }

        return $next($request);
    }
}
