<?php
/**
 * passwords.php
 * Copyright (C) 2016 Sander Dorigo
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

return [

    /*
    |--------------------------------------------------------------------------
    |--------------------------------------------------------------------------
    |
    | The following language lines are the default lines which match reasons
    | that are given by the password broker for a password update attempt
    | has failed, such as for an invalid token or invalid new password.
    |
    */

    'password' => 'Passwords must be at least six characters and match the confirmation.',
    'user'     => 'We can\'t find a user with that e-mail address.',
    'token'    => 'This password reset token is invalid.',
    'sent'     => 'We have e-mailed your password reset link!',
    'reset'    => 'Your password has been reset!',
    'blocked'  => 'Nice try though.',

];
