<?php

namespace App\Http\Controllers;

use App\Models\EngResultCompetitor;
use App\Models\EngResultCompetitorLite;
use App\Models\EngResultCompetitorPrice;
use App\Models\EngResultEvent;
use App\Models\EngResultEventPrize;
use App\Models\EngResultMeeting;
use App\Models\EngResultRacebet;
use App\Models\EngResultRule;
use App\Models\Event;
use App\Models\Meeting;
use App\Models\ResultCompetitor;
use App\Models\ResultCompetitorPrice;
use App\Models\ResultDividend;
use App\Models\ResultEvent;
use App\Models\ResultMeeting;
use App\Models\ResultMeetingEvent;
use App\Models\ResultPool;
use CallbackFilterIterator;
use Carbon\Carbon;
use DateTime;
use ErrorException;
use Exception;
use FilesystemIterator;
use GlobIterator;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use PDOException;
use PhpParser\Node\Stmt\TryCatch;


class ResultPaperController extends Controller
{

    public function index()
    {
        return view('feeds');
    }

    // Aussie result downloads
    // -----------------------

    public function storeAllAussieFeedData()
    {
        // start
        $startTime = microtime(true);

        $this->makeAussieBackupDirectory();

        $meetingDownloadSuccessfull = $this->storeAllAussieResultMeetingData();
        $eventDownloadSuccessfull = $this->storeAllAussieResultEventData();

        $downloadStatus = $meetingDownloadSuccessfull && $eventDownloadSuccessfull;

        // end
        $endTime = microtime(true);

        // exeuction time
        $executionTime = round(number_format(($endTime - $startTime), 4));

        return redirect()->route('result_paper.feeds')->with(['aussie_download_status' => $downloadStatus, 'aussie_execution_time' => $executionTime]);
    }

    // English result downloads
    // ------------------------

    public function storeAllEnglishFeedData()
    {
        // max execution time set for 30 minutes
        ini_set('max_execution_time', 1800);

        // start
        $startTime = microtime(true);

        $this->makeEnglishBackupDirectory();

        // Dual iteration
        // --------------
        $meetingDownloadSuccessfull = $this->storeAllEnglishResultMeetingData();
        $eventDownloadSuccessfull = $this->storeAllEnglishResultEventData();

        $downloadStatus = $meetingDownloadSuccessfull && $eventDownloadSuccessfull;

        // end
        $endTime = microtime(true);

        // exeuction time
        $executionTime = round(number_format(($endTime - $startTime), 4));

        return redirect()->route('result_paper.feeds')->with(['english_download_status' => $downloadStatus, 'english_execution_time' => $executionTime]);
    }

    // English result downloads v2 (optimized)
    // --------------------------------------

    public function storeAllEnglishFeedDataV2()
    {
        // max execution time set for 30 minutes
        ini_set('max_execution_time', 1800);

        // start
        $startTime = microtime(true);

        $this->makeEnglishBackupDirectory();

        // Single iteration
        // ----------------
        $dataDownloadSuccessfull = $this->storeAllEnglishResultData();

        $downloadStatus = $dataDownloadSuccessfull;

        // end
        $endTime = microtime(true);

        // exeuction time
        $executionTime = round(number_format(($endTime - $startTime), 4));

        return redirect()->route('result_paper.feeds')->with(['english_download_status' => $downloadStatus, 'english_execution_time' => $executionTime]);
    }


    public function makeAussieBackupDirectory()
    {
        try {
            $targetFolder = storage_path('app/xml/aussie_feed_backup/' . date('Y-m-d'));
            File::makeDirectory($targetFolder, 0755, true, true);
        } catch (Exception $e) {
            dd($e);
        }
    }


    public function makeEnglishBackupDirectory()
    {
        try {
            $targetFolder = storage_path('app/xml/english_feed_backup/' . date('Y-m-d'));
            File::makeDirectory($targetFolder, 0755, true, true);
        } catch (Exception $e) {
            dd($e);
        }
    }


    // Aussie feed processing, storing and backup
    // ------------------------------------------

