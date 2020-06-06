<?php

/**
 * import.php
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
    // ALL breadcrumbs and subtitles:
    'index_breadcrumb'                    => 'Importer des données dans Firefly III',
    'prerequisites_breadcrumb_fake'       => 'Prérequis pour la simulation d\'importation',
    'prerequisites_breadcrumb_spectre'    => 'Prérequis pour Spectre',
    'job_configuration_breadcrumb'        => 'Configuration pour ":key"',
    'job_status_breadcrumb'               => 'Statut d\'importation pour ":key"',
    'disabled_for_demo_user'              => 'désactivé pour la démo',

    // index page:
    'general_index_intro'                 => 'Bienvenue dans la routine d\'importation de Firefly III. Il existe différentes façons d\'importer des données dans Firefly III, affichées ici sous forme de boutons.',

    // notices about the CSV importer:
    'deprecate_csv_import' => 'Comme indiqué dans <a href="https://www.patreon.com/posts/future-updates-30012174">ce post Patreon</a>, la façon dont Firefly III gère l\'importation des données va changer. Cela signifie que l\'importateur CSV sera déplacé vers un nouvel outil séparé. Vous pouvez déjà bêta-tester cet outil si vous visitez <a href="https://github.com/firefly-iii/csv-importer">ce dépôt GitHub</a>. Je vous serais reconnaissant de tester le nouvel importateur et de me faire savoir ce que vous en pensez.',
    'final_csv_import'     => 'Comme indiqué dans <a href="https://www.patreon.com/posts/future-updates-30012174">ce post Patreon</a>, la façon dont Firefly III gère l\'importation des données va changer. Cela signifie qu\'il s\'agit de la dernière version de Firefly III comportant un importateur CSV. Un outil dédié est disponible, je vous invite à l\'essayer : <a href="https://github.com/firefly-iii/csv-importer">Firefly III CSV importer</a>. Je vous serais reconnaissant de tester le nouvel importateur et de me faire savoir ce que vous en pensez.',

    // import provider strings (index):
    'button_fake'                         => 'Simuler une importation',
    'button_file'                         => 'Importer un fichier',
    'button_spectre'                      => 'Importer en utilisant Spectre',

    // prerequisites box (index)
    'need_prereq_title'                   => 'Prérequis d\'importation',
    'need_prereq_intro'                   => 'Certaines méthodes d\'importation nécessitent votre attention avant de pouvoir être utilisées. Par exemple, elles peuvent nécessiter des clés d\'API spéciales ou des clés secrètes. Vous pouvez les configurer ici. L\'icône indique si ces conditions préalables ont été remplies.',
    'do_prereq_fake'                      => 'Prérequis pour la simulation',
    'do_prereq_file'                      => 'Prérequis pour les importations de fichiers',
    'do_prereq_spectre'                   => 'Prérequis pour les importations depuis Spectre',

    // prerequisites:
    'prereq_fake_title'                   => 'Prérequis pour une importation utilisant le simulateur d\'importation',
    'prereq_fake_text'                    => 'Le simulateur d\'importation nécessite une fausse clé d\'API. Vous pouvez utiliser la clé suivante : 123456789012345678901234567890AA',
    'prereq_spectre_title'                => 'Prérequis à l\'importation de données avec Spectre',
    'prereq_spectre_text'                 => 'Pour importer des données avec l\'API Spectre (v4) vous devez fournir à Firefly III deux secrets. Vous les trouverez sur <a href="https://www.saltedge.com/clients/profile/secrets">la page des secrets</a>.',
    'prereq_spectre_pub'                  => 'De même, l\'API Spectre doit connaitre votre clé publique affichée ci-dessous. Sans elle, vous ne serez pas reconnu. Merci de renseigner votre clé publique dans la <a href="https://www.saltedge.com/clients/profile/secrets">page des secrets</a>.',
    'callback_not_tls'                    => 'Firefly III a détecté le retour URI suivant. Il semble que votre serveur n’est pas configuré pour accepter les connexions TLS (https). YNAB n’acceptera pas cette URI. Vous pouvez continuer l’importation (au cas où Firefly III a tort) mais gardez cela en tête.',
    // prerequisites success messages:
    'prerequisites_saved_for_fake'        => 'Fausse clé API enregistrée avec succès !',
    'prerequisites_saved_for_spectre'     => 'ID App et secret enregistrés !',

    // job configuration:
    'job_config_apply_rules_title'        => 'Configuration de la tâche - Appliquer vos règles ?',
    'job_config_apply_rules_text'         => 'Une fois le fournisseur de la simulation exécuté, vos règles peuvent être appliquées aux opérations. Notez que ceci allongera le temps de l\'importation.',
    'job_config_input'                    => 'Vos données d\'entrée',
    // job configuration for the fake provider:
    'job_config_fake_artist_title'        => 'Saisir un nom d\'album',
    'job_config_fake_artist_text'         => 'Beaucoup de routines d\'importation ont quelques étapes de configuration par lesquelles vous devez passer. Dans le cas du fournisseur du simulateur d\'importation, vous devez répondre à des questions étranges. Dans ce cas, saisissez "David Bowie" pour continuer.',
    'job_config_fake_song_title'          => 'Saisir un nom de chanson',
    'job_config_fake_song_text'           => 'Citez la chanson "Golden years" pour continuer la simulation d\'importation.',
    'job_config_fake_album_title'         => 'Saisir un nom d\'album',
    'job_config_fake_album_text'          => 'Certaines routines d\'importation nécessitent des données complémentaires en milieu d\'exécution. Dans le cas du fournisseur du simulateur d\'importation, vous devez répondre à des questions étranges. Saisissez "Station to station" pour continuer.',
    // job configuration form the file provider
    'job_config_file_upload_title'        => 'Configuration de l\'importation (1/4) - Téléchargez votre fichier',
    'job_config_file_upload_text'         => 'Cette routine vous aidera à importer des fichiers depuis votre banque vers Firefly III. ',
    'job_config_file_upload_help'         => 'Choisissez votre fichier. Veuillez vous assurer qu\'il est encodé en UTF-8.',
    'job_config_file_upload_config_help'  => 'Si vous avez précédemment importé des données dans Firefly III, vous avez peut-être téléchargé un fichier de configuration qui définit les relations entre les différents champs. Pour certaines banques, des utilisateurs ont bien voulu partager leur fichier ici : <a href="https://github.com/firefly-iii/import-configurations/wiki">fichiers de configuration</a>',
    'job_config_file_upload_type_help'    => 'Sélectionnez le type de fichier que vous allez télécharger',
    'job_config_file_upload_submit'       => 'Envoyer des fichiers',
    'import_file_type_csv'                => 'CSV (valeurs séparées par des virgules)',
    'import_file_type_ofx'                => 'OFX',
    'file_not_utf8'                       => 'Le fichier téléchargé n\'est pas encodé en UTF-8 ou en ASCII. Firefly ne peut pas gérer un tel fichier. Veuillez utiliser Notepad++ ou Sublime Text pour convertir votre fichier en UTF-8.',
    'job_config_uc_title'                 => 'Configuration de l\'importation (2/4) - Configuration du fichier importé',
    'job_config_uc_text'                  => 'Pour pouvoir importer votre fichier correctement, veuillez valider les options ci-dessous.',
    'job_config_uc_header_help'           => 'Cochez cette case si la première ligne de votre fichier CSV contient les entêtes des colonnes.',
    'job_config_uc_date_help'             => 'Le format de la date et de l’heure dans votre fichier. Suivez les options de formatage décrites sur <a href="https://secure.php.net/manual/en/datetime.createfromformat.php#refsect1-datetime.createfromformat-parameters">cette page</a>. La valeur par défaut va analyser les dates ayant cette syntaxe : :dateExample.',
    'job_config_uc_delimiter_help'        => 'Choisissez le délimiteur de champ qui est utilisé dans votre fichier d’entrée. Si vous n\'en êtes pas certain, la virgule est l’option la plus sûre.',
    'job_config_uc_account_help'          => 'Si votre fichier ne contient AUCUNE information concernant vos compte(s) actif(s), utilisez cette liste déroulante pour choisir à quel compte les opérations contenues dans le fichier s\'appliquent.',
    'job_config_uc_apply_rules_title'     => 'Appliquer les règles',
    'job_config_uc_apply_rules_text'      => 'Appliquer vos règles à chaque opération importée. Notez que cela peut ralentir significativement l\'importation.',
    'job_config_uc_specifics_title'       => 'Options spécifiques à la banque',
    'job_config_uc_specifics_txt'         => 'Certaines banques délivrent des fichiers mal formatés. Firefly III peut les corriger automatiquement. Si votre banque délivre de tels fichiers mais qu\'elle n\'est pas listée ici, merci d\'ouvrir une demande sur GitHub.',
    'job_config_uc_submit'                => 'Continuer',
    'invalid_import_account'              => 'Vous avez sélectionné un compte non valide pour l\'importation.',
    'import_liability_select'             => 'Passif',
    // job configuration for Spectre:
    'job_config_spectre_login_title'      => 'Choisissez votre identifiant',
    'job_config_spectre_login_text'       => 'Firefly III a trouvé :count identifiant·s dans votre compte Spectre. Lequel voulez-vous utiliser pour importer des données ?',
    'spectre_login_status_active'         => 'Actif',
    'spectre_login_status_inactive'       => 'Inactif',
    'spectre_login_status_disabled'       => 'Désactivé',
    'spectre_login_new_login'             => 'S\'identifier avec une autre banque, ou à une de ces banques avec un autre identifiant.',
    'job_config_spectre_accounts_title'   => 'Sélectionnez le·s compte·s à importer',
    'job_config_spectre_accounts_text'    => 'Vous avez sélectionné ":name" (:country). Vous avez :count compte·s disponible·s chez ce fournisseur. Veuillez sélectionner le·s compte·s d\'actifs Firefly III dans le·s·quel·s enregistrer les opérations. Souvenez-vous, pour importer des données, le compte Firefly III et le compte ":name" doivent avoir la même devise.',
    'spectre_do_not_import'               => '(ne pas importer)',
    'spectre_no_mapping'                  => 'Il semble que vous n\'avez sélectionné aucun compte depuis lequel importer.',
    'imported_from_account'               => 'Importé depuis ":account"',
    'spectre_account_with_number'         => 'Compte :number',
    'job_config_spectre_apply_rules'      => 'Appliquer les règles',
    'job_config_spectre_apply_rules_text' => 'Par défaut vos règles seront appliquées aux opérations créées pendant l\'importation. Si vous ne voulez pas que vos règles s\'appliquent, décochez cette case.',

    // job configuration for bunq:
    'should_download_config'              => 'Vous devriez télécharger <a href=":route">le fichier de configuration</a> de cette tâche. Cela rendra vos futures importations plus faciles.',
    'share_config_file'                   => 'Si vous avez importé des données depuis une banque publique, vous devriez <a href="https://github.com/firefly-iii/import-configurations/wiki">partager votre fichier de configuration</a>. Il sera ainsi plus facile pour les autres utilisateurs d\'importer leurs données. Le partage de votre fichier de configuration n\'expose pas vos informations financières.',

    // keys from "extra" array:
    'spectre_extra_key_iban'               => 'IBAN',
    'spectre_extra_key_swift'              => 'SWIFT',
    'spectre_extra_key_status'             => 'Statut',
    'spectre_extra_key_card_type'          => 'Type de carte',
    'spectre_extra_key_account_name'       => 'Nom du compte',
    'spectre_extra_key_client_name'        => 'Nom du client',
    'spectre_extra_key_account_number'     => 'N° de compte',
    'spectre_extra_key_blocked_amount'     => 'Montant bloqué',
    'spectre_extra_key_available_amount'   => 'Montant disponible',
    'spectre_extra_key_credit_limit'       => 'Plafond de crédit',
    'spectre_extra_key_interest_rate'      => 'Taux d\'intérêt',
    'spectre_extra_key_expiry_date'        => 'Date d’expiration',
    'spectre_extra_key_open_date'          => 'Date d\'ouverture',
    'spectre_extra_key_current_time'       => 'Heure actuelle',
    'spectre_extra_key_current_date'       => 'Date actuelle',
    'spectre_extra_key_cards'              => 'Cartes',
    'spectre_extra_key_units'              => 'Unités',
    'spectre_extra_key_unit_price'         => 'Prix unitaire',
    'spectre_extra_key_transactions_count' => 'Nombre d\'opérations',

    // job config for the file provider (stage: mapping):
    'job_config_map_title'            => 'Configuration de l\'importation (4/4) - Connecter les données à importer aux données de Firefly III',
    'job_config_map_text'             => 'Dans les tableaux suivants, la valeur située à gauche vous montre l\'information trouvée dans votre fichier téléchargé. C’est à vous de mapper cette valeur, si possible, avec une valeur déjà présente dans votre base de données. Firefly III s’en tiendra à ce mappage. Si il n’y a pas de valeur correspondante ou que vous ne souhaitez pas mapper de valeur spécifique, ne sélectionnez rien.',
    'job_config_map_nothing'          => 'Il n\'y a aucun donnée dans votre fichier qui puisse être mappée aux valeurs existantes. Merci de cliquer sur "Démarrez l\'importation" pour continuer.',
    'job_config_field_value'          => 'Valeur du champ',
    'job_config_field_mapped'         => 'Mappé à',
    'map_do_not_map'                  => '(ne pas mapper)',
    'job_config_map_submit'           => 'Démarrez l\'importation',


    // import status page:
    'import_with_key'                 => 'Importer avec la clé \':key\'',
    'status_wait_title'               => 'Veuillez patienter...',
    'status_wait_text'                => 'Cette boîte disparaîtra dans un instant.',
    'status_running_title'            => 'L\'importation est en cours d\'exécution',
    'status_job_running'              => 'Veuillez patienter, importation des données en cours...',
    'status_job_storing'              => 'Veuillez patientez, enregistrement des données en cours...',
    'status_job_rules'                => 'Veuillez patienter, exécution des règles...',
    'status_fatal_title'              => 'Erreur fatale',
    'status_fatal_text'               => 'L\'importation a rencontré une erreur qui l\'a empêché de s\'achever correctement. Toutes nos excuses !',
    'status_fatal_more'               => 'Ce message d\'erreur (probablement très énigmatique) est complété par les fichiers de log que vous trouverez sur votre disque dur ou dans le container Docker depuis lequel vous exécutez Firefly III.',
    'status_finished_title'           => 'Importation terminée',
    'status_finished_text'            => 'L\'importation est terminée.',
    'finished_with_errors'            => 'Des erreurs se sont produites pendant l\'importation. Veuillez les examiner avec attention.',
    'unknown_import_result'           => 'Résultat de l\'importation inconnu',
    'result_no_transactions'          => 'Aucune opération n\'a été importée. Il s\'agissait peut être de doublons, ou il n\'y avait simplement aucune opération a importer. Le fichier de log pourra peut être vous en dire plus sur ce qu\'il s\'est passé. Si vous importez des données régulièrement, ceci est normal.',
    'result_one_transaction'          => 'Une seule opération a été importée. Elle est stockée sous le tag <a href=":route" class="label label-success" style="font-size:100%;font-weight:normal;">:tag</a> où vous pouvez l\'afficher en détail.',
    'result_many_transactions'        => 'Firefly III a importé :count opérations. Elles sont stockées sous le tag <a href=":route" class="label label-success" style="font-size:100%;font-weight:normal;">:tag</a> où vous pouvez les afficher en détail.',

    // general errors and warnings:
    'bad_job_status'                  => 'Vous ne pouvez pas accéder à cette page tant que l\'importation a le statut ":status".',

    // error message
    'duplicate_row'                   => 'La ligne n°:row (":description") n\'a pas pu être importée. Elle existe déjà.',

];
