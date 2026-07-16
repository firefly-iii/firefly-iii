<?php

/*
 * TransactionsController.php
 * Copyright (c) 2026 james@firefly-iii.org
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

namespace FireflyIII\Api\V1\Controllers\Models\Tag;

use FireflyIII\Api\V1\Controllers\Controller;
use FireflyIII\Api\V1\Requests\Models\Tag\TransactionLinkRequest;
use FireflyIII\Models\Tag;
use FireflyIII\Repositories\Tag\TagRepositoryInterface;
use FireflyIII\User;
use Illuminate\Http\JsonResponse;

/**
 * Class TransactionsController
 *
 * Links or unlinks a tag to/from one or more transaction journals, without
 * requiring the full transaction resource to be resent. See issue #11400.
 */
final class TransactionsController extends Controller
{
    private TagRepositoryInterface $repository;

    public function __construct()
    {
        parent::__construct();
        $this->middleware(function ($request, $next) {
            /** @var User $user */
            $user             = auth()->user();

            $this->repository = app(TagRepositoryInterface::class);
            $this->repository->setUser($user);

            return $next($request);
        });
    }

    /**
     * Attach the tag to one or more transaction journals.
     */
    public function attach(TransactionLinkRequest $request, Tag $tag): JsonResponse
    {
        $result = $this->repository->attachToJournals($tag, $request->getJournalIds());

        return response()->json(['data' => $result])->header('Content-Type', self::CONTENT_TYPE);
    }

    /**
     * Detach the tag from one or more transaction journals.
     */
    public function detach(TransactionLinkRequest $request, Tag $tag): JsonResponse
    {
        $result = $this->repository->detachFromJournals($tag, $request->getJournalIds());

        return response()->json(['data' => $result])->header('Content-Type', self::CONTENT_TYPE);
    }
}
