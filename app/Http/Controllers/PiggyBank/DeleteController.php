<?php
declare(strict_types=1);

namespace FireflyIII\Http\Controllers\PiggyBank;


use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Models\PiggyBank;
use FireflyIII\Repositories\PiggyBank\PiggyBankRepositoryInterface;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Class DeleteController
 */
class DeleteController extends Controller
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
     * Delete a piggy bank.
     *
     * @param PiggyBank $piggyBank
     *
     * @return Factory|View
     */
    public function delete(PiggyBank $piggyBank)
    {
        $subTitle = (string) trans('firefly.delete_piggy_bank', ['name' => $piggyBank->name]);

        // put previous url in session
        $this->rememberPreviousUri('piggy-banks.delete.uri');

        return view('piggy-banks.delete', compact('piggyBank', 'subTitle'));
    }

    /**
     * Destroy the piggy bank.
     *
     * @param PiggyBank $piggyBank
     *
     * @return RedirectResponse
     */
    public function destroy(PiggyBank $piggyBank): RedirectResponse
    {
        session()->flash('success', (string) trans('firefly.deleted_piggy_bank', ['name' => $piggyBank->name]));
        app('preferences')->mark();
        $this->piggyRepos->destroy($piggyBank);

        return redirect($this->getPreviousUri('piggy-banks.delete.uri'));
    }
}
