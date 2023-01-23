<?php

/**
 * Provides data record generation according to source data and predefined structure
 *
 * @property string $ResultRecord
 */
class JsonDataMapperGenerator
{

    /**
     * Result file of generated JSON record files
     * @property $GENERATED_FILE_RESULT
     */
    static string $GENERATED_FILE_RESULT = "generated";

    /**
     * Frequency authority
     * @property $FREQUENCY_AUTHORITY
     */
    static string $FREQUENCY_AUTHORITY = 'http://publications.europa.eu/resource/authority/frequency/';

    /**
     * Resource authority
     * @property $RESOURCE_AUTHORITY
     */
    static string $RESOURCE_AUTHORITY = 'http://publications.europa.eu/resource/authority/data-theme/';

    /**
     * Filetype authority
     * @property $FILETYPE_AUTHORITY
     */
    static string $FILETYPE_AUTHORITY = 'http://publications.europa.eu/resource/authority/file-type/';

    /**
     * Mediatype text
     * @property $MEDIATYPE_TEXT
     */
    static string $MEDIATYPE_TEXT = 'http://www.iana.org/assignments/media-types/text/';

    /**
     * Expected top level source records attribute keys
     * @property $EXPECTED_SOURCES_FIELDS
     */
    static array $EXPECTED_SOURCES_FIELDS = array(
        "title",
        "description",
        "extras",
        "uid_instance",
        "tags",
        "temaKod",
        "geoArea",
        "csv"
    );

    /**
     * Expected CSV record attribute keys
     * @property $EXPECTED_DISTRIBUTION_FIELDS
     */
    static array $EXPECTED_DISTRIBUTION_FIELDS = array(
        "uid",
        "datafile",
        "format",
        "specification"
    );

