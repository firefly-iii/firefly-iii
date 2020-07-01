<?php
declare(strict_types=1);

namespace FireflyIII\Console\Commands\Correction;

use DB;
use FireflyIII\Events\UpdatedTransactionGroup;
use FireflyIII\Handlers\Events\UpdatedGroupEventHandler;
use FireflyIII\Models\TransactionGroup;
use FireflyIII\Models\TransactionJournal;
use Illuminate\Console\Command;

/**
 * Class FixGroupAccounts
 */
class FixGroupAccounts extends Command
{
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Unify the source / destination accounts of split groups.';
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'firefly-iii:unify-group-accounts';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        // select transaction_group_id, count(transaction_group_id) as the_count from transaction_journals group by transaction_group_id having the_count > 1
        $groups = [];
        $res    = TransactionJournal
            ::groupBy('transaction_group_id')
            ->get(['transaction_group_id', DB::raw('COUNT(transaction_group_id) as the_count')]);
        foreach ($res as $journal) {
            if ((int) $journal->the_count > 1) {
                $groups[] = (int) $journal->transaction_group_id;
            }
        }
        $handler = new UpdatedGroupEventHandler;
        foreach($groups as $groupId) {
            $group = TransactionGroup::find($groupId);
            $event = new UpdatedTransactionGroup($group);
            $handler->unifyAccounts($event);
        }

        $this->line('Updated inconsistent transaction groups.');


        return 0;
    }
}
