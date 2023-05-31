<?php


/**
 * Script PHP settings
 */
declare(strict_types=1);
ini_set('display_errors', '0');


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
 * Chain order: save-and-show, save-and-noshow, nosave-and-show
 */
(new JsonDataMapperGenerator())
    ->setSourcesAndResults("lkod-data.json", "generated-another")
    ->generateFromSourceData()
    ->processResultRecords("save-and-show");

