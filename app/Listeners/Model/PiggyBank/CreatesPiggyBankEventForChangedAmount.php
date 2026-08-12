<?php




declare(strict_types=1);



namespace FireflyIII\Listeners\Model\PiggyBank;

use FireflyIII\Events\Model\PiggyBank\PiggyBankAmountIsChanged;
use FireflyIII\Models\PiggyBankEvent;
use FireflyIII\Models\TransactionGroup;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class CreatesPiggyBankEventForChangedAmount implements ShouldQueue
{
    public function handle(PiggyBankAmountIsChanged $event): void
    {
        // find journal if group is present.
        $journal = $event->transactionJournal;
        if ($event->transactionGroup instanceof TransactionGroup) {
            $journal = $event->transactionGroup->transactionJournals()->first();
        }
        $date    = $journal->date ?? today(config('app.timezone'));
        // sanity check: event must not already exist for this journal and piggy bank.
        if (null !== $journal) {
            $exists = PiggyBankEvent::query()->where('piggy_bank_id', $event->piggyBank->id)->where('transaction_journal_id', $journal->id)->exists();
            if ($exists) {
                Log::warning('Already have event for this journal and piggy, will not create another.');

                return;
            }
        }

        PiggyBankEvent::create([
            'piggy_bank_id'          => $event->piggyBank->id,
            'transaction_journal_id' => $journal?->id,
            'date'                   => $date->format('Y-m-d'),
            'date_tz'                => $date->format('e'),
            'amount'                 => $event->amount,
        ]);
    }
}
