<?php
declare(strict_types=1);

namespace FireflyIII\Support\System;

use Log;
use Ramsey\Uuid\Uuid;

/**
 * Trait GeneratesInstallationId
 */
trait GeneratesInstallationId
{
    /**
     *
     */
    protected function generateInstallationId(): void
    {
        $config = app('fireflyconfig')->get('installation_id', null);
        if (null === $config) {
            $uuid5    = Uuid::uuid5(Uuid::NAMESPACE_URL, 'firefly-iii.org');
            $uniqueId = (string) $uuid5;
            Log::info(sprintf('Created Firefly III installation ID %s', $uniqueId));
            app('fireflyconfig')->set('installation_id', $uniqueId);
        }
    }

}
