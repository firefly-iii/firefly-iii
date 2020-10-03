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
    '404_header'              => 'Firefly III nenašiel túto stránku.',
    '404_page_does_not_exist' => 'Stránka, ktorú ste si vyžiadali, neexistuje. Prosím, skontrolujte, či ste zadali správnu URL. Možno ste spravili preklep.',
    '404_send_error'          => 'Ak ste sem boli presmerovaní automaticky, ospravedlňujeme sa. V súbore denníka bude táto chyba spomenutá a budeme vďační, ak nám ju pošlete.',
    '404_github_link'         => 'Ak ste si istí, že by táto stránka mala existovať, prosím, vytvorte nové hlásenie na <strong><a href="https://github.com/firefly-iii/firefly-iii/issues">GitHub</a></strong>.',
    'whoops'                  => 'Ups',
    'fatal_error'             => 'Vyskytla sa závažná chyba. Prosím, pre zistenie, čo sa stalo, skontrolujte chybový záznam v "storage/logs" alebo použite "docker logs -f [container]".',
    'maintenance_mode'        => 'Firefly III je v údržbovom režime.',
    'be_right_back'           => 'Hneď sme späť!',
    'check_back'              => 'Firefly III je nedostupný kvôli nevyhnutnej údržbe. Prosím, skúste to neskôr.',
    'error_occurred'          => 'Ups! Vyskytla sa chyba.',
    'error_not_recoverable'   => 'Nanešťastie, túto chybu nie je možné opraviť :(. Firefly III sa pokazil. Chyba:',
    'error'                   => 'Chyba',
    'error_location'          => 'Chyba nastala v súbore "<span style="font-family: monospace;">:file</span>" na riadku :line s kódom :code.',
    'stacktrace'              => 'Trasovanie zásobníka',
    'more_info'               => 'Viac informácií',
    'collect_info'            => 'Prosím, získajte viac informácií v zložke <code>storage/logs</code>, kde nájdete chybové záznamy. Ak spúšťate aplikáciu cez Docker, použite <code>docker logs -f [container]</code>.',
    'collect_info_more'       => 'Viac o získavaní informácií o chybách si môžete prečítať v sekcii <a href="https://docs.firefly-iii.org/faq/other#how-do-i-enable-debug-mode">často kladených otázok</a>.',
    'github_help'             => 'Nájdite pomoc na GitHube',
    'github_instructions'     => 'Budeme viac než radi, ak vytvoríte nové hlásenie na <strong><a href="https://github.com/firefly-iii/firefly-iii/issues">GitHube</a></strong>.',
    'use_search'              => 'Použite vyhľadávanie!',
    'include_info'            => 'Priložiť informácie <a href=":link">z tejto ladiacej stránky</a>.',
    'tell_more'               => 'Povedzte nám viac, než "napísalo mi to Ups!"',
    'include_logs'            => 'Priložiť chybové záznamy (viď vyššie).',
    'what_did_you_do'         => 'Napíšte nám, čo ste robili, keď sa chyba vyskytla.',

];
