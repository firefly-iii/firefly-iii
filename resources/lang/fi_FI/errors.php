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

declare(strict_types=1);

return [
    '404_header'              => 'Firefly III ei löydä tätä sivua.',
    '404_page_does_not_exist' => 'Pyytämääsi sivua ei ole. Tarkista, että et ole antanut väärää URL-osoitetta. Teitkö ehkä kirjoitusvirheen?',
    '404_send_error'          => 'If you were redirected to this page automatically, please accept my apologies. There is a mention of this error in your log files and I would be grateful if you sent me the error to me.',
    '404_github_link'         => 'If you are sure this page should exist, please open a ticket on <strong><a href="https://github.com/firefly-iii/firefly-iii/issues">GitHub</a></strong>.',
    'whoops'                  => 'Hupsis',
    'fatal_error'             => 'There was a fatal error. Please check the log files in "storage/logs" or use "docker logs -f [container]" to see what\'s going on.',
    'maintenance_mode'        => 'Firefly III on huoltotilassa.',
    'be_right_back'           => 'Palaan pian!',
    'check_back'              => 'Firefly III is down for some necessary maintenance. Please check back in a second.',
    'error_occurred'          => 'Hupsista! Tapahtui virhe.',
    'error_not_recoverable'   => 'Unfortunately, this error was not recoverable :(. Firefly III broke. The error is:',
    'error'                   => 'Virhe',
    'error_location'          => 'This error occured in file <span style="font-family: monospace;">:file</span> on line :line with code :code.',
    'stacktrace'              => 'Pinojäljitys',
    'more_info'               => 'Lisää tietoja',
    'collect_info'            => 'Please collect more information in the <code>storage/logs</code> directory where you will find log files. If you\'re running Docker, use <code>docker logs -f [container]</code>.',
    'collect_info_more'       => 'You can read more about collecting error information in <a href="https://docs.firefly-iii.org/faq/other#how-do-i-enable-debug-mode">the FAQ</a>.',
    'github_help'             => 'Get help on GitHub',
    'github_instructions'     => 'You\'re more than welcome to open a new issue <strong><a href="https://github.com/firefly-iii/firefly-iii/issues">on GitHub</a></strong>.',
    'use_search'              => 'Käytä hakua!',
    'include_info'            => 'Include the information <a href=":link">from this debug page</a>.',
    'tell_more'               => 'Kerro meille enemmän kuin "se sanoo Whoops!"',
    'include_logs'            => 'Sisällytä virhelokit (katso yllä).',
    'what_did_you_do'         => 'Kerro meille mitä olit tekemässä.',

];
