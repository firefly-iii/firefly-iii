<?php

namespace FireflyIII\Console\Commands\Correction;

use FireflyIII\Models\AccountType;
use FireflyIII\Models\Preference;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Support\Facades\Preferences;
use FireflyIII\User;
use Illuminate\Console\Command;

/**
 * Class FixFrontpageAccounts
 */
class FixFrontpageAccounts extends Command
{
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fixes a preference that may include deleted accounts or accounts of another type.';
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'firefly-iii:fix-frontpage-accounts';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        $start          = microtime(true);

        $users = User::get();
        /** @var User $user */
        foreach ($users as $user) {
            $preference = Preferences::getForUser($user, 'frontPageAccounts', null);
            if (null !== $preference) {
                $this->fixPreference($preference);
            }
        }
        $end = round(microtime(true) - $start, 2);
        $this->info(sprintf('Verifying account preferences took %s seconds', $end));

        return 0;
    }

    /**
     * @param Preference $preference
     */
    private function fixPreference(Preference $preference): void
    {
        $fixed = [];
        /** @var AccountRepositoryInterface $repository */
        $repository = app(AccountRepositoryInterface::class);
        if (null === $preference->user) {
            return;
        }
        $repository->setUser($preference->user);
        $data = $preference->data;
        if (is_array($data)) {
            /** @var string $accountId */
            foreach ($data as $accountId) {
                $accountId = (int)$accountId;
                $account   = $repository->findNull($accountId);
                if (null !== $account) {
                    if (in_array($account->accountType->type, [AccountType::ASSET, AccountType::DEBT, AccountType::LOAN, AccountType::MORTGAGE], true)) {
                        $fixed[] = $account->id;
                        continue;
                    }
                }
            }
        }
        Preferences::setForUser($preference->user, 'frontPageAccounts', $fixed);
    }
}
