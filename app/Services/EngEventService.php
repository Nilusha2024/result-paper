<?php

namespace App\Services;

use App\Models\EngResultCompetitor;
use App\Models\EngResultCompetitorLite;
use App\Models\EngResultEvent;
use App\Models\EngResultRacebet;
use App\Models\EngResultRule;
use Illuminate\Support\Facades\DB;

use function PHPUnit\Framework\isEmpty;

// English Feed Event Service
// --------------------------

class EngEventService
{

    private function getPlaceCountForEvent($event)
    {
        $placeCount = $event->places_expected;
        return $placeCount;
    }

    // if there's a deadheat for the event, for positions 1,2,3, add one more to the place count
    public function getPlacingRunnersForEvent($event)
    {
        $deatheat = $this->getDeadHeat($event);

        $placeCount = (in_array($deatheat, [1, 2, 3])) ? $this->getPlaceCountForEvent($event) + 1 :  $this->getPlaceCountForEvent($event);

        $placingRunners = EngResultCompetitorLite::where('event_id', $event->event_id)->whereNotNull('finish_position')->orderBy('finish_position')->limit($placeCount)->get();

        return $placingRunners;
    }

    public function getNonRunnersForEvent($event)
    {
        // Found corresponding to horse non runners = N, W
        // Found corresponding to dog non runners = V

        $nonRunnerStatuses = ['N', 'W', 'V'];
        $nonRunners = EngResultCompetitorLite::where('event_id', $event->event_id)->whereIn('run_status', $nonRunnerStatuses)->get();

        // Trap vacants come in the database as 'VACANT's so rename them to 'Trap Vacant'
        $nonRunners->transform(function ($nonRunner) {
            if ($nonRunner->name === 'VACANT') {
                $nonRunner->name = 'Trap Vacant';
            }
            return $nonRunner;
        });

        return $nonRunners->isEmpty() ? NULL : $nonRunners;
    }

    public function getSpecialRacebetsForEvent($event)
    {
        // F is CSF
        // K is CSF(DG) / BF
        // J is also CSF(DG) / BF ?
        // T is TRICAST
        // M is TRICAST(DG)
        // L is also TRICAST(DG)
        // okay now this is getting confusing ...
        $betTypes = ['F', 'K', 'J', 'T', 'M', 'L'];

        $racebets = EngResultRacebet::where('event_id', $event->event_id)
            ->whereIn('bet_type', $betTypes)
            ->orderBy('instance', 'ASC')
            ->orderBy(DB::raw("CASE bet_type " . implode(" ", array_map(function ($type, $order) {
                return "WHEN '{$type}' THEN {$order} ";
            }, $betTypes, range(1, count($betTypes)))) . " END"))
            ->get();


        // will be returning this
        $racebetSets = [];

        $highestBetInstance = $this->getHighestBetInstance($event);
        $betInstance = 1;

        while ($betInstance <= $highestBetInstance) {

            // get the racebets by instance as and mark them as a set
            $racebetSet =  $racebets->where('instance', $betInstance);

            // push the racebets into the set, and push the set into the array
            $racebetSets[] = $racebetSet;

            // increment count to continue
            $betInstance++;
        }

        return $racebetSets;
    }


    public function getAllbetsForEvent($event)
    {
        $allbets = EngResultRule::where('event_id', $event->event_id)->where('type', 'A')->where('deduction', '>', 0)->first();;
        return $allbets;
    }

    public function getWinnerTrainerDataForEvent($event)
    {

        $winner = EngResultCompetitorLite::where('event_id', $event->event_id)->where('finish_position', 1)->first();

        // jockey data with allowance (if there is any)
        $jockey = $winner->jockey ?? '';
        $jockey .= !empty($winner->jockey_allowance) ? " {$winner->jockey_allowance}" : "";

        // trainer data
        $trainer = $winner->trainer ?? '';

        $trainerData = array($jockey, $trainer);

        return $trainerData;
    }

    public function isTricastBetAvailable($event)
    {
        $betTypesAvailable = EngResultRacebet::where('event_id', $event->event_id)->pluck('bet_type')->toArray();

        $isTricastBetAvailable = in_array('T', $betTypesAvailable) || in_array('M', $betTypesAvailable) || in_array('L', $betTypesAvailable);

        return $isTricastBetAvailable;
    }

    public function getTricastBonus($event)
    {
        return $event->runners <= 15 ? 5 : 10;
    }

    // returns the position of the deadheat if an event has deadheat, else returns null
    public function getDeadHeat($event)
    {
        $position = EngResultCompetitorLite::where('event_id', $event->event_id)->whereNotNull('deadheat')->value('finish_position');
        return $position ?? false;
    }

    public function getHighestBetInstance($event)
    {
        $highestBetInstance = EngResultRacebet::where('event_id', $event->event_id)->orderBy('instance', 'DESC')->value('instance');
        return $highestBetInstance;
    }

    // returns the runner number sequence for the deadheat bet
    public function getDeadHeatRunnerNumberSequence($event, $bet, $deadheat)
    {
        $runnerNumberSequence = '';

        $forecastTypes = ['F', 'K', 'J'];
        $tricastTypes = ['T', 'M', 'L'];

        // Arranging the sequence
        // ----------------------
        // Primarily order by the finish positions of the runners
        // Secondarily order by the deadheat attribute value, as ASC in instance 1, and DESC in instance 2
        // This way the runner numbers will be properly swapped according to the deadheat value while retaining the proper finish position order

        if (in_array($bet->bet_type, $forecastTypes)) {

            // CSF / BF
            // Forecasts consider the first 2 positions

            if (in_array($deadheat, [1, 2])) {
                if ($bet->instance == 1) {
                    $runnerNumberSequence = implode('/', EngResultCompetitorLite::where('event_id', $event->event_id)->orderByRaw('ISNULL(finish_position), finish_position ASC')->orderByRaw('ISNULL(deadheat), deadheat ASC')->limit(2)->pluck('num')->toArray());
                } else if ($bet->instance == 2) {
                    $runnerNumberSequence = implode('/', EngResultCompetitorLite::where('event_id', $event->event_id)->orderByRaw('ISNULL(finish_position), finish_position ASC')->orderByRaw('ISNULL(deadheat), deadheat DESC')->limit(2)->pluck('num')->toArray());
                }
            }
        } else if (in_array($bet->bet_type, $tricastTypes)) {

            // TC
            // Tricasts consider the first 3 positions

            if (in_array($deadheat, [1, 2, 3])) {
                if ($bet->instance == 1) {
                    $runnerNumberSequence = implode('/', EngResultCompetitorLite::where('event_id', $event->event_id)->orderByRaw('ISNULL(finish_position), finish_position ASC')->orderByRaw('ISNULL(deadheat), deadheat ASC')->limit(3)->pluck('num')->toArray());
                } else if ($bet->instance == 2) {
                    $runnerNumberSequence = implode('/', EngResultCompetitorLite::where('event_id', $event->event_id)->orderByRaw('ISNULL(finish_position), finish_position ASC')->orderByRaw('ISNULL(deadheat), deadheat DESC')->limit(3)->pluck('num')->toArray());
                }
            }
        }

        return $runnerNumberSequence;
    }
}
