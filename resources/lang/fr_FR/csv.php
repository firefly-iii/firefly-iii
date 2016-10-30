<?php
/**
 * csv.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types = 1);

return [

    'import_configure_title' => 'Configurer l\'import',
    'import_configure_intro' => 'Il y a des options pour l\'import CSV. Veuillez indiquer si votre fichier CSV contient les en-têtes dans la première colonne, et quel est le format de la date de votre champs date. Cela peut nécessiter quelques essais. Le délimiteur de champ est généralement un «, », mais pourrait également être un « ; ». Cochez cette case avec soin.',
    'import_configure_form'  => 'Formulaire',
    'header_help'            => 'Cochez cette case si la première ligne de votre fichier CSV contient les entêtes des colonnes',
    'date_help'              => 'Le format de la date et de l’heure dans votre fichier CSV. Utiliser les formats comme indiqué sur <a href="https://secure.php.net/manual/en/datetime.createfromformat.php#refsect1-datetime.createfromformat-parameters"> cette page</a>. La valeur par défaut va extraire les dates qui ressemblent à ceci: :dateExample.',
    'delimiter_help'         => 'Choisissez le délimiteur de champ qui est utilisé dans votre fichier d’entrée. Si vous n’êtes pas certain, la virgule est l’option la plus sûre.',
    'import_account_help'    => 'Si votre fichier CSV ne contient AUCUNE informations concernant vos compte(s) actif, utilisez cette liste déroulante pour choisir à quel compte les opérations contenues dans le CSV font référence.',
    'upload_not_writeable'   => 'La case grise contient un chemin d’accès. Il devrait être accessible en écriture. Veuillez vous en assurer.',

    // roles
    'column_roles_title'     => 'Définir le rôle des colonnes',
    'column_roles_text'      => '<p>Firefly III cannot guess what data each column contains. You must tell Firefly which kinds of data to expect. The example data can guide you into picking the correct type from the dropdown. If a column cannot be matched to a useful data type, please let me know <a href="https://github.com/JC5/firefly-iii/issues/new">by creating an issue</a>.</p><p>Some values in your CSV file, such as account names or categories, may already exist in your Firefly III database. If you select "map these values" Firefly will not attempt to search for matching values itself but allow you to match the CSV values against the values in your database. This allows you to fine-tune the import.</p>',
    'column_roles_table'     => 'Tableau',
    'column_name'            => 'Nom de colonne',
    'column_example'         => 'Exemple',
    'column_role'            => 'Sens de la donnée',
    'do_map_value'           => 'Mapper ces valeurs',
    'column'                 => 'Colonne',
    'no_example_data'        => 'Pas de données disponibles',
    'store_column_roles'     => 'Continuer l\'import',
    'do_not_map'             => '(Ne pas mapper)',
    'map_title'              => 'Lier les données importées aux données Firefly III',
    'map_text'               => 'In the following tables, the left value shows you information found in your uploaded CSV file. It is your task to map this value, if possible, to a value already present in your database. Firefly will stick to this mapping. If there is no value to map to, or you do not wish to map the specific value, select nothing.',

    'field_value'          => 'Valeur du champ',
    'field_mapped_to'      => 'Mappé à',
    'store_column_mapping' => 'Sauvegarder le mapping',

    // map things.


    'column__ignore'                => '(ignorer cette colonne)',
    'column_account-iban'           => 'Compte d’actif (IBAN)',
    'column_account-id'             => 'Compte d\'actif (ID correspondant à Firefly)',
    'column_account-name'           => 'Compte d’actif (nom)',
    'column_amount'                 => 'Montant',
    'column_amount-comma-separated' => 'Montant (virgule comme séparateur décimal)',
    'column_bill-id'                => 'Facture (ID correspondant à Firefly)',
    'column_bill-name'              => 'Nom de la facture',
    'column_budget-id'              => 'Budget (ID correspondant à Firefly)',
    'column_budget-name'            => 'Nom du budget',
    'column_category-id'            => 'Catégorie (ID correspondant à Firefly)',
    'column_category-name'          => 'Nom de la catégorie',
    'column_currency-code'          => 'Code des monnaies (<a href="https://fr. wikipedia. org/wiki/ISO_4217">ISO 4217</a>)',
    'column_currency-id'            => 'Devise (ID correspondant à Firefly)',
    'column_currency-name'          => 'Nom de la devise (correspondant à Firefly)',
    'column_currency-symbol'        => 'Symbole de la devise (correspondant à Firefly)',
    'column_date-interest'          => 'Date de calcul des intérêts',
    'column_date-book'              => 'Date de valeur de la transaction',
    'column_date-process'           => 'Date de traitement de la transaction',
    'column_date-transaction'       => 'Date',
    'column_description'            => 'Description',
    'column_opposing-iban'          => 'Compte destination(IBAN)',
    'column_opposing-id'            => 'Compte destination(ID correspondant Firefly)',
    'column_external-id'            => 'Identifiant externe',
    'column_opposing-name'          => 'Compte destination (nom)',
    'column_rabo-debet-credit'      => 'Rabobank specific debet/credit indicator',
    'column_ing-debet-credit'       => 'ING specific debet/credit indicator',
    'column_sepa-ct-id'             => 'SEPA Credit Transfer end-to-end ID',
    'column_sepa-ct-op'             => 'SEPA Credit Transfer opposing account',
    'column_sepa-db'                => 'SEPA Direct Debet',
    'column_tags-comma'             => 'Tags (séparé par des virgules)',
    'column_tags-space'             => 'Tags(séparé par des espaces)',
    'column_account-number'         => 'Compte d’actif (numéro de compte)',
    'column_opposing-number'        => 'Compte destination (numéro de compte)',
];