    /**
     * Expected target record file json structure
     * @property $DEFAULT_TARGET_RECORD_FILE_STRUCTURE
     */
    static array $DEFAULT_TARGET_RECORD_FILE_STRUCTURE = array(
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
     * @var array $ResultRecords
     */
    private array $ResultRecords;

    /**
     * @var string|null $ResultFolder
     */
    private ?string $ResultFolder = null;

    /**
     * @var string|null $SourceFile
     */
    private ?string $SourceFile = null;


    public function __construct()
    {

    }

    /**
     * @param array $resultRecord
     * @return JsonDataMapperGenerator|null
     */
    private function setResultRecords(array $resultRecord): ?JsonDataMapperGenerator
    {
        if($this->ResultRecords = $resultRecord){
            return $this;
        }
        return null;
    }

    /**
     * @return array
     */
    private function getResultRecords(): array
    {
        return $this->ResultRecords;
    }

    /**
     * @param string $sourceFile
     * @return void
     */
    private function setSourceFile(string $sourceFile): void
    {
        $this->SourceFile = $sourceFile;
    }

    /**
     * @return string
     */
    private function getSourceFile(): string
    {
        return $this->SourceFile;
    }

    /**
     * @param string $resultFolder
     * @return void
     */
    private function setResultFolder(string $resultFolder): void
    {
        $this->ResultFolder = $resultFolder;
    }

    /**
     * @return string
     */
    private function getResultFolder(): string
    {
        return $this->ResultFolder;
    }

    /**
     * Removes all files in defined file
     *
     * @return void
     */
    private function removeGeneratedContents(): void
    {
        if(file_exists($this->getResultFolder())){
            $files = glob($this->getResultFolder().'/*');
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
     * @param string $fileContent
     * @return bool
     */
    private function addContentsToGeneratedFolder(string $fileName, string $fileContent): bool
    {
        // Check availability of result folder & create it
        if (!is_null($this->getResultFolder()) && !file_exists($this->getResultFolder())) {
            mkdir($this->getResultFolder(), 0700);
        }
        // Put contents into set & prepared result folder
        if(mb_strlen($fileName) > 0 && fopen($this->getResultFolder()."/".$fileName. ".json", 'x') &&
            file_put_contents($this->getResultFolder()."/".$fileName. ".json", $fileContent)){
            return true;
        }
        return false;
    }

    /**
     * Handles record generation after source data process
     *
     * @param array $resultRecord
     * @return bool
     */
    private function handleSingleRecordGenerate(array $resultRecord): bool
    {
        if (json_last_error() === JSON_ERROR_NONE && array_key_exists("iri", $resultRecord)) {
            return($this->addContentsToGeneratedFolder($resultRecord["iri"], json_encode($resultRecord)));
        }
        return false;
    }

    /**
     * Handles record generation after source data process
     *
     * @return bool
     */
    private function handleMultiRecordGenerate(): bool
    {
        // Check if potentital data to save are available
        if(!is_null($this->getResultRecords())){
            // Data availability is checked - prepare folder for new data deleting old data
            $this->removeGeneratedContents();
            // Iterate throught all obtainted data and save it
            $processedGeneration = 0;
            foreach($this->getResultRecords() as $resultRecord){
                if($this->handleSingleRecordGenerate($resultRecord)){ // dynamic names
                    $processedGeneration++;
                }
            }
            // Count all results to obtain proper return
            if($processedGeneration == count($this->getResultRecords())){
                return true;
            }
        }
        // If data, records not available then return false
        return false;
    }

    /**
     * Provides generation data results written out
     *
     * @return string|null
     */
    private function provideGenerationResults(): ?string
    {
        if(!is_null($returnContent = $this->getResultRecords())){
            echo("<pre>");
            var_export($returnContent);
            echo("</pre>");
        }
        return null;
    }

    /**
     * Processes results of file generation
     *
     * @param string $operation
     * @return string|null
     */
    public function processResultRecords(string $operation): ?string
    {
        // Check if data to further process are available even assuming generateFromSourceData() return
        if($this->getResultRecords()){
            switch($operation){
                case"save-and-show":
                    // Save all obtained data & check to process show
                    if($this->handleMultiRecordGenerate()){
                        return $this->provideGenerationResults();
                    }
                    return null;
                case"nosave-and-show":
                    // Write obtain data out as a return
                    return $this->provideGenerationResults();
                case"save-and-noshow":
                    // Save all obtained data & write out confirmation return
                    if($this->handleMultiRecordGenerate()) {
                        return true;
                    }
                    return null;
                default:
                    return "unknown";
            }
        }
        // No data to process or show are available
        return null;
    }

    /**
     * Sets source file and result folder for data generation
     *
     * @param string $sourceFile
     * @param string|null $resultFolder
     * @return $this|null
     */
    public function setSourcesAndResults(string $sourceFile, string $resultFolder = null): ?JsonDataMapperGenerator
    {
        // Check & set source file
        try {
            if(mb_strlen($sourceFile) > 0){
                if(file_exists($sourceFile)){
                    $this->setSourceFile($sourceFile);
                }else{
                    throw new Exception("source file does not exists - wrong name? or directory?");
                }
            }else{
                throw new Exception("source file has to be set - no source data to process");
            }

        }catch(Exception $e){
            echo("Exception message: ".$e->getMessage());
        }
        // Set result folder
        try{
            if(!is_null($resultFolder) && mb_strlen($resultFolder) > 0){
                $this->setResultFolder($resultFolder);
            }else{
                if(property_exists($this, "GENERATED_FILE_RESULT")){
                    $this->setResultFolder(self::$GENERATED_FILE_RESULT);
                }else{
                    throw new Exception("no result folder available");
                }
            }
        }catch(Exception $e){
            echo("Exception message: ".$e->getMessage());
        }
        // Return class instance for further work
        return $this;
    }

    /**
     * Process source data by desired attributes
     * Generates result data from source data in JSON-LD
     *
     * @return JsonDataMapperGenerator|null
     */
    public function generateFromSourceData(): ?JsonDataMapperGenerator
    {
        // Check if source file is available & obtains its content & decode it from JSON & check decode status
        if(file_exists($this->getSourceFile()) &&
            !is_null($sourceFileContents = file_get_contents($this->getSourceFile())) &&
            ($jsonSourceFileContents = json_decode($sourceFileContents)) &&
            (json_last_error() === JSON_ERROR_NONE)){

            // Prepare result records array
            $resultRecords = array();

            // Prepare source fields
            $expectedSourceFields = self::$EXPECTED_SOURCES_FIELDS;

            // Prepare result records array
            $resultRecord = self::$DEFAULT_TARGET_RECORD_FILE_STRUCTURE;

            // Iterate through source data and expected fields to get match
            foreach($jsonSourceFileContents as $sourceFileContentsRecord){
                foreach($expectedSourceFields as $sourceField){
                    if(property_exists($sourceFileContentsRecord, $sourceField)){
                        $resultRecord = $this->obtainRecordFromSource($resultRecord, $sourceField, $sourceFileContentsRecord);
                    }
                }
                // Sets new record for return array
                $resultRecords[] = $resultRecord;
            }
            // Result set $this->ResultRecords - for further processing
            if($this->setResultRecords($resultRecords)){
                return $this;
            }
        }
        // No processed data available
        return null;
    }


    /**
     * Prepare record from source to further process
     *
     * @param $resultRecord
     * @param $sourceField
     * @param $sourceFileContentsRecord
     * @return array|null
     */
    private function obtainRecordFromSource($resultRecord, $sourceField, $sourceFileContentsRecord): ?array
    {
        // Prepare distribution fields
        $expectedDistributionFields = self::$EXPECTED_DISTRIBUTION_FIELDS;

        // Set result record according to processing
        if($sourceField == "extras"){
            $resultRecord["periodicita_aktualizace"] = static::$FREQUENCY_AUTHORITY.$sourceFileContentsRecord->{$sourceField}[2]->opendata;
        }elseif($sourceField == "uid_instance"){
            $resultRecord["iri"] = $sourceFileContentsRecord->{$sourceField};
        }elseif($sourceField == "title"){
            $resultRecord["název"]["cs"] = $sourceFileContentsRecord->{$sourceField};
        }elseif($sourceField == "description"){
            $resultRecord["popis"]["cs"] = $sourceFileContentsRecord->{$sourceField};
        }elseif($sourceField == "tags"){
            $resultRecord["klíčové_slovo"]["cs"] = $sourceFileContentsRecord->{$sourceField};
        }elseif($sourceField == "temaKod"){
            $resultRecord["téma"] = static::$RESOURCE_AUTHORITY.$sourceFileContentsRecord->{$sourceField};
        }elseif($sourceField == "geoArea"){
            $resultRecord["prvek_rúian"] = $sourceFileContentsRecord->{$sourceField};
        }elseif($sourceField == "csv"){
            if(!is_null($sourceFileContentsRecord->{$sourceField})){
                $distributionData = array(
                    "typ" => "Distribuce"
                );
                if($sourceFileContentsRecord->{$sourceField}->isValid){
                    foreach($sourceFileContentsRecord->{$sourceField} as $keyDistributionRecord => $distributionRecord){
                        if(in_array($keyDistributionRecord, $expectedDistributionFields)) {
                            if($keyDistributionRecord == "datafile"){
                                $distributionData["soubor_ke_stažení"] = $distributionRecord;
                                $distributionData["přístupové_url"] = $distributionRecord;
                            }elseif($keyDistributionRecord == "format"){
                                $distributionData["format"] = self::$FILETYPE_AUTHORITY.strtoupper($distributionRecord);
                                $distributionData["typ_média"] = self::$MEDIATYPE_TEXT.$distributionRecord;
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
                // Sets distribuce key in result record
                $resultRecord["distribuce"] = $distributionData;
            }
        }
        return $resultRecord;
    }





}