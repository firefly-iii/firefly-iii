<?php
/**
 * CurrencyController.php
 * Copyright (c) 2020 james@firefly-iii.org
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

namespace FireflyIII\Api\V1\Controllers\Autocomplete;


use FireflyIII\Api\V1\Controllers\Controller;
use FireflyIII\Api\V1\Requests\Autocomplete\AutocompleteRequest;
use FireflyIII\Repositories\Currency\CurrencyRepositoryInterface;
use FireflyIII\User;
use Illuminate\Http\JsonResponse;

/**
 * Class CurrencyController
 */
class CurrencyController extends Controller
{
    private CurrencyRepositoryInterface $repository;

    /**
     * CurrencyController constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->middleware(
            function ($request, $next) {
                /** @var User $user */
                $user             = auth()->user();
                $this->repository = app(CurrencyRepositoryInterface::class);
                $this->repository->setUser($user);

                return $next($request);
            }
        );
    }

    /**
     * TODO add limit
     *
     * @param AutocompleteRequest $request
     *
     * @return JsonResponse
     */
    public function currencyWithCode(AutocompleteRequest $request): JsonResponse
    {
        $data   = $request->getData();
        $result = $this->repository->searchCurrency($data['query'])->toArray();
        foreach ($result as $index => $item) {
            $result[$index]['name'] = sprintf('%s (%s)', $item['name'], $item['code']);
        }

        return response()->json($result);
    }
}
