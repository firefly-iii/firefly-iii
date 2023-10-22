<?php
declare(strict_types=1);

namespace FireflyIII\Http\Controllers\TransactionCurrency;

use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Repositories\Currency\CurrencyRepositoryInterface;
use FireflyIII\Repositories\User\UserRepositoryInterface;
use FireflyIII\User;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class IndexController extends Controller
{
    protected CurrencyRepositoryInterface $repository;
    protected UserRepositoryInterface     $userRepository;

    /**
     * CurrencyController constructor.
     *

     */
    public function __construct()
    {
        parent::__construct();

        $this->middleware(
            function ($request, $next) {
                app('view')->share('title', (string)trans('firefly.currencies'));
                app('view')->share('mainTitleIcon', 'fa-usd');
                $this->repository     = app(CurrencyRepositoryInterface::class);
                $this->userRepository = app(UserRepositoryInterface::class);

                return $next($request);
            }
        );
    }

    /**
     * Show overview of currencies.
     *
     * @param Request $request
     *
     * @return Factory|View
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function index(Request $request)
    {
        /** @var User $user */
        $user           = auth()->user();
        $page           = 0 === (int)$request->get('page') ? 1 : (int)$request->get('page');
        $pageSize       = (int)app('preferences')->get('listPageSize', 50)->data;
        $collection     = $this->repository->getAll();
        $total          = $collection->count();
        $collection     = $collection->slice(($page - 1) * $pageSize, $pageSize);

        // order so default is on top:
        $collection = $collection->sortBy(
            function (TransactionCurrency $currency) {
                $default = true === $currency->userDefault ? 0 : 1;
                $enabled = true === $currency->userEnabled ? 0 : 1;
                return sprintf('%s-%s-%s',$default, $enabled, $currency->code);
            }
        );

        $currencies = new LengthAwarePaginator($collection, $total, $pageSize, $page);
        $currencies->setPath(route('currencies.index'));
        $isOwner         = true;
        if (!$this->userRepository->hasRole($user, 'owner')) {
            $request->session()->flash('info', (string)trans('firefly.ask_site_owner', ['owner' => config('firefly.site_owner')]));
            $isOwner = false;
        }

        return view('currencies.index', compact('currencies',  'isOwner'));
    }

}
