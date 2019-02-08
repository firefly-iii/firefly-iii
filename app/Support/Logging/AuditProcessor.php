<?php
declare(strict_types=1);

namespace FireflyIII\Support\Logging;

/**
 * Class AuditProcessor
 */
class AuditProcessor
{
    /**
     * @param array $record
     *
     * @return array
     */
    public function __invoke(array $record): array
    {
        $record['extra']['path'] = request()->method() . ':' . request()->url();

        $record['extra']['IP'] = app('request')->ip();
        if (auth()->check()) {
            $record['extra']['user'] = auth()->user()->email;
        }


        return $record;
    }
}