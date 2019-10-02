<?php
/**
 * RemoveBills.php
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
/**
 * RemoveBills.php
 * Copyright (c) 2019 thegrumpydictator@gmail.com
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

namespace FireflyIII\Console\Commands\Correction;

use FireflyIII\Models\TransactionJournal;
use FireflyIII\Models\TransactionType;
use Illuminate\Console\Command;

/**
 * Class RemoveBills
 */
class RemoveBills extends Command
{
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove bills from transactions that shouldn\'t have one.';
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'firefly-iii:remove-bills';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle(): int
    {
        $start = microtime(true);
        /** @var TransactionType $withdrawal */
        $withdrawal = TransactionType::where('type', TransactionType::WITHDRAWAL)->first();
        $journals   = TransactionJournal::whereNotNull('bill_id')->where('transaction_type_id', '!=', $withdrawal->id)->get();
        /** @var TransactionJournal $journal */
        foreach ($journals as $journal) {
            $this->line(sprintf('Transaction journal #%d should not be linked to bill #%d.', $journal->id, $journal->bill_id));
            $journal->bill_id = null;
            $journal->save();
        }
        if (0 === $journals->count()) {
            $this->info('All transaction journals have correct bill information.');
        }
        if ($journals->count() > 0) {
            $this->info('Fixed all transaction journals so they have correct bill information.');
        }
        $end = round(microtime(true) - $start, 2);
        $this->info(sprintf('Verified bills / journals in %s seconds', $end));

        return 0;
    }
}
