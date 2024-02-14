<?php

namespace App\Console\Commands;

use App\Http\Controllers\ResultPaperController;
use Exception;
use Illuminate\Console\Command;

class FlushAussieResultTables extends Command
{

    protected $signature = 'app:flush-aussie-result-tables';
    protected $description = 'Flushes/Truncates all the data from the Aussie result tables in the database';

    // result paper controller for the logic
    protected $resultPaperController;

    // constructor
    public function __construct(ResultPaperController $resultPaperController)
    {
        parent::__construct();
        $this->resultPaperController = $resultPaperController;
    }

    public function handle()
    {
        try {
            $this->resultPaperController->flushAussieResultMeetingTables();
            $this->resultPaperController->flushAussieResultEventTables();

            $this->info('All Aussie result tables flushed');
            return Command::SUCCESS;
        } catch (Exception $e) {
            $this->error('Failed to flush Aussie result tables: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
