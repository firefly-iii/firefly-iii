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

    'import_configure_title' => 'Nastavitve uvoza',
    'import_configure_intro' => 'Tu je nekaj nastavitev za uvoz CSV datoteke. Prosim, označite, če vaša CSV datoteka vsebuje prvo vrstico z naslovi stolpcev in v kakšnem formatu so izpisani datumi. Morda bo to zahtevalo nekaj poizkušanja. Ločilo v CSV datoteki je ponavadi ",", lahko pa je tudi ";". Pozorno preverite.',
    'import_configure_form'  => 'Osnovne možnosti za uvoz CSV datoteke.',
    'header_help'            => 'Preverite ali prva vrstica v CSV datoteki vsebuje naslove stolpcev.',
    'date_help'              => 'Formatiranje datuma in časa v vaši CSV datoteki. Uporabite obliko zapisa kot je navedena<a href="https://secure.php.net/manual/en/datetime.createfromformat.php#refsect1-datetime.createfromformat-parameters"> na tej strani</a>. Privzeta vrednost bo prepoznala datume, ki so videti takole:: dateExample.',
    'delimiter_help'         => 'Izberi ločilo, ki je uporabljeno za ločevanje med posameznimi stolpci v vaši datoteki. Če niste prepričani, je vejica najbolj pogosta izbira.',
    'import_account_help'    => 'Če vaša CSV datoteka ne vsebuje informacij o vaših premoženjskih računih, uporabite ta seznam, da izberete kateremu računu pripadajo transakcije v CSV datoteki.',
    'upload_not_writeable'   => 'Prosim zagotovite, da ima Firefly dovoljenje za pisanje v datoteko, ki je navedena v sivem okvirčku.',

    // roles
    'column_roles_title'     => 'doličite pomen stolpcev',
    'column_roles_table'     => 'tabela',
    'column_name'            => 'Ime stolpca',
    'column_example'         => 'primeri podatkov',
    'column_role'            => 'pomen podatkov v stolpcu',
    'do_map_value'           => 'poveži te vrednosti',
    'column'                 => 'stolpec',
    'no_example_data'        => 'primeri podatkov niso na voljo',
    'store_column_roles'     => 'nadaljuj z uvozom',
    'do_not_map'             => '(ne poveži)',
    'map_title'              => 'poveži podatke za uvoz s podatki iz Firefly III',
    'map_text'               => 'Vrednosti na levi v spodnji tabeli prikazujejo podatke iz naložene CSV datoteke. Vaša naloga je, da jim, če je možno, določite obtoječio vrednost iz podatkovne baze. Firefly bo to upošteval pri uvozu. Če v podatkovni bazi ni ustrezne vrednosti, ali vrednosti ne želite določiti ničesar, potem pustite prazno.',

    'field_value'          => 'podatek',
    'field_mapped_to'      => 'povezan z',
    'store_column_mapping' => 'shrani nastavitve',

    // map things.


    'column__ignore'                => '(ignoriraj ta stolpec)',
    'column_account-iban'           => 'premoženjski račun (IBAN)',
    'column_account-id'             => 'ID premoženjskega računa (Firefly)',
    'column_account-name'           => 'premoženjski račun (ime)',
    'column_amount'                 => 'znesek',
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