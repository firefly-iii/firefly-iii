<?php
/**
 * bank.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 *
 * This file is part of Firefly III.
 *
 * Firefly III is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Firefly III is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Firefly III.  If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);


return [
    'bunq_prerequisites_title'      => 'Voorwaarden voor een import van bunq',
    'bunq_prerequisites_text'       => 'Om transacties bij bunq te importeren heb je een API sleutel nodig. Dit kan via de app.',

    // Spectre:
    'spectre_title'                 => 'Importeer via Spectre',
    'spectre_prerequisites_title'   => 'Voorwaarden voor een import via Spectre',
    'spectre_prerequisites_text'    => 'Als je gegevens wilt importeren via de Spectre API, moet je een aantal geheime codes bezitten. Ze zijn te vinden op <a href="https://www.saltedge.com/clients/profile/secrets">de secrets pagina</a>.',
    'spectre_enter_pub_key'         => 'Het importeren werkt alleen als je deze publieke sleutel op uw <a href="https://www.saltedge.com/clients/security/edit">security pagina</a> invoert.',
    'spectre_select_country_title'  => 'Selecteer een land',
    'spectre_select_country_text'   => 'Firefly III bevat een groot aantal banken en sites waaruit Spectre transactiegegevens voor je kan downloaden. Deze banken zijn gesorteerd per land. Let op: er is een "Fake Country" voor wanneer je dingen wilt testen. Als je uit andere financiële apps wilt importeren, gebruik dan het denkbeeldige land "Andere financiële applicaties". In Spectre kun je standaard alleen gegevens van nep-banken downloaden. Zorg ervoor dat je status "Live" is op je <a href="https://www.saltedge.com/clients/dashboard">Dashboard</a> als je wilt downloaden van echte banken.',
    'spectre_select_provider_title' => 'Selecteer een bank',
    'spectre_select_provider_text'  => 'Spectre ondersteunt de volgende banken of financiële apps onder <em>:country</em>. Kies degene waaruit je wilt importeren.',
    'spectre_input_fields_title'    => 'Verplichte velden',
    'spectre_input_fields_text'     => 'De volgende velden zijn verplicht voor ":provider" (uit :country).',
    'spectre_instructions_english'  => 'Deze instructies worden door Spectre verstrekt. Ze zijn in het Engels:',
];
