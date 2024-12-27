<?php

/**
 * RenameMetaFields.php
 * Copyright (c) 2020 james@firefly-iii.org
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

namespace FireflyIII\Console\Commands\Correction;

use FireflyIII\Console\Commands\ShowsFriendlyMessages;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CorrectsMetaDataFields extends Command
{
    use ShowsFriendlyMessages;

    protected $description = 'Rename changed meta fields.';
    protected $signature   = 'correction:meta-fields';

    private int $count     = 0;

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
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
            'external_uri'    => 'external_url',
        ];
        foreach ($changes as $original => $update) {
            $this->rename($original, $update);
        }
        if (0 !== $this->count) {
            $this->friendlyInfo(sprintf('Renamed %d meta field(s).', $this->count));
        }

        return 0;
    }

    private function rename(string $original, string $update): void
    {
        $total = DB::table('journal_meta')
            ->where('name', '=', $original)
            ->update(['name' => $update])
        ;
        $this->count += $total;
    }
}
