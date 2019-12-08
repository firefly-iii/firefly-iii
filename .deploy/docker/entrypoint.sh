#!/bin/bash

echo "Now in entrypoint.sh for Firefly III"

# make sure the correct directories exists (suggested by @chrif):
echo "Making directories..."
mkdir -p $FIREFLY_PATH/storage/app/public
mkdir -p $FIREFLY_PATH/storage/build
mkdir -p $FIREFLY_PATH/storage/database
mkdir -p $FIREFLY_PATH/storage/debugbar
mkdir -p $FIREFLY_PATH/storage/export
mkdir -p $FIREFLY_PATH/storage/framework/cache/data
mkdir -p $FIREFLY_PATH/storage/framework/sessions
mkdir -p $FIREFLY_PATH/storage/framework/testing
mkdir -p $FIREFLY_PATH/storage/framework/views/twig
mkdir -p $FIREFLY_PATH/storage/framework/views/v1
mkdir -p $FIREFLY_PATH/storage/framework/views/v2
mkdir -p $FIREFLY_PATH/storage/logs
mkdir -p $FIREFLY_PATH/storage/upload

if [ $DKR_CHECK_SQLITE != "false" ]
  echo "Touch DB file (if SQLlite)..."
  if [ $DB_CONNECTION == "sqlite" ]
  then
      touch $FIREFLY_PATH/storage/database/database.sqlite
      echo "Touched!"
  fi
fi

# make sure we own the volumes:
echo "Run chown on ${FIREFLY_PATH}/storage..."
chown -R www-data:www-data -R $FIREFLY_PATH/storage
echo "Run chmod on ${FIREFLY_PATH}/storage..."
chmod -R 775 $FIREFLY_PATH/storage

# remove any lingering files that may break upgrades:
echo "Remove log file..."
rm -f $FIREFLY_PATH/storage/logs/laravel.log

echo "Dump auto load..."
composer dump-autoload
echo "Discover packages..."
php artisan package:discover

echo "Wait for the database."
if [[ -z "$DB_PORT" ]]; then
  if [[ $DB_CONNECTION == "pgsql" ]]; then
    DB_PORT=5432
  elif [[ $DB_CONNECTION == "mysql" ]]; then
    DB_PORT=3306
  fi
fi
if [[ ! -z "$DB_PORT" ]]; then
  $FIREFLY_PATH/.deploy/docker/wait-for-it.sh "${DB_HOST}:${DB_PORT}" -t 60 -- echo "DB is up. Time to execute artisan commands."
fi

echo "Run various artisan commands..."

php artisan cache:clear

if [ $DKR_RUN_MIGRATION != "false" ]
  php artisan firefly-iii:create-database
  php artisan migrate --seed
  php artisan firefly-iii:decrypt-all
fi

# there are 13 upgrade commands
if [ $DKR_RUN_MIGRATION != "false" ]
  php artisan firefly-iii:transaction-identifiers
  php artisan firefly-iii:migrate-to-groups
  php artisan firefly-iii:account-currencies
  php artisan firefly-iii:transfer-currencies
  php artisan firefly-iii:other-currencies
  php artisan firefly-iii:migrate-notes
  php artisan firefly-iii:migrate-attachments
  php artisan firefly-iii:bills-to-rules
  php artisan firefly-iii:bl-currency
  php artisan firefly-iii:cc-liabilities
  php artisan firefly-iii:back-to-journals
  php artisan firefly-iii:rename-account-meta
  php artisan firefly-iii:migrate-recurrence-meta
fi

# there are 15 verify commands
if [ $DKR_RUN_VERIFY != "false" ]
  php artisan firefly-iii:fix-piggies
  php artisan firefly-iii:create-link-types
  php artisan firefly-iii:create-access-tokens
  php artisan firefly-iii:remove-bills
  php artisan firefly-iii:enable-currencies
  php artisan firefly-iii:fix-transfer-budgets
  php artisan firefly-iii:fix-uneven-amount
  php artisan firefly-iii:delete-zero-amount
  php artisan firefly-iii:delete-orphaned-transactions
  php artisan firefly-iii:delete-empty-journals
  php artisan firefly-iii:delete-empty-groups
  php artisan firefly-iii:fix-account-types
  php artisan firefly-iii:rename-meta-fields
  php artisan firefly-iii:fix-ob-currencies
  php artisan firefly-iii:fix-long-descriptions
fi

# report commands
if [ $DKR_RUN_REPORT != "false" ]
  php artisan firefly-iii:report-empty-objects
  php artisan firefly-iii:report-sum
fi


php artisan firefly-iii:restore-oauth-keys
if [ $DKR_RUN_PASSPORT_INSTALL != "false" ]
  php artisan passport:install
fi

php artisan firefly-iii:set-latest-version --james-is-cool
php artisan cache:clear

# make sure we own everything
echo "Run chown on ${FIREFLY_PATH}"
chown -R www-data:www-data -R $FIREFLY_PATH

php artisan firefly:instructions install

echo "Go!"
exec apache2-foreground
