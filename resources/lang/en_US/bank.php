<?php
declare(strict_types=1);


return [
    'bunq_prerequisites_title'     => 'Prerequisites for an import from bunq',
    'bunq_prerequisites_text'      => 'In order to import from bunq, you need to obtain an API key. You can do this through the app.',

    // Spectre:
    'spectre_title'                => 'Import using Spectre',
    'spectre_prerequisites_title'  => 'Prerequisites for an import using Spectre',
    'spectre_prerequisites_text'   => 'In order to import data using the Spectre API, you need to prove some secrets. They can be found on the <a href="https://www.saltedge.com/clients/profile/secrets">secrets page</a>.',
    'spectre_enter_pub_key'        => 'The import will only work when you enter this public key on your <a href="https://www.saltedge.com/clients/security/edit">security page</a>.',
    'spectre_select_country_title' => 'Select a country',
    'spectre_select_country_text'  => 'Firefly III has a large selection of banks and sites from which Spectre can download transactional data. These banks are sorted by country. Please not that there is a "Fake Country" for when you wish to test something. If you wish to import from other financial tools, please use the imaginary country called "Other financial applications". By default, Spectre only allows you to download data from fake banks. Make sure your status is "Live" on your <a href="https://www.saltedge.com/clients/dashboard">Dashboard</a> if you wish to download from real banks.',
];
