<?php
/**
 * AccountController.php
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

declare(strict_types=1);

namespace FireflyIII\Api\V1\Controllers\Models\Account;

use FireflyIII\Api\V1\Controllers\Controller;
use FireflyIII\Api\V1\Requests\Models\Account\UpdateRequest;
use FireflyIII\Models\Account;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Transformers\AccountTransformer;
use Illuminate\Http\JsonResponse;
use League\Fractal\Resource\Item;
use Log;

/**
 * Class UpdateController
 */
class UpdateController extends Controller
{
    public const RESOURCE_KEY = 'accounts';

    private AccountRepositoryInterface $repository;


    /**
     * AccountController constructor.
     *
     * @codeCoverageIgnore
     */
    public function __construct()
    {
        parent::__construct();
        $this->middleware(
            function ($request, $next) {
                $this->repository = app(AccountRepositoryInterface::class);
                $this->repository->setUser(auth()->user());

                return $next($request);
            }
        );
    }

    /**
     * Update account.
     *
     * @param UpdateRequest $request
     * @param Account       $account
     *
     * @return JsonResponse
     */
    public function update(UpdateRequest $request, Account $account): JsonResponse
    {
        Log::debug(sprintf('Now in %s', __METHOD__));
        $data         = $request->getUpdateData();
        $data['type'] = config('firefly.shortNamesByFullName.' . $account->accountType->type);
        $account      = $this->repository->update($account, $data);
        $manager      = $this->getManager();
        $account->refresh();

        /** @var AccountTransformer $transformer */
        $transformer = app(AccountTransformer::class);
        $transformer->setParameters($this->parameters);
        $resource = new Item($account, $transformer, self::RESOURCE_KEY);

        return response()->json($manager->createData($resource)->toArray())->header('Content-Type', self::CONTENT_TYPE);
    }
}
