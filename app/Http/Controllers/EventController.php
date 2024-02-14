<?php

namespace App\Http\Controllers;

use App\Models\EngResultEvent;
use App\Models\EngResultMeeting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use App\Models\ResultEvent;
use Illuminate\Support\Facades\DB;
use App\Models\ResultCompetitor;
use App\Models\ResultPool;
use App\Services\EngEventService;
use App\Services\EngMeetingService;
use App\Services\EngSelectionService;
use Carbon\Carbon;
use DateTime;
use Exception;
use PhpParser\Node\Stmt\TryCatch;

class EventController extends Controller
{
    /**
     * Display a listing of the events.
     *
     * @return \Illuminate\Http\Response
     */


    public function index(Request $request)
    {
        $fromDate = $request->input('fromDate');
        $toDate = $request->input('toDate');

        $query = ResultEvent::query();

        // Filter events based on date range if provided
        if ($fromDate && $toDate) {
            $fromDate = Carbon::parse($fromDate);
            $toDate = Carbon::parse($toDate)->endOfDay();

            $query->whereBetween('created_at', [$fromDate, $toDate]);
        }

        $events = $query->get();

        return view('events.index', compact('events', 'fromDate', 'toDate'));
    }

    public function result_date_selection()
    {
        return view('events.result_date_selection');
    }

