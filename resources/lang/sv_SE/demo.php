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
    'no_demo_text'           => 'Tyvärr, finns ingen extra demo-förklaringstext för <abbr title=":route">denna sida</abbr>.',
    'see_help_icon'          => 'Dock kan <i class="fa fa-question-circle"></i>-ikonen i övre högra hörnet eventuellt förklara mer.',
    'index'                  => 'Välkommen till <strong>Firefly III</strong>! På denna sida får du en snabb överblick över din ekonomi. För mer information, kolla in konton &rarr; <a href=":asset">tillgångskonton</a> och naturligtvis sidorna för <a href=":budgets">budgetar</a> och <a href=":reports">rapporter</a>. Eller så tar du bara en titt runt och ser var du hamnar.',
    'accounts-index'         => 'Konton är dina personliga bankkonton. Utgiftskonton är konton som du spenderar pengar från, som till butiker och vänner. Intäktskonton är konton som du får pengar till, från till exempel ditt arbete, regeringen eller andra inkomstkällor. Skulder är skulder och lån som till exempel gamla kreditkorts skulder eller studielån. På denna sida kan du redigera eller ta bort dem.',
    'budgets-index'          => 'Denna sida visar en översikt över din budget. Det översta fältet visar beloppet som är tillgängligt för att budgeteras. Detta kan anpassas för varje period genom att klicka på beloppet till höger. Summan av spenderat belopp visas i fältet nedan. Därunder visas kostnaderna per budgetering och vad du har budgeterat för dem.',
    'reports-index-start'    => 'Firefly III stödjer ett flertal olika typer av rapportet. Läs om dem genom att klicka på <i class="fa fa-question-circle"></i>-ikonen längst upp till höger.',
    'reports-index-examples' => 'Några exempel att titta på: <a href=":one">månadsöverblick</a>, <a href=":two">års-överblick</a> och <a href=":three">budgetöverblick</a>.',
    'currencies-index'       => 'Firefly III stödjer flera valutor. Standardvaluta är Euro, men kan ändras till US Dollar och många andra valutor. Ett litet urval av valutor har inkluderats och du kan skapa en egen valuta om du så önskar. Att byta standardvaluta ändrar inte valuta på befintliga transaktioner. Firefly III stödjer att man använder flera olika valutor samtidigt.',
    'transactions-index'     => 'Dessa utgifter, insättningar och överföringar är inte särskilt fantasifulla. De har genererats automatiskt.',
    'piggy-banks-index'      => 'Som ni ser finns det tre spargrisar. Använd plus- och minus-knapparna för att påverka mängden pengar i varje spargris. Klicka på spargrisens namn för att se administrationen för varje spargris.',
    'import-index'           => 'Alla CSV-filer kan importeras till Firefly III. Det stöder också import av data från bunq och Specter. Andra banker och finansiella aggregerare kommer att implementeras i framtiden. Som demo-användare kan du dock bara se den "falska" -leverantören i aktion. Det kommer att generera några slumpmässiga transaktioner för att visa hur processen fungerar.',
    'profile-index'          => 'Tänk på att demosidan återställs var fjärde timme. Din åtkomst kan återkallas när som helst. Detta händer automatiskt och är inte ett fel.',
];
