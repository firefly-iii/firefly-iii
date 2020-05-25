<?php

/**
 * email.php
 * Copyright (c) 2019 james@firefly-iii.org
 *
 * This file is part of Firefly III (https://github.com/firefly-iii).
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

return [
    // common items
    'greeting' => 'Hi there,',
    'closing' => 'Beep boop,',
    'signature' => 'The Firefly III Mail Robot',
    'footer_ps' => 'PS: This message was sent because a request from IP :ipAddress triggered it.',

    // admin test
    'admin_test_subject' => 'A test message from your Firefly III installation',
    'admin_test_body' => 'This is a test message from your Firefly III instance. It was sent to :email.',

    // access token created
    'access_token_created_subject' => 'A new access token was created',
    'access_token_created_body_1' => 'Somebody (hopefully you) just created a new Firefly III API Access Token for your user account.',
    'access_token_created_body_2_html' => 'With this token, they can access <strong>all</strong> of your financial records through the Firefly III API.',
    'access_token_created_body_2_text' => 'With this token, they can access *all* of your financial records through the Firefly III API.',
    'access_token_created_body_3' => 'If this wasn\'t you, please revoke this token as soon as possible at :url.',

    // registered
    'registered_subject' => 'Welcome to Firefly III!',
    'registered_welcome_html' => 'Welcome to <a style="color:#337ab7" href=":address">Firefly III</a>. Your registration has made it, and this email is here to confirm it. Yay!',
    'registered_welcome_text' => 'Welcome to Firefly III. Your registration has made it, and this email is here to confirm it. Yay!',
    'registered_pw_html' => 'If you have forgotten your password already, please reset it using <a style="color:#337ab7" href=":address/password/reset">the password reset tool</a>.',
    'registered_pw_text' => 'If you have forgotten your password already, please reset it using the password reset tool.',
    'registered_help' => 'There is a help-icon in the top right corner of each page. If you need help, click it!',
    'registered_doc_html' => 'If you haven\'t already, please read the <a style="color:#337ab7" href="https://docs.firefly-iii.org/about-firefly-iii/grand-theory">grand theory</a>.',
    'registered_doc_text' => 'If you haven\'t already, please read the first use guide and the full description.',
    'registered_closing' => 'Enjoy!',
    'registered_firefly_iii_link' => 'Firefly III:',
    'registered_pw_reset_link' => 'Password reset:',
    'registered_doc_link' => 'Documentation:',

    // email change
    'email_change_subject' => 'Your Firefly III email address has changed',
    'email_change_body_to_new' => 'You or somebody with access to your Firefly III account has changed your email address. If you did not expect this message, please ignore and delete it.',
    'email_change_body_to_old_html' => 'You or somebody with access to your Firefly III account has changed your email address.
    If you did not expect this to happen, you <strong>must</strong> follow the "undo"-link below to protect your account!',
    'email_change_body_to_old_text' => 'You or somebody with access to your Firefly III account has changed your email address. If you did not expect this to happen,
    you must follow the "undo"-link below to protect your account!',
    'email_change_ignore' => 'If you initiated this change, you may safely ignore this message.',
    'email_change_old' => 'The old email address was:',
    'email_change_new' => 'The new email address is:',
    'email_change_instructions' => 'You cannot use Firefly III until you confirm this change. Please follow the link below to do so.',
    'email_change_undo_link' => 'To undo the change, follow this link:',

    // OAuth token created
    'oauth_created_subject' => 'A new OAuth client has been created',
    'oauth_created_body_html' => 'Somebody (hopefully you) just created a new Firefly III API OAuth Client for your user account. It\'s labeled ":name" and has callback URL <span style="font-family: monospace;">:url</span>.',
    'oauth_created_body_text' => 'Somebody (hopefully you) just created a new Firefly III API OAuth Client for your user account. It\'s labeled ":name" and has callback URL:',
    'oauth_created_explanation_html' => 'With this client, they can access <strong>all</strong> of your financial records through the Firefly III API.',
    'oauth_created_explanation_text' => 'With this client, they can access *all* of your financial records through the Firefly III API.',
    'oauth_created_undo' => 'If this wasn\'t you, please revoke this client as soon as possible at :url.',

    // reset password
    'reset_pw_subject' => 'Your password reset request',
    'reset_pw_instructions' => 'Somebody tried to reset your password. If it was you, please follow the link below to do so.',
    'reset_pw_warning_html' => '<strong>PLEASE</strong> verify that the link actually goes to the Firefly III you expect it to go!',
    'reset_pw_warning_text' => '*PLEASE* verify that the link actually goes to the Firefly III you expect it to go!',

    // error
    'error_subject' => 'Caught an error in Firefly III',
    'error_intro_html' => 'Firefly III v:version ran into an error: <span style="font-family: monospace;">:errorMessage</span>',
    'error_type' => 'The error was of type ":class".',
    'error_timestamp' => 'The error occurred on/at: :time.',
    'error_location_html' => 'This error occurred in file <span style="font-family: monospace;">:file</span> on line :line with code :code.',
    'error_user_html' => 'The error was encountered by user #:id, <a href="mailto::email">:email</a>.',
    'error_no_user' => 'There was no user logged in for this error or no user was detected.',
    'error_ip' => 'The IP address related to this error is:',
    'error_url' => 'URL is:',
    'error_user_agent' => 'User agent:',
    'error_stacktrace' => 'The full stacktrace is below. If you think this is a bug in Firefly III, you  can forward this message to :email. This can help fix the bug you just encountered.',
    'error_github' => 'If you prefer, you can also open a new issue on :link.',
    'error_stacktrace_below' => 'The full stacktrace is below:'
];
