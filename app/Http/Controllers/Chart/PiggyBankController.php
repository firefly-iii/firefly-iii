<?php
/**
 * PiggyBankController.php
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

namespace FireflyIII\Http\Controllers\Chart;

use FireflyIII\Generator\Chart\Basic\GeneratorInterface;
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Models\PiggyBank;
use FireflyIII\Models\PiggyBankEvent;
use FireflyIII\Repositories\PiggyBank\PiggyBankRepositoryInterface;
use FireflyIII\Support\CacheProperties;
use Response;

/**
 * Class PiggyBankController.
 */
class PiggyBankController extends Controller
{
    /** @var GeneratorInterface */
    protected $generator;

    /**
     *
     */
    public function __construct()
    {
        parent::__construct();
        // create chart generator:
        $this->generator = app(GeneratorInterface::class);
    }

    /**
     * Shows the piggy bank history.
     *
     * @param PiggyBankRepositoryInterface $repository
     * @param PiggyBank                    $piggyBank
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function history(PiggyBankRepositoryInterface $repository, PiggyBank $piggyBank)
    {
        // chart properties for cache:
        $cache = new CacheProperties;
        $cache->addProperty('chart.piggy-bank.history');
        $cache->addProperty($piggyBank->id);
        if ($cache->has()) {
            return Response::json($cache->get()); // @codeCoverageIgnore
        }

        $set       = $repository->getEvents($piggyBank);
        $set       = $set->reverse();
        $chartData = [];
        $sum       = '0';
        /** @var PiggyBankEvent $entry */
        foreach ($set as $entry) {
            $label             = $entry->date->formatLocalized(strval(trans('config.month_and_day')));
            $sum               = bcadd($sum, $entry->amount);
            $chartData[$label] = $sum;
        }

        $data = $this->generator->singleSet($piggyBank->name, $chartData);
        $cache->store($data);

        return Response::json($data);
    }
}
