<?php
/**
 * RenameMetaFields.php
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
 * RenameMetaFields.php
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

use DB;
use Illuminate\Console\Command;

/**
 * Class RenameMetaFields
 */
class RenameMetaFields extends Command
{
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Rename changed meta fields.';
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'firefly-iii:rename-meta-fields';

    /** @var int */
    private $count;

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        $this->count = 0;
        $start       = microtime(true);

        $changes = [
            'original-source' => 'original_source',
            'importHash'      => 'import_hash',
            'importHashV2'    => 'import_hash_v2',
            'sepa-cc'         => 'sepa_cc',
            'sepa-ct-op'      => 'sepa_ct_op',
            'sepa-ct-id'      => 'sepa_ct_id',
            'sepa-db'         => 'sepa_db',
            'sepa-country'    => 'sepa_country',
            'sepa-ep'         => 'sepa_ep',
            'sepa-ci'         => 'sepa_ci',
            'sepa-batch-id'   => 'sepa_batch_id',
        ];
        foreach ($changes as $original => $update) {
            $this->rename($original, $update);
        }
        if (0 === $this->count) {
            $this->line('All meta fields are correct.');
        }
        if (0 !== $this->count) {
            $this->line(sprintf('Renamed %d meta field(s).', $this->count));
        }

        $end = round(microtime(true) - $start, 2);
        $this->info(sprintf('Renamed meta fields in %s seconds', $end));

        return 0;
    }

    /**
     * @param string $original
     * @param string $update
     */
    private function rename(string $original, string $update): void
    {
        $count       = DB::table('journal_meta')
                         ->where('name', '=', $original)
                         ->update(['name' => $update]);
        $this->count += $count;
    }
}