    // storing aussie meetings
    // NOTE: This one doesn't use the main meeting index file, it uses the event index files
    // No problem since the tag data are the same on both
    public function storeAllAussieResultMeetingData()
    {
        try {
            DB::beginTransaction();

            // change this for the server directory on release
            // $directory = storage_path('app/xml/AUS 2023-11-07/');
            $directory = '//172.16.50.39/Headend/TabAussieXML/';

            // Directory and file check
            File::isDirectory($directory);
            $filesFound =  count(File::files($directory)) > 0;
            if (!$filesFound) {
                throw new FileNotFoundException("Directory " . $directory . " is empty.");
            }

            $iterator = new GlobIterator($directory . '*.xml', FilesystemIterator::KEY_AS_FILENAME);

            foreach ($iterator as $file) {

                $filename = $iterator->key();

                // skip unreadable files
                try {
                    // $xmlNode = simplexml_load_file(storage_path('app/xml/AUS 2023-11-07/') . $filename);
                    $xmlNode = simplexml_load_file('//172.16.50.39/Headend/TabAussieXML/' . $filename);
                } catch (ErrorException $e) {
                    continue;
                }

                $arrayData = $this->xmlToArray($xmlNode);

                /**
                 *
                 * @var object $arrayData
                 */

                // backup
                File::copy($file->getPathname(), storage_path('app/xml/aussie_feed_backup/' . date('Y-m-d') . '/' . $file->getFilename()));

                // meeting date filter's over here, add it to the if check below when you need it :
                //  && date('Y-m-d') === date('Y-m-d', strtotime($arrayData['meeting']['@date']))

                if (
                    isset($arrayData['meeting']) &&
                    isset($arrayData['meeting']['@date'])
                    #&& date('2023-11-07') === date('Y-m-d', strtotime($arrayData['meeting']['@date']))
                ) {

                    $meetingList = $arrayData['meeting'];

                    // wrapping if single element/is exposed
                    $meetingList = $this->wrapIfExposed($meetingList, 'location');

                    foreach ($meetingList as $meeting) {

                        // grab meeting if exists
                        $ausResultMeeting = ResultMeeting::where('code', $meeting['@code'])->where('date', $meeting['@date'])->first();

                        // prepare data
                        $ausResultMeetingData = [
                            'location' => $meeting['@location'] ?? null,
                            'date' => new DateTime($meeting['@date'] ?? null),
                            'name' => $meeting['@name'] ?? null,
                            'type' => $meeting['@type'] ?? null,
                            'going' => $meeting['@going'] ?? null,
                            'code' => $meeting['@code'] ?? null,
                            'weather' => $meeting['@weather'] ?? null,
                        ];

                        if (!$ausResultMeeting) {
                            ResultMeeting::create($ausResultMeetingData);
                        } else {
                            $ausResultMeeting->update($ausResultMeetingData);
                        }
                    }
                }
            }

            // if the transaction concluded without any errors, commit em
            DB::commit();
            return 1;
        } catch (Exception $e) {
            DB::rollback();
            dd($e, $filename);
        }
    }
    // storing aussie events
    public function storeAllAussieResultEventData()
    {
        try {

            DB::beginTransaction();

            // change this for the server directory on release
            // $directory = storage_path('app/xml/AUS 2023-11-07/');
            $directory = '//172.16.50.39/Headend/TabAussieXML/';

            // Directory and file check
            File::isDirectory($directory);
            $filesFound =  count(File::files($directory)) > 0;
            if (!$filesFound) {
                throw new FileNotFoundException("Directory " . $directory . " is empty.");
            }

            $iterator = new GlobIterator($directory . '*.xml', FilesystemIterator::KEY_AS_FILENAME);

            foreach ($iterator as $file) {

                $filename = $iterator->key();

                // skip unreadable files
                try {
                    // $xmlNode = simplexml_load_file(storage_path('app/xml/AUS 2023-11-07/') . $filename);
                    $xmlNode = simplexml_load_file('//172.16.50.39/Headend/TabAussieXML/' . $filename);
                } catch (ErrorException $e) {
                    continue;
                }

                $arrayData = $this->xmlToArray($xmlNode);

                /**
                 *
                 * @var object $arrayData
                 */

                // event date filter's over here, add it to the if check below when you need it:
                // && date('Y-m-d') === date('Y-m-d', strtotime($arrayData['category']['@date']))

                if (
                    isset($arrayData['category']) &&
                    isset($arrayData['category']['@date'])
                    #&& date('2023-11-07') === date('Y-m-d', strtotime($arrayData['meeting']['@date']))
                ) {


                    // if events are set & mtp is available (temporary fix)
                    // Event index files are marked with mnem="MS"
                    if (isset($arrayData['category']['competition']['event']) && (isset($arrayData['category']['@mnem']) && $arrayData['category']['@mnem'] == "MS")) {

                        // loading all event & competition data
                        $eventList = $arrayData['category']['competition']['event'];
                        $competition = $arrayData['category']['competition'];

                        // wrapping if single element/is exposed
                        $eventList = $this->wrapIfExposed($eventList, 'id');

                        foreach ($eventList as $event) {

                            // grab the meeting record that matches for the event
                            $meetingForEvent = ResultMeeting::where('name', $event['@name'])->first();

                            $ausResultEvent = ResultEvent::where('event_id', $event['@id'])->first();

                            $ausResultEventData = [
                                'event_id' => $event['@id'] ?? null,
                                'meeting_id' => $meetingForEvent->id ?? null,
                                'event_name' => $event['@name'] ?? null,
                                'race_num' => $event['@racenum'] ?? null,
                                'event_type' => $competition['@racetype'] ?? null,
                                'description' => $event['@description'] ?? null,
                                'start_datetime' => new DateTime($event['@startdatetime'] ?? null),
                                'utc_start_datetime' => new DateTime($event['@utc_startdatetime'] ?? null),
                                'end_datetime' => new DateTime($event['@enddatetime'] ?? null),
                                'going' => $event['@going'] ?? null,
                                'status' => $event['@status'] ?? null,
                                'length' => $event['@length'] ?? null,
                                'country_name' => $event['@countryname'] ?? null,
                                'country_code' => $event['@countrycode'] ?? null,
                                'location_code' => $event['@locationcode'] ?? null,
                                'mtp' => $event['@mtp'] ?? null,
                                'closed_time' => new DateTime($event['@closedtime'] ?? null)
                            ];

                            if (!$ausResultEvent) {
                                ResultEvent::create($ausResultEventData);
                            } else {
                                $ausResultEvent->update($ausResultEventData);
                            }


                            if (isset($event['market']['competitor'])) {

                                // wrap if single element/is exposed
                                $competitorList = $this->wrapIfExposed($event['market']['competitor'], 'id');

                                foreach ($competitorList as $competitor) {

                                    // Note : Greyhounds don't have jockeys, so their jockey field comes as ''.
                                    // Creating competitor tag records

                                    $ausResultCompetitor = ResultCompetitor::where('event_id', $event['@id'])->where('competitor_id', $competitor['@id'])->first();

                                    $ausResultCompetitorData = [
                                        'competitor_id' => $competitor['@id'] ?? null,
                                        'event_id' => $event['@id'] ?? null,
                                        'name' => $competitor['@name'] ?? null,
                                        'description' => $competitor['@description'] ?? null,
                                        'competitor_no' => $competitor['@number'] ?? null,
                                        'competitor_type' => $competition['@racetype'] ?? null,
                                        'post_no' => $competitor['@postnumber'] ?? null,
                                        'finish_position' => $competitor['@finishposn'] ?? null,
                                        'form' => $competitor['@form'] ?? null,
                                        'weight' => $competitor['@weight'] ?? null,
                                        'jockey' => (isset($competitor['@jockey']) && $competitor['@jockey'] !== '') ? $competitor['@jockey'] : null,
                                        'trainer' => $competitor['@trainer'] ?? null,
                                        'status' => $competitor['@status'] ?? null,
                                        'fav_status' => $competitor['@favstatus'] ?? null,
                                    ];

                                    if (!$ausResultCompetitor) {
                                        ResultCompetitor::create($ausResultCompetitorData);
                                    } else {
                                        $ausResultCompetitor->update($ausResultCompetitorData);
                                    }


                                    if (isset($competitor['price'])) {

                                        // wrap if single element/is exposed
                                        $priceList = $this->wrapIfExposed($competitor['price'], 'odds');

                                        foreach ($priceList as $price) {

                                            $ausResultCompetitorPrice = ResultCompetitorPrice::where('event_id', $event['@id'])->where('competitor_id', $competitor['@id'])->where('price_type', $price['@pricetype'])->where('odds', $price['@odds'])->first();

                                            $ausResultCompetitiorPriceData = [
                                                'event_id' => $event['@id'] ?? null,
                                                'competitor_id' => $competitor['@id'] ?? null,
                                                'price_type' => $price['@pricetype'] ?? null,
                                                'odds' => $price['@odds'] ?? null,
                                            ];

                                            if (!$ausResultCompetitorPrice) {
                                                ResultCompetitorPrice::create($ausResultCompetitiorPriceData);
                                            } else {
                                                $ausResultCompetitorPrice->update($ausResultCompetitiorPriceData);
                                            }
                                        }
                                    }
                                }
                            }


                            // Process 'pool' ------------------------------------------------

                            if (isset($event['market'])) {
                                if (isset($event['market']['pool'])) {

                                    // wrap if single element/is exposed
                                    $poolList = $this->wrapIfExposed($event['market']['pool'], 'id');

                                    foreach ($poolList as $pool) {

                                        $ausResultPool = ResultPool::where('event_id', $event['@id'])->where('pool_id', $pool['@id'])->first();

                                        $ausResultPoolData = [
                                            'event_id' => $event['@id'] ?? null,
                                            'pool_id' => $pool['@id'] ?? null,
                                            'pool_type' => $pool['@pooltype'] ?? null,
                                            'jackpot' => $pool['@jackpot'] ?? null,
                                            'leg_number' => $pool['@legnumber'] ?? null,
                                            'status' => $pool['@status'] ?? null,
                                            'pool_total' => $pool['@pooltotal'] ?? null,
                                            'substitute' => $pool['@substitude'] ?? null,
                                            'closed_time' => new DateTime($pool['@closedtime'] ?? null),
                                        ];

                                        if (!$ausResultPool) {
                                            ResultPool::create($ausResultPoolData);
                                        } else {
                                            $ausResultPool->update($ausResultPoolData);
                                        }
                                    }
                                }
                            }

                            // Process 'result' and 'dividend' tags
                            if (isset($event['market']['result']['dividend'])) {

                                // wrap if single element/is exposed
                                $dividendList = $this->wrapIfExposed($event['market']['result']['dividend'], 'dividendtype');

                                foreach ($dividendList as $dividend) {

                                    $ausResultDividend = ResultDividend::where('event_id', $event['@id'])->where('dividend_type', $dividend['@dividendtype'])->where('instance', $dividend['@instance'])->first();

                                    $ausResultDividendData = [
                                        'event_id' => $event['@id'] ?? null,
                                        'event_type' => $competition['@racetype'] ?? null,
                                        'dividend_type' => $dividend['@dividendtype'] ?? null,
                                        'instance' => $dividend['@instance'] ?? null,
                                        'dividend_amount' => $dividend['@dividendamount'] ?? null,
                                        'jackpot_carried_over' => $dividend['@jackpotcarriedover'] ?? null,
                                        'status' => $dividend['@status'] ?? null,
                                        'runner_numbers' => $dividend['@runnernumbers'] ?? null,
                                    ];

                                    if (!$ausResultDividend) {
                                        ResultDividend::create($ausResultDividendData);
                                    } else {
                                        $ausResultDividend->update($ausResultDividendData);
                                    }
                                }
                            }
                        }
                    }
                }
            }

            // if the transaction concluded without any errors, commit em
            DB::commit();
            return 1;
        } catch (Exception $e) {
            DB::rollback();
            dd($e, $filename);
        }
    }

