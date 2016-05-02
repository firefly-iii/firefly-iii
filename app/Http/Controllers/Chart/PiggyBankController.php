<?php
declare(strict_types = 1);

namespace FireflyIII\Http\Controllers\Chart;

use FireflyIII\Generator\Chart\PiggyBank\PiggyBankChartGeneratorInterface;
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Models\PiggyBank;
use FireflyIII\Models\PiggyBankEvent;
use FireflyIII\Repositories\PiggyBank\PiggyBankRepositoryInterface;
use FireflyIII\Support\CacheProperties;
use Illuminate\Support\Collection;
use Response;


/**
 * Class PiggyBankController
 *
 * @package FireflyIII\Http\Controllers\Chart
 */
class PiggyBankController extends Controller
{

    /** @var PiggyBankChartGeneratorInterface */
    protected $generator;

    /**
     *
     */
    public function __construct()
    {
        parent::__construct();
        // create chart generator:
        $this->generator = app(PiggyBankChartGeneratorInterface::class);
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
        $cache->addProperty('piggy-history');
        $cache->addProperty($piggyBank->id);
        if ($cache->has()) {
            return Response::json($cache->get());
        }

        $set        = $repository->getEvents($piggyBank);
        $set        = $set->reverse();
        $collection = [];
        /** @var PiggyBankEvent $entry */
        foreach ($set as $entry) {
            $date   = $entry->date->format('Y-m-d');
            $amount = $entry->amount;
            if (isset($collection[$date])) {
                $amount = bcadd($amount, $collection[$date]);
            }
            $collection[$date] = $amount;
        }

        $data = $this->generator->history(new Collection($collection));
        $cache->store($data);

        return Response::json($data);

    }
}
