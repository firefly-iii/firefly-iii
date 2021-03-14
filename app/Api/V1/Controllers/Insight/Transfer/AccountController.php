<?php
/*
 * AccountController.php
 * Copyright (c) 2021 james@firefly-iii.org
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

namespace FireflyIII\Api\V1\Controllers\Insight\Transfer;


use FireflyIII\Api\V1\Controllers\Controller;
use FireflyIII\Api\V1\Requests\Insight\GenericRequest;
use FireflyIII\Repositories\Account\OperationsRepositoryInterface;
use FireflyIII\Support\Http\Api\ApiSupport;
use Illuminate\Http\JsonResponse;

/**
 * Class AccountController
 */
class AccountController extends Controller
{
    use ApiSupport;

    private OperationsRepositoryInterface $opsRepository;

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
                $user                = auth()->user();
                $this->opsRepository = app(OperationsRepositoryInterface::class);
                $this->opsRepository->setUser($user);

                return $next($request);
            }
        );
    }

    /**
     * TODO same code as Expense/AccountController.
     *
     * @param GenericRequest $request
     *
     * @return JsonResponse
     */
    public function asset(GenericRequest $request): JsonResponse
    {
        $start         = $request->getStart();
        $end           = $request->getEnd();
        $assetAccounts = $request->getAssetAccounts();
        $income        = $this->opsRepository->sumTransfers($start, $end, $assetAccounts);
        $result        = [];
        /** @var array $entry */
        foreach ($income as $entry) {
            $result[] = [
                'difference'       => $entry['sum'],
                'difference_float' => (float)$entry['sum'],
                'currency_id'      => (string)$entry['currency_id'],
                'currency_code'    => $entry['currency_code'],
            ];
        }

        return response()->json($result);
    }
}