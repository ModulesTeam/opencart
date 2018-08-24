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

echo -e "The integrityCheck file is OK."
echo -e "######################################\n"
exit 0