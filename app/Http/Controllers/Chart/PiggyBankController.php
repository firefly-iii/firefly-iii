<?php
declare(strict_types = 1);

namespace FireflyIII\Http\Controllers\Chart;

use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Models\PiggyBank;
use FireflyIII\Repositories\PiggyBank\PiggyBankRepositoryInterface;
use FireflyIII\Support\CacheProperties;
use Response;


/**
 * Class PiggyBankController
 *
 * @package FireflyIII\Http\Controllers\Chart
 */
class PiggyBankController extends Controller
{

    /** @var  \FireflyIII\Generator\Chart\PiggyBank\PiggyBankChartGeneratorInterface */
    protected $generator;

    /**
     *
     */
    public function __construct()
    {
        parent::__construct();
        // create chart generator:
        $this->generator = app('FireflyIII\Generator\Chart\PiggyBank\PiggyBankChartGeneratorInterface');
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

        $set  = $repository->getEventSummarySet($piggyBank);
        $data = $this->generator->history($set);
        $cache->store($data);

        return Response::json($data);

    }
}
