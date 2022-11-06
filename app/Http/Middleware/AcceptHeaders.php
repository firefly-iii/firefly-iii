<?php

/*
 * AcceptHeaders.php
 * Copyright (c) 2022 james@firefly-iii.org
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
        $allowed = ['application/x-www-form-urlencoded','application/json'];
        $submitted = (string)$request->header('Content-Type');
        if (('POST' === $method || 'PUT' === $method) && !in_array($submitted, $allowed, true)) {
            $error             = new BadHttpHeaderException(sprintf('Content-Type cannot be "%s"', $submitted));
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
