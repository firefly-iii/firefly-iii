<?php
/**
 * CreateLinkTypes.php
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

namespace FireflyIII\Console\Commands\Correction;

use FireflyIII\Models\LinkType;
use Illuminate\Console\Command;

/**
 * Class CreateLinkTypes. Created all link types in case a migration hasn't fired.
 */
class CreateLinkTypes extends Command
{
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Creates all link types.';
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'firefly-iii:create-link-types';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle(): int
    {
        $start = microtime(true);
        $count = 0;
        $set   = [
            'Related'       => ['relates to', 'relates to'],
            'Refund'        => ['(partially) refunds', 'is (partially) refunded by'],
            'Paid'          => ['(partially) pays for', 'is (partially) paid for by'],
            'Reimbursement' => ['(partially) reimburses', 'is (partially) reimbursed by'],
        ];
        foreach ($set as $name => $values) {
            $link = LinkType::where('name', $name)
                            ->first();
            if (null === $link) {
                $link          = new LinkType;
                $link->name    = $name;
                $link->inward  = $values[1];
                $link->outward = $values[0];
                ++$count;
                $this->line(sprintf('Created missing link type "%s"', $name));
            }
            $link->editable = false;
            $link->save();
        }
        if (0 === $count) {
            $this->info('All link types OK!');
        }
        $end = round(microtime(true) - $start, 2);
        $this->info(sprintf('Verified link types in %s seconds', $end));

        return 0;
    }
}
