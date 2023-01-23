<?php


/**
 * Script PHP settings
 */
declare(strict_types=1);
error_reporting(E_ERROR | E_WARNING | E_PARSE);
ini_set('display_errors', "1");
ini_set('log_errors', "1");


/**
 * Autoload
 */
spl_autoload_register(function ($class) {
    $file = str_replace('\\', DIRECTORY_SEPARATOR, $class) . '.php';
    if (file_exists($file)) {
        require $file;
        return true;
    }
    return false;
});


/**
 * Initialize JsonDataMapperGenerator class object & perform data generation
 */
(new JsonDataMapperGenerator())
    ->setSourcesAndResults("lkod-data.json", "generated-another")
    ->generateFromSourceData()
    ->processResultRecords("save-and-show"); // save-and-show, save-and-noshow, nosave-and-show

