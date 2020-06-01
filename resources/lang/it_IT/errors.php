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
    '404_header'              => 'Firefly III non riesce a trovare questa pagina.',
    '404_page_does_not_exist' => 'La pagina che hai richiesto non esiste. Controlla di non aver inserito l\'URL sbagliato. Hai fatto un errore di battitura?',
    '404_send_error'          => 'Se sei stato reindirizzato a questa pagina automaticamente, accetta le mie scuse. Nei tuoi file di log puoi trovare questo errore e ti sarei grato se me lo inviassi.',
    '404_github_link'         => 'Se sei sicuro che questa pagina dovrebbe esistere, apri un ticket su <strong><a href="https://github.com/firefly-iii/firefly-iii/issues">GitHub</a></strong>.',
    'whoops'                  => 'Oops!',
    'fatal_error'             => 'Si è verificato un errore fatale. Controlla i file di log in "storage/logs" o usa "docker logs -f [container]" per vedere cosa sta succedendo.',
    'maintenance_mode'        => 'Firefly III è in modalità di manutenzione.',
    'be_right_back'           => 'Torno subito!',
    'check_back'              => 'Firefly III non è in funzione per una manutenzione necessaria. Ricontrolla tra qualche secondo.',
    'error_occurred'          => 'Ops! Si è verificato un errore.',
    'error_not_recoverable'   => 'Sfortunatamente questo errore non è riparabile :(. Firefly III è rotto. L\'errore è:',
    'error'                   => 'Errore',
    'error_location'          => 'Questo errore si è verificato nel file <span style="font-family: monospace;">:file</span> alla riga :line con codice :code.',
    'stacktrace'              => 'Stack trace',
    'more_info'               => 'Ulterioni informazioni',
    'collect_info'            => 'Raccogli ulteriori informazioni nella cartella <code>storage/log</code> dove troverai i file di log. Se stai eseguendo Docker, usa <code>docker logs -f [container]</code>.',
    'collect_info_more'       => 'Puoi leggere maggiori informazioni sulla raccolta delle informazioni di errore in <a href="https://docs.firefly-iii.org/faq/other#how-do-i-enable-debug-mode">FAQ</a>.',
    'github_help'             => 'Ottieni aiuto su GitHub',
    'github_instructions'     => 'Sei più che benvenuto ad aprire una nuova issue <strong><a href="https://github.com/firefly-iii/firefly-iii/issues">su GitHub</a></strong>.',
    'use_search'              => 'Usa la ricerca!',
    'include_info'            => 'Includi le informazioni <a href=":link">da questa pagina di debug</a>.',
    'tell_more'               => 'Dicci di più di "dice Oops!"',
    'include_logs'            => 'Includi i log degli errori (vedi sopra).',
    'what_did_you_do'         => 'Dicci cosa stavi facendo.',

];
