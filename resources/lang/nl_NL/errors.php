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
    '404_header'              => 'Firefly III kan deze pagina niet vinden.',
    '404_page_does_not_exist' => 'De opgevraagde pagina bestaat niet. Controleer of je niet de verkeerde URL hebt ingevoerd. Of heb je een typefout gemaakt?',
    '404_send_error'          => 'Als je hier automatisch naar werd doorgestuurd, sorry! Deze fout staat ook in je logboeken dus stuur hem vooral door.',
    '404_github_link'         => 'Als je zeker weet dat deze pagina zou moeten bestaan, open dan een ticket op <strong><a href="https://github.com/firefly-iii/firefly-iii/issues">GitHub</a></strong>.',
    'whoops'                  => 'Oeps',
    'fatal_error'             => 'Er is een fatale fout opgetreden. Controleer de logbestanden in "storage/logs" of gebruik "docker logs -f [container]" om te zien wat er gebeurde.',
    'maintenance_mode'        => 'Firefly III is in onderhoudsmodus.',
    'be_right_back'           => 'Zo terug!',
    'check_back'              => 'Firefly III is offline voor onderhoud. Kom later terug.',
    'error_occurred'          => 'Oeps! Er is een fout opgetreden.',
    'error_not_recoverable'   => 'Helaas was deze fout niet te herstellen :(. Firefly III is stuk. De fout is:',
    'error'                   => 'Fout',
    'error_location'          => 'De fout is opgetreden in bestand <span style="font-family: monospace;">:file</span> op regel :line met code :code.',
    'stacktrace'              => 'Stack trace',
    'more_info'               => 'Meer informatie',
    'collect_info'            => 'Verzamel meer informatie in de <code>storage/logs</code>-directory waar je de logbestanden kan vinden. Als Docker gebruikt, gebruik dan <code>docker logs -f [container]</code>.',
    'collect_info_more'       => 'Je kan meer lezen over het verzamelen van foutinformatie in <a href="https://docs.firefly-iii.org/faq/other#how-do-i-enable-debug-mode">de FAQ</a>.',
    'github_help'             => 'Check voor hulp op GitHub',
    'github_instructions'     => 'Je bent meer dan welkom om een nieuw issue te openen <strong><a href="https://github.com/firefly-iii/firefly-iii/issues">op GitHub</a></strong>.',
    'use_search'              => 'Gebruik de search!',
    'include_info'            => 'Voeg de informatie toe van <a href=":link">deze debug pagina</a>.',
    'tell_more'               => 'Meer info dan "hij is stuk" gaarne',
    'include_logs'            => 'Inclusief foutlogs (zie hierboven).',
    'what_did_you_do'         => 'Zet er bij wat je deed.',

];
