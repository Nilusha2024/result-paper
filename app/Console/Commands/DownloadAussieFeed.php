<?php

namespace App\Console\Commands;

use App\Http\Controllers\ResultPaperController;
use Exception;
use Illuminate\Console\Command;

class DownloadAussieFeed extends Command
{

    // signature and description
    protected $signature = 'app:download-aussie-feed';
    protected $description = 'Downloads the XML files from the Aussie feed and stores it in the database.';

    // result paper controller for the logic
    protected $resultPaperController;

    // constructor
    public function __construct(ResultPaperController $resultPaperController)
    {
        parent::__construct();
        $this->resultPaperController = $resultPaperController;
    }

    // execution
    public function handle()
    {
        try {

            // create backup directory, if there isn't one
            $this->resultPaperController->makeAussieBackupDirectory();

            // store data while making file backups
            $this->resultPaperController->storeAllAussieResultMeetingData();
            $this->resultPaperController->storeAllAussieResultEventData();

            $this->info('Aussie feed downloaded successfully');
            return Command::SUCCESS;
        } catch (Exception $e) {
            $this->error('Failed to download Aussie feed: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
