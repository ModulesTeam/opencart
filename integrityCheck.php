<?php
//setting parameters based on modman file.
$modmanFilePath = 'modman';
$modmanRawData = file_get_contents($modmanFilePath);
$rawLines = explode("\n", $modmanRawData);
foreach ($rawLines as $rawLine) {
    if (
        substr($rawLine,0,1) !== '#' &&
        strlen($rawLine) > 0
    ) {
        $line = array_values(array_filter(explode(' ', $rawLine)));
        if ($line[0] === 'modman') {
            $integrityEngineBasePath = explode('/', $line[1]);
            array_shift($integrityEngineBasePath);
            array_shift($integrityEngineBasePath);
            array_pop($integrityEngineBasePath);
            $integrityEngineBasePath = './' . implode('/', $integrityEngineBasePath) . '/';
            $integrityEngineClassFilePath = $integrityEngineBasePath . 'IntegrityEngine.php';
            $integrityFilePath = $integrityEngineBasePath . 'integrityCheck';
            break;
        }
    }
}
if (!isset($integrityEngineBasePath)) {
    die("error on setting parameters!");
}
//creating moduleFiles md5;
$autoload = $argv[1];
require_once $autoload;
$integrityEngine = new \Mundipagg\Integrity\IntegrityEngine();
$modmanRawData = file_get_contents($modmanFilePath);
$rawLines = explode("\n", $modmanRawData);
$directoriesIgnored = explode(',', $argv[2]);
$result = $integrityEngine->verifyIntegrity($modmanFilePath,$integrityFilePath,$directoriesIgnored);
exit(count($result['alteredFiles']));