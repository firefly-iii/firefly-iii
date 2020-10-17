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
    '404_header'              => 'Az oldal nem található.',
    '404_page_does_not_exist' => 'A keresett oldal nem létezik. Kérem ellenőrizze, hogy helyes URL-t írt-e be. Nincs-e elírás a URL címben?',
    '404_send_error'          => 'Elnézését kérem, ha automatikus átirányítással érkezett erre az oldalra. A hibával kapcsolatban további információkat talál a napló fájlokban. Sokat segítene a hiba javításán, ha elküldené az adatokat.',
    '404_github_link'         => 'Kérem nyisson egy hibajegyet <strong><a href="https://github.com/firefly-iii/firefly-iii/issues">GitHub</a></strong>-on, ha biztos benne, hogy ennek az lapnak léteznie kellene.',
    'whoops'                  => 'Whoops',
    'fatal_error'             => 'Súlyos hiba történt. Kérem ellenőrizze a részleteket a napló fájlokban a "storage/logs" könyvtárban vagy használja a "docker logs -f [container]" parancsot.',
    'maintenance_mode'        => 'Firefly III is in maintenance mode.',
    'be_right_back'           => 'Be right back!',
    'check_back'              => 'FireFly III jelenleg karbantartás alatt. Kérem látogasson vissza később.',
    'error_occurred'          => 'Whoops! An error occurred.',
    'error_not_recoverable'   => 'Unfortunately, this error was not recoverable :(. Firefly III broke. The error is:',
    'error'                   => 'Error',
    'error_location'          => 'This error occured in file <span style="font-family: monospace;">:file</span> on line :line with code :code.',
    'stacktrace'              => 'Stack trace',
    'more_info'               => 'More information',
    'collect_info'            => 'További információk gyűjthetők a <code>storage/logs</code> könyvtárban lévő napló fájlokból. Vagy ha Dockert használ, akkor a <code>docker logs -f [container]</code> paranccsal.',
    'collect_info_more'       => 'You can read more about collecting error information in <a href="https://docs.firefly-iii.org/faq/other#how-do-i-enable-debug-mode">the FAQ</a>.',
    'github_help'             => 'Get help on GitHub',
    'github_instructions'     => 'You\'re more than welcome to open a new issue <strong><a href="https://github.com/firefly-iii/firefly-iii/issues">on GitHub</a></strong>.',
    'use_search'              => 'Use the search!',
    'include_info'            => 'Include the information <a href=":link">from this debug page</a>.',
    'tell_more'               => 'Tell us more than "it says Whoops!"',
    'include_logs'            => 'Include error logs (see above).',
    'what_did_you_do'         => 'Tell us what you were doing.',

];
