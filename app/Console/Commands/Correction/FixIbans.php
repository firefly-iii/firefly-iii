<?php

namespace FireflyIII\Console\Commands\Correction;

use FireflyIII\Models\Account;
use Illuminate\Console\Command;

/**
 * Class FixIbans
 */
class FixIbans extends Command
{
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Removes spaces from IBANs';
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'firefly-iii:fix-ibans';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        $accounts = Account::whereNotNull('iban')->get();
        /** @var Account $account */
        foreach ($accounts as $account) {
            $iban = $account->iban;
            if (str_contains($iban, ' ')) {

                $iban = app('steam')->filterSpaces((string)$account->iban);
                if ('' !== $iban) {
                    $account->iban = $iban;
                    $account->save();
                    $this->line(sprintf('Removed spaces from IBAN of account #%d', $account->id));
                }
            }
        }

        return 0;
    }
}
