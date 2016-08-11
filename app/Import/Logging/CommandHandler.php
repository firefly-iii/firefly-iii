<?php
/**
 * Handler.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

declare(strict_types = 1);

namespace FireflyIII\Import\Logging;

use Illuminate\Console\Command;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Logger;

/**
 * Class CommandHandler
 *
 * @package FireflyIII\Import\Logging
 */
class CommandHandler extends AbstractProcessingHandler
{

    /** @var  Command */
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
        $this->changeLevel(env('LOG_LEVEL', 'debug'));
    }

    /**
     * Writes the record down to the log of the implementing handler
     *
     * @param  array $record
     *
     * @return void
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
        switch ($level) {
            case 'debug':
                $this->setLevel(Logger::DEBUG);
                break;
            case 'info':
                $this->setLevel(Logger::INFO);
                break;
            case 'notice':
                $this->setLevel(Logger::NOTICE);
                break;
            case 'warning':
                $this->setLevel(Logger::WARNING);
                break;
            case 'error':
                $this->setLevel(Logger::ERROR);
                break;
            case 'critical':
                $this->setLevel(Logger::CRITICAL);
                break;
            case 'alert':
                $this->setLevel(Logger::ALERT);
                break;
            case 'emergency':
                $this->setLevel(Logger::EMERGENCY);
                break;
        }
    }
}