    // English feed processing, storing and backup
    // --------------------------------------------

    // storing engilsh meetings (UPDATE: This uses ES Files now :3)
    // if exists update logic added
    public function storeAllEnglishResultMeetingData()
    {
        try {

            DB::beginTransaction();

            // server directory
            $directory = '//172.16.50.39/Masters/';

            // Directory and file check
            File::isDirectory($directory);
            $filesFound =  count(File::files($directory)) > 0;
            if (!$filesFound) {
                throw new FileNotFoundException("Directory " . $directory . " is empty.");
            }

            $iterator = new GlobIterator($directory . '*.xml', FilesystemIterator::KEY_AS_FILENAME);

            foreach ($iterator as $file) {

                $filename = $iterator->key();

                // skip unreadable files
                try {
                    $xmlNode = simplexml_load_file($directory . $filename);
                } catch (ErrorException $e) {
                    continue;
                }

                $arrayData = $this->xmlToArray($xmlNode);

                // backup
                File::copy($file->getPathname(), storage_path('app/xml/english_feed_backup/' . date('Y-m-d') . '/' . $file->getFilename()));


                if (
                    (isset($arrayData['data']['@mnem']) && $arrayData['data']['@mnem'] == 'ES') &&
                    (isset($arrayData['data']['@date']) && date('Y-m-d', time() - 7200) === date('Y-m-d', strtotime($arrayData['data']['@date'])))
                ) {

                    $coreData = $arrayData['data'];
                    $meetingList = $arrayData['data']['meeting'];

                    // wrapping if single element/is exposed
                    $meetingList = $this->wrapIfExposed($meetingList, 'code');

                    foreach ($meetingList as $meeting) {

                        // grab meeting if exists
                        $engResultMeeting = EngResultMeeting::where('code', $meeting['@code'])->where('date', $meeting['@date'])->first();

                        // prepare data
                        $engResultMeetingData = [
                            'name' => $meeting['@name'] ?? null,
                            'code' => $meeting['@code'] ?? null,
                            'country' => $coreData['@country'] ?? null,
                            'category' => $coreData['@category'] ?? null,
                            'sportcode' => $meeting['@sportcode'] ?? null,
                            'date' => new DateTime($meeting['@date'] ?? null),
                            'events' => $meeting['@events'] ?? null,
                            'status' => $meeting['@status'] ?? null,
                            'coverage_code' => $meeting['@coverageCode'] ?? null,
                            'start_time' => ($meeting['@startTime'] ?? false) ? Carbon::createFromFormat('H:i:s', $meeting['@startTime']) : null,
                            'end_time' => ($meeting['@endTime'] ?? false) ? Carbon::createFromFormat('H:i:s', $meeting['@endTime']) : null,
                            'going' => $meeting['@going'] ?? null,
                        ];


                        if (!$engResultMeeting) {
                            // if the meeting isn't there, create one
                            EngResultMeeting::create($engResultMeetingData);
                        } else {
                            // if there is, just update the model
                            $engResultMeeting->update($engResultMeetingData);
                        }
                    }
                }
            }

            // if the transaction concluded without any errors, commit em
            DB::commit();
            return 1;
        } catch (Exception $e) {
            DB::rollback();
            dd($e, $filename);
        }
    }

