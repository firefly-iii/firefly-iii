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
use FireflyIII\Models\Account;

/**
 * Class ImportEntry
 *
 * @package FireflyIII\Import
 */
class ImportEntry
{
    /** @var  Account */
    public $assetAccount;

    /**
     * @param $role
     * @param $value
     *
     * @throws FireflyException
     */
    public function fromRawValue($role, $value)
    {
        switch ($role) {
            default:
                throw new FireflyException('Cannot handle role of type "' . $role . '".');

        }
    }
}