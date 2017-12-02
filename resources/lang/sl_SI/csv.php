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

declare(strict_types=1);

return [

    // initial config
    'initial_title'                 => 'Nastavitve uvoza (1/3) - Osnovne nastavitve',
    'initial_text'                  => 'Če želite pravilno uvoziti svojo datoteko, preverite spodnje nastavitve.',
    'initial_box'                   => 'Osnovne nastavitve uvoza CSV',
    'initial_box_title'             => 'Osnovne nastavitve uvoza CSV',
    'initial_header_help'           => 'Obkljukajte ta okvirček, če prva vrstica v CSV datoteki vsebuje naslove stolpcev.',
    'initial_date_help'             => 'Formatiranje datuma in časa v vaši CSV datoteki. Uporabite obliko zapisa kot je navedena<a href="https://secure.php.net/manual/en/datetime.createfromformat.php#refsect1-datetime.createfromformat-parameters"> na tej strani</a>. Privzeta vrednost bo prepoznala datume, ki so videti takole:: dateExample.',
    'initial_delimiter_help'        => 'Izberi ločilo, ki je uporabljeno za ločevanje med posameznimi stolpci v vaši datoteki. Če niste prepričani, je vejica najbolj pogosta izbira.',
    'initial_import_account_help'   => 'Če vaša CSV datoteka ne vsebuje informacij o vaših premoženjskih računih, uporabite ta seznam, da izberete kateremu računu pripadajo transakcije v CSV datoteki.',
    'initial_submit'                => 'Nadaljujte s korakom 2/3',

    // new options:
    'apply_rules_title'             => 'Apply rules',
    'apply_rules_description'       => 'Apply your rules. Note that this slows the import significantly.',
    'match_bills_title'             => 'Match bills',
    'match_bills_description'       => 'Match your bills to newly created withdrawals. Note that this slows the import significantly.',

    // roles config
    'roles_title'                   => 'Nastavitve uvoza (1/3) - Določite vlogo vsakega stolpca',
    'roles_text'                    => 'Vsak stolpec v vaši datoteki CSV vsebuje določene podatke. Določite, kateri podatki se pričakujejo v katerih stolpcih. Možnost »mapiranja« podatkov pomeni, da boste vsak vnos, ki ste ga našli v stolpcu, povezal z vrednostjo v svoji bazi podatkov. Često izbran je stolpec, ki vsebuje IBAN protiračuna. Tako je mogoče transackijo enostavno povezati s protiračunom v vaši bazi podatkov.',
    'roles_table'                   => 'Tabela',
    'roles_column_name'             => 'Ime stolpca',
    'roles_column_example'          => 'primeri podatkov v stolpcu',
    'roles_column_role'             => 'pomen podatkov v stolpcu',
    'roles_do_map_value'            => 'poveži te vrednosti',
    'roles_column'                  => 'Stolpec',
    'roles_no_example_data'         => 'primerov podatkov ni na voljo',
    'roles_submit'                  => 'Nadaljujte s korakom 2/3',
    'roles_warning'                 => 'Čisto na koncu določite stolpec z zneskom. Priporočljivo je da izberete tudi stolpce z opisom, datumom in protiračunom.',

    // map data
    'map_title'                     => 'Uvozna nastavitev (3/3) - Povežite uvozne podatke s podatki v Firefly III',
    'map_text'                      => 'Vrednosti na levi v spodnji tabeli prikazujejo podatke iz naložene CSV datoteke. Vaša naloga je, da jim, če je možno, določite obtoječio vrednost iz podatkovne baze. Firefly bo to upošteval pri uvozu. Če v podatkovni bazi ni ustrezne vrednosti, ali vrednosti ne želite določiti ničesar, potem pustite prazno.',
    'map_field_value'               => 'podatek',
    'map_field_mapped_to'           => 'povezan z',
    'map_do_not_map'                => '(ne poveži)',
    'map_submit'                    => 'Začnite uvoz',

    // map things.
    'column__ignore'                => '(ignoriraj ta stolpec)',
    'column_account-iban'           => 'premoženjski račun (IBAN)',
    'column_account-id'             => 'ID premoženjskega računa (Firefly)',
    'column_account-name'           => 'premoženjski račun (ime)',
    'column_amount'                 => 'znesek',
    'column_amount_debet'           => 'Znesek v breme',
    'column_amount_credit'          => 'Znesek v dobro',
    'column_amount-comma-separated' => 'znesek (z decimalno vejico)',
    'column_bill-id'                => 'ID trajnika (Firefly)',
    'column_bill-name'              => 'Ime trajnika',
    'column_budget-id'              => 'ID bugžeta (Firefly)',
    'column_budget-name'            => 'ime budžeta',
    'column_category-id'            => 'ID Kategorije (Firefly)',
    'column_category-name'          => 'ime kategorije',
    'column_currency-code'          => 'koda valute (ISO 4217)',
    'column_currency-id'            => 'ID valute (Firefly)',
    'column_currency-name'          => 'ime valute (Firefly)',
    'column_currency-symbol'        => 'simbol valute (Firefly)',
    'column_date-interest'          => 'Datum obračuna obresti',
    'column_date-book'              => 'datum knjiženja transakcije',
    'column_date-process'           => 'datum izvedbe transakcije',
    'column_date-transaction'       => 'datum',
    'column_description'            => 'opis',
    'column_opposing-iban'          => 'ciljni račun (IBAN)',
    'column_opposing-id'            => 'protiračun (firefly)',
    'column_external-id'            => 'zunanja ID številka',
    'column_opposing-name'          => 'ime ciljnega računa',
    'column_rabo-debet-credit'      => 'Poseben indikator za Rabobank',
    'column_ing-debet-credit'       => 'Poseben indikator za banko ING',
    'column_sepa-ct-id'             => 'SEPA številka transakcije',
    'column_sepa-ct-op'             => 'SEPA protiračun',
    'column_sepa-db'                => 'SEPA direktna obremenitev',
    'column_tags-comma'             => 'značke (ločene z vejicami)',
    'column_tags-space'             => 'značke (ločene s presledki)',
    'column_account-number'         => 'premoženjski račun (številka računa)',
    'column_opposing-number'        => 'protiračun (številka računa)',
];
