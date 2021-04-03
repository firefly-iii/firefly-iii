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
    '404_header'              => 'Firefly III nemůže najít tuto stránku.',
    '404_page_does_not_exist' => 'Požadovaná stránka neexistuje. Zkontrolujte, zda jste zadali správnou adresu URL. Možná jste jste se překlepli?',
    '404_send_error'          => 'Pokud jste byli automaticky přesměrováni na tuto stránku, přijměte mou omluvu. V souboru deníku je zmíněna tato chyba a budu vděčný, pokud by jste mi ji poslali.',
    '404_github_link'         => 'Pokud jste si jisti, že tato stránka by měla existovat, otevřete prosím ticket na <strong><a href="https://github.com/firefly-iii/firefly-iii/issues">GitHub</a></strong>.',
    'whoops'                  => 'Hups',
    'fatal_error'             => 'Došlo k fatální chybě. Zkontrolujte soubory protokolu v "storage/logs" nebo použijte "docker logs -f [container]", abyste zjistili, co se děje.',
    'maintenance_mode'        => 'Firefly III je v režimu údržby.',
    'be_right_back'           => 'Hned jsme zpět!',
    'check_back'              => 'Firefly III je vypnutý kvůli nezbytné údržbě. Zkuste to prosím později.',
    'error_occurred'          => 'Jejda! Došlo k chybě.',
    'error_not_recoverable'   => 'Bohužel, tato chyba je neopravitelná :(. Firefly III se pokazil. Chyba je:',
    'error'                   => 'Chyba',
    'error_location'          => 'Došlo k chybě v souboru <span style="font-family: monospace;">:file</span> na řádku :line s kódem :code.',
    'stacktrace'              => 'Trasování zásobníku',
    'more_info'               => 'Více informací',
    'collect_info'            => 'Shromažďujte prosím další informace do adresáře <code>storage/logs</code>, kde najdete chybové záznamy. Pokud používáte Docker, použijte <code>docker logs -f [container]</code>.',
    'collect_info_more'       => 'Více informací o shromažďování chyb si můžete přečíst v <a href="https://docs.firefly-iii.org/faq/other#how-do-i-enable-debug-mode">FAQ</a>.',
    'github_help'             => 'Získejte nápovědu na GitHub',
    'github_instructions'     => 'Jste více než vítáni při otevření nových hlášení <strong><a href="https://github.com/firefly-iii/firefly-iii/issues">na GitHub</a></strong>.',
    'use_search'              => 'Použijte vyhledávání!',
    'include_info'            => 'Zahrnout informace <a href=":link">z této ladící stránky</a>.',
    'tell_more'               => 'Řekněte nám více než "se objevilo Hups!"',
    'include_logs'            => 'Zahrnout protokoly chyb (viz výše).',
    'what_did_you_do'         => 'Řekněte nám, co jste dělali.',

];
