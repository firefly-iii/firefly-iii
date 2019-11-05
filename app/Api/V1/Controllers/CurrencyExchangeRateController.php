<?php
/**
 * CurrencyExchangeRateController.php
 * Copyright (c) 2019 thegrumpydictator@gmail.com
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

namespace FireflyIII\Api\V1\Controllers;

use Carbon\Carbon;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Repositories\Currency\CurrencyRepositoryInterface;
use FireflyIII\Services\Currency\ExchangeRateInterface;
use FireflyIII\Transformers\CurrencyExchangeRateTransformer;
use FireflyIII\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use League\Fractal\Resource\Item;

/**
 * Class CurrencyExchangeRateController
 *
 * @codeCoverageIgnore
 */
class CurrencyExchangeRateController extends Controller
{
    /** @var CurrencyRepositoryInterface The currency repository */
    private $repository;

    /**
     * CurrencyExchangeRateController constructor.
     *
     */
    public function __construct()
    {
        parent::__construct();
        $this->middleware(
            function ($request, $next) {
                /** @var User $admin */
                $admin = auth()->user();

                $this->repository = app(CurrencyRepositoryInterface::class);
                $this->repository->setUser($admin);

                return $next($request);
            }
        );

    }

    /**
     * Show an exchange rate.
     *
     * @param Request $request
     *
     * @return JsonResponse
     * @throws FireflyException
     */
    public function index(Request $request): JsonResponse
    {
        $manager      = $this->getManager();
        $fromCurrency = $this->repository->findByCodeNull($request->get('from') ?? 'EUR');
        $toCurrency   = $this->repository->findByCodeNull($request->get('to') ?? 'USD');

        if (null === $fromCurrency) {
            throw new FireflyException('200007: Unknown source currency');
        }
        if (null === $toCurrency) {
            throw new FireflyException('200007: Unknown destination currency');
        }

        /** @var Carbon $dateObj */
        $dateObj = Carbon::createFromFormat('Y-m-d', $request->get('date') ?? date('Y-m-d'));
        $this->parameters->set('from', $fromCurrency->code);
        $this->parameters->set('to', $toCurrency->code);
        $this->parameters->set('date', $dateObj->format('Y-m-d'));
        $this->parameters->set('amount', $request->get('amount'));

        $rate = $this->repository->getExchangeRate($fromCurrency, $toCurrency, $dateObj);
        if (null === $rate) {
            /** @var User $admin */
            $admin = auth()->user();
            // create service:
            /** @var ExchangeRateInterface $service */
            $service = app(ExchangeRateInterface::class);
            $service->setUser($admin);
            $rate = $service->getRate($fromCurrency, $toCurrency, $dateObj);
        }
        /** @var CurrencyExchangeRateTransformer $transformer */
        $transformer = app(CurrencyExchangeRateTransformer::class);
        $transformer->setParameters($this->parameters);
        $resource = new Item($rate, $transformer, 'currency_exchange_rates');

        return response()->json($manager->createData($resource)->toArray())->header('Content-Type', 'application/vnd.api+json');
    }
}
