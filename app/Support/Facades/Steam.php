<?php
/**
 * Steam.php
 * Copyright (c) 2019 thegrumpydictator@gmail.com
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

namespace FireflyIII\Support\Facades;

use Carbon\Carbon;
use FireflyIII\Models\Account;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Facade;

/**
 * Class Steam.
 *
 * @method string balance(Account $account, Carbon $date)
 * @method string balanceIgnoreVirtual(Account $account, Carbon $date)
 * @method array balanceInRange(Account $account, Carbon $start, Carbon $end)
 * @method array balancesByAccounts(Collection $accounts, Carbon $date)
 * @method decrypt(int $isEncrypted, string $value)
 * @method array getLastActivities(array $accounts)
 * @method string negative(string $amount)
 * @method string|null opposite(string $amount = null)
 * @method int phpBytes(string $string)
 * @method string positive(string $amount)
 * @method array balancesPerCurrencyByAccounts(Collection $accounts, Carbon $date)
 *
 * @codeCoverageIgnore
 */
class Steam extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return 'steam';
    }
}
