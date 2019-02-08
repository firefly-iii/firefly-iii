<?php
declare(strict_types=1);

namespace FireflyIII\Support\Logging;

/**
 * Class AuditLogger
 */
class AuditLogger
{
    /**
     * Customize the given logger instance.
     *
     * @param  \Illuminate\Log\Logger $logger
     *
     * @return void
     */
    public function __invoke($logger)
    {
        $processor  = new AuditProcessor;
        $logger->pushProcessor($processor);
    }
}