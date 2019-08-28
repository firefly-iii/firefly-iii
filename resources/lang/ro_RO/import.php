<?php

/**
 * import.php
 * Copyright (c) 2018 thegrumpydictator@gmail.com
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
 * along with Firefly III. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

return [
    // ALL breadcrumbs and subtitles:
    'index_breadcrumb'                    => 'Importă tranzacții în Firefly III',
    'prerequisites_breadcrumb_fake'       => 'Cerințe preliminare pentru furnizorul de import fals',
    'prerequisites_breadcrumb_spectre'    => 'Premisele pentru Spectre',
    'prerequisites_breadcrumb_bunq'       => 'Premisele pentru bunq',
    'prerequisites_breadcrumb_ynab'       => 'Premisele pentru YNAB',
    'job_configuration_breadcrumb'        => 'Configurare pentru ":key"',
    'job_status_breadcrumb'               => 'Statutul import pentru ":key"',
    'disabled_for_demo_user'              => 'dezactivat în demo',

    // index page:
    'general_index_intro'                 => 'Bine ați venit la rutina de import Firefly III. Există câteva moduri de a importa date în Firefly III, afișate aici ca butoane.',

    // import provider strings (index):
    'button_fake'                         => 'Simulează un import',
    'button_file'                         => 'Importă un fișier',
    'button_bunq'                         => 'Import din bunq',
    'button_spectre'                      => 'Import folosind Spectre',
    'button_plaid'                        => 'Import folosind Plaid',
    'button_yodlee'                       => 'Import folosind Yodlee',
    'button_quovo'                        => 'Import folosind Quovo',
    'button_ynab'                         => 'Import din You Need A Budget',
    'button_fints'                        => 'Import folosind FinTS',


    // prerequisites box (index)
    'need_prereq_title'                   => 'Premise de import',
    'need_prereq_intro'                   => 'Unele metode de import necesită atenția dvs. înainte ca acestea să poată fi utilizate. De exemplu, pot necesita chei API speciale sau parole de aplicație. Puteți să le configurați aici. Pictograma indică dacă aceste condiții preliminare au fost îndeplinite.',
    'do_prereq_fake'                      => 'Cerințe preliminare pentru furnizorul de import fals',
    'do_prereq_file'                      => 'Premisele pentru importurile de fişier',
    'do_prereq_bunq'                      => 'Premisele pentru importurile din bunq',
    'do_prereq_spectre'                   => 'Premisele pentru importurile folosind Spectre',
    'do_prereq_plaid'                     => 'Premisele pentru importurile din Plaid',
    'do_prereq_yodlee'                    => 'Premisele pentru importurile folosind Yodlee',
    'do_prereq_quovo'                     => 'Premisele pentru importurile folosind Quovo',
    'do_prereq_ynab'                      => 'Premisele pentru importurile din YNAB',

    // prerequisites:
    'prereq_fake_title'                   => 'Premisele pentru un import fals de la furnizorul de servicii',
    'prereq_fake_text'                    => 'Acest furnizor de fals necesită un o cheie API falsă. Acesta trebuie să fie de 32 de caractere. Îl puteţi folosi pe acesta: 123456789012345678901234567890AA',
    'prereq_spectre_title'                => 'Premisele pentru un import folosind API-ul Spectre',
    'prereq_spectre_text'                 => 'Pentru a importa date utilizând API-ul Spectre (v4), trebuie să furnizaţi către Firefly III două valori secrete. Acestea pot fi găsite <a href="https://www.saltedge.com/clients/profile/secrets">în pagina de secrete</a>.',
    'prereq_spectre_pub'                  => 'De asemenea, API-ul Spectre trebuie să cunoască cheia publică pe care o vedeţi mai jos. Fără ea, acesta nu vă va recunoaşte. Vă rugăm să introduceţi această cheie publică <a href="https://www.saltedge.com/clients/profile/secrets">în pagina de secrete</a>.',
    'prereq_bunq_title'                   => 'Premisele pentru un import din bunq',
    'prereq_bunq_text'                    => 'Pentru a importa din bunq, aveţi nevoie să obţineți o cheie API. Puteţi face acest lucru prin intermediul aplicașiei. Vă rugăm să reţineţi că funcţia de import pentru bunq este în versiune BETA. Aceasta a fost testată doar cu API-ul din sandbox.',
    'prereq_bunq_ip'                      => 'bunq cere adresa dvs IP externă. Firefly al III-lea a încercat să completeze acest lucru folosind <a href="https://www.ipify.org/">serviciul ipify </a>. Asiguraţi-vă că acest IP este corect sau importul va eşua.',
    'prereq_ynab_title'                   => 'Premisele pentru un import din YNAB',
    'prereq_ynab_text'                    => 'Pentru a putea descărca tranzacții de la YNAB, creați o nouă aplicație pe <a href="https://app.youneedabudget.com/settings/developer"> Pagina cu setările pentru dezvoltatori </a> și introduceți ID-ul clientului și parola secretă pe această pagină.',
    'prereq_ynab_redirect'                => 'Pentru a finaliza configurația, introduceți următoarea adresă URL în <a href="https://app.youneedabudget.com/settings/developer"> Pagina cu setări pentru dezvoltatori </a> în secțiunea "Redirect URI(s) - URI de redirecționare".',
    'callback_not_tls'                    => 'Firefly III a detectat următorul calback URI. Se pare că serverul dvs. nu este configurat să accepte conexiuni TLS (https). YNAB nu va accepta acest URI. Puteți continua importul (deoarece Firefly III ar putea greși), dar vă rugăm să păstrați acest lucru în minte.',
    // prerequisites success messages:
    'prerequisites_saved_for_fake'        => 'Cheia API falsă a fost stocată cu succes!',
    'prerequisites_saved_for_spectre'     => 'App ID și secret stocate!',
    'prerequisites_saved_for_bunq'        => 'Cheia API și adresa IP stocată!',
    'prerequisites_saved_for_ynab'        => 'ID-ul clientului YNAB și parola secretă au fost stocate!',

    // job configuration:
    'job_config_apply_rules_title'        => 'Configurarea Job-ului - aplicați regulile dvs.?',
    'job_config_apply_rules_text'         => 'Odată ce furnizorul fals a rulat, regulile dvs. pot fi aplicate tranzacțiilor. Aceasta adaugă timp la import.',
    'job_config_input'                    => 'Datele introduse de dvs.',
    // job configuration for the fake provider:
    'job_config_fake_artist_title'        => 'Introduceți numele albumului',
    'job_config_fake_artist_text'         => 'Multe rutine de import au câțiva pași de configurare ce trebuie să-i faceți. În cazul furnizorului fals de import, trebuie să răspundeți la câteva întrebări ciudate. În acest caz, introduceți "David Bowie" pentru a continua.',
    'job_config_fake_song_title'          => 'Introduceţi numele cântecului',
    'job_config_fake_song_text'           => 'Menţiona melodia "Golden years - Anii de aur" pentru a continua cu importul fals.',
    'job_config_fake_album_title'         => 'Introduceți numele albumului',
    'job_config_fake_album_text'          => 'Unele rutine de import necesită date suplimentare la jumătatea perioadei de import. În cazul furnizorului fals de import, trebuie să răspundeți la câteva întrebări ciudate. Introduceți "Station to station" pentru a continua.',
    // job configuration form the file provider
    'job_config_file_upload_title'        => 'Configurare import (1/4) - Încărcați fișierul',
    'job_config_file_upload_text'         => 'Această rutină vă va ajuta să importați fișiere din banca dvs. în Firefly III.',
    'job_config_file_upload_help'         => 'Selectaţi fişierul. Asigurați-vă că fişierul este codificat UTF-8.',
    'job_config_file_upload_config_help'  => 'Dacă ați importat anterior date în Firefly III, este posibil să aveți un fișier de configurare, care va preseta valorile de configurare pentru dvs. Pentru unele bănci, alți utilizatori au oferit cu amabilitate <a href="https://github.com/firefly-iii/import-configurations/wiki"> fișierul de configurare </a>',
    'job_config_file_upload_type_help'    => 'Selectați tipul de fișier pe care îl încărcați',
    'job_config_file_upload_submit'       => 'Încarcă fişiere',
    'import_file_type_csv'                => 'CSV (valori separate prin virgulă)',
    'import_file_type_ofx'                => 'OFX',
    'file_not_utf8'                       => 'Fișierul pe care l-ați încărcat nu este codificat ca UTF-8 sau ASCII. Firefly III nu poate gestiona astfel de fișiere. Utilizați Notepad ++ sau Sublime pentru a vă converti fișierul în UTF-8.',
    'job_config_uc_title'                 => 'Configurare import (2/4) - configurare fișier de bază',
    'job_config_uc_text'                  => 'Pentru a putea importa fișierul corect, validați opțiunile de mai jos.',
    'job_config_uc_header_help'           => 'Bifați această casetă dacă primul rând al fișierului dvs. CSV reprezintă titlurile coloanei.',
    'job_config_uc_date_help'             => 'Formatul datei n fișierul dvs. Urmați formatul <a href="https://secure.php.net/manual/en/datetime.createfromformat.php#refsect1-datetime.createfromformat-parameters">din această pagină </a>. Valoarea implicită va analiza datele care arată astfel: :dateExample.',
    'job_config_uc_delimiter_help'        => 'Alegeți delimitatorul de câmp utilizat în fișierul de intrare. Dacă nu sunteți sigur, virgula este cea mai sigură opțiune.',
    'job_config_uc_account_help'          => 'Dacă fișierul dvs. NU conține informații despre contul(conturile) de active, utilizați acest dropdown pentru a selecta în ce cont aparțin tranzacțiile din fișier.',
    'job_config_uc_apply_rules_title'     => 'Aplică reguli',
    'job_config_uc_apply_rules_text'      => 'Aplică regulile dvs. pentru fiecare tranzacție importată. Rețineți că acest lucru încetinește semnificativ importul.',
    'job_config_uc_specifics_title'       => 'Opţiunile specifice pentru banca',
    'job_config_uc_specifics_txt'         => 'Unele bănci furnizează fișiere prost formatate. Firefly III le poate remedia în mod automat. Dacă banca dvs. furnizează astfel de fișiere, dar nu este listată aici, vă rugăm să deschideți o problemă pe GitHub.',
    'job_config_uc_submit'                => 'Continuă',
    'invalid_import_account'              => 'Ați selectat un cont nevalid în care să importați.',
    'import_liability_select'             => 'Provizioane',
    // job configuration for Spectre:
    'job_config_spectre_login_title'      => 'Alegeţi datele de conectare',
    'job_config_spectre_login_text'       => 'Firefly III a găsit :count login-urile existente în contul dvs. Spectre. De la care doriți să importați?',
    'spectre_login_status_active'         => 'Activ',
    'spectre_login_status_inactive'       => 'Inactiv',
    'spectre_login_status_disabled'       => 'Dezactivat',
    'spectre_login_new_login'             => 'Conectați-vă la o altă bancă sau la una dintre aceste bănci cu acreditări diferite.',
    'job_config_spectre_accounts_title'   => 'Selectaţi conturile din care doriți să se importe',
    'job_config_spectre_accounts_text'    => 'Ați selectat ":name" (:country). Aveți :count cont(uri)disponibile de la acest furnizor. Selectați contul (urile) de active Firefly III în care trebuie să fie stocate tranzacțiile din aceste conturi. Rețineți că, pentru a importa date, contul Firefly III și contul ":name" trebuie să aibă aceeași monedă.',
    'spectre_do_not_import'               => '(nu importați)',
    'spectre_no_mapping'                  => 'Se pare că nu ați selectat niciun cont de unde să importați.',
    'imported_from_account'               => 'Importat din ":account"',
    'spectre_account_with_number'         => 'Contul :number',
    'job_config_spectre_apply_rules'      => 'Aplică reguli',
    'job_config_spectre_apply_rules_text' => 'Implicit, regulile dvs. vor fi aplicate tranzacțiilor create în timpul acestei rutine de import. Dacă nu doriți ca acest lucru să se întâmple, deselectați această casetă de selectare.',

    // job configuration for bunq:
    'job_config_bunq_accounts_title'      => 'Conturi bunq',
    'job_config_bunq_accounts_text'       => 'Acestea sunt conturile asociate contului tău bunq. Selectați conturile din care doriți să importați și în ce cont trebuie să fie importate tranzacțiile.',
    'bunq_no_mapping'                     => 'Se pare că nu ați selectat niciun cont de unde să importați.',
    'should_download_config'              => 'Ar trebui să descărcați <a href=":route"> fișierul de configurare </a> pentru acest job. Acest lucru va ușura importurile viitoare.',
    'share_config_file'                   => 'Dacă ați importat date dintr-o bancă publică, trebuie să <a href="https://github.com/firefly-iii/import-configurations/wiki"> partajați fișierul de configurare </a>, astfel încât să fie ușor pentru alți utilizatori să importe datele lor. Partajarea fișierului dvs. de configurare nu va expune detaliile dvs. financiare.',
    'job_config_bunq_apply_rules'         => 'Aplică reguli',
    'job_config_bunq_apply_rules_text'    => 'Implicit, regulile dvs. vor fi aplicate tranzacțiilor create în timpul acestei rutine de import. Dacă nu doriți ca acest lucru să se întâmple, deselectați această casetă de selectare.',
    'bunq_savings_goal'                   => 'Obiectiv economisire: :amount (:percentage%)',
    'bunq_account_status_CANCELLED'       => 'Închide contul bunq',

    'ynab_account_closed'                  => 'Contul este inchis!',
    'ynab_account_deleted'                 => 'Contul este șters!',
    'ynab_account_type_savings'            => 'cont de economii',
    'ynab_account_type_checking'           => 'cont curent',
    'ynab_account_type_cash'               => 'cont de numerar',
    'ynab_account_type_creditCard'         => 'card de credit',
    'ynab_account_type_lineOfCredit'       => 'linie de credit',
    'ynab_account_type_otherAsset'         => 'alt cont de active',
    'ynab_account_type_otherLiability'     => 'alte datorii',
    'ynab_account_type_payPal'             => 'Paypal',
    'ynab_account_type_merchantAccount'    => 'cont de comerciant',
    'ynab_account_type_investmentAccount'  => 'cont de investitii',
    'ynab_account_type_mortgage'           => 'credit ipotecar',
    'ynab_do_not_import'                   => '(nu importați)',
    'job_config_ynab_apply_rules'          => 'Aplică reguli',
    'job_config_ynab_apply_rules_text'     => 'Implicit, regulile dvs. vor fi aplicate tranzacțiilor create în timpul acestei rutine de import. Dacă nu doriți ca acest lucru să se întâmple, deselectați această casetă de selectare.',

    // job configuration for YNAB:
    'job_config_ynab_select_budgets'       => 'Selectaţi bugetul',
    'job_config_ynab_select_budgets_text'  => 'Aveți :count bugete stocate în YNAB. Vă rugăm să selectaţi din care Firefly III va importa tranzacţiile.',
    'job_config_ynab_no_budgets'           => 'Nu există bugete disponibile pentru a fi importate.',
    'ynab_no_mapping'                      => 'Se pare că nu ați selectat niciun cont de unde să importați.',
    'job_config_ynab_bad_currency'         => 'Nu puteți importa din următorul buget (următoarele bugete), deoarece nu aveți conturi cu aceeași monedă ca și aceste bugete.',
    'job_config_ynab_accounts_title'       => 'Selectaţi contul',
    'job_config_ynab_accounts_text'        => 'Aveți la dispoziție următoarele conturi în acest buget. Selectați din conturile pe care doriți să le importați și unde ar trebui să fie stocate tranzacțiile.',


    // keys from "extra" array:
    'spectre_extra_key_iban'               => 'IBAN',
    'spectre_extra_key_swift'              => 'SWIFT',
    'spectre_extra_key_status'             => 'Statut',
    'spectre_extra_key_card_type'          => 'Tip card',
    'spectre_extra_key_account_name'       => 'Nume cont',
    'spectre_extra_key_client_name'        => 'Nume client',
    'spectre_extra_key_account_number'     => 'Număr de cont',
    'spectre_extra_key_blocked_amount'     => 'Suma blocată',
    'spectre_extra_key_available_amount'   => 'Sumă disponibilă',
    'spectre_extra_key_credit_limit'       => 'Limita de credit',
    'spectre_extra_key_interest_rate'      => 'Rata dobânzii',
    'spectre_extra_key_expiry_date'        => 'Data expirării',
    'spectre_extra_key_open_date'          => 'Data deschidere',
    'spectre_extra_key_current_time'       => 'Ora curentă',
    'spectre_extra_key_current_date'       => 'Data curentă',
    'spectre_extra_key_cards'              => 'Carduri',
    'spectre_extra_key_units'              => 'Unităţi',
    'spectre_extra_key_unit_price'         => 'Preţ unitar',
    'spectre_extra_key_transactions_count' => 'Numărul de tranzacții',

    //job configuration for finTS
    'fints_connection_failed'              => 'A apărut o eroare în timp ce încercați să vă conectați la banca dvs. Asigurați-vă că toate datele pe care le-ați introdus sunt corecte. Mesaj de eroare original: :originalError',

    'job_config_fints_url_help'       => 'Exemplu https://banking-dkb.s-fints-pt-dkb.de/fints30',
    'job_config_fints_username_help'  => 'Pentru mai multe bănci, acesta este numărul de cont.',
    'job_config_fints_port_help'      => 'Portul prestabilit este 443.',
    'job_config_fints_account_help'   => 'Selectaţi contul pentru care doriţi să importaţi tranzacţii.',
    'job_config_local_account_help'   => 'Alegeți contul Firefly III corespunzător contului dvs. bancar ales mai sus.',
    // specifics:
    'specific_ing_name'               => 'ING NL',
    'specific_ing_descr'              => '41/5000
Creați descrieri mai bune în exporturile ING',
    'specific_sns_name'               => 'SNS / Volksbank NL',
    'specific_sns_descr'              => 'Reguli din exportul de fișiere SNS / Volksbank',
    'specific_abn_name'               => 'ABN AMRO NL',
    'specific_abn_descr'              => 'Remediază posibile probleme cu fișierele ABN AMRO',
    'specific_rabo_name'              => 'Rabobank NL',
    'specific_rabo_descr'             => 'Remediază posibile probleme cu fișierele Rabobank',
    'specific_pres_name'              => 'Președintele CA pentru alegerea financiară',
    'specific_pres_descr'             => 'Remediază posibile probleme cu fișierele PC',
    'specific_belfius_name'           => 'Belfius BE',
    'specific_belfius_descr'          => 'Remediază posibile probleme cu fișierele Belfius',
    'specific_ingbelgium_name'        => 'ING BE',
    'specific_ingbelgium_descr'       => 'Fixes potential problems with ING Belgium files',
    // job configuration for file provider (stage: roles)
    'job_config_roles_title'          => 'Configurarea importului (3/4) - Definiți rolul fiecărei coloane',
    'job_config_roles_text'           => 'Fiecare coloană din fișierul dvs. CSV conține anumite date. Vă rugăm să indicați ce fel de date ar trebui să aștepte importatorul. Opțiunea de a "mapa" datele înseamnă că veți conecta fiecare intrare găsită în coloană cu o valoare din baza dvs. de date. O coloană desenată de multe ori este coloana care conține IBAN-ul contului opus. Acest lucru poate fi ușor comparat cu prezența IBAN în baza dvs. de date.',
    'job_config_roles_submit'         => 'Continuă',
    'job_config_roles_column_name'    => 'Numele coloanei',
    'job_config_roles_column_example' => 'Exemplu de date de coloană',
    'job_config_roles_column_role'    => 'Semnificația datelor din coloană',
    'job_config_roles_do_map_value'   => 'Harta acestor valori',
    'job_config_roles_no_example'     => 'Nu există exemple disponibile',
    'job_config_roles_fa_warning'     => 'Dacă marcați o coloană ca având o sumă într-o valută străină, trebuie să setați și coloana care conține ce valută este.',
    'job_config_roles_rwarning'       => 'Cel puțin, marcați o coloană ca și coloană sumă. Se recomandă de asemenea să selectați o coloană pentru descriere, data și contul opus.',
    'job_config_roles_colum_count'    => 'Coloană',
    // job config for the file provider (stage: mapping):
    'job_config_map_title'            => 'Configurare import (4/4) - Conectați date de import la Firefly III',
    'job_config_map_text'             => 'În tabelele următoare, valoarea din stânga vă arată informațiile găsite în fișierul încărcat. Sarcina dvs. este aceea de a mapa această valoare, dacă este posibil, la o valoare deja prezentă în baza dvs. de date. Firefly se va lipi de această cartografiere. Dacă nu există nicio valoare pentru care să fie mapată sau dacă nu doriți să cartografiați valoarea specifică, nu selectați nimic.',
    'job_config_map_nothing'          => 'Nu există date prezente în fișierul dvs. pe care să le puteți mapa la valorile existente. Vă rugăm să apăsați "Start import" pentru a continua.',
    'job_config_field_value'          => 'Valoarea câmpului',
    'job_config_field_mapped'         => 'Mapat la',
    'map_do_not_map'                  => '(nu mapați)',
    'job_config_map_submit'           => 'Porniți importul',


    // import status page:
    'import_with_key'                 => 'Importați cu cheia \':key\'',
    'status_wait_title'               => 'Vă rugăm să așteptați...',
    'status_wait_text'                => 'Această casetă va dispărea într-o clipă.',
    'status_running_title'            => 'Importul se execută',
    'status_job_running'              => 'Așteptați, importul se execută...',
    'status_job_storing'              => 'Așteptați, stocăm datele...',
    'status_job_rules'                => 'Așteptați, rulăm regulile...',
    'status_fatal_title'              => 'Eroare fatala',
    'status_fatal_text'               => 'Importul a întampinat o eroare și nu s-a putut recupera. Ne cerem scuze!',
    'status_fatal_more'               => 'Acest mesaj de eroare (posibil foarte criptic) este completat de fișierele jurnal, pe care le puteți găsi pe unitatea hard disk sau în containerul Docker de unde executați Firefly III.',
    'status_finished_title'           => 'Importul s-a terminat',
    'status_finished_text'            => 'Importul s-a terminat.',
    'finished_with_errors'            => 'Au existat unele erori în timpul importului. Revedeți-le cu atenție.',
    'unknown_import_result'           => 'Rezultat necunoscut pentru import',
    'result_no_transactions'          => 'Nu au fost importate tranzacții. Poate că toate au fost duplicate. Poate că fișierele de jurnale vă pot spune ce s-a întâmplat. Dacă importați date în mod regulat, este normal.',
    'result_one_transaction'          => 'Exact o tranzacție a fost importată. Aceasta este stocată sub eticheta <a href=":route" class="label label-success" style="font-size:100%;font-weight:normal;">:tag</a> unde o puteți inspecta mai departe.',
    'result_many_transactions'        => 'Firefly III a importat :count tranzacții. Ele sunt stocate sub eticheta <a href=":route" class="label label-success" style="font-size:100%;font-weight:normal;">:tag</a> unde le puteți inspecta mai departe.',


    // general errors and warnings:
    'bad_job_status'                  => 'Pentru a accesa această pagină, job-ul de import nu poate avea statusul ":status".',

    // column roles for CSV import:
    'column__ignore'                  => '(ignorați această coloană)',
    'column_account-iban'             => 'Contul contului de active (IBAN)',
    'column_account-id'               => 'ID-ul contului de activ (care se potrivește cu FF3)',
    'column_account-name'             => 'Cont activ (nume)',
    'column_account-bic'              => 'Cont activ (BIC)',
    'column_amount'                   => 'Sumă',
    'column_amount_foreign'           => 'Sumă (în monedă străină)',
    'column_amount_debit'             => 'Sumă (coloana de debit)',
    'column_amount_credit'            => 'Sumă (coloana de credit)',
    'column_amount_negated'           => 'Sumă (coloană negată)',
    'column_amount-comma-separated'   => 'Sumă (virgula ca separator zecimal)',
    'column_bill-id'                  => 'ID-ul facturii (care se potrivește cu FF3)',
    'column_bill-name'                => 'Nume de factură',
    'column_budget-id'                => 'ID-ul bugetului (care se potrivește cu FF3)',
    'column_budget-name'              => 'Nume buget',
    'column_category-id'              => 'ID-ul categoriei (care se potrivește cu FF3)',
    'column_category-name'            => 'Numele categoriei',
    'column_currency-code'            => 'Cod valută (ISO 4217)',
    'column_foreign-currency-code'    => 'Codul de valută străină (ISO 4217)',
    'column_currency-id'              => 'ID-ul monedei (care se potrivește cu FF3)',
    'column_currency-name'            => 'Numele monedei (care se potrivește cu FF3)',
    'column_currency-symbol'          => 'Simbolul monedei (care se potrivește cu FF3)',
    'column_date-interest'            => 'Data de calcul a dobânzii',
    'column_date-book'                => 'Data rezervării tranzacției',
    'column_date-process'             => 'Data procesării tranzacției',
    'column_date-transaction'         => 'Dată',
    'column_date-due'                 => 'Data expirării tranzacției',
    'column_date-payment'             => 'Data plății tranzacției',
    'column_date-invoice'             => 'Data facturării tranzacției',
    'column_description'              => 'Descriere',
    'column_opposing-iban'            => 'Contul opus (IBAN)',
    'column_opposing-bic'             => 'Contul opus (BIC)',
    'column_opposing-id'              => 'ID-ul contului opus (care se potrivește cu FF3)',
    'column_external-id'              => 'ID Extern',
    'column_opposing-name'            => 'Contul opus (nume)',
    'column_rabo-debit-credit'        => 'Indicatorul specific de debit / credit Rabobank',
    'column_ing-debit-credit'         => 'Indicatorul ING de debit / credit specific',
    'column_generic-debit-credit'     => 'Indicatorul de debit / credit bancar general',
    'column_sepa_ct_id'               => 'Identificator final SEPA',
    'column_sepa_ct_op'               => 'Identificatorul contului opus SEPA',
    'column_sepa_db'                  => 'Identificatorul creditorului SEPA',
    'column_sepa_cc'                  => 'Cod de compensare SEPA',
    'column_sepa_ci'                  => 'Identificatorul creditorului SEPA',
    'column_sepa_ep'                  => 'Scopul extern SEPA',
    'column_sepa_country'             => 'Codul țării SEPA',
    'column_sepa_batch_id'            => 'ID-ul lotului SEPA',
    'column_tags-comma'               => 'Etichete (separate prin virgulă)',
    'column_tags-space'               => 'Etichete (separate prin spațiu)',
    'column_account-number'           => 'Cont activ (numărul contului)',
    'column_opposing-number'          => 'Cont opus (numărul contului)',
    'column_note'                     => 'Notițe',
    'column_internal-reference'       => 'Referință internă',

    // error message
    'duplicate_row'                   => 'Row #:row (":description") could not be imported. It already exists.',

];
