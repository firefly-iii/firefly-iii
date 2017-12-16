<?php
/**
 * BankController.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
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
 * along with Firefly III.  If not, see <http://www.gnu.org/licenses/>.
 */
declare(strict_types=1);

namespace FireflyIII\Http\Controllers\Import;

use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Repositories\ImportJob\ImportJobRepositoryInterface;
use FireflyIII\Support\Import\Prerequisites\PrerequisitesInterface;
use Illuminate\Http\Request;
use Log;
use Session;

class BankController extends Controller
{

    /**
     * Once there are no prerequisites, this method will create an importjob object and
     * redirect the user to a view where this object can be used by a bank specific
     * class to process.
     *
     * @param ImportJobRepositoryInterface $repository
     * @param string                       $bank
     *
     * @return \Illuminate\Http\RedirectResponse|null
     * @throws FireflyException
     */
    public function createJob(ImportJobRepositoryInterface $repository, string $bank)
    {
        $class = config(sprintf('firefly.import_pre.%s', $bank));
        if (!class_exists($class)) {
            throw new FireflyException(sprintf('Cannot find class %s', $class));
        }
        $importJob                 = $repository->create($bank);
        $config                    = $importJob->configuration;
        $config['has-config-file'] = false;
        $config['auto-start']      = true;
        $importJob->configuration  = $config;
        $importJob->save();

        return redirect(route('import.file.configure', [$importJob->key]));
    }



}
