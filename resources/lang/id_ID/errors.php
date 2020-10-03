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
    '404_header'              => 'Firefly III tidak dapat menemukan halaman ini.',
    '404_page_does_not_exist' => 'Halaman yang anda minta tidak ada. Harap pastikan bahwa anda sudah memasukkan URL yang benar. Mungkin ada kesalahan pengetikan?',
    '404_send_error'          => 'Jika anda diarahkan ke halaman ini secara otomatis, saya mohon maaf. Kesalahan ini sudah dicatat pada file log dan saya sangat berterima kasih jika anda mengirimkan kesalahan ini kepada saya.',
    '404_github_link'         => 'Jika anda yakin halaman ini seharusnya ada, silakan buat tiket isu pada <strong><a href="https://github.com/firefly-iii/firefly-iii/issues">GitHub</a></strong>.',
    'whoops'                  => 'Whoops',
    'fatal_error'             => 'There was a fatal error. Please check the log files in "storage/logs" or use "docker logs -f [container]" to see what\'s going on.',
    'maintenance_mode'        => 'Firefly III sedang dalam mode pemeliharaan.',
    'be_right_back'           => 'Segera kembali!',
    'check_back'              => 'Firefly III is down for some necessary maintenance. Please check back in a second.',
    'error_occurred'          => 'Whoops! An error occurred.',
    'error_not_recoverable'   => 'Unfortunately, this error was not recoverable :(. Firefly III broke. The error is:',
    'error'                   => 'Error',
    'error_location'          => 'This error occured in file <span style="font-family: monospace;">:file</span> on line :line with code :code.',
    'stacktrace'              => 'Stack trace',
    'more_info'               => 'Informasi lebih lanjut',
    'collect_info'            => 'Please collect more information in the <code>storage/logs</code> directory where you will find log files. If you\'re running Docker, use <code>docker logs -f [container]</code>.',
    'collect_info_more'       => 'You can read more about collecting error information in <a href="https://docs.firefly-iii.org/faq/other#how-do-i-enable-debug-mode">the FAQ</a>.',
    'github_help'             => 'Dapatkan bantuan di GitHub',
    'github_instructions'     => 'You\'re more than welcome to open a new issue <strong><a href="https://github.com/firefly-iii/firefly-iii/issues">on GitHub</a></strong>.',
    'use_search'              => 'Gunakan pencarian!',
    'include_info'            => 'Include the information <a href=":link">from this debug page</a>.',
    'tell_more'               => 'Tell us more than "it says Whoops!"',
    'include_logs'            => 'Include error logs (see above).',
    'what_did_you_do'         => 'Tell us what you were doing.',

];
