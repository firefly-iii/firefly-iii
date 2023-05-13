<?php

declare(strict_types=1);

namespace FireflyIII\Console\Commands\Correction;

use FireflyIII\Models\Account;
use FireflyIII\Services\Internal\Support\CreditRecalculateService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Class CorrectionSkeleton
 * TODO DONT FORGET TO ADD THIS TO THE DOCKER BUILD
 */
class TriggerCreditCalculation extends Command
{
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Triggers the credit recalculation service for liabilities.';
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'firefly-iii:trigger-credit-recalculation';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        $this->processAccounts();
        return 0;
    }

    /**
     * @param  Account  $account
     * @return void
     */
    private function processAccount(Account $account): void
    {
        /** @var CreditRecalculateService $object */
        $object = app(CreditRecalculateService::class);
        $object->setAccount($account);
        $object->recalculate();
    }

    private function processAccounts(): void
    {
        $accounts = Account::leftJoin('account_types', 'accounts.account_type_id', 'account_types.id')
                           ->whereIn('account_types.type', config('firefly.valid_liabilities'))
                           ->get(['accounts.*']);
        foreach ($accounts as $account) {
            Log::debug(sprintf('Processing account #%d ("%s")', $account->id, $account->name));
            $this->processAccount($account);
        }
    }
}
