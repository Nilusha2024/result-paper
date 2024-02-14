<?php

namespace App\Services;

use App\Models\EngResultCompetitorLite;
use App\Models\EngResultCompetitorPrice;
use App\Models\EngResultEvent;
use Exception;

// English Feed Selection Service
// ------------------------------

class EngSelectionService
{

    private $oddsToDividendsMap;

    public function __construct()
    {
        $this->oddsToDividendsMap = config('odds.odds_to_dividends_map');
    }


    public function getOddsForSelection($selection, $format = "DEFAULT")
    {
        $finalPrice = EngResultCompetitorPrice::where('competitor_id', $selection->competitor_id)->orderByDesc('timestamp')->first();

        $odd = ($format == "PRINT") ?
            str_replace('/', ' to ', $finalPrice->fract ?? '?/?')
            : str_replace('/', '-', $finalPrice->fract ?? '?/?');

        return $odd;
    }


    public function getDividendsForOdds($odd, $off, $deadheat, $finish_pos)
    {
        // get odd fraction
        [$oddNumerator, $oddDenominator] = array_map('intval', explode('-', $odd));

        $decimal = $oddNumerator / $oddDenominator;
        $winDividendCore = $decimal * 10;
        $oneFourthDividendCore = ($decimal / 4) * 10;
        $oneFifthDividendCore = ($decimal / 5) * 10;

        // calculate dividends
        if ($off > 0) {

            $offPercentage = $off / 100;

            $winDividends = ($winDividendCore - ($winDividendCore *  $offPercentage)) + 10;
            $oneFourthDividends = ($oneFourthDividendCore - ($oneFourthDividendCore * $offPercentage)) + 10;
            $oneFifthDividends = ($oneFifthDividendCore - ($oneFifthDividendCore * $offPercentage)) + 10;
        } else {
            $winDividends = $winDividendCore + 10;
            $oneFourthDividends = $oneFourthDividendCore + 10;
            $oneFifthDividends = $oneFifthDividendCore + 10;
        }

        // format the dividends to two decimal places cause the print has it that way
        // half the win dividends if the deadheat position is 1
        // half the place dividends if the deadheat position is for the rest of the eligible finish positions

        $winDividends = number_format(($deadheat == 1 && $finish_pos == $deadheat) ? $winDividends / 2 : $winDividends, 2, '.',  '');
        $oneFourthDividends = number_format((in_array($deadheat, [2, 3, 4]) && $finish_pos == $deadheat) ? $oneFourthDividends / 2 : $oneFourthDividends, 2, '.',  '');
        $oneFifthDividends = number_format((in_array($deadheat, [2, 3, 4]) && $finish_pos == $deadheat) ? $oneFifthDividends / 2 : $oneFifthDividends, 2, '.',  '');


        $dividends = array(
            'WIN' => $winDividends,
            'PLACE (1-4)' => $oneFourthDividends,
            'PLACE (1-5)' => $oneFifthDividends,
        );

        return $dividends;
    }


    public function getDividendForSelection($selection, $off, $deadheat)
    {
        try {

            // Get all valid dividends for selection
            $odd = $this->getOddsForSelection($selection);

            // If missing, return prematurely
            if ($odd == '?-?' || $odd == '? to ?') {
                $dividends = $selection->finish_position == 1 ? array('WIN' => '???', 'PLACE' => '???') : array('PLACE' => '???');
                return $dividends;
            }

            // via formula
            $dividends = $this->getDividendsForOdds($odd, $off, $deadheat, $selection->finish_position);

            $relevantEvent = EngResultEvent::where('event_id', $selection->event_id)->first();

            if ($relevantEvent->event_type == 'DG') {
                $dividends = $selection->finish_position == 1 ? array('WIN' => $dividends['WIN'], 'PLACE' => $dividends['PLACE (1-4)']) : array('PLACE' => $dividends['PLACE (1-4)']);
            } else if ($relevantEvent->runners <= 7 || $relevantEvent->runners >= 12) {
                if ($relevantEvent->runners <= 4) {

                    // only wind dividends for the winner in events with 4 or less runners
                    $dividends = $selection->finish_position == 1 ? array('WIN' => $dividends['WIN']) : [];
                } else {

                    // checking if the tricast bet is there for the race
                    // If there is, then 1/4th
                    // If ther isn't, then 1/5th

                    $engEventService = new EngEventService();
                    $isTricastBetAvailable = $engEventService->isTricastBetAvailable($relevantEvent);

                    $dividendType = (!$isTricastBetAvailable && $relevantEvent->runners >= 12) ? 'PLACE (1-5)' : 'PLACE (1-4)';
                    $dividends = $selection->finish_position == 1 ? array('WIN' => $dividends['WIN'], 'PLACE' => $dividends[$dividendType]) : array('PLACE' => $dividends[$dividendType]);
                }
            } else if ($relevantEvent->runners >= 8 || $relevantEvent->runners <= 11) {
                $dividends = $selection->finish_position == 1 ? array('WIN' => $dividends['WIN'], 'PLACE' => $dividends['PLACE (1-5)']) : array('PLACE' => $dividends['PLACE (1-5)']);
            }

            return $dividends;
        } catch (Exception $e) {
            dd($e, $odd, $selection);
        }
    }

    // public function jokeyHasAllowance($record)
    // {
    //     return EngResultCompetitorLite::find($record->id)->value('jockey_allowance') ? true : false;
    // }


    private function fetchAnEquivalentOdd($odd)
    {
        // NOTE: Based on some retrieved data, you can assume that the feed sends the simplified odd

        [$oddNumerator, $oddDenominator] =  array_map('intval', explode('-', $odd));

        // if the numerator & denominators are divisible by 10, check for a simplified version in the map
        // if not check for a unsimplified one

        $equivalentOdd = ($oddNumerator % 10 == 0 && $oddDenominator % 10 == 0) ?
            ($oddNumerator / 10) . '-' . ($oddDenominator / 10)
            : ($oddNumerator * 10) . '-' . ($oddDenominator * 10);

        return $equivalentOdd;
    }
}
