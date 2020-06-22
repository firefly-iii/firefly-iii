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

        // delete if wrong UUID:
        if (null !== $config && 'b2c27d92-be90-5c10-8589-005df5b314e6' === $config->data) {
            $config = null;
        }

        if (null === $config) {
            $uuid4    = Uuid::uuid4();
            $uniqueId = (string) $uuid4;
            Log::info(sprintf('Created Firefly III installation ID %s', $uniqueId));
            app('fireflyconfig')->set('installation_id', $uniqueId);
        }
    }

}
