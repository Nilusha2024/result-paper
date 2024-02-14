<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class GenerateOddsMap extends Command
{
    protected $signature = 'app:generate-odds-map';
    protected $description = 'Generate odds map from CSV';

    public function handle()
    {
        // Generate odds map utilizing our CSV and write it to our oddsmap configuration.
        $filename = 'odds_sheet_updated.csv';

        $csvFilePath = storage_path("app/odds_sheets/$filename");

        $oddsMap = $this->generateOddsMap($csvFilePath);

        $this->writeToOddsMapConfiguration($oddsMap);

        $this->info('Odds map generated and written to odds configuration file successfully!');
    }

    private function generateOddsMap($csvFilePath)
    {
        $file = fopen($csvFilePath, 'r');

        // Initialize an empty array to store the odds map
        $oddsMap = [];

        // Read the header row to get column names
        $header = fgetcsv($file);

        // Read the remaining rows
        while (($row = fgetcsv($file)) !== false) {
            $rowData = array_combine($header, $row);
            $odds = $rowData['ODDS'];
            $oddsMap[$odds] = [
                'WIN' => number_format($rowData['WIN'], 2, '.', ''),
                'PLACE (1-4)' => number_format($rowData['PLACE (1-4)'], 2, '.', ''),
                'PLACE (1-5)' => number_format($rowData['PLACE (1-5)'], 2, '.', ''),
            ];
        }

        // Close the file
        fclose($file);

        return $oddsMap;
    }

    private function writeToOddsMapConfiguration($oddsMap)
    {
        $configPath = config_path('odds.php');

        // Generate the PHP code for the odds map
        $phpCode = '<?php' . PHP_EOL . PHP_EOL;
        $phpCode .= 'return ' . var_export(['odds_to_dividends_map' => $oddsMap], true) . ';';

        // Write the PHP code to the configuration file
        File::put($configPath, $phpCode);
    }
}
