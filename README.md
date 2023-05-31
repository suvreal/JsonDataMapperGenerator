# JsonDataMapperGenerator
Generator of JSON files according to data structure definition

## Installation
1. Clone repository
2. Enter project root
3. Add source file 'lkod-data.json' to root
4. Start local CLI dev webserver:
    ```
    php -S localhost:8090
    ```
5. Create new instance of JsonDataMapperGenerator class with configuration of expected result:
   ```
   (new JsonDataMapperGenerator()) // new instance of json data mapper generator
    ->setSourcesAndResults("lkod-data-another.json", "generated-another") // source file, result folder without slashes
    ->generateFromSourceData() // Provides source structure
    ->processResultRecords("save-and-show") // Sets results of source structure processing
   ```
6. Visit expected location: localhost:8090
7. Generated data will be available in generated/ folder

## JsonDataMapperGenerator configuration
Configuration options for processing and generating result JSON structure for processResultRecords() method:
- save-and-show = generates result data and shows results as string
- save-and-noshow = generates result data and shows bool as return
- nosave-and-show = source data is processed to be shown

## TODO
 - possibly add source file and result folder
