<?php

/**
 * demo.php
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
    'no_demo_text'           => 'Spiacenti, non esiste un testo dimostrativo aggiuntivo per <abbr title=":route">questa pagina</abbr>.',
    'see_help_icon'          => 'Tuttavia, l\'icona <i class="fa fa-question-circle"></i> in alto a destra potrebbe dirti di più.',
    'index'                  => 'Benvenuto in <strong>Firefly III</strong>! In questa pagina ottieni una rapida panoramica delle tue finanze. Per ulteriori informazioni, controlla Conti &rarr; <a href=":asset">Conti attività</a> e, naturalmente, le pagine <a href=":budgets">Budget</a> e <a href=":reports">Resoconti</a>. O semplicemente dai un\'occhiata in giro e vedi dove finisci.',
    'accounts-index'         => 'I conti attività sono i conti bancari personali. I conti spese sono i conti verso cui si spendono soldi, come negozi e amici. I conti entrate sono conti da cui ricevi denaro, come il tuo lavoro, il governo o altre fonti di reddito. Le passività sono i tuoi debiti e prestiti come i debiti di una vecchia carta di credito o i prestiti studenteschi. In questa pagina puoi modificarli o rimuoverli.',
    'budgets-index'          => 'Questa pagina ti mostra una panoramica dei tuoi budget. La barra in alto mostra l\'importo disponibile per essere preventivato. Questo può essere personalizzato per qualsiasi periodo facendo clic sull\'importo a destra. La quantità che hai effettivamente speso è mostrata nella barra sottostante. Di seguito sono indicate le spese per budget e ciò che hai preventivato per loro.',
    'reports-index-start'    => 'Firefly III supporta un certo numero di tipi di resoconto. Leggi facendo clic sull\'icona <i class="fa fa-question-circle"></i> in alto a destra.',
    'reports-index-examples' => 'Assicurati di dare un occhiata a questi esempi: <a href=":one"> una panoramica finanziaria mensile </a>, <a href=":two"> una panoramica finanziaria annuale </a> e <a href=":three">una panoramica del budget </a>.',
    'currencies-index'       => 'Firefly III supporta più valute. Sebbene sia impostato su Euro, può essere impostato sul dollaro USA e su molte altre valute. Come puoi vedere, è stata inclusa una piccola selezione di valute, ma puoi aggiungere la tua se lo desideri. Tuttavia, la modifica della valuta predefinita non cambierà la valuta delle transazioni esistenti: Firefly III supporta un uso di più valute allo stesso tempo.',
    'transactions-index'     => 'Queste spese, depositi e trasferimenti non sono particolarmente fantasiosi. Sono stati generati automaticamente.',
    'piggy-banks-index'      => 'Come puoi vedere, ci sono tre salvadanai. Utilizzare i pulsanti più e meno per influenzare la quantità di denaro in ogni salvadanaio. Fare clic sul nome del salvadanaio per visualizzare la gestione per ciascun salvadanaio.',
    'import-index'           => 'Qualsiasi file CSV può essere importato in Firefly III. Supporta anche l\'importazione di dati da bunq e Spectre. Altre banche e aggregatori finanziari saranno implementati in futuro. Tuttavia, come utente demo, puoi vedere solo il provider "fittizio" in azione. Genererà alcune transazioni casuali per mostrarti come funziona il processo.',
    'profile-index'          => 'Tieni a mente che il sito demo viene reimpostato ogni quattro ore. L\'acceso può essere revocato in qualsiasi momento. Questo avviene automaticamente e non è un bug.',
];
