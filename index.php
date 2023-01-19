<?php

/**
 * Script PHP settings
 */
declare(strict_types=1);
error_reporting(E_ERROR | E_WARNING | E_PARSE);
ini_set('display_errors', "1");
ini_set('log_errors', "1");

/**
 * Result file of generated JSON record files
 * @const GENERATED_FILE_RESULT
 */
const GENERATED_FILE_RESULT = "generated";

/**
 * Source file for processing
 * @const SOURCE_RECORDS_FILE
 */
const SOURCE_RECORDS_FILE = "lkod-data.json";

/**
 * Expected top level source records attribute keys
 * @const EXPECTED_SOURCES_FIELDS
 */
const EXPECTED_SOURCES_FIELDS = array("title", "description", "extras", "uid_instance", "tags", "temaKod", "geoArea", "csv");

/**
 * Expected CSV record attribute keys
 * @const EXPECTED_SOURCES_FIELDS
 */
const EXPECTED_DISTRIBUTION_FIELDS = array("uid", "datafile", "format", "specification");

/**
 * Expected target record file json structure
 * @const DEFAULT_TARGET_RECORD_FILE_STRUCTURE
 */
const DEFAULT_TARGET_RECORD_FILE_STRUCTURE = array(
    "@context" => "https://ofn.gov.cz/rozhraní-katalogů-otevřených-dat/2021-01-11/kontexty/rozhraní-katalogů-otevřených-dat.jsonld",
    "iri" => "",
    "typ" => "Datová sada",
    "poskytovatel" => "https://rpp-opendata.egon.gov.cz/odrpp/zdroj/orgán-veřejné-moci",
    "název" => array(),
    "popis" => array(),
    "klíčové_slovo" => array(),
    "téma" => "",
    "periodicita_aktualizace" => "",
    "prvek_rúian" => "",
    "distribuce" => array(),
);

/**
 * Removes all files in defined file
 *
 * @return void
 */
function removeGeneratedContents(): void
{
    if(file_exists(constant("GENERATED_FILE_RESULT"))){
        $files = glob(constant("GENERATED_FILE_RESULT").'/*');
        foreach($files as $file){
            if(is_file($file)) {
                unlink($file);
            }
        }
    }
}

/**
 * Creates record in generation result folder
 *
 * @param string $fileName
 * @param string $fileExtension
 * @param string $fileContent
 * @return bool
 */
function addContentsToGeneratedFolder(string $fileName, string $fileExtension, string $fileContent): bool
{
    if(!file_exists(constant("GENERATED_FILE_RESULT"))) {
        mkdir(constant("GENERATED_FILE_RESULT"), 0700);
    }

    if(fopen(constant("GENERATED_FILE_RESULT")."/".$fileName.$fileExtension, 'x') &&
        file_put_contents(constant("GENERATED_FILE_RESULT")."/".$fileName.$fileExtension, $fileContent)){
        return true;
    }

    return false;
}


/**
 * Handles record generation after source data process
 *
 * @param array $resultRecord
 * @return void
 */
function handleRecordGenerate(array $resultRecord): void
{
    echo("<pre>");
    if (json_last_error() === JSON_ERROR_NONE) {
        echo(addContentsToGeneratedFolder($resultRecord["iri"], ".json", json_encode($resultRecord)));
    }
    echo("</pre>");
    echo("<hr>");
}

/**
 * Process source data by desired attributes
 */
if(file_exists(constant("SOURCE_RECORDS_FILE")) && !is_null($sourceFileContents = file_get_contents(constant("SOURCE_RECORDS_FILE")))){
    $jsonSourceFileContents = json_decode($sourceFileContents);
    if (json_last_error() === JSON_ERROR_NONE) {
        $resultRecords = array();
        removeGeneratedContents();
        foreach($jsonSourceFileContents as $sourceFileContentsRecord){
                $resultRecord = constant("DEFAULT_TARGET_RECORD_FILE_STRUCTURE");
                foreach(constant("EXPECTED_SOURCES_FIELDS") as $sourceField){
                    // TODO: process cleaner by array result type map definitino
                    if(property_exists($sourceFileContentsRecord, $sourceField)){
                        if($sourceField == "extras"){
                            $resultRecord["periodicita_aktualizace"] = "http://publications.europa.eu/resource/authority/frequency/".$sourceFileContentsRecord->{$sourceField}[2]->opendata;
                        }elseif($sourceField == "uid_instance"){
                            $resultRecord["iri"] = $sourceFileContentsRecord->{$sourceField};
                        }elseif($sourceField == "title"){
                            $resultRecord["název"]["cs"] = $sourceFileContentsRecord->{$sourceField};
                        }elseif($sourceField == "description"){
                            $resultRecord["popis"]["cs"] = $sourceFileContentsRecord->{$sourceField};
                        }elseif($sourceField == "tags"){
                            $resultRecord["klíčové_slovo"]["cs"] = $sourceFileContentsRecord->{$sourceField};
                        }elseif($sourceField == "temaKod"){
                            $resultRecord["téma"] = "http://publications.europa.eu/resource/authority/data-theme/".$sourceFileContentsRecord->{$sourceField};
                        }elseif($sourceField == "geoArea"){
                            $resultRecord["prvek_rúian"] = $sourceFileContentsRecord->{$sourceField};
                        }elseif($sourceField == "csv"){
                            if(!is_null($sourceFileContentsRecord->{$sourceField})){
                                $distributionData = array(
                                    "typ" => "Distribuce"
                                );
                                if($sourceFileContentsRecord->{$sourceField}->isValid){
                                    foreach($sourceFileContentsRecord->{$sourceField} as $keyDistributionRecord => $distributionRecord){
                                        // TODO: process cleaner by array result type map definitino
                                        if(in_array($keyDistributionRecord, constant("EXPECTED_DISTRIBUTION_FIELDS"))) {
                                            if($keyDistributionRecord == "datafile"){
                                                $distributionData["soubor_ke_stažení"] = $distributionRecord;
                                                $distributionData["přístupové_url"] = $distributionRecord;
                                            }elseif($keyDistributionRecord == "format"){
                                                $distributionData["format"] = "http://publications.europa.eu/resource/authority/file-type/".strtoupper($distributionRecord);
                                                $distributionData["typ_média"] = "http://www.iana.org/assignments/media-types/text/".$distributionRecord;
                                            }elseif($keyDistributionRecord == "specification"){
                                                $distributionData["podmínky_užití"] = array();
                                                $distributionData["typ"] = "Specifikace podmínek užití";
                                                $distributionData["podmínky_užití"]["autorské_dílo"] = $distributionRecord[0]->value;
                                                $distributionData["podmínky_užití"]["autor"]["cs"] = "Portál otevřených dat";
                                                $distributionData["podmínky_užití"]["databáze_chráněná_zvláštními_právy"] = $distributionRecord[2]->value;
                                                $distributionData["podmínky_užití"]["databáze_jako_autorské_dílo"] = $distributionRecord[1]->value;
                                                $distributionData["podmínky_užití"]["autor_databáze"]["cs"] = "Portál otevřených dat";
                                                $distributionData["podmínky_užití"]["osobní_údaje"] = $distributionRecord[3]->value;
                                            }elseif($keyDistributionRecord == "uid"){
                                                $distributionData["iri"] = $distributionRecord;
                                            }

                                        }
                                    }
                                }
                                $resultRecord["distribuce"] = $distributionData;
                            }

                        }
                    }
                }
                $resultRecords[] = $resultRecord;
                handleRecordGenerate($resultRecord);
        }
    }
}