    // storing engilsh events and event data
    // if exists update logic added
    public function storeAllEnglishResultEventData()
    {
        try {

            DB::beginTransaction();

            // server directory
            $directory = '//172.16.50.39/Masters/';

            // Directory and file check
            File::isDirectory($directory);
            $filesFound =  count(File::files($directory)) > 0;
            if (!$filesFound) {
                throw new FileNotFoundException("Directory " . $directory . " is empty.");
            }

            $iterator = new GlobIterator($directory . '*.xml', FilesystemIterator::KEY_AS_FILENAME);

            foreach ($iterator as $file) {

                $filename = $iterator->key();

                // skip unreadable files
                try {
                    $xmlNode = simplexml_load_file($directory . $filename);
                } catch (ErrorException $e) {
                    continue;
                }

                $arrayData = $this->xmlToArray($xmlNode);

                /**
                 *
                 * @var object $arrayData
                 */

                // File type filter
                // ----------------

                if (
                    // ES for events, competitor prices, & racebets
                    (isset($arrayData['data']['@mnem']) && $arrayData['data']['@mnem'] == 'ES') &&
                    (isset($arrayData['data']['@date']) && date('Y-m-d', time() - 7200) === date('Y-m-d', strtotime($arrayData['data']['@date'])))
                ) {

                    $meeting = $arrayData['data']['meeting'];
                    $eventList = $arrayData['data']['meeting']['event'];
                    $competitorList = $arrayData['data']['meeting']['event']['selection'];

                    // wrapping if single element/is exposed

                    $eventList = $this->wrapIfExposed($eventList, 'id');
                    $competitorList = $this->wrapIfExposed($competitorList, 'id');

                    // grab the meeting record that matches for the event
                    $meetingForEvent = EngResultMeeting::where('code', $meeting['@code'])->first();

                    foreach ($eventList as $event) {

                        $engResultEvent = EngResultEvent::where('event_id', $event['@id'])->first();

                        $engResultEventData = [
                            'meeting_auto_id' => $meetingForEvent->id ?? null,
                            'meeting_code' => $meeting['@code'] ?? null,
                            'name' => $event['@name'] ?? null,
                            'event_type' => $meetingForEvent->category ?? null,
                            'is_virtual' => $meetingForEvent->country == 'VR',
                            'event_id' => $event['@id'] ?? null,
                            'num' => $event['@num'] ?? null,
                            'date' => $meetingForEvent->date ?? null,
                            'time' => ($event['@time'] ?? false) ? Carbon::createFromFormat('H:i:s', $event['@time']) : null,
                            'places_expected' => $event['@placesExpected'] ?? null,
                            'each_way_places' => $event['@eachWayPlaces'] ?? null,
                            'coverage_code' => $event['@coverageCode'] ?? null,
                            'course_type' => $event['@courseType'] ?? null,
                            'surface' => $event['@surface'] ?? null,
                            'grade' => $event['@grade'] ?? null,
                            'handicap' => $event['@handicap'] ?? null,
                            'status' => $event['@status'] ?? null,
                            'runners' => $event['@runners'] ?? null,
                            'going' => $event['@going'] ?? null,
                            'distance' => $event['@distance'] ?? null,
                            'offtime' => ($event['@offTime'] ?? false) ? Carbon::createFromFormat('H:i:s', $event['@offTime']) : null,
                            'progress_code' => $event['@progressCode'] ?? null,
                            'pmsg' => $event['@pmsg'] ?? null,
                        ];

                        if (!$engResultEvent) {
                            EngResultEvent::create($engResultEventData);
                        } else {
                            $engResultEvent->update($engResultEventData);
                        }

                        if (isset($event['racebet'])) {

                            // some races come with single racebets, wrap if exposed
                            $racebetList = $this->wrapIfExposed($event['racebet'], 'id');

                            foreach ($racebetList as $racebet) {

                                $engResultRacebet = EngResultRacebet::where('racebet_id', $racebet['@id'])->first();

                                $engResultRacebetData = [
                                    'event_id' => $event['@id'] ?? null,
                                    'racebet_id' => $racebet['@id'] ?? null,
                                    'bet_type' => $racebet['@bettype'] ?? null,
                                    'amount' => $racebet['@amount'] ?? null,
                                    'instance' => $racebet['@instance'] ?? null,
                                    'type' => $racebet['@type'] ?? null,
                                ];

                                if (!$engResultRacebet) {
                                    EngResultRacebet::create($engResultRacebetData);
                                } else {
                                    $engResultRacebet->update($engResultRacebetData);
                                }
                            }
                        }

                        if (isset($event['market'])) {

                            $marketList = $this->wrapIfExposed($event['market'], 'id');

                            foreach ($marketList as $market) {

                                if (isset($market['rule4'])) {

                                    // some files may come with more than 1 rule4 tag
                                    $ruleList = $this->wrapIfExposed($market['rule4'], 'id');

                                    foreach ($ruleList as $rule) {

                                        $engResultRule = EngResultRule::where('rule_id', $rule['@id'])->first();

                                        $engResultRuleData = [
                                            'event_id' => $event['@id'] ?? null,
                                            'competitor_id' => $rule['@selectionref'] ?? null,
                                            'rule_id' => $rule['@id'] ?? null,
                                            'type' => $rule['@type'] ?? null,
                                            'deduction' => $rule['@deduction'] ?? null,
                                            'runner_deduction' => $rule['@runnerDeduction'] ?? null,
                                        ];

                                        if (!$engResultRule) {
                                            EngResultRule::create($engResultRuleData);
                                        } else {
                                            $engResultRule->update($engResultRuleData);
                                        }
                                    }
                                }
                            }
                        }
                    }


                    foreach ($competitorList as $competitor) {

                        $engResultCompetitorLite = EngResultCompetitorLite::where('competitor_id', $competitor['@id'])->first();

                        $engResultCompetitorLiteData = [
                            'event_id' => $event['@id'] ?? null,
                            'name' => $competitor['@name'] ?? null,
                            'competitor_id' => $competitor['@id'] ?? null,
                            'jockey' => $competitor['@jockey'] ?? null,
                            'jockey_allowance' => $competitor['@claiming'] ?? null,
                            'num' => $competitor['@num'] ?? null,
                            'trainer' => $competitor['@trainer'] ?? null,
                            'run_status' => $competitor['@status'] ?? null,
                        ];

                        if (!$engResultCompetitorLite) {

                            // TODO: you might need to find a way to polish this bit after you check if the rest of the stuff is working as usual

                            $createdCompetitor = EngResultCompetitorLite::create($engResultCompetitorLiteData);

                            // competitor positions (directly referencing the first found cause there's only one result tag per ES file from the looks of it)
                            if (isset($event['result']['position'])) {

                                // wrap if single element/is exposed
                                $positionList = $this->wrapIfExposed($event['result']['position'], 'id');

                                foreach ($positionList as $position) {

                                    if ($position['@selectionref'] == $createdCompetitor->competitor_id) {
                                        $createdCompetitor->update(['finish_position' => $position['@position']]);
                                        $createdCompetitor->update(['fav_status' => $position['@fav'] ?? null]);

                                        // if deadheat exists add it too
                                        $createdCompetitor->update(['deadheat' => $position['@deadheat'] ?? null]);
                                    }
                                }
                            }
                        } else {

                            $engResultCompetitorLite->update($engResultCompetitorLiteData);

                            $createdCompetitor = $engResultCompetitorLite;

                            // competitor positions (directly referencing the first found cause there's only one result tag per ES file from the looks of it)
                            if (isset($event['result']['position'])) {

                                // wrap if single element/is exposed
                                $positionList = $this->wrapIfExposed($event['result']['position'], 'id');

                                foreach ($positionList as $position) {

                                    if ($position['@selectionref'] == $createdCompetitor->competitor_id) {
                                        $createdCompetitor->update(['finish_position' => $position['@position']]);
                                        $createdCompetitor->update(['fav_status' => $position['@fav'] ?? null]);

                                        // if deadheat exists add it too
                                        $createdCompetitor->update(['deadheat' => $position['@deadheat'] ?? null]);
                                    }
                                }
                            }
                        }

                        // competitor price
                        if (isset($competitor['price'])) {

                            $priceList = $competitor['price'];

                            // wrapping if single element/is exposed
                            $priceList = $this->wrapIfExposed($priceList, 'id');

                            foreach ($priceList as $price) {

                                $engResultCompetitorPrice = EngResultCompetitorPrice::where('price_id', $price['@id'])->first();

                                $engResultCompetitorPriceData = [
                                    'competitor_id' => $competitor['@id'] ?? null,
                                    'price_id' => $price['@id'] ?? null,
                                    'time' => ($price['@time'] ?? false) ? Carbon::createFromFormat('H:i:s', $price['@time']) : null,
                                    'fract' => $price['@fract'] ?? null,
                                    'dec' => $price['@dec'] ?? null,
                                    'mktnum' => $price['@mktnum'] ?? null,
                                    'mkttype' => $price['@mkttype'] ?? null,
                                    'timestamp' => $price['@timestamp'] ?? null,
                                ];

                                if (!$engResultCompetitorPrice) {
                                    EngResultCompetitorPrice::create($engResultCompetitorPriceData);
                                } else {
                                    $engResultCompetitorPrice->update($engResultCompetitorPriceData);
                                }
                            }
                        }
                    }
                } else if (
                    // EX for competitors, positions
                    (isset($arrayData['data']['@mnem']) && $arrayData['data']['@mnem'] == 'EX') &&
                    (isset($arrayData['data']['@date']) && date('Y-m-d', time() - 7200) === date('Y-m-d', strtotime($arrayData['data']['@date'])))
                ) {

                    $event = $arrayData['data']['meeting']['event'];
                    $competitorList = $arrayData['data']['meeting']['event']['selection'];

                    // wrapping if single element/is exposed

                    $competitorList = $this->wrapIfExposed($competitorList, 'id');

                    foreach ($competitorList as $competitor) {

                        $engResultCompetitor = EngResultCompetitor::where('competitor_id', $competitor['@id'])->first();

                        $engResultCompetitorData = [
                            'event_id' => $event['@id'] ?? null,
                            'name' => $competitor['@name'] ?? null,
                            'competitor_id' => $competitor['@id'] ?? null,
                            'short_name' => $competitor['@shortName'] ?? null,
                            'jockey' => $competitor['@jockey'] ?? null,
                            'jockey_allowance' => $competitor['@jockeyAllowance'] ?? null,
                            'short_jockey' => $competitor['@shortJockey'] ?? null,
                            'num' => $competitor['@num'] ?? null,
                            'age' => $competitor['@age'] ?? null,
                            'trainer' => $competitor['@trainer'] ?? null,
                            'owner' => $competitor['@owner'] ?? null,
                            'dam' => $competitor['@dam'] ?? null,
                            'sire' => $competitor['@sire'] ?? null,
                            'damsire' => $competitor['@damsire'] ?? null,
                            'bred' => $competitor['@bred'] ?? null,
                            'weight' => $competitor['@weight'] ?? null,
                            'born_date' => $competitor['@bornDate'] ?? null,
                            'color' => $competitor['@colour'] ?? null,
                            'sex' => $competitor['@sex'] ?? null,
                        ];


                        if (!$engResultCompetitor) {

                            // TODO: same polishing up required here

                            $createdCompetitor = EngResultCompetitor::create($engResultCompetitorData);

                            if (isset($event['result']['position'])) {

                                // wrap if single element/is exposed
                                $positionList = $this->wrapIfExposed($event['result']['position'], 'id');

                                foreach ($positionList as $position) {

                                    if ($position['@selectionref'] == $createdCompetitor->competitor_id) {
                                        $createdCompetitor->update(['finish_position' => $position['@position']]);
                                        $createdCompetitor->update(['fav_status' => $position['@fav'] ?? null]);
                                    }
                                }
                            }
                        } else {
                            $engResultCompetitor->update($engResultCompetitorData);

                            $createdCompetitor = $engResultCompetitor;

                            if (isset($event['result']['position'])) {

                                // wrap if single element/is exposed
                                $positionList = $this->wrapIfExposed($event['result']['position'], 'id');

                                foreach ($positionList as $position) {

                                    if ($position['@selectionref'] == $createdCompetitor->competitor_id) {
                                        $createdCompetitor->update(['finish_position' => $position['@position']]);
                                        $createdCompetitor->update(['fav_status' => $position['@fav'] ?? null]);
                                    }
                                }
                            }
                        }
                    }
                }
            }

            // if the transaction concluded without any errors, commit em
            DB::commit();
            return 1;
        } catch (Exception $e) {
            DB::rollback();
            dd($e, $filename);
        }
    }

