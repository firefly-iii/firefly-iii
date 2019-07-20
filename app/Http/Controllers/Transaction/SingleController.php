<?php
/**
 * SingleController.php
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
 * along with Firefly III. If not, see <http://www.gnu.org/licenses/>.
 */
declare(strict_types=1);

namespace FireflyIII\Http\Controllers\Transaction;

use Carbon\Carbon;
use FireflyIII\Events\StoredTransactionGroup;
use FireflyIII\Events\UpdatedTransactionGroup;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Helpers\Attachments\AttachmentHelperInterface;
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Http\Requests\JournalFormRequest;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Models\TransactionJournalMeta;
use FireflyIII\Models\TransactionType;
use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use FireflyIII\Support\Http\Controllers\ModelInformation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Log;
use View;

/**
 * Class SingleController.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class SingleController extends Controller
{

    use ModelInformation;

    /** @var AttachmentHelperInterface The attachment helper. */
    private $attachments;
    /** @var BudgetRepositoryInterface The budget repository */
    private $budgets;
    /** @var JournalRepositoryInterface Journals and transactions overview */
    private $repository;

    /**
     * SingleController constructor.
     */
    public function __construct()
    {
        throw new FireflyException('Do not use me.');
        parent::__construct();

        $maxFileSize = app('steam')->phpBytes(ini_get('upload_max_filesize'));
        $maxPostSize = app('steam')->phpBytes(ini_get('post_max_size'));
        $uploadSize  = min($maxFileSize, $maxPostSize);
        app('view')->share('uploadSize', $uploadSize);

        // some useful repositories:
        $this->middleware(
            function ($request, $next) {
                $this->budgets     = app(BudgetRepositoryInterface::class);
                $this->attachments = app(AttachmentHelperInterface::class);
                $this->repository  = app(JournalRepositoryInterface::class);

                app('view')->share('title', (string)trans('firefly.transactions'));
                app('view')->share('mainTitleIcon', 'fa-repeat');

                return $next($request);
            }
        );
    }

}
