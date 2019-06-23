<?php
/**
 * TransactionController.php
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
/** @noinspection CallableParameterUseCaseInTypeContextInspection */
/** @noinspection MoreThanThreeArgumentsInspection */
declare(strict_types=1);

namespace FireflyIII\Http\Controllers;

use Carbon\Carbon;
use FireflyIII\Repositories\Attachment\AttachmentRepositoryInterface;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use FireflyIII\Support\Http\Controllers\ModelInformation;
use FireflyIII\Support\Http\Controllers\PeriodOverview;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Log;

/**
 * Class TransactionController.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class TransactionController extends Controller
{
    use ModelInformation, PeriodOverview;
    /** @var AttachmentRepositoryInterface */
    private $attachmentRepository;
    /** @var JournalRepositoryInterface Journals and transactions overview */
    private $repository;

    /**
     * TransactionController constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->middleware(
            function ($request, $next) {
                app('view')->share('title', (string)trans('firefly.transactions'));
                app('view')->share('mainTitleIcon', 'fa-repeat');
                $this->repository           = app(JournalRepositoryInterface::class);
                $this->attachmentRepository = app(AttachmentRepositoryInterface::class);

                return $next($request);
            }
        );
    }

    /**
     * Reorder transactions.
     *
     * @param Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function reorder(Request $request): JsonResponse
    {
        $ids  = $request->get('items');
        $date = new Carbon($request->get('date'));
        if (count($ids) > 0) {
            $order = 0;
            $ids   = array_unique($ids);
            foreach ($ids as $id) {
                $journal = $this->repository->findNull((int)$id);
                if (null !== $journal && $journal->date->isSameDay($date)) {
                    $this->repository->setOrder($journal, $order);
                    ++$order;
                }
            }
        }
        app('preferences')->mark();

        return response()->json([true]);
    }


}