    // store all english result meeting and event data in a single iteration for a more OPTIMIZED approach
    public function storeAllEnglishResultData()
    {
        try {

            DB::beginTransaction();

            // server directory
            $directory = '//172.16.50.39/Masters/';

            // Directory and file check
            File::isDirectory($directory);
            $filesFound =  count(File::files($directory)) > 0;
            if (!$filesFound) {
                throw new FileNotFoundException("Directory " . $directory . " is empty.");
            }

            // date modified filter ranges
            $currentTimestamp = now()->timestamp;
            $thresholdTimestamp = now()->subDays(1)->timestamp;

            // date modified filter iterator (only includes files modified within the last 24 hours)
            $iterator = new CallbackFilterIterator(
                new GlobIterator($directory . '*.xml', FilesystemIterator::KEY_AS_FILENAME),
                function ($fileInfo) use ($thresholdTimestamp, $currentTimestamp) {
                    $lastModifiedTimestamp = $fileInfo->getMTime();
                    return $lastModifiedTimestamp >= $thresholdTimestamp && $lastModifiedTimestamp <= $currentTimestamp;
                }
            );

            foreach ($iterator as $file) {

                $filename = $iterator->key();

                // skip unreadable files
                try {
                    $xmlNode = simplexml_load_file($directory . $filename);
                } catch (ErrorException $e) {
                    continue;
                }

                $arrayData = $this->xmlToArray($xmlNode);

                // backup
                File::copy($file->getPathname(), storage_path('app/xml/english_feed_backup/' . date('Y-m-d') . '/' . $file->getFilename()));


                /**
                 *
                 * @var object $arrayData
                 */

                // File type filter
                // ----------------

                if (
                    // ES for meetings, events, competitor prices, & racebets
                    (isset($arrayData['data']['@mnem']) && $arrayData['data']['@mnem'] == 'ES') &&
                    (isset($arrayData['data']['@date']) && date('Y-m-d', time() - 7200) === date('Y-m-d', strtotime($arrayData['data']['@date'])))
                ) {

                    $coreData = $arrayData['data'];
                    $meeting = $arrayData['data']['meeting'];

                    $eventList = $arrayData['data']['meeting']['event'];
                    $competitorList = $arrayData['data']['meeting']['event']['selection'];

                    // wrapping if single element/is exposed
                    $eventList = $this->wrapIfExposed($eventList, 'id');
                    $competitorList = $this->wrapIfExposed($competitorList, 'id');

                    // grab meeting if exists
                    $engResultMeeting = EngResultMeeting::where('code', $meeting['@code'])->where('date', $meeting['@date'])->first();

                    // prepare data
                    $engResultMeetingData = [
                        'name' => $meeting['@name'] ?? null,
                        'code' => $meeting['@code'] ?? null,
                        'country' => $coreData['@country'] ?? null,
                        'category' => $coreData['@category'] ?? null,
                        'sportcode' => $meeting['@sportcode'] ?? null,
                        'date' => new DateTime($meeting['@date'] ?? null),
                        'events' => $meeting['@events'] ?? null,
                        'status' => $meeting['@status'] ?? null,
                        'coverage_code' => $meeting['@coverageCode'] ?? null,
                        'start_time' => ($meeting['@startTime'] ?? false) ? Carbon::createFromFormat('H:i:s', $meeting['@startTime']) : null,
                        'end_time' => ($meeting['@endTime'] ?? false) ? Carbon::createFromFormat('H:i:s', $meeting['@endTime']) : null,
                        'going' => $meeting['@going'] ?? null,
                    ];


                    if (!$engResultMeeting) {
                        // if the meeting isn't there, create one, use the same variable from before
                        $engResultMeeting = EngResultMeeting::create($engResultMeetingData);
                    } else {
                        // if there is, just update the model
                        $engResultMeeting->update($engResultMeetingData);
                    }

                    foreach ($eventList as $event) {

                        $engResultEvent = EngResultEvent::where('event_id', $event['@id'])->first();

                        $engResultEventData = [
                            'meeting_auto_id' => $engResultMeeting->id ?? null,
                            'meeting_code' => $meeting['@code'] ?? null,
                            'name' => $event['@name'] ?? null,
                            'event_type' => $engResultMeeting->category ?? null,
                            'is_virtual' => $engResultMeeting->country == 'VR',
                            'event_id' => $event['@id'] ?? null,
                            'num' => $event['@num'] ?? null,
                            'date' => $engResultMeeting->date ?? null,
                            'time' => ($event['@time'] ?? false) ? Carbon::createFromFormat('H:i:s', $event['@time']) : null,
                            'places_expected' => $event['@placesExpected'] ?? null,
                            'each_way_places' => $event['@eachWayPlaces'] ?? null,
                            'coverage_code' => $event['@coverageCode'] ?? null,
                            'course_type' => $event['@courseType'] ?? null,
                            'surface' => $event['@surface'] ?? null,
                            'grade' => $event['@grade'] ?? null,
                            'handicap' => $event['@handicap'] ?? null,
                            'status' => $event['@status'] ?? null,
                            'runners' => $event['@runners'] ?? null,
                            'going' => $event['@going'] ?? null,
                            'distance' => $event['@distance'] ?? null,
                            'offtime' => ($event['@offTime'] ?? false) ? Carbon::createFromFormat('H:i:s', $event['@offTime']) : null,
                            'progress_code' => $event['@progressCode'] ?? null,
                            'pmsg' => $event['@pmsg'] ?? null,
                        ];

                        if (!$engResultEvent) {
                            EngResultEvent::create($engResultEventData);
                        } else {
                            $engResultEvent->update($engResultEventData);
                        }

                        if (isset($event['racebet'])) {

                            // some races come with single racebets, wrap if exposed
                            $racebetList = $this->wrapIfExposed($event['racebet'], 'id');

                            foreach ($racebetList as $racebet) {

                                $engResultRacebet = EngResultRacebet::where('racebet_id', $racebet['@id'])->first();

                                $engResultRacebetData = [
                                    'event_id' => $event['@id'] ?? null,
                                    'racebet_id' => $racebet['@id'] ?? null,
                                    'bet_type' => $racebet['@bettype'] ?? null,
                                    'amount' => $racebet['@amount'] ?? null,
                                    'instance' => $racebet['@instance'] ?? null,
                                    'type' => $racebet['@type'] ?? null,
                                ];

                                if (!$engResultRacebet) {
                                    EngResultRacebet::create($engResultRacebetData);
                                } else {
                                    $engResultRacebet->update($engResultRacebetData);
                                }
                            }
                        }

                        if (isset($event['market'])) {

                            $marketList = $this->wrapIfExposed($event['market'], 'id');

                            foreach ($marketList as $market) {

                                if (isset($market['rule4'])) {

                                    // some files may come with more than 1 rule4 tag
                                    $ruleList = $this->wrapIfExposed($market['rule4'], 'id');

                                    foreach ($ruleList as $rule) {

                                        $engResultRule = EngResultRule::where('rule_id', $rule['@id'])->first();

                                        $engResultRuleData = [
                                            'event_id' => $event['@id'] ?? null,
                                            'competitor_id' => $rule['@selectionref'] ?? null,
                                            'rule_id' => $rule['@id'] ?? null,
                                            'type' => $rule['@type'] ?? null,
                                            'deduction' => $rule['@deduction'] ?? null,
                                            'runner_deduction' => $rule['@runnerDeduction'] ?? null,
                                        ];

                                        if (!$engResultRule) {
                                            EngResultRule::create($engResultRuleData);
                                        } else {
                                            $engResultRule->update($engResultRuleData);
                                        }
                                    }
                                }
                            }
                        }
                    }


                    foreach ($competitorList as $competitor) {

                        $engResultCompetitorLite = EngResultCompetitorLite::where('competitor_id', $competitor['@id'])->first();

                        $engResultCompetitorLiteData = [
                            'event_id' => $event['@id'] ?? null,
                            'name' => $competitor['@name'] ?? null,
                            'competitor_id' => $competitor['@id'] ?? null,
                            'jockey' => $competitor['@jockey'] ?? null,
                            'jockey_allowance' => $competitor['@claiming'] ?? null,
                            'num' => $competitor['@num'] ?? null,
                            'trainer' => $competitor['@trainer'] ?? null,
                            'run_status' => $competitor['@status'] ?? null,
                        ];

                        if (!$engResultCompetitorLite) {

                            $createdCompetitor = EngResultCompetitorLite::create($engResultCompetitorLiteData);

                            // competitor positions (directly referencing the first found cause there's only one result tag per ES file from the looks of it)
                            if (isset($event['result']['position'])) {

                                // wrap if single element/is exposed
                                $positionList = $this->wrapIfExposed($event['result']['position'], 'id');

                                foreach ($positionList as $position) {

                                    if ($position['@selectionref'] == $createdCompetitor->competitor_id) {
                                        $createdCompetitor->update(['finish_position' => $position['@position']]);
                                        $createdCompetitor->update(['fav_status' => $position['@fav'] ?? null]);

                                        // if deadheat exists add it too
                                        $createdCompetitor->update(['deadheat' => $position['@deadheat'] ?? null]);
                                    }
                                }
                            }
                        } else {

                            $engResultCompetitorLite->update($engResultCompetitorLiteData);

                            $createdCompetitor = $engResultCompetitorLite;

                            // competitor positions (directly referencing the first found cause there's only one result tag per ES file from the looks of it)
                            if (isset($event['result']['position'])) {

                                // wrap if single element/is exposed
                                $positionList = $this->wrapIfExposed($event['result']['position'], 'id');

                                foreach ($positionList as $position) {

                                    if ($position['@selectionref'] == $createdCompetitor->competitor_id) {
                                        $createdCompetitor->update(['finish_position' => $position['@position']]);
                                        $createdCompetitor->update(['fav_status' => $position['@fav'] ?? null]);

                                        // if deadheat exists add it too
                                        $createdCompetitor->update(['deadheat' => $position['@deadheat'] ?? null]);
                                    }
                                }
                            }
                        }

                        // competitor price
                        if (isset($competitor['price'])) {

                            $priceList = $competitor['price'];

                            // wrapping if single element/is exposed
                            $priceList = $this->wrapIfExposed($priceList, 'id');

                            foreach ($priceList as $price) {

                                $engResultCompetitorPrice = EngResultCompetitorPrice::where('price_id', $price['@id'])->first();

                                $engResultCompetitorPriceData = [
                                    'competitor_id' => $competitor['@id'] ?? null,
                                    'price_id' => $price['@id'] ?? null,
                                    'time' => ($price['@time'] ?? false) ? Carbon::createFromFormat('H:i:s', $price['@time']) : null,
                                    'fract' => $price['@fract'] ?? null,
                                    'dec' => $price['@dec'] ?? null,
                                    'mktnum' => $price['@mktnum'] ?? null,
                                    'mkttype' => $price['@mkttype'] ?? null,
                                    'timestamp' => $price['@timestamp'] ?? null,
                                ];

                                if (!$engResultCompetitorPrice) {
                                    EngResultCompetitorPrice::create($engResultCompetitorPriceData);
                                } else {
                                    $engResultCompetitorPrice->update($engResultCompetitorPriceData);
                                }
                            }
                        }
                    }
                }
                // EX check removed due to no longer being needed
            }

            // if the transaction concluded without any errors, commit em
            DB::commit();
            return 1;
        } catch (Exception $e) {
            DB::rollback();
            dd($e);
        }
    }

