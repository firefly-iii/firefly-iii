<?php
declare(strict_types=1);

namespace FireflyIII\Http\Controllers\PiggyBank;


use Carbon\Carbon;
use FireflyIII\Helpers\Attachments\AttachmentHelperInterface;
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Http\Requests\PiggyBankFormRequest;
use FireflyIII\Repositories\PiggyBank\PiggyBankRepositoryInterface;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Redirector;
use Illuminate\View\View;

/**
 * Class CreateController
 */
class CreateController extends Controller
{
    private AttachmentHelperInterface    $attachments;
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

                $this->attachments = app(AttachmentHelperInterface::class);
                $this->piggyRepos  = app(PiggyBankRepositoryInterface::class);

                return $next($request);
            }
        );
    }

    /**
     * Create a piggy bank.
     *
     * @return Factory|View
     */
    public function create()
    {
        $subTitle     = (string) trans('firefly.new_piggy_bank');
        $subTitleIcon = 'fa-plus';

        // put previous url in session if not redirect from store (not "create another").
        if (true !== session('piggy-banks.create.fromStore')) {
            $this->rememberPreviousUri('piggy-banks.create.uri');
        }
        session()->forget('piggy-banks.create.fromStore');

        return view('piggy-banks.create', compact('subTitle', 'subTitleIcon'));
    }


    /**
     * Store a new piggy bank.
     *
     * @param PiggyBankFormRequest $request
     *
     * @return RedirectResponse|Redirector
     */
    public function store(PiggyBankFormRequest $request)
    {
        $data = $request->getPiggyBankData();
        if (null === $data['startdate']) {
            $data['startdate'] = new Carbon;
        }
        $piggyBank = $this->piggyRepos->store($data);

        session()->flash('success', (string) trans('firefly.stored_piggy_bank', ['name' => $piggyBank->name]));
        app('preferences')->mark();

        // store attachment(s):
        /** @var array $files */
        $files = $request->hasFile('attachments') ? $request->file('attachments') : null;
        if (null !== $files && !auth()->user()->hasRole('demo')) {
            $this->attachments->saveAttachmentsForModel($piggyBank, $files);
        }
        if (null !== $files && auth()->user()->hasRole('demo')) {
            session()->flash('info', (string) trans('firefly.no_att_demo_user'));
        }

        if (count($this->attachments->getMessages()->get('attachments')) > 0) {
            $request->session()->flash('info', $this->attachments->getMessages()->get('attachments')); // @codeCoverageIgnore
        }


        $redirect = redirect($this->getPreviousUri('piggy-banks.create.uri'));

        if (1 === (int) $request->get('create_another')) {
            // @codeCoverageIgnoreStart
            session()->put('piggy-banks.create.fromStore', true);

            $redirect = redirect(route('piggy-banks.create'))->withInput();
            // @codeCoverageIgnoreEnd
        }

        return $redirect;
    }
}
