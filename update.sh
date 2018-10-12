#!/bin/bash

#
# FireflyIII updater script for virtual or real server
#
# Usage:
#   ./update.sh -v NEW_FIREFLY_VERSION
#
# Example:
#   ./update.sh -v 4.7.7
#

STANDARD='\033[0;0;39m'
RED='\033[0;41;30m'
WHITE='\033[1m'
UNDERLINE='\033[4m'
ITALIC='\033[3m'

#########################
# The command line help #
#########################
display_help() {
    printf "\n${WHITE}USAGE:${STANDARD} ${ITALIC}$0${STANDARD} ${RED}[ -v | --version ]${STANDARD} ${UNDERLINE}NEW_FIREFLY_VERSION${STANDARD}\n\n" >&2
    printf "Current options:\n"
    printf "  -v, --version\t\tThe new version of FireflyIII\n"
    printf "  -h, --help\t\tView help menù\n\n"

    printf "This is the updater script of FireflyIII for virtual or real server.\n\n"

    printf "${WHITE}Examples:${STANDARD} ${ITALIC}$0${STANDARD} -v ${UNDERLINE}4.7.7${STANDARD}\n" >&2
}

lowercase(){
    echo "$1" | sed "y/ABCDEFGHIJKLMNOPQRSTUVWXYZ/abcdefghijklmnopqrstuvwxyz/"
}

OSTYPE=`lowercase \`uname -s\``

format_path(){
    if [[ "$OSTYPE" =~ ^linux* || "$OSTYPE" =~ ^darwin* || "$OSTYPE" =~ ^cygwin* || "$OSTYPE" =~ ^msys* || "$OSTYPE" =~ ^mingw* || "$OSTYPE" =~ ^freebsd* || "$OSTYPE" =~ ^sunos* ]]; then
        # For Unix-based OS
        echo "$1" | sed -e 's/\\/\//g' -e 's/://'
    elif [[ "$OSTYPE" =~ ^win32* || "$OSTYPE" =~ ^win64* ]]; then
        # For Windows OS
        echo "$1" | sed -e 's/^\///' -e 's/\//\\/g' -e 's/^./\0:/'
    else
        # Unknown OS
        echo "Unidentified OS!"
        exit 1
    fi
}

################################
# Check if parameters options  #
# are given on the commandline #
################################
while :
do
    case "$1" in
        -h | --help)
            display_help
            exit 0
            ;;
        -v | --version)
            OLD_FIREFLY_PATH=$(pwd)
            FIREFLY_CURRENT_DIR=${OLD_FIREFLY_PATH#$(dirname $OLD_FIREFLY_PATH)/}
            NEW_FIREFLY_PATH="$(dirname $OLD_FIREFLY_PATH)/$FIREFLY_CURRENT_DIR-updated"
            NEW_FIREFLY_VERSION=$2
: '
            echo $OLD_FIREFLY_PATH
            echo $FIREFLY_CURRENT_DIR
            echo $NEW_FIREFLY_PATH
            echo $NEW_FIREFLY_VERSION
'

            echo "Creating a new updated FireflyIII installation!"
            composer create-project grumpydictator/firefly-iii --no-dev --prefer-dist $NEW_FIREFLY_PATH $NEW_FIREFLY_VERSION

            echo "Copying old configuration files and storage to new FireflyIII installation!"
            if [ -e $OLD_FIREFLY_PATH/.env ]; then
                cp -L -R $OLD_FIREFLY_PATH/.env $NEW_FIREFLY_PATH/.env
            fi 
            if [ -d $OLD_FIREFLY_PATH/storage/upload/ ]; then
                cp -L -R $OLD_FIREFLY_PATH/storage/upload/* $NEW_FIREFLY_PATH/storage/upload/
            fi 
            if [ -d $OLD_FIREFLY_PATH/storage/export/ ]; then
                cp -L -R $OLD_FIREFLY_PATH/storage/export/* $NEW_FIREFLY_PATH/storage/export/
            fi 

            cd `format_path $NEW_FIREFLY_PATH` > /dev/null

            rm -rf `format_path $NEW_FIREFLY_PATH/bootstrap/cache/`*

            echo "Installing project libraries..."
            composer update --no-scripts --no-dev
            composer update --no-dev
            echo "Performing update checks..."
            php artisan cache:clear
            php artisan migrate --seed
            echo "Updating database..."
            php artisan firefly:upgrade-database
            php artisan firefly:verify
            echo "Completing installation..."
            php artisan passport:install

            printf "Update done!!!\n\n${UNDERLINE}Now launch the file move.sh in parent directory to perform directories rename!${STANDARD}"
            tee $OLD_FIREFLY_PATH/../move.sh <<EOF >/dev/null
#!/bin/bash

mv ./$FIREFLY_CURRENT_DIR ./$FIREFLY_CURRENT_DIR-old
mv ./$FIREFLY_CURRENT_DIR-updated ./$FIREFLY_CURRENT_DIR

echo "Update completed!!!"
exit 0
EOF
            exit 0
            ;;
        -*)
            printf "${RED}Error: unknown option $1!${STANDARD}"
            exit 1
            ;;
        *)
            display_help
            exit 1
            ;;
    esac
done
