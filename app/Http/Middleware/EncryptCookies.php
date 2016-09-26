<?php
/**
 * EncryptCookies.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */
declare(strict_types = 1);

namespace FireflyIII\Http\Middleware;

use Illuminate\Cookie\Middleware\EncryptCookies as BaseEncrypter;

/**
 * Class EncryptCookies
 *
 * @package FireflyIII\Http\Middleware
 */
class EncryptCookies extends BaseEncrypter
{
    /**
     * The names of the cookies that should not be encrypted.
     *
     * @var array
     */
    protected $except
        = [
            //
        ];
}
