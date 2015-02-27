<?php namespace FireflyIII\Http\Controllers;

use Amount;
use Auth;
use FireflyIII\Http\Requests;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Models\TransactionGroup;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use Illuminate\Support\Collection;
use Response;
use Input;

/**
 * Class RelatedController
 *
 * @package FireflyIII\Http\Controllers
 */
class RelatedController extends Controller
{

    /**
     *
     * @param TransactionJournal $journal
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function alreadyRelated(TransactionJournal $journal)
    {
        $ids = [];
        /** @var TransactionGroup $group */
        foreach ($journal->transactiongroups()->get() as $group) {
            /** @var TransactionJournal $loopJournal */
            foreach ($group->transactionjournals()->get() as $loopJournal) {
                if ($loopJournal->id != $journal->id) {
                    $ids[] = $loopJournal->id;
                }
            }
        }
        $unique = array_unique($ids);
        if (count($unique) > 0) {

            $set = Auth::user()->transactionjournals()->whereIn('id', $unique)->get();
            $set->each(
                function (TransactionJournal $journal) {
                    /** @var Transaction $t */
                    foreach ($journal->transactions()->get() as $t) {
                        if ($t->amount > 0) {
                            $journal->amount = $t->amount;
                        }
                    }

                }
            );

            return Response::json($set->toArray());
        } else {
            return Response::json((new Collection)->toArray());
        }
    }

    /**
     * @param TransactionJournal $parentJournal
     * @param TransactionJournal $childJournal
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function relate(TransactionJournal $parentJournal, TransactionJournal $childJournal)
    {
        $group           = new TransactionGroup;
        $group->relation = 'balance';
        $group->user_id  = Auth::user()->id;
        $group->save();
        $group->transactionjournals()->save($parentJournal);
        $group->transactionjournals()->save($childJournal);

        return Response::json(true);

    }

    /**
     * @param TransactionJournal $journal
     *
     * @return \Illuminate\View\View
     */
    public function related(TransactionJournal $journal)
    {
        $groups  = $journal->transactiongroups()->get();
        $members = new Collection;
        /** @var TransactionGroup $group */
        foreach ($groups as $group) {
            /** @var TransactionJournal $loopJournal */
            foreach ($group->transactionjournals()->get() as $loopJournal) {
                if ($loopJournal->id != $journal->id) {
                    $members->push($loopJournal);
                }
            }
        }

        return view('related.relate', compact('journal', 'members'));
    }

    /**
     * @SuppressWarnings("CyclomaticComplexity") // It's exactly 5. So I don't mind.
     *
     * @param TransactionJournal $parentJournal
     * @param TransactionJournal $childJournal
     *
     * @return \Illuminate\Http\JsonResponse
     * @throws Exception
     */
    public function removeRelation(TransactionJournal $parentJournal, TransactionJournal $childJournal)
    {
        $groups = $parentJournal->transactiongroups()->get();
        /** @var TransactionGroup $group */
        foreach ($groups as $group) {
            foreach ($group->transactionjournals()->get() as $loopJournal) {
                if ($loopJournal->id == $childJournal->id) {
                    // remove from group:
                    $group->transactionjournals()->detach($childJournal);
                }
            }
            if ($group->transactionjournals()->count() == 1) {
                $group->delete();
            }
        }

        return Response::json(true);
    }

    /**
     * @param TransactionJournal $journal
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function search(TransactionJournal $journal, JournalRepositoryInterface $repository)
    {

        $search = e(trim(Input::get('searchValue')));

        $result = $repository->searchRelated($search, $journal);
        $result->each(
            function (TransactionJournal $journal) {
                /** @var Transaction $t */
                foreach ($journal->transactions()->get() as $t) {
                    if ($t->amount > 0) {
                        $journal->amount = $t->amount;
                    }
                }
            }
        );

        return Response::json($result->toArray());
    }

}
