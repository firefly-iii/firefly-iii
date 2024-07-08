<?php
/**
 * ShowController.php
 * Copyright (c) 2019 james@firefly-iii.org
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

namespace FireflyIII\Http\Controllers\Recurring;

use Carbon\Carbon;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Models\Attachment;
use FireflyIII\Models\Recurrence;
use FireflyIII\Repositories\Attachment\AttachmentRepositoryInterface;
use FireflyIII\Repositories\Recurring\RecurringRepositoryInterface;
use FireflyIII\Support\Http\Controllers\GetConfigurationData;
use FireflyIII\Transformers\AttachmentTransformer;
use FireflyIII\Transformers\RecurrenceTransformer;
use Illuminate\Contracts\View\Factory;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * Class ShowController
 */
class ShowController extends Controller
{
    use GetConfigurationData;

    /** @var RecurringRepositoryInterface Recurring repository */
    private $recurring;

    /**
     * IndexController constructor.
     */
    public function __construct()
    {
        parent::__construct();
        app('view')->share('showCategory', true);

        // translations:
        $this->middleware(
            function ($request, $next) {
                app('view')->share('mainTitleIcon', 'fa-paint-brush');
                app('view')->share('title', (string)trans('firefly.recurrences'));

                $this->recurring = app(RecurringRepositoryInterface::class);

                return $next($request);
            }
        );
    }

    /**
     * Show a single recurring transaction.
     *
     * @return Factory|View
     *
     * @throws FireflyException
     */
    public function show(Recurrence $recurrence)
    {
        $repos                 = app(AttachmentRepositoryInterface::class);

        /** @var RecurrenceTransformer $transformer */
        $transformer           = app(RecurrenceTransformer::class);
        $transformer->setParameters(new ParameterBag());

        $array                 = $transformer->transform($recurrence);

        $groups                = $this->recurring->getTransactions($recurrence);
        $today                 = today(config('app.timezone'));
        $array['repeat_until'] = null !== $array['repeat_until'] ? new Carbon($array['repeat_until']) : null;

        // transform dates back to Carbon objects and expand information
        foreach ($array['repetitions'] as $index => $repetition) {
            foreach ($repetition['occurrences'] as $item => $occurrence) {
                $date                                               = (new Carbon($occurrence))->startOfDay();
                $set                                                = [
                    'date'  => $date,
                    'fired' => $this->recurring->createdPreviously($recurrence, $date)
                               || $this->recurring->getJournalCount($recurrence, $date) > 0,
                ];
                $array['repetitions'][$index]['occurrences'][$item] = $set;
            }
        }

        // add attachments to the recurrence object.
        $attachments           = $recurrence->attachments()->get();
        $array['attachments']  = [];
        $attachmentTransformer = app(AttachmentTransformer::class);

        /** @var Attachment $attachment */
        foreach ($attachments as $attachment) {
            $item                   = $attachmentTransformer->transform($attachment);
            $item['file_exists']    = $repos->exists($attachment); // TODO this should be part of the transformer
            $array['attachments'][] = $item;
        }

        $subTitle              = (string)trans('firefly.overview_for_recurrence', ['title' => $recurrence->title]);

        return view('recurring.show', compact('recurrence', 'subTitle', 'array', 'groups', 'today'));
    }
}
