<?php
declare(strict_types=1);

namespace FireflyIII\Http\Controllers\PiggyBank;


use FireflyIII\Helpers\Attachments\AttachmentHelperInterface;
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Http\Requests\PiggyBankFormRequest;
use FireflyIII\Models\PiggyBank;
use FireflyIII\Repositories\PiggyBank\PiggyBankRepositoryInterface;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Redirector;
use Illuminate\View\View;

/**
 * Class EditController
 */
class EditController extends Controller
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
     * Edit a piggy bank.
     *
     * @param PiggyBank $piggyBank
     *
     * @return Factory|View
     */
    public function edit(PiggyBank $piggyBank)
    {
        $subTitle     = (string) trans('firefly.update_piggy_title', ['name' => $piggyBank->name]);
        $subTitleIcon = 'fa-pencil';
        $targetDate   = null;
        $startDate    = null;
        $note         = $piggyBank->notes()->first();
        // Flash some data to fill the form.
        if (null !== $piggyBank->targetdate) {
            $targetDate = $piggyBank->targetdate->format('Y-m-d');
        }
        if (null !== $piggyBank->startdate) {
            $startDate = $piggyBank->startdate->format('Y-m-d');
        }

        $preFilled = ['name'         => $piggyBank->name,
                      'account_id'   => $piggyBank->account_id,
                      'targetamount' => $piggyBank->targetamount,
                      'targetdate'   => $targetDate,
                      'startdate'    => $startDate,
                      'object_group' => $piggyBank->objectGroups->first() ? $piggyBank->objectGroups->first()->title : '',
                      'notes'        => null === $note ? '' : $note->text,
        ];
        session()->flash('preFilled', $preFilled);

        // put previous url in session if not redirect from store (not "return_to_edit").
        if (true !== session('piggy-banks.edit.fromUpdate')) {
            $this->rememberPreviousUri('piggy-banks.edit.uri');
        }
        session()->forget('piggy-banks.edit.fromUpdate');

        return view('piggy-banks.edit', compact('subTitle', 'subTitleIcon', 'piggyBank', 'preFilled'));
    }


    /**
     * Update a piggy bank.
     *
     * @param PiggyBankFormRequest $request
     * @param PiggyBank            $piggyBank
     *
     * @return RedirectResponse|Redirector
     */
    public function update(PiggyBankFormRequest $request, PiggyBank $piggyBank)
    {
        $data      = $request->getPiggyBankData();
        $piggyBank = $this->piggyRepos->update($piggyBank, $data);

        session()->flash('success', (string) trans('firefly.updated_piggy_bank', ['name' => $piggyBank->name]));
        app('preferences')->mark();

        // store new attachment(s):
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


        $redirect = redirect($this->getPreviousUri('piggy-banks.edit.uri'));

        if (1 === (int) $request->get('return_to_edit')) {
            // @codeCoverageIgnoreStart
            session()->put('piggy-banks.edit.fromUpdate', true);

            $redirect = redirect(route('piggy-banks.edit', [$piggyBank->id]));
            // @codeCoverageIgnoreEnd
        }

        return $redirect;
    }

}
