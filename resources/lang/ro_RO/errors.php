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
    '404_header'              => 'Firefly III nu a găsit această pagină.',
    '404_page_does_not_exist' => 'Pagina pe care ați solicitat-o nu există. Vă rugăm să verificați că nu ați introdus adresa URL greșită. Probabil ați făcut o greșeală?',
    '404_send_error'          => 'Dacă ați fost redirecționat către această pagină automat, vă rugăm să acceptați scuzele mele. Există o menţiune despre această eroare în fişierele de jurnal şi aş fi recunoscător dacă mi-aţi trimite eroarea.',
    '404_github_link'         => 'Dacă sunteți sigur că această pagină ar trebui să existe, vă rugăm să deschideți un tichet pe <strong><a href="https://github.com/firefly-iii/firefly-iii/issues">GitHub</a></strong>.',
    'whoops'                  => 'Hopaa',
    'fatal_error'             => 'A existat o eroare fatală. Vă rugăm să verificaţi fişierele de jurnal din "storage/logs" sau utilizaţi "docker logs -f [container] pentru a vedea ce se întâmplă.',
    'maintenance_mode'        => 'Firefly III este în modul de întreținere.',
    'be_right_back'           => 'Revin imediat!',
    'check_back'              => 'Firefly III este oprit pentru o întreținere necesară. Vă rugăm să reveniți într-o secundă.',
    'error_occurred'          => 'Ups! A apărut o eroare.',
    'error_not_recoverable'   => 'Din păcate, această eroare nu a putut fi recuperată :(. Firefly III s-a stricat. Eroarea este:',
    'error'                   => 'Eroare',
    'error_location'          => 'Această eroare a apărut în fișierul "<span style="font-family: monospace;">:file</span>" pe linia :line cu codul :code.',
    'stacktrace'              => 'Stack trace',
    'more_info'               => 'Mai multe informaţii',
    'collect_info'            => 'Vă rugăm să colectați mai multe informații în directorul <code>storage/logs</code> unde veți găsi fișiere jurnal. Dacă rulați Docker, folosiți <code>docker logs -f[container]</code>.',
    'collect_info_more'       => 'Poți citi mai multe despre colectarea informațiilor despre erori în <a href="https://docs.firefly-iii.org/faq/other#how-do-i-enable-debug-mode">FAQ</a>.',
    'github_help'             => 'Obțineți ajutor pe GitHub',
    'github_instructions'     => 'Dacă sunteți sigur că această pagină ar trebui să existe, vă rugăm să deschideți un tichet pe <strong><a href="https://github.com/firefly-iii/firefly-iii/issues">GitHub</a></strong>.',
    'use_search'              => 'Folosește căutarea!',
    'include_info'            => 'Include informațiile <a href=":link">din această pagină de depanare</a>.',
    'tell_more'               => 'Spune-ne mai mult decât „spune Whoops!”',
    'include_logs'            => 'Include jurnalele de erori (a se vedea mai sus).',
    'what_did_you_do'         => 'Spune-ne ce făceai.',

];
