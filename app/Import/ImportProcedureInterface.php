<?php
/**
 * ImportProcedureInterface.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types=1);

namespace FireflyIII\Import;

use FireflyIII\Models\ImportJob;
use Illuminate\Support\Collection;

/**
 * Interface ImportProcedureInterface
 *
 * @package FireflyIII\Import
 */
interface ImportProcedureInterface
{
    /**
     * @param ImportJob $job
     *
     * @return Collection
     */
    public function runImport(ImportJob $job): Collection;

}
