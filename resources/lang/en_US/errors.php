<?php

/**
 * firefly.php
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

// Ignore this comment

declare(strict_types=1);

return [
    '404_header'              => 'Firefly III cannot find this page.',
    '404_page_does_not_exist' => 'The page you have requested does not exist. Please check that you have not entered the wrong URL. Did you make a typo perhaps?',
    '404_send_error'          => 'If you were redirected to this page automatically, please accept my apologies. There is a mention of this error in your log files and I would be grateful if you sent me the error to me.',
    '404_github_link'         => 'If you are sure this page should exist, please open a ticket on <strong><a href="https://github.com/firefly-iii/firefly-iii/issues">GitHub</a></strong>.',
    'whoops'                  => 'Whoops',
    'fatal_error'             => 'There was a fatal error. Please check the log files in "storage/logs" or use "docker logs -f [container]" to see what\'s going on.',
    'maintenance_mode'        => 'Firefly III is in maintenance mode.',
    'be_right_back'           => 'Be right back!',
    'check_back'              => 'Firefly III is down for some necessary maintenance. Please check back in a second. If you happen to see this message on the demo site, just wait a few minutes. The database is reset every few hours.',
    'error_occurred'          => 'Whoops! An error occurred.',
    'db_error_occurred'       => 'Whoops! A database error occurred.',
    'error_not_recoverable'   => 'Unfortunately, this error was not recoverable :(. Firefly III broke. The error is:',
    'error'                   => 'Error',
    'error_location'          => 'This error occurred in file <span style="font-family: monospace;">:file</span> on line :line with code :code.',
    'stacktrace'              => 'Stack trace',
    'more_info'               => 'More information',

    // Ignore this comment

    'collect_info'            => 'Please collect more information in the <code>storage/logs</code> directory where you will find log files. If you\'re running Docker, use <code>docker logs -f [container]</code>.',
    'collect_info_more'       => 'You can read more about collecting error information in <a href="https://docs.firefly-iii.org/how-to/general/debug/">the FAQ</a>.',
    'github_help'             => 'Get help on GitHub',
    'github_instructions'     => 'You\'re more than welcome to open a new issue <strong><a href="https://github.com/firefly-iii/firefly-iii/issues">on GitHub</a></strong>.',
    'use_search'              => 'Use the search!',
    'include_info'            => 'Include the information <a href=":link">from this debug page</a>.',
    'tell_more'               => 'Tell us more than "it says Whoops!"',
    'include_logs'            => 'Include error logs (see above).',
    'what_did_you_do'         => 'Tell us what you were doing.',
    'offline_header'          => 'You are probably offline',
    'offline_unreachable'     => 'Firefly III is unreachable. Your device is currently offline or the server is not working.',
    'offline_github'          => 'If you are sure both your device and the server are online, please open a ticket on <strong><a href="https://github.com/firefly-iii/firefly-iii/issues">GitHub</a></strong>.',
];
