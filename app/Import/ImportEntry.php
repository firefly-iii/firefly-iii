<?php
/**
 * ImportEntry.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

declare(strict_types = 1);

namespace FireflyIII\Import;

use FireflyIII\Exceptions\FireflyException;
use Log;

/**
 * Class ImportEntry
 *
 * @package FireflyIII\Import
 */
class ImportEntry
{
    public $amount;




    /**
     * @param string $role
     * @param string $value
     * @param int    $certainty
     * @param        $convertedValue
     *
     * @throws FireflyException
     */
    public function importValue(string $role, string $value, int $certainty, $convertedValue)
    {
        Log::debug('Going to import', ['role' => $role, 'value' => $value, 'certainty' => $certainty]);

        switch ($role) {
            default:
                Log::error('Import entry cannot handle object.', ['role' => $role]);
                throw new FireflyException('Import entry cannot handle object of type "' . $role . '".');
                break;

            case 'amount':
                /*
                 * Easy enough.
                 */
                $this->setAmount($convertedValue);

                return;
        }
    }

    /**
     * @param float $amount
     */
    private function setAmount(float $amount)
    {
        $this->amount = $amount;
    }
}