<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ConvertXmlToText extends Command
{
    protected $signature = 'convert:xmltotext';
    protected $description = 'Convert XML files to text files';

    public function handle()
    {
        $sourceFolder = public_path('xmlFiles');
        $destinationFolder = public_path('textFiles');

        // Ensure the destination folder exists
        if (!File::exists($destinationFolder)) {
            File::makeDirectory($destinationFolder, 0755, true);
        }

        // Get all XML files from the source folder
        $xmlFiles = File::glob($sourceFolder . '/*.xml');

        foreach ($xmlFiles as $xmlFile) {
            $xmlContent = File::get($xmlFile);

            // Perform the XML to text conversion
            $textContent = $this->convertXmlToText($xmlContent);

            // Create the corresponding text file in the destination folder
            $textFileName = pathinfo($xmlFile, PATHINFO_FILENAME) . '.txt';
            $textFilePath = $destinationFolder . '/' . $textFileName;
            File::put($textFilePath, $textContent);

            $this->info("Converted $xmlFile to $textFilePath");
        }

        $this->info('Conversion complete!');
    }

    private function convertXmlToText($xmlContent)
    {
        // Implement your XML to text conversion logic here
        // This is a basic example, adjust as needed
        $xml = simplexml_load_string($xmlContent);
        $text = json_encode($xml, JSON_PRETTY_PRINT);

        return $text;
    }
}
