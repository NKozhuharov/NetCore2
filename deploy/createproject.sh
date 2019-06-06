#Param 1 is project name
#Param 2 is site name

if [ -z "${1}" ] 
then
    exit "Define a project name!"
fi

if [ -z "${2}" ] 
then
    exit "Define a site name!"
fi

FPREFIX="/var/www"

GREEN="\033[0;32m"
BLUE="\033[0;36m"
RED="\033[0;31m"
NC="\033[0m" # No Color

mkdir "$FPREFIX/${1}/"
mkdir "$FPREFIX/${1}/classes/"
mkdir "$FPREFIX/${1}/files/"
mkdir "$FPREFIX/${1}/images/"
mkdir "$FPREFIX/${1}/images_org/"
mkdir "$FPREFIX/${1}/settings/"

chown -R apache:apache "$FPREFIX/${1}/files/"
chown -R apache:apache "$FPREFIX/${1}/images/"
chown -R apache:apache "$FPREFIX/${1}/images_org/"

printf "Created project ${GREEN}${1}${NC}\n"

mkdir "$FPREFIX/${1}/${2}/"
mkdir "$FPREFIX/${1}/${2}/www"

cp "$FPREFIX/platform/deploy/exampleindex.php" "$FPREFIX/${1}/${2}/www/index.php"
cp "$FPREFIX/platform/deploy/examplesettings.php" "$FPREFIX/${1}/settings/${2}.php"

printf "Created site ${BLUE}${2}${NC}\n"
printf "${RED}Please have a look at the settings folder to complete the setup of the site!${NC}\n"
printf "${RED}Please have a look at the index in www folder and setup SITE_PATH and PROJECT_PATH variables!${NC}\n"

printf "Finished!\n"
