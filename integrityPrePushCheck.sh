#!/bin/bash
echo -e "###############Pre-push###############"
echo -e "Checking integrity file..."

COMMAND="php integrityCheck.php system/library/mundipagg/vendor/autoload.php './system/library/mundipagg//vendor'"

$COMMAND
RC=$?

if [ $RC != "0" ]
    then
        echo -e "The integrity file wasn't generated. Please, run the 'integrityDeploy.php' script before pushing."
        echo -e "######################################\n"
        exit 1
fi

echo -e "The integrityCheck file is OK.\n"

echo -e "Checking for uncommited files..."
STAGINGFILESCOUNT=$(git status --porcelain | grep '^[^?]' | wc -l)

if [ $STAGINGFILESCOUNT != 0 ]; then
	echo -e "There are some files waiting for commit. Please, commit then before pushing."
	echo -e "######################################\n"
	exit 1
fi
echo -e "There aren't files waiting for commit. Push can continue."
echo -e "######################################\n"
exit 0