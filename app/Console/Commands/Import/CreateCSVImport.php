<?php
/**
 * CreateCSVImport.php
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

/** @noinspection MultipleReturnStatementsInspection */

declare(strict_types=1);

namespace FireflyIII\Console\Commands\Import;

use Exception;
use FireflyIII\Console\Commands\VerifiesAccessToken;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Import\Routine\RoutineInterface;
use FireflyIII\Import\Storage\ImportArrayStorage;
use FireflyIII\Models\ImportJob;
use FireflyIII\Repositories\ImportJob\ImportJobRepositoryInterface;
use FireflyIII\Repositories\User\UserRepositoryInterface;
use FireflyIII\User;
use Illuminate\Console\Command;
use Log;

/**
 * Class CreateCSVImport.
 *
 * @deprecated
 * @codeCoverageIgnore
 */
class CreateCSVImport extends Command
{
    use VerifiesAccessToken;
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Use this command to create a new CSV file import.';
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature
        = 'firefly-iii:csv-import
                            {file? : The CSV file to import.}
                            {configuration? : The configuration file to use for the import.}
                            {--user=1 : The user ID that the import should import for.}
                            {--token= : The user\'s access token.}';
    /**
     * Run the command.
     */
    public function handle(): int
    {
        $this->error('This command is disabled.');
        return 1;
    }


}
