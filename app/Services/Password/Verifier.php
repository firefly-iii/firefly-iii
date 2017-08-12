<?php
/**
 * Verifier.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 * This software may be modified and distributed under the terms of the Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types=1);

namespace FireflyIII\Services\Password;

/**
 * Interface Verifier
 *
 * @package FireflyIII\Services\Password
 */
interface Verifier
{
    /**
     * Verify the given password against (some) service.
     *
     * @param string $password
     *
     * @return bool
     */
    public function validPassword(string $password): bool;

}