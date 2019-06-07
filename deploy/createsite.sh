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

mkdir -p "$FPREFIX/${1}/${2}/"
mkdir -p "$FPREFIX/${1}/${2}/controllers"
mkdir -p "$FPREFIX/${1}/${2}/includes"
mkdir -p "$FPREFIX/${1}/${2}/models"
mkdir -p "$FPREFIX/${1}/${2}/parts"
mkdir -p "$FPREFIX/${1}/${2}/templates"
mkdir -p "$FPREFIX/${1}/${2}/views"
mkdir -p "$FPREFIX/${1}/${2}/www"

cp "$FPREFIX/platform/deploy/exampleindex.php" "$FPREFIX/${1}/${2}/www/index.php"
cp "$FPREFIX/platform/deploy/examplesettings.php" "$FPREFIX/${1}/settings/${2}.php"

touch "$FPREFIX/${1}/${2}/includes/header.php"
touch "$FPREFIX/${1}/${2}/includes/footer.php"
touch "$FPREFIX/${1}/${2}/controllers/index.php"

printf "Created site ${BLUE}${2}${NC}\n"
printf "${RED}Please have a look at the settings folder to complete the setup of the site!${NC}\n"
printf "${RED}Please have a look at the index in www folder and setup SITE_PATH and PROJECT_PATH variables!${NC}\n"

printf "Finished!\n"