    public function eventCheck(Request $request)
    {
        // Assuming $date is available in your context
        // $date = '2023/11/07';
        $date = $request->input('selected_date');

        $events = ResultEvent::with(['competitors' => function ($query) {
            $query->orderBy('finish_position');
        }])
            ->where('country_code', 'AU')
            ->whereDate('utc_start_datetime', '=', $date)
            ->orWhereDate('start_datetime', '=', $date)
            ->orderByRaw("CASE
                WHEN event_type = 'R' THEN 1
                WHEN event_type = 'H' THEN 2
                ELSE 3
                END")
            ->orderByRaw("SUBSTRING_INDEX(event_id, '-', 1), CAST(SUBSTRING_INDEX(event_id, '-', -1) AS SIGNED)")
            ->get();

        return view('events.result_check', compact('events'));
    }

    public function downloadEvents(Request $request)
    {
        $selectedEvents = $request->input('selectedEvents', []);

        $eventsToDownload = ResultEvent::with(['competitors' => function ($query) {
            $query->orderBy('finish_position');
        }])
            ->whereIn('id', $selectedEvents)
            ->orderByRaw("CASE
                    WHEN event_type = 'R' THEN 1
                    WHEN event_type = 'H' THEN 2
                    ELSE 3
                    END")
            ->orderByRaw("SUBSTRING_INDEX(event_id, '-', 1), CAST(SUBSTRING_INDEX(event_id, '-', -1) AS SIGNED)")
            ->get();

        $textData = '';
        $currentCourse = null;
        $eventCount = 0;
        $previousCourse = null;

        foreach ($eventsToDownload as $event) {

            $courseInfo = "@Course: " . $event->event_name;

            $event_type = $event->event_type;

            if ($event_type == 'R') {
                $courseInfo .= ' (THR)';
            } elseif ($event_type == 'H') {
                $courseInfo .= ' (HNS)';
            } elseif ($event_type == 'G') {
                $courseInfo .= ' (GRY)';
            }

            // Check if the course has changed, and add course info if it's a new course
            if ($previousCourse !== $courseInfo) {
                $textData .= $courseInfo . "\n\n";
                $previousCourse = $courseInfo;
            }

            // Check if the course has changed
            if ($currentCourse !== $courseInfo) {
                $currentCourse = $courseInfo;
                $eventCount = 0; // Reset the event count for the new course
            }

            // Increment the event count for the current course
            $eventCount++;

            // Get all related data for the event
            $eventData = [
                "@Race : <B>" . $event->race_num . "<B>",
            ];

            $horseNumber = 1;

            // Loop through competitors associated with the event
            $competitors = $event->competitors;

            $horse1Position = null;
            $horse2Position = null;
            $horse3Position = null;
            $horse4Position = null;

            foreach ($competitors as $competitor) {

                // Check if finish_position is not null before adding to $eventData
                if ($competitor->finish_position !== null && $competitor->competitor_type === $event->event_type) {
                    $dividendWin = null;
                    $dividendPlace = null;

                    if ($horseNumber === 1) {
                        $dividendWin = $competitor->dividend()
                            ->where('dividend_type', 'WIN')
                            ->first();
                    }

                    // Check for horse positions
                    if ($horseNumber === 1) {
                        $horse1Position = $competitor->finish_position;
                    } elseif ($horseNumber === 2) {
                        $horse2Position = $competitor->finish_position;
                    } elseif ($horseNumber === 3) {
                        $horse3Position = $competitor->finish_position;
                    } elseif ($horseNumber === 4) {
                        $horse4Position = $competitor->finish_position;
                    }

                    if ($horseNumber === 4) {

                        $nameLength = strlen($competitor->name);

                        if ($nameLength >= 1 && $nameLength <= 4) {
                            $horseInfo = "@Horse" . $horseNumber . " :\t" . $competitor->finish_position . "\t" . ucwords(strtolower($competitor->name)) . "(" . $competitor->competitor_no . ")" . "\t\t\t\t\tN/F/D";
                        } elseif ($nameLength >= 5 && $nameLength <= 12) {
                            $horseInfo = "@Horse" . $horseNumber . " :\t" . $competitor->finish_position . "\t" . ucwords(strtolower($competitor->name)) . "(" . $competitor->competitor_no . ")" . "\t\t\t\tN/F/D";
                        } elseif ($nameLength >= 13 && $nameLength <= 20) {
                            $horseInfo = "@Horse" . $horseNumber . " :\t" . $competitor->finish_position . "\t" . ucwords(strtolower($competitor->name)) . "(" . $competitor->competitor_no . ")" . "\t\t\tN/F/D";
                        } elseif ($nameLength >= 21 && $nameLength <= 29) {
                            $horseInfo = "@Horse" . $horseNumber . " :\t" . $competitor->finish_position . "\t" . ucwords(strtolower($competitor->name)) . "(" . $competitor->competitor_no . ")" . "\t\tN/F/D";
                        } elseif ($nameLength >= 29 && $nameLength <= 36) {
                            $horseInfo = "@Horse" . $horseNumber . " :\t" . $competitor->finish_position . "\t" . ucwords(strtolower($competitor->name)) . "(" . $competitor->competitor_no . ")" . "\t\tN/F/D";
                        }
                    } else {
                        // Retrieve the PLC dividend based on the finish position for all other horses
                        $dividendPlace = $competitor->dividend()
                            ->where('dividend_type', 'PLC')
                            ->where('instance', $competitor->finish_position)
                            ->first();

                        $horseInfo = "";

                        // Add <B> tags for the first horse
                        if ($horseNumber === 1) {

                            $nameLength = strlen($competitor->name);

                            if ($nameLength >= 1 && $nameLength <= 5) {
                                $horseInfo .= "@Horse" . $horseNumber . " :\t" . $competitor->finish_position . "	<B>" . $competitor->name . "(" . $competitor->competitor_no . ")</B>\t\t\t";
                            } elseif ($nameLength >= 6 && $nameLength <= 13) {
                                $horseInfo .= "@Horse" . $horseNumber . " :\t" . $competitor->finish_position . "	<B>" . $competitor->name . "(" . $competitor->competitor_no . ")</B>\t\t";
                            } elseif ($nameLength >= 14 && $nameLength <= 21) {
                                $horseInfo .= "@Horse" . $horseNumber . " :\t" . $competitor->finish_position . "	<B>" . $competitor->name . "(" . $competitor->competitor_no . ")</B>\t";
                            }
                        } elseif ($horseNumber === 2) {

                            $nameLength = strlen($competitor->name);

                            if ($nameLength >= 1 && $nameLength <= 4) {
                                $horseInfo .= "@Horse" . $horseNumber . " :\t" . $competitor->finish_position . "	" . ucwords(strtolower($competitor->name)) . "(" . $competitor->competitor_no . ")\t\t\t\t";
                            } elseif ($nameLength >= 5 && $nameLength <= 12) {
                                $horseInfo .= "@Horse" . $horseNumber . " :\t" . $competitor->finish_position . "	" . ucwords(strtolower($competitor->name)) . "(" . $competitor->competitor_no . ")\t\t\t";
                            } elseif ($nameLength >= 13 && $nameLength <= 20) {
                                $horseInfo .= "@Horse" . $horseNumber . " :\t" . $competitor->finish_position . "	" . ucwords(strtolower($competitor->name)) . "(" . $competitor->competitor_no . ")\t\t";
                            } elseif ($nameLength >= 21 && $nameLength <= 29) {
                                $horseInfo .= "@Horse" . $horseNumber . " :\t" . $competitor->finish_position . "	" . ucwords(strtolower($competitor->name)) . "(" . $competitor->competitor_no . ")\t";
                            } elseif ($nameLength >= 29 && $nameLength <= 36) {
                                $horseInfo .= "@Horse" . $horseNumber . " :\t" . $competitor->finish_position . "	" . ucwords(strtolower($competitor->name)) . "(" . $competitor->competitor_no . ")\t";
                            }
                        } elseif ($horseNumber === 3) {

                            $nameLength = strlen($competitor->name);
                            if ($nameLength >= 1 && $nameLength <= 4) {
                                $horseInfo .= "@Horse" . $horseNumber . " :\t" . $competitor->finish_position . "	" . ucwords(strtolower($competitor->name)) . "(" . $competitor->competitor_no . ")\t\t\t\t";
                            } elseif ($nameLength >= 5 && $nameLength <= 12) {
                                $horseInfo .= "@Horse" . $horseNumber . " :\t" . $competitor->finish_position . "	" . ucwords(strtolower($competitor->name)) . "(" . $competitor->competitor_no . ")\t\t\t";
                            } elseif ($nameLength >= 13 && $nameLength <= 20) {
                                $horseInfo .= "@Horse" . $horseNumber . " :\t" . $competitor->finish_position . "	" . ucwords(strtolower($competitor->name)) . "(" . $competitor->competitor_no . ")\t\t";
                            } elseif ($nameLength >= 21 && $nameLength <= 29) {
                                $horseInfo .= "@Horse" . $horseNumber . " :\t" . $competitor->finish_position . "	" . ucwords(strtolower($competitor->name)) . "(" . $competitor->competitor_no . ")\t";
                            } elseif ($nameLength >= 29 && $nameLength <= 36) {
                                $horseInfo .= "@Horse" . $horseNumber . " :\t" . $competitor->finish_position . "	" . ucwords(strtolower($competitor->name)) . "(" . $competitor->competitor_no . ")\t";
                            }
                        } else {

                            $nameLength = strlen($competitor->name);

                            if ($nameLength >= 1 && $nameLength <= 4) {
                                $horseInfo .= "@Horse" . $horseNumber . " :\t" . $competitor->finish_position . "	" . ucwords(strtolower($competitor->name)) . "(" . $competitor->competitor_no . ")\t\t\t\t";
                            } elseif ($nameLength >= 5 && $nameLength <= 12) {
                                $horseInfo .= "@Horse" . $horseNumber . " :\t" . $competitor->finish_position . "	" . ucwords(strtolower($competitor->name)) . "(" . $competitor->competitor_no . ")\t\t\t";
                            } elseif ($nameLength >= 13 && $nameLength <= 20) {
                                $horseInfo .= "@Horse" . $horseNumber . " :\t" . $competitor->finish_position . "	" . ucwords(strtolower($competitor->name)) . "(" . $competitor->competitor_no . ")\t\t";
                            } elseif ($nameLength >= 21 && $nameLength <= 29) {
                                $horseInfo .= "@Horse" . $horseNumber . " :\t" . $competitor->finish_position . "	" . ucwords(strtolower($competitor->name)) . "(" . $competitor->competitor_no . ")\t";
                            } elseif ($nameLength >= 29 && $nameLength <= 36) {
                                $horseInfo .= "@Horse" . $horseNumber . " :\t" . $competitor->finish_position . "	" . ucwords(strtolower($competitor->name)) . "(" . $competitor->competitor_no . ")\t";
                            }
                        }

                        if ($dividendWin && $horseNumber === 1) {
                            $horseInfo .= "" . number_format($dividendWin->dividend_amount * 10, 2);
                        }
                        if ($dividendPlace) {
                            $horseInfo .= "	" . number_format($dividendPlace->dividend_amount * 10, 2);
                        } else {
                            $nameLength = strlen($competitor->name);

                            if ($horseNumber > 4) {
                                // Display N/F/D based on $nameLength
                                if ($nameLength >= 1 && $nameLength <= 4) {
                                    $horseInfo .= "\t\t\t\tN/F/D";
                                } elseif ($nameLength >= 5 && $nameLength <= 12) {
                                    $horseInfo .= "\tN/F/D";
                                } elseif ($nameLength >= 13 && $nameLength <= 20) {
                                    $horseInfo .= "\tN/F/D";
                                } elseif ($nameLength >= 21 && $nameLength <= 29) {
                                    $horseInfo .= "\tN/F/D";
                                } elseif ($nameLength >= 29 && $nameLength <= 36) {
                                    $horseInfo .= "\tN/F/D";
                                }
                            } else {
                                // Display N/T/D for other horses
                                if ($nameLength >= 1 && $nameLength <= 4) {
                                    $horseInfo .= "\t\t\t\tN/T/D";
                                } elseif ($nameLength >= 5 && $nameLength <= 12) {
                                    $horseInfo .= "\tN/T/D";
                                } elseif ($nameLength >= 13 && $nameLength <= 20) {
                                    $horseInfo .= "\tN/T/D";
                                } elseif ($nameLength >= 21 && $nameLength <= 29) {
                                    $horseInfo .= "\tN/T/D";
                                } elseif ($nameLength >= 29 && $nameLength <= 36) {
                                    $horseInfo .= "\tN/T/D";
                                }
                            }
                        }
                    }

                    $runnerNumber = $competitor->runner->runner_numbers;

                    $eventData[] = $horseInfo;
                    $horseNumber++;
                }
            }

            $notRunners = $competitors->where('status', 'NR')->pluck('name', 'competitor_no')->toArray();

            if ($event_type == 'G') {
                if (empty($notRunners)) {
                    // $eventData[] = "@NotRunners: <B> Not Run: - <B>";
                    $vCompetitors = $competitors->where('status', 'V')->pluck('competitor_no')->toArray();
                    if (!empty($vCompetitors)) {
                        // Combine the competitor numbers with "NR" and create the event data entry
                        $formattedCompetitors = array_map(function ($competitor_no) {
                            return "...Vacant($competitor_no)";
                        }, $vCompetitors);

                        $eventData[] = "@NotRunners: <B> Not Run: - <B>" . implode(', ', $formattedCompetitors);
                    }
                } else {

                    $notRunners = array_map(function ($name, $competitor_no) {
                        return ucwords(strtolower($name)) . "($competitor_no)";
                    }, $notRunners, array_keys($notRunners)); // Pass keys (competitor_no) as the second argument

                    // Retrieve competitors with status 'V'
                    $vCompetitors = $competitors->where('status', 'V')->pluck('competitor_no')->toArray();
                    if (!empty($vCompetitors)) {
                        // Combine the competitor numbers with "V" and create the event data entry
                        $formattedVCompetitors = array_map(function ($competitor_no) {
                            return "...Vacant($competitor_no)";
                        }, $vCompetitors);

                        // Merge the two arrays and create the final message
                        $notRunners = array_merge($notRunners, $formattedVCompetitors);
                    }

                    // Sort $notRunners array based on competitor number
                    usort($notRunners, function ($a, $b) {
                        $competitorNumberA = (int)filter_var($a, FILTER_SANITIZE_NUMBER_INT);
                        $competitorNumberB = (int)filter_var($b, FILTER_SANITIZE_NUMBER_INT);
                        return $competitorNumberA - $competitorNumberB;
                    });

                    $notRunnersText = "@NotRunners: <B> Not Run: - <B>  " . implode(', ', $notRunners);
                    $eventData[] = $notRunnersText;
                }
            } else {
                if (!empty($notRunners)) {
                    // Convert "Not Runners" names to sentence case with competitor_no in brackets
                    $notRunners = array_map(function ($name, $competitor_no) {
                        return ucwords(strtolower($name)) . "($competitor_no)";
                    }, $notRunners, array_keys($notRunners));
                    $notRunnersText = "@NotRunners: <B> Not Run: - <B>  " . implode(', ', $notRunners);
                    $eventData[] = $notRunnersText;
                }
            }

            $comments1 = "@Comments1:";
            $comments2 = "";
            $dividendTypes = ['QIN' => 'QU', 'EXA' => 'EX', 'TRF' => 'TF', 'FirstFour' => 'FF'];

            // Extract competitor numbers from the $competitors array
            $competitorNumbers = [];

            foreach ($competitors as $competitor) {
                // Check if finish_position is not null before adding to $competitorNumbers
                if ($competitor->finish_position !== null) {
                    $competitorNumbers[] = $competitor->competitor_no;
                }
            }

            foreach ($event->dividends as $dividend) {

                $dividendAmount = $dividend ? $dividend->dividend_amount * 10 : '0.00';

                if ($horse1Position === 1 && $horse2Position === 2 && $horse3Position === 3 && $horse4Position === 4 && $dividend->event_type === $event->event_type) {
                    // dd('reached normal');
                    if ($dividend->dividend_type === 'QIN' && $dividend->instance === 1 && count($competitorNumbers) >= 2) {

                        if ($dividendAmount >= 2500) {
                            $comments1 .= " QU ({$competitorNumbers[0]}-{$competitorNumbers[1]}) 2,500.00\t";
                        } else {

                            $comments1 .= " QU ({$competitorNumbers[0]}-{$competitorNumbers[1]}) " . number_format($dividendAmount, 2) . "\t";
                        }
                    }

                    if ($dividend->dividend_type === 'EXA' && $dividend->instance === 1 && count($competitorNumbers) >= 2) {
                        $comments1 .= " EX ({$competitorNumbers[0]}-{$competitorNumbers[1]}) " . number_format($dividendAmount, 2) . "\t";
                    }

                    if ($dividend->dividend_type === 'TRF' && $dividend->instance === 1 && count($competitorNumbers) >= 3) {

                        if ($dividendAmount >= 20000) {
                            $comments1 .= "\nTF ({$competitorNumbers[0]}-{$competitorNumbers[1]}-{$competitorNumbers[2]}) 20,000.00\t";
                        } else {
                            $comments1 .= "\nTF ({$competitorNumbers[0]}-{$competitorNumbers[1]}-{$competitorNumbers[2]}) " . number_format($dividendAmount, 2) . "\t";
                        }
                    }

                    if ($dividend->dividend_type === 'FirstFour' && $dividend->instance === 1 && count($competitorNumbers) >= 4) {
                        $comments1 .= " FF ({$competitorNumbers[0]}-{$competitorNumbers[1]}-{$competitorNumbers[2]}-{$competitorNumbers[3]}) " . number_format($dividendAmount, 2) . "\t";
                    }
                }
                //first deadhit scenario
                if ($horse1Position === 1 && $horse2Position === 1 && $horse3Position === 3 && $horse4Position === 4 && $dividend->event_type === $event->event_type) {
                    // dd('reached a 1st deadhit');
                    if ($dividend->dividend_type === 'QIN' && $dividend->instance === 1 && count($competitorNumbers) >= 2) {

                        if ($dividendAmount >= 2500) {
                            $comments1 .= " QU ({$competitorNumbers[0]}-{$competitorNumbers[1]}) 2,500.00\t";
                        } else {

                            $comments1 .= " QU ({$competitorNumbers[0]}-{$competitorNumbers[1]}) " . number_format($dividendAmount, 2) . "\t";
                        }
                    }

                    if ($dividend->dividend_type === 'EXA' && $dividend->instance === 1 && count($competitorNumbers) >= 2) {
                        $comments1 .= " EX ({$competitorNumbers[0]}-{$competitorNumbers[1]}) " . number_format($dividendAmount, 2) . "\t";
                    }
                    if ($dividend->dividend_type === 'EXA' && $dividend->instance === 2 && count($competitorNumbers) >= 2) {
                        $comments2 .= " EX ({$competitorNumbers[1]}-{$competitorNumbers[0]}) " . number_format($dividendAmount, 2) . "\t";
                    }
                    if ($dividend->dividend_type === 'TRF' && $dividend->instance === 1 && count($competitorNumbers) >= 3) {

                        if ($dividendAmount >= 20000) {
                            $comments1 .= "\nTF ({$competitorNumbers[0]}-{$competitorNumbers[1]}-{$competitorNumbers[2]}) 20,000.00  ";
                        } else {
                            $comments1 .= "\nTF ({$competitorNumbers[0]}-{$competitorNumbers[1]}-{$competitorNumbers[2]}) " . number_format($dividendAmount, 2) . "\t";
                        }
                    }
                    if ($dividend->dividend_type === 'TRF' && $dividend->instance === 2 && count($competitorNumbers) >= 3) {

                        if ($dividendAmount >= 20000) {
                            $comments2 .= "\nTF ({$competitorNumbers[1]}-{$competitorNumbers[0]}-{$competitorNumbers[2]}) 20,000.00\t";
                        } else {
                            $comments2 .= "\nTF ({$competitorNumbers[1]}-{$competitorNumbers[0]}-{$competitorNumbers[2]}) " . number_format($dividendAmount, 2) . "\t";
                        }
                    }
                    if ($dividend->dividend_type === 'FirstFour' && $dividend->instance === 1 && count($competitorNumbers) >= 4) {
                        $comments1 .= " FF ({$competitorNumbers[0]}-{$competitorNumbers[1]}-{$competitorNumbers[2]}-{$competitorNumbers[3]}) " . number_format($dividendAmount, 2) . "\t";
                    }
                    if ($dividend->dividend_type === 'FirstFour' && $dividend->instance === 2 && count($competitorNumbers) >= 4) {
                        $comments2 .= " FF ({$competitorNumbers[1]}-{$competitorNumbers[0]}-{$competitorNumbers[2]}-{$competitorNumbers[3]}) " . number_format($dividendAmount, 2) . "\t";
                    }
                }

                //second deadhit scenario
                if ($horse1Position === 1 && $horse2Position === 2 && $horse3Position === 2 && $horse4Position === 4 && $dividend->event_type === $event->event_type) {
                    // dd('reached a 2nd deadhit');
                    if ($dividend->dividend_type === 'QIN' && $dividend->instance === 1 && count($competitorNumbers) >= 2) {
                        if ($dividendAmount >= 2500) {
                            $comments1 .= " QU ({$competitorNumbers[0]}-{$competitorNumbers[1]}) 2,500.00\t";
                        } else {

                            $comments1 .= " QU ({$competitorNumbers[0]}-{$competitorNumbers[1]}) " . number_format($dividendAmount, 2) . "\t";
                        }
                    }
                    if ($dividend->dividend_type === 'QIN' && $dividend->instance === 2 && count($competitorNumbers) >= 2) {
                        if ($dividendAmount >= 2500) {
                            $comments2 .= "QU ({$competitorNumbers[0]}-{$competitorNumbers[2]}) 2,500.00\t";
                        } else {

                            $comments2 .= "QU ({$competitorNumbers[0]}-{$competitorNumbers[2]}) " . number_format($dividendAmount, 2) . "\t";
                        }
                    }
                    if ($dividend->dividend_type === 'EXA' && $dividend->instance === 1 && count($competitorNumbers) >= 2) {
                        $comments1 .= " EX ({$competitorNumbers[0]}-{$competitorNumbers[1]}) " . number_format($dividendAmount, 2) . "\t";
                    }
                    if ($dividend->dividend_type === 'EXA' && $dividend->instance === 2 && count($competitorNumbers) >= 2) {
                        $comments2 .= " EX ({$competitorNumbers[0]}-{$competitorNumbers[2]}) " . number_format($dividendAmount, 2) . "\t";
                    }
                    if ($dividend->dividend_type === 'TRF' && $dividend->instance === 1 && count($competitorNumbers) >= 3) {
                        if ($dividendAmount >= 20000) {
                            $comments1 .= "\nTF ({$competitorNumbers[0]}-{$competitorNumbers[1]}-{$competitorNumbers[2]}) 20,000.00\t";
                        } else {
                            $comments1 .= "\nTF ({$competitorNumbers[0]}-{$competitorNumbers[1]}-{$competitorNumbers[2]}) " . number_format($dividendAmount, 2) . "\t";
                        }
                    }
                    if ($dividend->dividend_type === 'TRF' && $dividend->instance === 2 && count($competitorNumbers) >= 3) {
                        if ($dividendAmount >= 20000) {
                            $comments2 .= "\nTF ({$competitorNumbers[0]}-{$competitorNumbers[2]}-{$competitorNumbers[1]}) 20,000.00\t";
                        } else {
                            $comments2 .= "\nTF ({$competitorNumbers[0]}-{$competitorNumbers[2]}-{$competitorNumbers[1]}) " . number_format($dividendAmount, 2) . "\t";
                        }
                    }
                    if ($dividend->dividend_type === 'FirstFour' && $dividend->instance === 1 && count($competitorNumbers) >= 4) {
                        $comments1 .= " FF ({$competitorNumbers[0]}-{$competitorNumbers[1]}-{$competitorNumbers[2]}-{$competitorNumbers[3]}) " . number_format($dividendAmount, 2) . "\t";
                    }
                    if ($dividend->dividend_type === 'FirstFour' && $dividend->instance === 2 && count($competitorNumbers) >= 4) {
                        $comments2 .= " FF ({$competitorNumbers[0]}-{$competitorNumbers[2]}-{$competitorNumbers[1]}-{$competitorNumbers[3]}) " . number_format($dividendAmount, 2) . "\t";
                    }
                }
                //third deadhit scenario
                if ($horse1Position === 1 && $horse2Position === 2 && $horse3Position === 3 && $horse4Position === 3 && $dividend->event_type === $event->event_type) {
                    // dd('reached a 3rd deadhit');
                    if ($dividend->dividend_type === 'QIN' && $dividend->instance === 1 && count($competitorNumbers) >= 2) {
                        if ($dividendAmount >= 2500) {
                            $comments1 .= " QU ({$competitorNumbers[0]}-{$competitorNumbers[1]}) 2,500.00\t";
                        } else {

                            $comments1 .= " QU ({$competitorNumbers[0]}-{$competitorNumbers[1]}) " . number_format($dividendAmount, 2) . "\t";
                        }
                    }

                    if ($dividend->dividend_type === 'EXA' && $dividend->instance === 1 && count($competitorNumbers) >= 2) {
                        $comments1 .= " EX ({$competitorNumbers[0]}-{$competitorNumbers[1]}) " . number_format($dividendAmount, 2) . "\t";
                    }

                    if ($dividend->dividend_type === 'TRF' && $dividend->instance === 1 && count($competitorNumbers) >= 3) {
                        if ($dividendAmount >= 20000) {
                            $comments1 .= "\nTF ({$competitorNumbers[0]}-{$competitorNumbers[1]}-{$competitorNumbers[2]}) 20,000.00\t";
                        } else {
                            $comments1 .= "\nTF ({$competitorNumbers[0]}-{$competitorNumbers[1]}-{$competitorNumbers[2]}) " . number_format($dividendAmount, 2) . "\t";
                        }
                    }
                    if ($dividend->dividend_type === 'TRF' && $dividend->instance === 2 && count($competitorNumbers) >= 3) {
                        if ($dividendAmount >= 20000) {
                            $comments2 .= "\nTF ({$competitorNumbers[0]}-{$competitorNumbers[1]}-{$competitorNumbers[3]}) 20,000.00\t";
                        } else {
                            $comments2 .= " TF ({$competitorNumbers[0]}-{$competitorNumbers[1]}-{$competitorNumbers[3]}) " . number_format($dividendAmount, 2) . "\t";
                        }
                    }
                    if ($dividend->dividend_type === 'FirstFour' && $dividend->instance === 1 && count($competitorNumbers) >= 4) {
                        $comments1 .= " FF ({$competitorNumbers[0]}-{$competitorNumbers[1]}-{$competitorNumbers[2]}-{$competitorNumbers[3]}) " . number_format($dividendAmount, 2) . "\t";
                    }
                    if ($dividend->dividend_type === 'FirstFour' && $dividend->instance === 2 && count($competitorNumbers) >= 4) {
                        $comments2 .= " FF ({$competitorNumbers[0]}-{$competitorNumbers[1]}-{$competitorNumbers[3]}-{$competitorNumbers[2]}) " . number_format($dividendAmount, 2) . "\t";
                    }
                }
            }

            // Add the comments section to the eventData array
            $eventData[] = $comments1;
            if (!empty($comments2)) {
                $eventData[] = $comments2;
            }

            foreach ($competitors as $competitor) {
                // Check if finish_position is not null and the event_type is 'R' or 'H' before adding to $eventData
                if ($competitor->finish_position !== null && in_array($event->event_type, ['R', 'H'])) {
                    $dividendInstance = $competitor->dividend->instance;

                    $horseInfo = "@Trainer1 :";

                    if ($competitor->jockey !== null) {
                        $horseInfo .= " (" . $competitor->jockey;
                        if ($competitor->trainer !== null) {
                            $horseInfo .= ")                                (" . $competitor->trainer . ")";
                        }
                    } elseif ($competitor->trainer !== null) {
                        $horseInfo .= " (" . $competitor->trainer . ")";
                    }

                    $eventData[] = $horseInfo;
                    break;
                }
            }

            $eventData[] = "@ Line : ----------------------------------------------------";

            // Create a text representation of the data for this event
            foreach ($eventData as $value) {
                $textData .= $value . "\n";
            }
        }

        $today = now()->format('Y-m-d');
        $filename = "events_$today.txt";
        $headers = [
            'Content-Type' => 'text/plain',
            'Content-Disposition' => "attachment; filename=$filename",
        ];

        return Response::make($textData, 200, $headers);
    }



    public function show(ResultEvent $event)
    {
        $event->load('competitors', 'dividends', 'pools', 'competitors.prices');
        return view('events.show', compact('event'));
    }



    public function download(Request $request)
    {

        // Get the selected from_date from the request
        $fromDate = $request->input('from_date');

        // Parse the date string into a Carbon instance
        $fromDate = Carbon::parse($fromDate);

        $events = ResultEvent::with(['competitors' => function ($query) {
            $query->orderBy('finish_position');
        }])
            ->whereDate('utc_start_datetime', '=', $fromDate)
            ->orWhereDate('start_datetime', '=', $fromDate)
            ->where('country_code', 'AU') // Exclude records with country_code 'NZ'
            ->orderByRaw("CASE
                WHEN event_type = 'R' THEN 1
                WHEN event_type = 'H' THEN 2
                ELSE 3
                END")
            ->orderByRaw("SUBSTRING_INDEX(event_id, '-', 1), CAST(SUBSTRING_INDEX(event_id, '-', -1) AS SIGNED)")
            ->get();
        // ->sortBy('event_id', SORT_NATURAL);

        $textData = '';
        $previousCourse = null; // Variable to track the previous course
        $currentCourse = null;
        $eventCount = 0;

        foreach ($events as $event) {

            $courseInfo = "@Course: " . $event->event_name;

            $event_type = $event->event_type;

            if ($event_type == 'R') {
                $courseInfo .= ' (THR)';
            } elseif ($event_type == 'H') {
                $courseInfo .= ' (HNS)';
            } elseif ($event_type == 'G') {
                $courseInfo .= ' (GRY)';
            }

            // Check if the course has changed, and add course info if it's a new course
            if ($previousCourse !== $courseInfo) {
                $textData .= $courseInfo . "\n\n";
                $previousCourse = $courseInfo;
            }

            // Check if the course has changed
            if ($currentCourse !== $courseInfo) {
                $currentCourse = $courseInfo;
                $eventCount = 0; // Reset the event count for the new course
            }

            // Increment the event count for the current course
            $eventCount++;

            // Get all related data for the event
            $eventData = [
                "@Race : <B>" . $event->race_num . "<B>",
            ];

            $horseNumber = 1;

            // Loop through competitors associated with the event
            $competitors = $event->competitors;

            $horse1Position = null;
            $horse2Position = null;
            $horse3Position = null;
            $horse4Position = null;

            foreach ($competitors as $competitor) {

                // Check if finish_position is not null before adding to $eventData
                if ($competitor->finish_position !== null && $competitor->competitor_type === $event->event_type) {
                    $dividendWin = null;
                    $dividendPlace = null;

                    if ($horseNumber === 1) {
                        $dividendWin = $competitor->dividend()
                            ->where('dividend_type', 'WIN')
                            ->first();
                    }

                    // Check for horse positions
                    if ($horseNumber === 1) {
                        $horse1Position = $competitor->finish_position;
                    } elseif ($horseNumber === 2) {
                        $horse2Position = $competitor->finish_position;
                    } elseif ($horseNumber === 3) {
                        $horse3Position = $competitor->finish_position;
                    } elseif ($horseNumber === 4) {
                        $horse4Position = $competitor->finish_position;
                    }

                    if ($horseNumber === 4) {

                        $nameLength = strlen($competitor->name);

                        if ($nameLength >= 1 && $nameLength <= 4) {
                            $horseInfo = "@Horse" . $horseNumber . " :\t" . $competitor->finish_position . "\t" . ucwords(strtolower($competitor->name)) . "(" . $competitor->competitor_no . ")" . "\t\t\t\t\tN/F/D";
                        } elseif ($nameLength >= 5 && $nameLength <= 12) {
                            $horseInfo = "@Horse" . $horseNumber . " :\t" . $competitor->finish_position . "\t" . ucwords(strtolower($competitor->name)) . "(" . $competitor->competitor_no . ")" . "\t\t\t\tN/F/D";
                        } elseif ($nameLength >= 13 && $nameLength <= 20) {
                            $horseInfo = "@Horse" . $horseNumber . " :\t" . $competitor->finish_position . "\t" . ucwords(strtolower($competitor->name)) . "(" . $competitor->competitor_no . ")" . "\t\t\tN/F/D";
                        } elseif ($nameLength >= 21 && $nameLength <= 29) {
                            $horseInfo = "@Horse" . $horseNumber . " :\t" . $competitor->finish_position . "\t" . ucwords(strtolower($competitor->name)) . "(" . $competitor->competitor_no . ")" . "\t\tN/F/D";
                        } elseif ($nameLength >= 29 && $nameLength <= 36) {
                            $horseInfo = "@Horse" . $horseNumber . " :\t" . $competitor->finish_position . "\t" . ucwords(strtolower($competitor->name)) . "(" . $competitor->competitor_no . ")" . "\t\tN/F/D";
                        }
                    } else {
                        // Retrieve the PLC dividend based on the finish position for all other horses
                        $dividendPlace = $competitor->dividend()
                            ->where('dividend_type', 'PLC')
                            ->where('instance', $competitor->finish_position)
                            ->first();

                        $horseInfo = "";

                        // Add <B> tags for the first horse
                        if ($horseNumber === 1) {

                            $nameLength = strlen($competitor->name);

                            if ($nameLength >= 1 && $nameLength <= 5) {
                                $horseInfo .= "@Horse" . $horseNumber . " :\t" . $competitor->finish_position . "	<B>" . $competitor->name . "(" . $competitor->competitor_no . ")</B>\t\t\t";
                            } elseif ($nameLength >= 6 && $nameLength <= 13) {
                                $horseInfo .= "@Horse" . $horseNumber . " :\t" . $competitor->finish_position . "	<B>" . $competitor->name . "(" . $competitor->competitor_no . ")</B>\t\t";
                            } elseif ($nameLength >= 14 && $nameLength <= 21) {
                                $horseInfo .= "@Horse" . $horseNumber . " :\t" . $competitor->finish_position . "	<B>" . $competitor->name . "(" . $competitor->competitor_no . ")</B>\t";
                            }
                        } elseif ($horseNumber === 2) {

                            $nameLength = strlen($competitor->name);

                            if ($nameLength >= 1 && $nameLength <= 4) {
                                $horseInfo .= "@Horse" . $horseNumber . " :\t" . $competitor->finish_position . "	" . ucwords(strtolower($competitor->name)) . "(" . $competitor->competitor_no . ")\t\t\t\t";
                            } elseif ($nameLength >= 5 && $nameLength <= 12) {
                                $horseInfo .= "@Horse" . $horseNumber . " :\t" . $competitor->finish_position . "	" . ucwords(strtolower($competitor->name)) . "(" . $competitor->competitor_no . ")\t\t\t";
                            } elseif ($nameLength >= 13 && $nameLength <= 20) {
                                $horseInfo .= "@Horse" . $horseNumber . " :\t" . $competitor->finish_position . "	" . ucwords(strtolower($competitor->name)) . "(" . $competitor->competitor_no . ")\t\t";
                            } elseif ($nameLength >= 21 && $nameLength <= 29) {
                                $horseInfo .= "@Horse" . $horseNumber . " :\t" . $competitor->finish_position . "	" . ucwords(strtolower($competitor->name)) . "(" . $competitor->competitor_no . ")\t";
                            } elseif ($nameLength >= 29 && $nameLength <= 36) {
                                $horseInfo .= "@Horse" . $horseNumber . " :\t" . $competitor->finish_position . "	" . ucwords(strtolower($competitor->name)) . "(" . $competitor->competitor_no . ")\t";
                            }
                        } elseif ($horseNumber === 3) {

                            $nameLength = strlen($competitor->name);
                            if ($nameLength >= 1 && $nameLength <= 4) {
                                $horseInfo .= "@Horse" . $horseNumber . " :\t" . $competitor->finish_position . "	" . ucwords(strtolower($competitor->name)) . "(" . $competitor->competitor_no . ")\t\t\t\t";
                            } elseif ($nameLength >= 5 && $nameLength <= 12) {
                                $horseInfo .= "@Horse" . $horseNumber . " :\t" . $competitor->finish_position . "	" . ucwords(strtolower($competitor->name)) . "(" . $competitor->competitor_no . ")\t\t\t";
                            } elseif ($nameLength >= 13 && $nameLength <= 20) {
                                $horseInfo .= "@Horse" . $horseNumber . " :\t" . $competitor->finish_position . "	" . ucwords(strtolower($competitor->name)) . "(" . $competitor->competitor_no . ")\t\t";
                            } elseif ($nameLength >= 21 && $nameLength <= 29) {
                                $horseInfo .= "@Horse" . $horseNumber . " :\t" . $competitor->finish_position . "	" . ucwords(strtolower($competitor->name)) . "(" . $competitor->competitor_no . ")\t";
                            } elseif ($nameLength >= 29 && $nameLength <= 36) {
                                $horseInfo .= "@Horse" . $horseNumber . " :\t" . $competitor->finish_position . "	" . ucwords(strtolower($competitor->name)) . "(" . $competitor->competitor_no . ")\t";
                            }
                        } else {

                            $nameLength = strlen($competitor->name);

                            if ($nameLength >= 1 && $nameLength <= 4) {
                                $horseInfo .= "@Horse" . $horseNumber . " :\t" . $competitor->finish_position . "	" . ucwords(strtolower($competitor->name)) . "(" . $competitor->competitor_no . ")\t\t\t\t";
                            } elseif ($nameLength >= 5 && $nameLength <= 12) {
                                $horseInfo .= "@Horse" . $horseNumber . " :\t" . $competitor->finish_position . "	" . ucwords(strtolower($competitor->name)) . "(" . $competitor->competitor_no . ")\t\t\t";
                            } elseif ($nameLength >= 13 && $nameLength <= 20) {
                                $horseInfo .= "@Horse" . $horseNumber . " :\t" . $competitor->finish_position . "	" . ucwords(strtolower($competitor->name)) . "(" . $competitor->competitor_no . ")\t\t";
                            } elseif ($nameLength >= 21 && $nameLength <= 29) {
                                $horseInfo .= "@Horse" . $horseNumber . " :\t" . $competitor->finish_position . "	" . ucwords(strtolower($competitor->name)) . "(" . $competitor->competitor_no . ")\t";
                            } elseif ($nameLength >= 29 && $nameLength <= 36) {
                                $horseInfo .= "@Horse" . $horseNumber . " :\t" . $competitor->finish_position . "	" . ucwords(strtolower($competitor->name)) . "(" . $competitor->competitor_no . ")\t";
                            }
                        }

                        if ($dividendWin && $horseNumber === 1) {
                            $horseInfo .= "" . number_format($dividendWin->dividend_amount * 10, 2);
                        }
                        if ($dividendPlace) {
                            $horseInfo .= "	" . number_format($dividendPlace->dividend_amount * 10, 2);
                        } else {
                            $nameLength = strlen($competitor->name);

                            if ($horseNumber > 4) {
                                // Display N/F/D based on $nameLength
                                if ($nameLength >= 1 && $nameLength <= 4) {
                                    $horseInfo .= "\t\t\t\tN/F/D";
                                } elseif ($nameLength >= 5 && $nameLength <= 12) {
                                    $horseInfo .= "\tN/F/D";
                                } elseif ($nameLength >= 13 && $nameLength <= 20) {
                                    $horseInfo .= "\tN/F/D";
                                } elseif ($nameLength >= 21 && $nameLength <= 29) {
                                    $horseInfo .= "\tN/F/D";
                                } elseif ($nameLength >= 29 && $nameLength <= 36) {
                                    $horseInfo .= "\tN/F/D";
                                }
                            } else {
                                // Display N/T/D for other horses
                                if ($nameLength >= 1 && $nameLength <= 4) {
                                    $horseInfo .= "\t\t\t\tN/T/D";
                                } elseif ($nameLength >= 5 && $nameLength <= 12) {
                                    $horseInfo .= "\tN/T/D";
                                } elseif ($nameLength >= 13 && $nameLength <= 20) {
                                    $horseInfo .= "\tN/T/D";
                                } elseif ($nameLength >= 21 && $nameLength <= 29) {
                                    $horseInfo .= "\tN/T/D";
                                } elseif ($nameLength >= 29 && $nameLength <= 36) {
                                    $horseInfo .= "\tN/T/D";
                                }
                            }
                        }
                    }

                    $runnerNumber = $competitor->runner->runner_numbers;

                    $eventData[] = $horseInfo;
                    $horseNumber++;
                }
            }

            $notRunners = $competitors->where('status', 'NR')->pluck('name', 'competitor_no')->toArray();

            if ($event_type == 'G') {
                if (empty($notRunners)) {
                    // $eventData[] = "@NotRunners: <B> Not Run: - <B>";
                    $vCompetitors = $competitors->where('status', 'V')->pluck('competitor_no')->toArray();
                    if (!empty($vCompetitors)) {
                        // Combine the competitor numbers with "NR" and create the event data entry
                        $formattedCompetitors = array_map(function ($competitor_no) {
                            return "...Vacant($competitor_no)";
                        }, $vCompetitors);

                        $eventData[] = "@NotRunners: <B> Not Run: - <B>" . implode(', ', $formattedCompetitors);
                    }
                } else {

                    $notRunners = array_map(function ($name, $competitor_no) {
                        return ucwords(strtolower($name)) . "($competitor_no)";
                    }, $notRunners, array_keys($notRunners)); // Pass keys (competitor_no) as the second argument

                    // Retrieve competitors with status 'V'
                    $vCompetitors = $competitors->where('status', 'V')->pluck('competitor_no')->toArray();
                    if (!empty($vCompetitors)) {
                        // Combine the competitor numbers with "V" and create the event data entry
                        $formattedVCompetitors = array_map(function ($competitor_no) {
                            return "...Vacant($competitor_no)";
                        }, $vCompetitors);

                        // Merge the two arrays and create the final message
                        $notRunners = array_merge($notRunners, $formattedVCompetitors);
                    }

                    // Sort $notRunners array based on competitor number
                    usort($notRunners, function ($a, $b) {
                        $competitorNumberA = (int)filter_var($a, FILTER_SANITIZE_NUMBER_INT);
                        $competitorNumberB = (int)filter_var($b, FILTER_SANITIZE_NUMBER_INT);
                        return $competitorNumberA - $competitorNumberB;
                    });

                    $notRunnersText = "@NotRunners: <B> Not Run: - <B>  " . implode(', ', $notRunners);
                    $eventData[] = $notRunnersText;
                }
            } else {
                if (!empty($notRunners)) {
                    // Convert "Not Runners" names to sentence case with competitor_no in brackets
                    $notRunners = array_map(function ($name, $competitor_no) {
                        return ucwords(strtolower($name)) . "($competitor_no)";
                    }, $notRunners, array_keys($notRunners));
                    $notRunnersText = "@NotRunners: <B> Not Run: - <B>  " . implode(', ', $notRunners);
                    $eventData[] = $notRunnersText;
                }
            }

            $comments1 = "@Comments1:";
            $comments2 = "";
            $dividendTypes = ['QIN' => 'QU', 'EXA' => 'EX', 'TRF' => 'TF', 'FirstFour' => 'FF'];

            // Extract competitor numbers from the $competitors array
            $competitorNumbers = [];

            foreach ($competitors as $competitor) {
                // Check if finish_position is not null before adding to $competitorNumbers
                if ($competitor->finish_position !== null) {
                    $competitorNumbers[] = $competitor->competitor_no;
                }
            }

            foreach ($event->dividends as $dividend) {

                $dividendAmount = $dividend ? $dividend->dividend_amount * 10 : '0.00';

                if ($horse1Position === 1 && $horse2Position === 2 && $horse3Position === 3 && $horse4Position === 4 && $dividend->event_type === $event->event_type) {
                    // dd('reached normal');
                    if ($dividend->dividend_type === 'QIN' && $dividend->instance === 1 && count($competitorNumbers) >= 2) {

                        if ($dividendAmount >= 2500) {
                            $comments1 .= " QU ({$competitorNumbers[0]}-{$competitorNumbers[1]}) 2,500.00\t";
                        } else {

                            $comments1 .= " QU ({$competitorNumbers[0]}-{$competitorNumbers[1]}) " . number_format($dividendAmount, 2) . "\t";
                        }
                    }

                    if ($dividend->dividend_type === 'EXA' && $dividend->instance === 1 && count($competitorNumbers) >= 2) {
                        $comments1 .= " EX ({$competitorNumbers[0]}-{$competitorNumbers[1]}) " . number_format($dividendAmount, 2) . "\t";
                    }

                    if ($dividend->dividend_type === 'TRF' && $dividend->instance === 1 && count($competitorNumbers) >= 3) {

                        if ($dividendAmount >= 20000) {
                            $comments1 .= "\nTF ({$competitorNumbers[0]}-{$competitorNumbers[1]}-{$competitorNumbers[2]}) 20,000.00\t";
                        } else {
                            $comments1 .= "\nTF ({$competitorNumbers[0]}-{$competitorNumbers[1]}-{$competitorNumbers[2]}) " . number_format($dividendAmount, 2) . "\t";
                        }
                    }

                    if ($dividend->dividend_type === 'FirstFour' && $dividend->instance === 1 && count($competitorNumbers) >= 4) {
                        $comments1 .= " FF ({$competitorNumbers[0]}-{$competitorNumbers[1]}-{$competitorNumbers[2]}-{$competitorNumbers[3]}) " . number_format($dividendAmount, 2) . "\t";
                    }
                }
                //first deadhit scenario
                if ($horse1Position === 1 && $horse2Position === 1 && $horse3Position === 3 && $horse4Position === 4 && $dividend->event_type === $event->event_type) {
                    // dd('reached a 1st deadhit');
                    if ($dividend->dividend_type === 'QIN' && $dividend->instance === 1 && count($competitorNumbers) >= 2) {

                        if ($dividendAmount >= 2500) {
                            $comments1 .= " QU ({$competitorNumbers[0]}-{$competitorNumbers[1]}) 2,500.00\t";
                        } else {

                            $comments1 .= " QU ({$competitorNumbers[0]}-{$competitorNumbers[1]}) " . number_format($dividendAmount, 2) . "\t";
                        }
                    }

                    if ($dividend->dividend_type === 'EXA' && $dividend->instance === 1 && count($competitorNumbers) >= 2) {
                        $comments1 .= " EX ({$competitorNumbers[0]}-{$competitorNumbers[1]}) " . number_format($dividendAmount, 2) . "\t";
                    }
                    if ($dividend->dividend_type === 'EXA' && $dividend->instance === 2 && count($competitorNumbers) >= 2) {
                        $comments2 .= " EX ({$competitorNumbers[1]}-{$competitorNumbers[0]}) " . number_format($dividendAmount, 2) . "\t";
                    }
                    if ($dividend->dividend_type === 'TRF' && $dividend->instance === 1 && count($competitorNumbers) >= 3) {

                        if ($dividendAmount >= 20000) {
                            $comments1 .= "\nTF ({$competitorNumbers[0]}-{$competitorNumbers[1]}-{$competitorNumbers[2]}) 20,000.00  ";
                        } else {
                            $comments1 .= "\nTF ({$competitorNumbers[0]}-{$competitorNumbers[1]}-{$competitorNumbers[2]}) " . number_format($dividendAmount, 2) . "\t";
                        }
                    }
                    if ($dividend->dividend_type === 'TRF' && $dividend->instance === 2 && count($competitorNumbers) >= 3) {

                        if ($dividendAmount >= 20000) {
                            $comments2 .= "\nTF ({$competitorNumbers[1]}-{$competitorNumbers[0]}-{$competitorNumbers[2]}) 20,000.00\t";
                        } else {
                            $comments2 .= "\nTF ({$competitorNumbers[1]}-{$competitorNumbers[0]}-{$competitorNumbers[2]}) " . number_format($dividendAmount, 2) . "\t";
                        }
                    }
                    if ($dividend->dividend_type === 'FirstFour' && $dividend->instance === 1 && count($competitorNumbers) >= 4) {
                        $comments1 .= " FF ({$competitorNumbers[0]}-{$competitorNumbers[1]}-{$competitorNumbers[2]}-{$competitorNumbers[3]}) " . number_format($dividendAmount, 2) . "\t";
                    }
                    if ($dividend->dividend_type === 'FirstFour' && $dividend->instance === 2 && count($competitorNumbers) >= 4) {
                        $comments2 .= " FF ({$competitorNumbers[1]}-{$competitorNumbers[0]}-{$competitorNumbers[2]}-{$competitorNumbers[3]}) " . number_format($dividendAmount, 2) . "\t";
                    }
                }

                //second deadhit scenario
                if ($horse1Position === 1 && $horse2Position === 2 && $horse3Position === 2 && $horse4Position === 4 && $dividend->event_type === $event->event_type) {
                    // dd('reached a 2nd deadhit');
                    if ($dividend->dividend_type === 'QIN' && $dividend->instance === 1 && count($competitorNumbers) >= 2) {
                        if ($dividendAmount >= 2500) {
                            $comments1 .= " QU ({$competitorNumbers[0]}-{$competitorNumbers[1]}) 2,500.00\t";
                        } else {

                            $comments1 .= " QU ({$competitorNumbers[0]}-{$competitorNumbers[1]}) " . number_format($dividendAmount, 2) . "\t";
                        }
                    }
                    if ($dividend->dividend_type === 'QIN' && $dividend->instance === 2 && count($competitorNumbers) >= 2) {
                        if ($dividendAmount >= 2500) {
                            $comments2 .= "QU ({$competitorNumbers[0]}-{$competitorNumbers[2]}) 2,500.00\t";
                        } else {

                            $comments2 .= "QU ({$competitorNumbers[0]}-{$competitorNumbers[2]}) " . number_format($dividendAmount, 2) . "\t";
                        }
                    }
                    if ($dividend->dividend_type === 'EXA' && $dividend->instance === 1 && count($competitorNumbers) >= 2) {
                        $comments1 .= " EX ({$competitorNumbers[0]}-{$competitorNumbers[1]}) " . number_format($dividendAmount, 2) . "\t";
                    }
                    if ($dividend->dividend_type === 'EXA' && $dividend->instance === 2 && count($competitorNumbers) >= 2) {
                        $comments2 .= " EX ({$competitorNumbers[0]}-{$competitorNumbers[2]}) " . number_format($dividendAmount, 2) . "\t";
                    }
                    if ($dividend->dividend_type === 'TRF' && $dividend->instance === 1 && count($competitorNumbers) >= 3) {
                        if ($dividendAmount >= 20000) {
                            $comments1 .= "\nTF ({$competitorNumbers[0]}-{$competitorNumbers[1]}-{$competitorNumbers[2]}) 20,000.00\t";
                        } else {
                            $comments1 .= "\nTF ({$competitorNumbers[0]}-{$competitorNumbers[1]}-{$competitorNumbers[2]}) " . number_format($dividendAmount, 2) . "\t";
                        }
                    }
                    if ($dividend->dividend_type === 'TRF' && $dividend->instance === 2 && count($competitorNumbers) >= 3) {
                        if ($dividendAmount >= 20000) {
                            $comments2 .= "\nTF ({$competitorNumbers[0]}-{$competitorNumbers[2]}-{$competitorNumbers[1]}) 20,000.00\t";
                        } else {
                            $comments2 .= "\nTF ({$competitorNumbers[0]}-{$competitorNumbers[2]}-{$competitorNumbers[1]}) " . number_format($dividendAmount, 2) . "\t";
                        }
                    }
                    if ($dividend->dividend_type === 'FirstFour' && $dividend->instance === 1 && count($competitorNumbers) >= 4) {
                        $comments1 .= " FF ({$competitorNumbers[0]}-{$competitorNumbers[1]}-{$competitorNumbers[2]}-{$competitorNumbers[3]}) " . number_format($dividendAmount, 2) . "\t";
                    }
                    if ($dividend->dividend_type === 'FirstFour' && $dividend->instance === 2 && count($competitorNumbers) >= 4) {
                        $comments2 .= " FF ({$competitorNumbers[0]}-{$competitorNumbers[2]}-{$competitorNumbers[1]}-{$competitorNumbers[3]}) " . number_format($dividendAmount, 2) . "\t";
                    }
                }
                //third deadhit scenario
                if ($horse1Position === 1 && $horse2Position === 2 && $horse3Position === 3 && $horse4Position === 3 && $dividend->event_type === $event->event_type) {
                    // dd('reached a 3rd deadhit');
                    if ($dividend->dividend_type === 'QIN' && $dividend->instance === 1 && count($competitorNumbers) >= 2) {
                        if ($dividendAmount >= 2500) {
                            $comments1 .= " QU ({$competitorNumbers[0]}-{$competitorNumbers[1]}) 2,500.00\t";
                        } else {

                            $comments1 .= " QU ({$competitorNumbers[0]}-{$competitorNumbers[1]}) " . number_format($dividendAmount, 2) . "\t";
                        }
                    }

                    if ($dividend->dividend_type === 'EXA' && $dividend->instance === 1 && count($competitorNumbers) >= 2) {
                        $comments1 .= " EX ({$competitorNumbers[0]}-{$competitorNumbers[1]}) " . number_format($dividendAmount, 2) . "\t";
                    }

                    if ($dividend->dividend_type === 'TRF' && $dividend->instance === 1 && count($competitorNumbers) >= 3) {
                        if ($dividendAmount >= 20000) {
                            $comments1 .= "\nTF ({$competitorNumbers[0]}-{$competitorNumbers[1]}-{$competitorNumbers[2]}) 20,000.00\t";
                        } else {
                            $comments1 .= "\nTF ({$competitorNumbers[0]}-{$competitorNumbers[1]}-{$competitorNumbers[2]}) " . number_format($dividendAmount, 2) . "\t";
                        }
                    }
                    if ($dividend->dividend_type === 'TRF' && $dividend->instance === 2 && count($competitorNumbers) >= 3) {
                        if ($dividendAmount >= 20000) {
                            $comments2 .= "\nTF ({$competitorNumbers[0]}-{$competitorNumbers[1]}-{$competitorNumbers[3]}) 20,000.00\t";
                        } else {
                            $comments2 .= " TF ({$competitorNumbers[0]}-{$competitorNumbers[1]}-{$competitorNumbers[3]}) " . number_format($dividendAmount, 2) . "\t";
                        }
                    }
                    if ($dividend->dividend_type === 'FirstFour' && $dividend->instance === 1 && count($competitorNumbers) >= 4) {
                        $comments1 .= " FF ({$competitorNumbers[0]}-{$competitorNumbers[1]}-{$competitorNumbers[2]}-{$competitorNumbers[3]}) " . number_format($dividendAmount, 2) . "\t";
                    }
                    if ($dividend->dividend_type === 'FirstFour' && $dividend->instance === 2 && count($competitorNumbers) >= 4) {
                        $comments2 .= " FF ({$competitorNumbers[0]}-{$competitorNumbers[1]}-{$competitorNumbers[3]}-{$competitorNumbers[2]}) " . number_format($dividendAmount, 2) . "\t";
                    }
                }
            }

            // Add the comments section to the eventData array
            $eventData[] = $comments1;
            if (!empty($comments2)) {
                $eventData[] = $comments2;
            }

            foreach ($competitors as $competitor) {
                // Check if finish_position is not null and the event_type is 'R' or 'H' before adding to $eventData
                if ($competitor->finish_position !== null && in_array($event->event_type, ['R', 'H'])) {
                    $dividendInstance = $competitor->dividend->instance;

                    $horseInfo = "@Trainer1 :";

                    if ($competitor->jockey !== null) {
                        $horseInfo .= " (" . $competitor->jockey;
                        if ($competitor->trainer !== null) {
                            $horseInfo .= ")                                (" . $competitor->trainer . ")";
                        }
                    } elseif ($competitor->trainer !== null) {
                        $horseInfo .= " (" . $competitor->trainer . ")";
                    }

                    $eventData[] = $horseInfo;
                    break;
                }
            }

            $eventData[] = "@ Line : ----------------------------------------------------";

            // Create a text representation of the data for this event
            foreach ($eventData as $value) {
                $textData .= $value . "\n";
            }
        }

        $today = now()->format('Y-m-d');
        $filename = "events_$today.txt";
        $headers = [
            'Content-Type' => 'text/plain',
            'Content-Disposition' => "attachment; filename=$filename",
        ];

        // Return a response with the text data
        return response($textData, 200, $headers);
    }


    // UK text download page
    public function englishTextDownload(Request $request)
    {
        return view('english-text-download');
    }

    // loads all the events and meetings into a view
    public function loadEngResult(Request $request)
    {
        try {

            // grab the date from the front
            $targetDate = $request->input('targetDate');

            // set it to an object
            $date = new DateTime($targetDate);

            // Grab the meetings for the date and the country codes. VR is virtual race
            $countryCodes = ['FR', 'IE', 'DE', 'ZA', 'UK', 'IN', 'AE', 'VR'];

            // Each grouping alphabetically ordered
            $meetings = EngResultMeeting::with(['events' => function ($query) {
                $query->orderBy('num');
            }])
                ->whereDate('date', $date)
                ->whereIn('country', $countryCodes)
                ->orderByRaw("CASE
                         WHEN country != 'VR' AND category = 'HR' THEN 1
                         WHEN country != 'VR' AND category = 'DG' THEN 2
                         WHEN country = 'VR' AND category = 'DG' THEN 3
                         WHEN country = 'VR' AND category = 'HR' THEN 4
                         ELSE 5
                     END")
                ->orderBy('name')
                ->orderByRaw("FIELD(country, 'FR', 'IE', 'DE', 'ZA', 'UK', 'IN', 'AE', 'VR')")
                ->get();

            // Dismiss Ireland Dog Races
            $meetings = $meetings->reject(function ($meeting) {
                return $meeting->country === 'IE' && $meeting->category === 'DG';
            });

            return view('english-result-listing')->with(compact('targetDate', 'meetings'))->render();
        } catch (Exception $e) {
            dd($e);
        }
    }


    // UK text download
    public function downloadEng(Request $request)
    {
        // max execution time set for 30 minutes
        ini_set('max_execution_time', 1800);

        try {

            // grab service classes
            $engMeetingService = new EngMeetingService();
            $engEventService = new EngEventService();
            $engSelectionService = new EngSelectionService();

            // grab the date from the front form
            $targetDate = $request->input('target-date');

            // set it to an object
            $date = new DateTime($targetDate);

            // grab the event and meeting ids dropped from the front
            $droppedEvents = array_map('intval', json_decode($request->input('dropped-events')));
            $droppedMeetings = array_map('intval', json_decode($request->input('dropped-meetings')));

            // grab the meetings for the date and the country codes. VR is virtual race
            $countryCodes = ['FR', 'IE', 'DE', 'ZA', 'UK', 'IN', 'AE', 'VR'];

            // Each grouping alphabetically ordered
            $meetings = EngResultMeeting::with(['events' => function ($query) use ($droppedEvents) {
                $query->whereNotIn('id', $droppedEvents)->orderBy('num');
            }])
                ->whereNotIn('id', $droppedMeetings)
                ->whereDate('date', $date)
                ->whereIn('country', $countryCodes)
                ->orderByRaw("CASE
                        WHEN country != 'VR' AND category = 'HR' THEN 1
                        WHEN country != 'VR' AND category = 'DG' THEN 2
                        WHEN country = 'VR' AND category = 'DG' THEN 3
                        WHEN country = 'VR' AND category = 'HR' THEN 4
                        ELSE 5
                    END")
                ->orderBy('name')
                ->orderByRaw("FIELD(country, 'FR', 'IE', 'DE', 'ZA', 'UK', 'IN', 'AE', 'VR')")
                ->get();

            // Dismiss Ireland Dog Races
            $meetings = $meetings->reject(function ($meeting) {
                return $meeting->country === 'IE' && $meeting->category === 'DG';
            });

            // final text variable (version data for quark express)
            $textData = ' <v9.00> <e0>';

            // iterate through each meeting
            foreach ($meetings as $meeting) {

                $courseInfo =  trim("@Course : " . strtoupper($meeting->name));
                $courseInfo .= $meeting->category == 'DG' ? "\t\tGREYHOUNDS" : '';

                // events from the relations
                $events = $meeting->getRelations()['events'];

                // add course info into
                $textData .= $courseInfo . "\n\n";

                foreach ($events as $event) {

                    // VR Horse races will retain the 24H format
                    $eventFormattedTime = ($meeting->category == 'HR' && $meeting->country == 'VR') ? $event->time->format('H.i') : $event->time->format('g.i');

                    // will maintain, race details, selections, specialBets etc.
                    $eventData = [
                        "@Race : <B>" . $event->num . "<B>" . " <B>(" . $eventFormattedTime  . ")<B>",
                    ];

                    $placingCompetitors = $engEventService->getPlacingRunnersForEvent($event);
                    $nonRunners = $engEventService->getNonRunnersForEvent($event);
                    $allbets = $engEventService->getAllbetsForEvent($event);
                    $specialBets = $engEventService->getSpecialRacebetsForEvent($event);
                    $trainers = $engEventService->getWinnerTrainerDataForEvent($event);
                    $deadheat = $engEventService->getDeadHeat($event);

                    // Adding competitors
                    // ------------------
                    foreach ($placingCompetitors as $competitor) {

                        // competitor details
                        $horseTextTagNo = $competitor->finish_position;
                        $odds = $engSelectionService->getOddsForSelection($competitor, 'PRINT');
                        $runnerNumber = $competitor->num;
                        $name = str_replace("'", '?', $competitor->name);
                        $finishPosition = $competitor->finish_position;

                        // when the allbets is not empty, pass the deduction value to the getDividend function
                        $dividends = empty($allbets) ? $engSelectionService->getDividendForSelection($competitor, 0, $deadheat) : $engSelectionService->getDividendForSelection($competitor, $allbets->deduction, $deadheat);


                        if ($event->runners <= 4) {
                            $horseInfo = $competitor->finish_position == 1 ?
                                "@Horse{$horseTextTagNo} : \t<B>{$odds}<B>\t<B>({$runnerNumber}) " . strtoupper(str_pad("{$name}<B>", 19)) . "\t<B>" . str_pad($dividends['WIN'], 8, " ", STR_PAD_LEFT) . "<B>(W/O)\t<B>" . str_pad("", 8, " ", STR_PAD_LEFT)  . "<B>\t{$finishPosition}"
                                : "@Horse{$horseTextTagNo} : \t\t" . str_pad(ucwords(strtolower("({$runnerNumber}) " . $name)), 20) . "\t" . str_repeat(' ', 8)  . "\t" . str_repeat(' ', 8) . "\t{$finishPosition}";
                        } else if ($event->runners <= 7 && $engEventService->isTricastBetAvailable($event)) {

                            // hiding odds & dividends for 3rd place
                            if ($competitor->finish_position != 3) {
                                $horseInfo = $competitor->finish_position == 1 ?
                                    "@Horse{$horseTextTagNo} : \t<B>{$odds}<B>\t<B>({$runnerNumber}) " . strtoupper(str_pad("{$name}<B>", 19)) . "\t<B>" . str_pad($dividends['WIN'], 8, " ", STR_PAD_LEFT) . "<B>\t<B>" . str_pad($dividends['PLACE'], 8, " ", STR_PAD_LEFT) . "<B>\t{$finishPosition}"
                                    : "@Horse{$horseTextTagNo} : \t$odds\t" . str_pad(ucwords(strtolower("({$runnerNumber}) " . $name)), 20) . "\t"  . str_repeat(' ', 8)  . "\t" . str_repeat(' ', 3) . "{$dividends['PLACE']}\t{$finishPosition}";
                            } else {
                                $horseInfo = "@Horse{$horseTextTagNo} : \t\t" . str_pad(ucwords(strtolower("({$runnerNumber}) " . $name)), 20) . "\t" . str_repeat(' ', 8)  . "\t" . str_repeat(' ', 8) .  "\t{$finishPosition}";
                            }
                        } else {
                            $horseInfo = $competitor->finish_position == 1 ?
                                "@Horse{$horseTextTagNo} : \t<B>{$odds}<B>\t<B>({$runnerNumber}) " . strtoupper(str_pad("{$name}<B>", 19)) . "\t<B>" . str_pad($dividends['WIN'], 8, " ", STR_PAD_LEFT) . "<B>\t<B>" . str_pad($dividends['PLACE'], 8, " ", STR_PAD_LEFT) . "<B>\t{$finishPosition}"
                                : "@Horse{$horseTextTagNo} : \t$odds\t" . str_pad(ucwords(strtolower("({$runnerNumber}) " . $name)), 20) . "\t" . str_repeat(' ', 8)  . "\t" . str_repeat(' ', 3)  . "{$dividends['PLACE']}\t{$finishPosition}";
                        }

                        // append horse info here
                        $eventData[] = $horseInfo;
                    }

                    // Adding the dead heat position (if there is any)
                    // -----------------------------------------------

                    if (!empty($deadheat)) {

                        $places = ['first', 'second', 'third', 'fourth'];
                        $i = $deadheat - 1;

                        $eventData[] = " <B> D. heat for {$places[$i]} place <B>";
                    }

                    // Adding non runners (if there is any)
                    // -----------------------------------
                    if (!empty($nonRunners)) {

                        $nonRunnerInfo = "@NotRunners : <B> Not Run:- <B>";

                        foreach ($nonRunners as $nonRunner) {

                            // non runner details
                            $runnerNumber = $nonRunner->num;
                            $name = str_replace("'", '?', $nonRunner->name);

                            // append current non runner to the event info
                            $nonRunnerInfo .= " ({$runnerNumber}) {$name},";
                        }

                        // append non runner info here
                        $eventData[] = rtrim($nonRunnerInfo, ',');
                    }


                    // Adding allbets off percentage (if there is any)
                    // -----------------------------------------------
                    if (!empty($allbets)) {

                        $offPercentage = number_format($allbets->deduction, 2, '.', '');
                        $allbetsOffInfo = "@OFF: <B> {$offPercentage}% off <B>";

                        // append non runner info here
                        $eventData[] = $allbetsOffInfo;
                    }

                    // Adding special bets (if there is any too lol)
                    // ---------------------------------------------
                    if (!empty($specialBets)) {

                        // to keep track of forecasts and tricasts between each set to identify repeating ones
                        $uniqueForecast = 0;
                        $uniqueTricast = 0;

                        foreach ($specialBets as $key => $specialBetSet) {

                            $specialBetInfo = "@Comments" . $key + 1 . " : ";

                            foreach ($specialBetSet as $specialBet) {
                                $amount = ($specialBet->amount * 10) >= 20000 ? '20000.00' : number_format($specialBet->amount * 10, 2, '.', '');

                                // if there's a deadheat get the runner number sequence
                                $deadheatSequence = $deadheat ? $engEventService->getDeadHeatRunnerNumberSequence($event, $specialBet, $deadheat)  : '';

                                switch ($specialBet->bet_type) {
                                    case 'F':
                                    case 'K':
                                    case 'J':

                                        // CSF comes with dist
                                        // BF just seems to be CSF for dogs

                                        // assign if forecast amount is unique, else make it false
                                        $uniqueForecast = $uniqueForecast != $amount ? $amount : false;

                                        if ($uniqueForecast) {
                                            if (!$deadheatSequence) {
                                                $specialBetInfo .=  $event->event_type == 'DG' ?
                                                    "BF  {$amount}\t \t"
                                                    : "CSF  {$amount}\t" . (!$event->is_virtual ? "<B>Dist :-<B>\t" : "\t");
                                            } else {
                                                $specialBetInfo .=  $event->event_type == 'DG' ?
                                                    "BF {$deadheatSequence} {$amount}\t"
                                                    : "CSF {$deadheatSequence} {$amount}\t" . (!$event->is_virtual ? "<B>Dist :-<B>\t" : "\t");
                                            }
                                        }


                                        break;
                                    case 'T':
                                    case 'M':
                                    case 'L':

                                        // TC has bonus attached to it
                                        // But the bonus is not included for dog events

                                        // assign if tricast amount is unique, else make it false
                                        $uniqueTricast = $uniqueTricast != $amount ? $amount : false;

                                        if ($uniqueTricast) {
                                            if (!$deadheatSequence) {
                                                $specialBetInfo .=  $event->event_type == 'DG' ?
                                                    "TC  {$amount}"
                                                    : "TC  {$amount}+{$engEventService->getTricastBonus($event)}% Bonus";
                                            } else {
                                                $specialBetInfo .=  $event->event_type == 'DG' ?
                                                    "TC {$deadheatSequence} {$amount}"
                                                    : "TC {$deadheatSequence} {$amount}+{$engEventService->getTricastBonus($event)}% Bonus";
                                            }
                                        }

                                        break;
                                    default:
                                        break;
                                }
                            }

                            $eventData[] = trim($specialBetInfo);
                        }
                    }

                    // Adding trainer data (if the event is non dog and non virtual)
                    // -----------------------------------------------

                    if ($event->event_type != 'DG' && !$event->is_virtual) {
                        if (!empty($trainers)) {

                            $trainerInfo = "@Trainer1 : ";

                            // foreach ($trainers as $trainer) {
                            //     $totalStrLength = preg_match('/\d/', $trainer) ? 20 : 20;
                            //     $trainerInfo .= !empty($trainer) ? str_pad("(" . $trainer . ")", $totalStrLength) : "";
                            // }

                            foreach ($trainers as $trainer) {
                                $trainerInfo .= !empty($trainer) ? "({$trainer})\t\t" : "";
                            }

                            // append special bet info here
                            $eventData[] = trim($trainerInfo);
                        }
                    }

                    // Add the last separator before the next event
                    $eventData[] = "@ Line : ----------------------------------------------------";

                    // Add all the collected event data into this one
                    // Competitors
                    // Non runners
                    // Racebets
                    // Dividends
                    // Seperator
                    foreach ($eventData as $data) {
                        $textData .= $data . "\n";
                    }
                }
            }

            // Downlaod part goes here !
            $filename = "eng_events_{$date->format('Y-m-d')}.txt";
            $headers = [
                'Content-Type' => 'text/plain',
                'Content-Disposition' => "attachment; filename=$filename",
            ];

            // based on the headers, this will trigger a download
            return response($textData, 200, $headers);
        } catch (Exception $e) {
            dd($e);
        }
    }
}
