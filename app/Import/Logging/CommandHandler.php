<?php
/**
 * CommandHandler.php
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

namespace FireflyIII\Import\Logging;

use Illuminate\Console\Command;
use Monolog\Handler\AbstractProcessingHandler;

/**
 * Class CommandHandler.
 */
class CommandHandler extends AbstractProcessingHandler
{
    /** @var Command */
    private $command;

    /**
     * Handler constructor.
     *
     * @param Command $command
     */
    public function __construct(Command $command)
    {
        parent::__construct();
        $this->command = $command;

        $this->changeLevel(env('APP_LOG_LEVEL', 'info'));
    }

    /**
     * Writes the record down to the log of the implementing handler.
     *
     * @param array $record
     */
    protected function write(array $record)
    {
        $this->command->line((string)trim($record['formatted']));
    }

    /**
     * @param string $level
     */
    private function changeLevel(string $level)
    {
        $level     = strtoupper($level);
        $reference = sprintf('\Monolog\Logger::%s', $level);
        if (defined($reference)) {
            $this->setLevel(constant($reference));
        }
    }
}
