<?php

/**
 * email.php
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
    // common items
    'greeting'                         => 'Hej,',
    'closing'                          => 'Pip boop,',
    'signature'                        => 'Firefly III Epost Robot',
    'footer_ps'                        => 'P.S. Detta meddelande skickades efter en begäran från IP :ipAddress begärde det.',

    // admin test
    'admin_test_subject'               => 'Ett testmeddelande från din Firefly III-installation',
    'admin_test_body'                  => 'Detta är ett testmeddelande från din Firefly III-instans. Det skickades till :email.',

    // new IP
    'login_from_new_ip'                => 'Ny inloggning för Firefly III',
    'new_ip_body'                      => 'Firefly III upptäckte en ny inloggning på ditt konto från en okänd IP-adress. Om du aldrig loggat in från IP-adressen nedan, eller om det har varit mer än sex månader sedan, kommer Firefly III att varna dig.',
    'new_ip_warning'                   => 'Om du känner igen denna IP-adress eller inloggningen kan du ignorera detta meddelande. Om det inte var du, eller om du inte har någon aning om vad detta handlar om, verifiera din lösenordssäkerhet, ändra den och logga ut alla andra sessioner. För att göra detta, gå till din profilsida. Naturligtvis har du redan 2FA aktiverat, eller hur? Håll dig säker!',
    'ip_address'                       => 'IP-adress',
    'host_name'                        => 'Värd',
    'date_time'                        => 'Datum + tid',

    // access token created
    'access_token_created_subject'     => 'En ny åtkomsttoken skapades',
    'access_token_created_body'        => 'Någon (förhoppningsvis du) har just skapat en ny Firefly III API Access-token för ditt användarkonto.',
    'access_token_created_explanation' => 'Med denna token, kan de få tillgång till <strong>alla</strong> av dina finansiella poster genom Firefly III API.',
    'access_token_created_revoke'      => 'Om detta inte var du, återkalla denna token så snart som möjligt på :url.',

    // registered
    'registered_subject'               => 'Välkommen till Firefly III!',
    'registered_welcome'               => 'Välkommen till <a style="color:#337ab7" href=":address">Firefly III</a>. Din registrering lyckades, och detta e-postmeddelande är här för att bekräfta det. Yay!',
    'registered_pw'                    => 'Om du redan har glömt ditt lösenord, vänligen återställ det med <a style="color:#337ab7" href=":address/password/reset">lösenordsåterställningsverktyget</a>.',
    'registered_help'                  => 'Det finns en hjälp-ikon i det övre högra hörnet av varje sida. Om du behöver hjälp, klicka på den!',
    'registered_doc_html'              => 'Om du inte redan har gjort det, läs <a style="color:#337ab7" href="https://docs.firefly-iii.org/about-firefly-iii/grand-theory">stora idén.</a>.',
    'registered_doc_text'              => 'Om du inte redan har gjort det, läs den första användarguiden och den fullständiga beskrivningen.',
    'registered_closing'               => 'Ha det så kul!',
    'registered_firefly_iii_link'      => 'Firefly III:',
    'registered_pw_reset_link'         => 'Återställ lösenord:',
    'registered_doc_link'              => 'Dokumentation:',

    // email change
    'email_change_subject'             => 'Din Firefly III e-postadress har ändrats',
    'email_change_body_to_new'         => 'Du eller någon med åtkomst till ditt Firefly III konto har ändrat din e-postadress. Om du inte förväntade dig detta meddelande, vänligen ignorera och ta bort det.',
    'email_change_body_to_old'         => 'Du eller någon med åtkomst till ditt Firefly III-konto har ändrat din e-postadress. Om du inte förväntade dig att detta skulle ske, <strong>måste du</strong> följa länken "ångra" nedan för att skydda ditt konto!',
    'email_change_ignore'              => 'Om du startade denna ändring kan du säkert ignorera detta meddelande.',
    'email_change_old'                 => 'Den gamla e-postadressen var: :email',
    'email_change_old_strong'          => 'Den gamla e-postadressen var: <strong>:email</strong>',
    'email_change_new'                 => 'Den nya e-postadressen är: :email',
    'email_change_new_strong'          => 'Den nya e-postadressen är: <strong>:email</strong>',
    'email_change_instructions'        => 'Du kan inte använda Firefly III förrän du bekräftar denna ändring. Följ länken nedan för att göra det.',
    'email_change_undo_link'           => 'För att ångra ändringen, följ denna länk:',

    // OAuth token created
    'oauth_created_subject'            => 'En ny OAuth klient har skapats',
    'oauth_created_body'               => 'Någon (förhoppningsvis du) har just skapat en ny Firefly III API OAuth Client för ditt användarkonto. Den är märkt ":name" och har callback URL <span style="font-family: monospace;">:url</span>.',
    'oauth_created_explanation'        => 'Med denna klient, kan de komma åt <strong>alla</strong> av dina finansiella poster genom Firefly III API.',
    'oauth_created_undo'               => 'Om detta inte var du, vänligen återkalla denna klient så snart som möjligt på :url.',

    // reset password
    'reset_pw_subject'                 => 'Begäran om lösenordåterställning',
    'reset_pw_instructions'            => 'Någon försökte återställa ditt lösenord. Om det var du, följ länken nedan för att göra det.',
    'reset_pw_warning'                 => '<strong>VÄNLIGEN</strong> kontrollera att länken faktiskt går till den Firefly III du förväntar dig att den ska gå!',

    // error
    'error_subject'                    => 'Hittade ett fel i Firefly III',
    'error_intro'                      => 'Firefly III v:version stötte på ett fel: <span style="font-family: monospace;">:errorMessage</span>.',
    'error_type'                       => 'Felet var av typen ":class".',
    'error_timestamp'                  => 'Felet inträffade vid/på: :time.',
    'error_location'                   => 'Detta fel inträffade i filen "<span style="font-family: monospace;">:file</span>" på rad :line med kod :code.',
    'error_user'                       => 'Felet påträffades av användaren #:id, <a href="mailto::email">:email</a>.',
    'error_no_user'                    => 'Det fanns ingen användare inloggad för detta fel eller så upptäcktes ingen användare.',
    'error_ip'                         => 'IP-adressen relaterad till detta fel är: :ip',
    'error_url'                        => 'URL är: :url',
    'error_user_agent'                 => 'Användaragent: :userAgent',
    'error_stacktrace'                 => 'Komplett stacktrace finns nedan. Om du tror att detta är en bugg i Firefly III, kan du vidarebefordra detta meddelande till <a href="mailto:james@firefly-iii.org?subject=BUG!">james@firefly-iii. rg</a>. Detta kan hjälpa till att åtgärda felet du just stött på.',
    'error_github_html'                => 'Om du föredrar kan du även öppna ett nytt ärende på <a href="https://github.com/firefly-iii/firefly-iii/issues">GitHub</a>.',
    'error_github_text'                => 'Om du föredrar kan du även öppna ett nytt ärende på https://github.com/firefly-ii/firefly-ii/issues.',
    'error_stacktrace_below'           => 'Komplett stacktrace nedan:',

    // report new journals
    'new_journals_subject'             => 'Firefly III har skapat en ny transaktion|Firefly III har skapat :count nya transaktioner',
    'new_journals_header'              => 'Firefly III har skapat en transaktion åt dig. Du hittar den i din Firefly III-installation:|Firefly III har skapat :count transaktioner åt dig. Du hittar dem i din Firefly III-installation:',
];
