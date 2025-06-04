<?php

/**
 * EditController.php
 * Copyright (c) 2020 james@firefly-iii.org
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

namespace FireflyIII\Http\Controllers\PiggyBank;

use FireflyIII\Helpers\Attachments\AttachmentHelperInterface;
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Http\Requests\PiggyBankUpdateRequest;
use FireflyIII\Models\PiggyBank;
use FireflyIII\Repositories\PiggyBank\PiggyBankRepositoryInterface;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\Log;
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

    public function resetHistory(PiggyBank $piggyBank): RedirectResponse {
        $this->piggyRepos->resetHistory($piggyBank);
        session()->flash('success', (string) trans('firefly.piggy_history_reset'));
        return redirect(route('piggy-banks.show', [$piggyBank->id]));
    }

    /**
     * Edit a piggy bank.
     *
     * @return Factory|View
     */
    public function edit(PiggyBank $piggyBank)
    {
        $subTitle     = (string) trans('firefly.update_piggy_title', ['name' => $piggyBank->name]);
        $subTitleIcon = 'fa-pencil';
        $note         = $piggyBank->notes()->first();
        // Flash some data to fill the form.
        $targetDate   = $piggyBank->target_date?->format('Y-m-d');
        $startDate    = $piggyBank->start_date?->format('Y-m-d');

        $preFilled    = [
            'name'                    => $piggyBank->name,
            'transaction_currency_id' => (int) $piggyBank->transaction_currency_id,
            'target_amount'           => app('steam')->bcround($piggyBank->target_amount, $piggyBank->transactionCurrency->decimal_places),
            'target_date'             => $targetDate,
            'start_date'              => $startDate,
            'accounts'                => [],
            'object_group'            => null !== $piggyBank->objectGroups->first() ? $piggyBank->objectGroups->first()->title : '',
            'notes'                   => null === $note ? '' : $note->text,
        ];
        foreach ($piggyBank->accounts as $account) {
            $preFilled['accounts'][] = $account->id;
        }
        if (0 === bccomp($piggyBank->target_amount, '0')) {
            $preFilled['target_amount'] = '';
        }
        session()->flash('preFilled', $preFilled);

        // put previous url in session if not redirect from store (not "return_to_edit").
        if (true !== session('piggy-banks.edit.fromUpdate')) {
            $this->rememberPreviousUrl('piggy-banks.edit.url');
        }
        session()->forget('piggy-banks.edit.fromUpdate');

        return view('piggy-banks.edit', compact('subTitle', 'subTitleIcon', 'piggyBank', 'preFilled'));
    }

    /**
     * Update a piggy bank.
     *
     * @return Redirector|RedirectResponse
     */
    public function update(PiggyBankUpdateRequest $request, PiggyBank $piggyBank)
    {
        $data      = $request->getPiggyBankData();
        $piggyBank = $this->piggyRepos->update($piggyBank, $data);

        session()->flash('success', (string) trans('firefly.updated_piggy_bank', ['name' => $piggyBank->name]));
        app('preferences')->mark();

        // store new attachment(s):
        /** @var null|array $files */
        $files     = $request->hasFile('attachments') ? $request->file('attachments') : null;
        if (null !== $files && !auth()->user()->hasRole('demo')) {
            $this->attachments->saveAttachmentsForModel($piggyBank, $files);
        }
        if (null !== $files && auth()->user()->hasRole('demo')) {
            Log::channel('audit')->warning(sprintf('The demo user is trying to upload attachments in %s.', __METHOD__));
            session()->flash('info', (string) trans('firefly.no_att_demo_user'));
        }

        if (count($this->attachments->getMessages()->get('attachments')) > 0) {
            $request->session()->flash('info', $this->attachments->getMessages()->get('attachments'));
        }
        $redirect  = redirect($this->getPreviousUrl('piggy-banks.edit.url'));

        if (1 === (int) $request->get('return_to_edit')) {
            session()->put('piggy-banks.edit.fromUpdate', true);

            $redirect = redirect(route('piggy-banks.edit', [$piggyBank->id]));
        }

        return $redirect;
    }
}
