#!/bin/bash
echo -e "###############Pre-push###############"
echo -e "Checking integrity file..."

COMMAND="php integrityCheck.php system/library/mundipagg/vendor/autoload.php './system/library/mundipagg//vendor'"

$COMMAND
RC=$?

ERRORS=0

if [ $RC != "0" ]; then
    echo -e "\e[31mThe integrity file wasn't generated. Please, run the 'integrityDeploy.php' script before pushing.\e[0m"
    ERRORS=1
else
    echo -e "\e[32mThe integrityCheck file is OK.\e[0m\n"
fi

echo -e "Checking for uncommited files..."
STAGINGFILESCOUNT=$(git status --porcelain | grep '^[^?]' | wc -l)

if [ $STAGINGFILESCOUNT != 0 ]; then
	echo -e "\e[31mThere are some files waiting for commit. Please, commit then before pushing.\e[0m"
	ERRORS=1
else
    echo -e "\e[32mThere aren't files waiting for commit.\e[0m\n"
fi

if [ $ERRORS != 0 ]; then
    echo -e "\n\e[31m\e[1mThere are some errors. Please, fix them before pushing.\e[0m"
else
    echo -e "\n\e[32m\e[1mThere are no errors. Push can continue.\e[0m"
fi

echo -e "######################################\n"
exit $ERRORS