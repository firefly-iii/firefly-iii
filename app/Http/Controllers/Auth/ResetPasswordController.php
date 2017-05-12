<?php
/**
 * ResetPasswordController.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */
declare(strict_types=1);

namespace FireflyIII\Http\Controllers\Auth;

use FireflyIII\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\ResetsPasswords;

/**
 * @codeCoverageIgnore
 *
 * Class ResetPasswordController
 *
 * @package FireflyIII\Http\Controllers\Auth
 */
class ResetPasswordController extends Controller
{

    use ResetsPasswords;

    /**
     * Create a new controller instance.
     *
     */
    public function __construct()
    {
        parent::__construct();

        $this->middleware('guest');
    }
}