    public function flushAussieResultEventTables()
    {
        try {
            ResultEvent::truncate();
            ResultCompetitor::truncate();
            ResultCompetitorPrice::truncate();
            ResultPool::truncate();
            ResultDividend::truncate();
        } catch (Exception $e) {
            dd($e);
        }
    }

    public function flushAussieResultMeetingTables()
    {
        try {
            ResultMeeting::truncate();
            ResultMeetingEvent::truncate();
        } catch (Exception $e) {
            dd($e);
        }
    }

    public function flushEnglishResultMeetingTables()
    {
        try {
            EngResultMeeting::truncate();
        } catch (Exception $e) {
            dd($e);
        }
    }

    public function flushEnglishResultEventTables()
    {
        try {
            EngResultEvent::truncate();
            EngResultEventPrize::truncate();
            EngResultCompetitor::truncate();
            EngResultCompetitorLite::truncate();
            EngResultCompetitorPrice::truncate();
            EngResultRacebet::truncate();
            EngResultRule::truncate();
        } catch (Exception $e) {
            dd($e);
        }
    }

    // wrap converted array if the children are exposed
    // ------------------------------------------------

    private function wrapIfExposed($element, $attributeToCheck)
    {
        if ($element['@' . $attributeToCheck] ?? false) {
            $element = [$element];
        }
        return $element;
    }


