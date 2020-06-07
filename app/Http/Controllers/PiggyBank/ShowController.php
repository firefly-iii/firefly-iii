<?php
declare(strict_types=1);

namespace FireflyIII\Http\Controllers\PiggyBank;


use Carbon\Carbon;
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Models\PiggyBank;
use FireflyIII\Repositories\PiggyBank\PiggyBankRepositoryInterface;
use FireflyIII\Transformers\PiggyBankTransformer;
use Illuminate\Contracts\View\Factory;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * Class ShowController
 */
class ShowController extends Controller
{
    private PiggyBankRepositoryInterface $piggyRepos;

    /**
     * PiggyBankController constructor.
     *
     * @codeCoverageIgnore
     */
    public function __construct()
    {
        parent::__construct();

        $this->middleware(
            function ($request, $next) {
                app('view')->share('title', (string) trans('firefly.piggyBanks'));
                app('view')->share('mainTitleIcon', 'fa-bullseye');

                $this->piggyRepos = app(PiggyBankRepositoryInterface::class);

                return $next($request);
            }
        );
    }

    /**
     * Show a single piggy bank.
     *
     * @param PiggyBank $piggyBank
     *
     * @return Factory|View
     */
    public function show(PiggyBank $piggyBank)
    {
        /** @var Carbon $end */
        $end = session('end', Carbon::now()->endOfMonth());
        // transform piggies using the transformer:
        $parameters = new ParameterBag;
        $parameters->set('end', $end);

        /** @var PiggyBankTransformer $transformer */
        $transformer = app(PiggyBankTransformer::class);
        $transformer->setParameters($parameters);
        $piggy       = $transformer->transform($piggyBank);
        $events      = $this->piggyRepos->getEvents($piggyBank);
        $subTitle    = $piggyBank->name;
        $attachments = $this->piggyRepos->getAttachments($piggyBank);

        return view('piggy-banks.show', compact('piggyBank', 'events', 'subTitle', 'piggy', 'attachments'));
    }
}
