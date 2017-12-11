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
    'bunq_prerequisites_title'      => 'Voraussetzungen für einen Import von bunq',
    'bunq_prerequisites_text'       => 'Um aus bunq importieren zu können, benötigen Sie einen API-Schlüssel. Sie können dies über die App tun.',

    // Spectre:
    'spectre_title'                 => 'Import using Spectre',
    'spectre_prerequisites_title'   => 'Prerequisites for an import using Spectre',
    'spectre_prerequisites_text'    => 'In order to import data using the Spectre API, you need to prove some secrets. They can be found on the <a href="https://www.saltedge.com/clients/profile/secrets">secrets page</a>.',
    'spectre_enter_pub_key'         => 'The import will only work when you enter this public key on your <a href="https://www.saltedge.com/clients/security/edit">security page</a>.',
    'spectre_select_country_title'  => 'Select a country',
    'spectre_select_country_text'   => 'Firefly III has a large selection of banks and sites from which Spectre can download transactional data. These banks are sorted by country. Please not that there is a "Fake Country" for when you wish to test something. If you wish to import from other financial tools, please use the imaginary country called "Other financial applications". By default, Spectre only allows you to download data from fake banks. Make sure your status is "Live" on your <a href="https://www.saltedge.com/clients/dashboard">Dashboard</a> if you wish to download from real banks.',
    'spectre_select_provider_title' => 'Select a bank',
    'spectre_select_provider_text'  => 'Spectre supports the following banks or financial services grouped under <em>:country</em>. Please pick the one you wish to import from.',
    'spectre_input_fields_title'    => 'Input mandatory fields',
    'spectre_input_fields_text'     => 'The following fields are mandated by ":provider" (from :country).',
    'spectre_instructions_english'  => 'These instructions are provided by Spectre for your convencience. They are in English:',
];