    // XML to Array conversion
    // -----------------------

    public function xmlToArray($xml, $options = array())
    {
        if ($xml) {
            $defaults = array(
                'namespaceSeparator' => ':', //you may want this to be something other than a colon
                'attributePrefix' => '@',   //to distinguish between attributes and nodes with the same name
                'alwaysArray' => array(),   //array of xml tag names which should always become arrays
                'autoArray' => true,        //only create arrays for tags which appear more than once
                'textContent' => '$',       //key used for the text content of elements
                'autoText' => true,         //skip textContent key if node has no attributes or child nodes
                'keySearch' => false,       //optional search and replace on tag and attribute names
                'keyReplace' => false       //replace values for above search values (as passed to str_replace())
            );
            $options = array_merge($defaults, $options);
            $namespaces = $xml->getDocNamespaces();
            $namespaces[''] = null; //add base (empty) namespace

            //get attributes from all namespaces
            $attributesArray = array();
            foreach ($namespaces as $prefix => $namespace) {

                foreach ($xml->attributes($namespace) as $attributeName => $attribute) {
                    //replace characters in attribute name
                    if ($options['keySearch']) $attributeName =
                        str_replace($options['keySearch'], $options['keyReplace'], $attributeName);
                    $attributeKey = $options['attributePrefix']
                        . ($prefix ? $prefix . $options['namespaceSeparator'] : '')
                        . $attributeName;
                    $attributesArray[$attributeKey] = (string)$attribute;
                }
            }

            //get child nodes from all namespaces
            $tagsArray = array();
            foreach ($namespaces as $prefix => $namespace) {
                foreach ($xml->children($namespace) as $childXml) {
                    //recurse into child nodes
                    $childArray = $this->xmlToArray($childXml, $options);
                    // while(list($childTagName, $childProperties) = each($childArray)){

                    // check if the childArray is empty
                    if ($childArray) {
                        foreach ($childArray as $childTagName => $childProperties) {

                            //replace characters in tag name
                            if ($options['keySearch']) $childTagName =
                                str_replace($options['keySearch'], $options['keyReplace'], $childTagName);
                            //add namespace prefix, if any
                            if ($prefix) $childTagName = $prefix . $options['namespaceSeparator'] . $childTagName;

                            if (!isset($tagsArray[$childTagName])) {
                                //only entry with this key
                                //test if tags of this type should always be arrays, no matter the element count
                                $tagsArray[$childTagName] =
                                    in_array($childTagName, $options['alwaysArray']) || !$options['autoArray']
                                    ? array($childProperties) : $childProperties;
                            } elseif (
                                is_array($tagsArray[$childTagName]) && array_keys($tagsArray[$childTagName])
                                === range(0, count($tagsArray[$childTagName]) - 1)
                            ) {
                                //key already exists and is integer indexed array
                                $tagsArray[$childTagName][] = $childProperties;
                            } else {
                                //key exists so convert to integer indexed array with previous value in position 0
                                $tagsArray[$childTagName] = array($tagsArray[$childTagName], $childProperties);
                            }
                        }
                    }
                }
            }

            //get text content of node
            $textContentArray = array();
            $plainText = trim((string)$xml);
            if ($plainText !== '') $textContentArray[$options['textContent']] = $plainText;

            //stick it all together
            $propertiesArray = !$options['autoText'] || $attributesArray || $tagsArray || ($plainText === '')
                ? array_merge($attributesArray, $tagsArray, $textContentArray) : $plainText;

            //return node as array
            return array(
                $xml->getName() => $propertiesArray
            );
        }
    }
}
