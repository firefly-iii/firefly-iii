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
    'greeting'                         => 'Hello,',
    'closing'                          => 'Pøuì Poû Põuì,',
    'signature'                        => 'Le Robot de Firefly III',
    'footer_ps'                        => 'PS : Ce message a été envoyé car une requête de l\'adresse IP :ipAddress l\'a déclenché.',

    // admin test
    'admin_test_subject'               => 'Un message de test de votre installation de Firefly III',
    'admin_test_body'                  => 'Ceci est un message de test de votre instance Firefly III. Il a été envoyé à :email.',

    // access token created
    'access_token_created_subject'     => 'Un nouveau jeton d\'accès a été créé',
    'access_token_created_body'        => 'Quelqu\'un (espérons vous) vient de créer un nouveau jeton d\'accès à l\'API Firefly III pour votre compte utilisateur.',
    'access_token_created_explanation' => 'Avec ce jeton, cette personne peut accéder à <strong>toutes vos transactions financières</strong> via l\'API Firefly III.',
    'access_token_created_revoke'      => 'Si ce n\'était pas vous, veuillez révoquer ce jeton dès que possible à :url.',

    // registered
    'registered_subject'               => 'Bienvenue sur Firefly III !',
    'registered_welcome'               => 'Bienvenue sur <a style="color:#337ab7" href=":address">Firefly III</a>. Votre inscription a été enregistrée, et cet e-mail est là pour le confirmer. Wouhou !',
    'registered_pw'                    => 'Si vous avez déjà oublié votre mot de passe, veuillez le réinitialiser en utilisant <a style="color:#337ab7" href=":address/password/reset">l\'outil de réinitialisation du mot de passe</a>.',
    'registered_help'                  => 'Il y a une icône d\'aide en haut à droite de chaque page. Si vous avez besoin d\'aide, cliquez dessus !',
    'registered_doc_html'              => 'Si vous ne l\'avez pas déjà fait, veuillez lire la <a style="color:#337ab7" href="https://docs.firefly-iii.org/about-firefly-iii/grand-theory">documentation</a>.',
    'registered_doc_text'              => 'Si vous ne l\'avez pas déjà fait, veuillez lire le guide de première utilisation.',
    'registered_closing'               => 'À bientôt !',
    'registered_firefly_iii_link'      => 'Firefly III :',
    'registered_pw_reset_link'         => 'Réinitialisation du mot de passe :',
    'registered_doc_link'              => 'Documentation :',

    // email change
    'email_change_subject'             => 'Votre adresse e-mail Firefly III a été modifiée',
    'email_change_body_to_new'         => 'Vous ou quelqu\'un ayant accès à votre compte Firefly III avez changé votre adresse e-mail. Si vous ne vous attendiez pas à ce message, veuillez l\'ignorer et le supprimer.',
    'email_change_body_to_old'         => 'Vous ou quelqu\'un ayant accès à votre compte Firefly III avez changé votre adresse e-mail. Si vous ne vous attendiez pas à ce que cela se produise, vous <strong>devez</strong> suivre le lien d\'annulation ci-dessous pour protéger votre compte !',
    'email_change_ignore'              => 'Si vous avez initié ce changement, vous pouvez ignorer ce message en toute sécurité.',
    'email_change_old'                 => 'L\'ancienne adresse e-mail était : :email',
    'email_change_old_strong'          => 'L\'ancienne adresse e-mail était : <strong>:email</strong>',
    'email_change_new'                 => 'La nouvelle adresse email est : :email',
    'email_change_new_strong'          => 'La nouvelle adresse email est : <strong>:email</strong>',
    'email_change_instructions'        => 'Vous ne pouvez pas utiliser Firefly III tant que vous ne confirmez pas ce changement. Veuillez suivre le lien ci-dessous pour le faire.',
    'email_change_undo_link'           => 'Pour annuler ce changement, suivez ce lien :',

    // OAuth token created
    'oauth_created_subject'            => 'Un nouveau client OAuth a été créé',
    'oauth_created_body'               => 'Quelqu\'un (espérons vous) vient de créer un nouveau client OAuth API Firefly III pour votre compte utilisateur. Il se nomme ":name" et a pour URL de callback <span style="font-family: monospace;">:url</span>.',
    'oauth_created_explanation'        => 'Avec ce client, cette personne peut accéder à <strong>toutes vos transactions financières</strong> via l\'API Firefly III.',
    'oauth_created_undo'               => 'Si ce n\'était pas vous, veuillez révoquer ce jeton dès que possible sur :url.',

    // reset password
    'reset_pw_subject'                 => 'Votre demande de réinitialisation de mot de passe',
    'reset_pw_instructions'            => 'Quelqu\'un a essayé de réinitialiser votre mot de passe. Si c\'était vous, veuillez suivre le lien ci-dessous pour le faire.',
    'reset_pw_warning'                 => '<strong>VEUILLEZ VÉRIFIER</strong> que le lien va vers le bon site Firefly III !',

    // error
    'error_subject'                    => 'Une erreur s\'est produite dans Firefly III',
    'error_intro'                      => 'Firefly III v:version a rencontré une erreur : <span style="font-family: monospace;">:errorMessage</span>.',
    'error_type'                       => 'L\'erreur était de type ":class".',
    'error_timestamp'                  => 'L\'erreur s\'est produite le/à: :time.',
    'error_location'                   => 'Cette erreur est survenue dans le fichier "<span style="font-family: monospace;">:file</span>" à la ligne :line avec le code :code.',
    'error_user'                       => 'L\'erreur a été rencontrée par l\'utilisateur n°:id, <a href="mailto::email">:email</a>.',
    'error_no_user'                    => 'Il n\'y avait aucun utilisateur connecté pour cette erreur ou aucun utilisateur n\'a été détecté.',
    'error_ip'                         => 'L\'adresse IP liée à cette erreur est : :ip',
    'error_url'                        => 'L\'URL est : :url',
    'error_user_agent'                 => 'User agent : :userAgent',
    'error_stacktrace'                 => 'La stacktrace complète se trouve plus bas. Si vous pensez qu\'il s\'agit d\'un bogue dans Firefly III, vous pouvez transmettre ce message à <a href="mailto:james@firefly-iii.org?subject=BUG!">james@firefly-iii.org</a> (en anglais). Cela peut aider à corriger le bogue que vous venez de rencontrer.',
    'error_github_html'                => 'Si vous le préférez, vous pouvez également ouvrir un nouveau ticket sur <a href="https://github.com/firefly-iii/firefly-iii/issues">GitHub</a> (en anglais).',
    'error_github_text'                => 'Si vous le préférez, vous pouvez également ouvrir un nouveau ticket sur https://github.com/firefly-ii/firefly-iii/issues (en anglais).',
    'error_stacktrace_below'           => 'La stacktrace complète se trouve ci-dessous :',

    // report new journals
    'new_journals_subject'             => 'Firefly III a créé une nouvelle opération|Firefly III a créé :count nouvelles opérations',
    'new_journals_header'              => 'Firefly III a créé une opération pour vous. Vous pouvez la trouver dans votre installation de Firefly III :|Firefly III a créé :count opérations pour vous. Vous pouvez les trouver dans votre installation de Firefly III :',
];
