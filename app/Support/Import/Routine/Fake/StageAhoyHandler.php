<?php
/**
 * StageAhoyHandler.php
 * Copyright (c) 2018 thegrumpydictator@gmail.com
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

declare(strict_types=1);

namespace FireflyIII\Support\Import\Routine\Fake;

use FireflyIII\Exceptions\FireflyException;
use Log;

/**
 * @codeCoverageIgnore
 * Class StageAhoyHandler
 */
class StageAhoyHandler
{
    /**
     * @throws FireflyException
     */
    public function run(): void
    {
        for ($i = 0; $i < 5; $i++) {
            Log::debug(sprintf('Am now in stage AHOY hander, sleeping... (%d)', $i));
            sleep(1);
        }
    }

}
