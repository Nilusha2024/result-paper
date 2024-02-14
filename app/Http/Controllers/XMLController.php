<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use DateTime;
use NumberFormatter;

class XMLController extends Controller
{
    public function index()
    {
        $xmlFiles = Storage::files('xml/xml');
        return view('xml.index', compact('xmlFiles'));
    }
    //Kill Form
    public function download($filename)
    {
        $xmlContent = Storage::get('xml/xml/' . $filename);

        if ($xmlContent === false) {
            return response('Error: Unable to read XML file', 500);
        }

        $xml = simplexml_load_string($xmlContent);

        if ($xml === false) {
            return response('Error: Invalid XML format', 500);
        }

        // Extract track name and date from the XML
        $trackName = (string)$xml->track['name'];
        $date = (string)$xml->date;

        // Include track name and date in the output
        $output = "@Cour:{$trackName} {$date}\n@Going: Going: Good";



        foreach ($xml->races->race as $race) {
            $output .= "\n@Space:";
            foreach ($race->classes->class as $class) {
                foreach ($race->start_time as $start_time) {
                    // Convert the start time to a DateTime object
                    $startTimeObj = new DateTime($start_time);

                    // Format the start time in 24-hour format
                    $formattedStartTime = $startTimeObj->format('H:i');

                    //
                    $startTimeObj = DateTime::createFromFormat('H:i', $formattedStartTime);

                    // Add 6 hours and 30 minutes
                    $startTimeObj->modify('+6 hours 30 minutes');

                    // Get the modified time in the same format
                    $modifiedStartTime = $startTimeObj->format('h:i');
                    //

                    // Get the total prize value
                    $totalValue = number_format((int)$race->prizes->prize[10]['value']);

                    // $output .= "\n@Race:{$race->track->attributes()['track_3char_abbrev']}/{$race->attributes()['number']}<&te><&tb(45,28,a)*C>$formattedStartTime ($modifiedStartTime) <&te>{$race->attributes()['name']} ($class) <B>({$race->restrictions->attributes()['age']}) \${$totalValue}";

                    //
                    $ageAttribute = $race->restrictions->attributes()['age'];
                    $ageOutput = '';

                    if (strpos($ageAttribute, '+') !== false) {
                        // If the age attribute contains '+', extract the numeric part
                        $numericAge = (int) filter_var($ageAttribute, FILTER_SANITIZE_NUMBER_INT);

                        // If there is a numeric part, include it in the output with 'Up'
                        if ($numericAge > 0) {
                            $ageOutput = "{$numericAge}yo Up";
                        } else {
                            // If no numeric part, default to 'Up'
                            $ageOutput = 'Up';
                        }
                    } else {
                        // Otherwise, output the age as is  

                        $ageOutput = "{$ageAttribute} Only";
                    }
                    //distence 

                    $distanceInMetres  = $race->distance->attributes()['metres'];
                    $distance = $this->metersToMiles($distanceInMetres);
                    $output .= "\n@Race:{$race->track->attributes()['track_3char_abbrev']}/{$race->attributes()['number']}<&te><&tb(45,28,a)*C>$formattedStartTime ($modifiedStartTime) <&te>{$race->attributes()['name']} ($class) <B>({$ageOutput}) {$distance} \${$totalValue}";

                    //
                }
            }



            $recordId = 1;

            // foreach ($race->horses->horse as $horse) {

            //     if (isset($horse->forms->form[1])) {
            //         $secondFormFinishPosition = number_format((int)$horse->forms->form[1]->finish_position);
            //         if (isset($horse->forms->form[2])) {
            //             $therdFormFinishPosition = number_format((int)$horse->forms->form[2]->finish_position);

            //             $output .= "\n@HossD:\t{$therdFormFinishPosition}\t{$secondFormFinishPosition}\t{$horse->forms->form->finish_position}\t($recordId)\t\t{$horse->attributes()['name']} ({$horse->attributes()['country']}) <B>\t{$horse->weight_carried}-{$horse->barrier} <B>{$horse->jockey->attributes()['name']} {$horse->barrier}\t{$horse->finish_position}";
            //             $output .= "\n@Own:\t{$horse->barrier}\t{$horse->barrier}\t{$horse->barrier}\t\t{$horse->owners}";
            //             $output .= "\n@NewsComm:\t{$horse->finish_position}\t{$horse->barrier}\t{$horse->finish_position}\t<I>{$horse->form_comments}";
            //         }else {
            //             $therdFormFinishPosition = "--";
            //         }
            //     }else {
            //         $secondFormFinishPosition = "--";
            //     }

            //     $recordId = $recordId + 1;
            // }

            foreach ($race->horses->horse as $horse) {
                $secondFormFinishPosition = "--";
                $therdFormFinishPosition = "--";

                if (isset($horse->forms->form[1])) {
                    $secondFormFinishPosition = number_format((int)$horse->forms->form[1]->finish_position);
                }

                if (isset($horse->forms->form[2])) {
                    $therdFormFinishPosition = number_format((int)$horse->forms->form[2]->finish_position);
                }

                if (isset($horse->forms->form[0]) && isset($horse->forms->form[0]->positions)) {
                    $m800Attribute = isset($horse->forms->form[0]->positions->attributes()['m800'])
                        ? $horse->forms->form[0]->positions->attributes()['m800']
                        : "--";
                }

                if (isset($horse->forms->form[0]) && isset($horse->forms->form[0]->positions)) {
                    $m1200Attribute = isset($horse->forms->form[0]->positions->attributes()['m1200'])
                        ? $horse->forms->form[0]->positions->attributes()['m1200']
                        : "--";
                }

                if (isset($horse->forms->form[0]) && isset($horse->forms->form[0]->positions)) {
                    $settling_downAttribute = isset($horse->forms->form[0]->positions->attributes()['settling_down'])
                        ? $horse->forms->form[0]->positions->attributes()['settling_down']
                        : "--";
                }

                //NewComm
                $settling_down = " ";

                if (isset($horse->forms->form[0]) && isset($horse->forms->form[0]->positions)) {
                    $settling_down_numeric = isset($horse->forms->form[0]->positions->attributes()['settling_down']) ? (int)$horse->forms->form[0]->positions->attributes()['settling_down'] : null;

                    if ($settling_down_numeric !== null) {
                        $settling_down = $this->convertToEnglishOrdinal($settling_down_numeric);
                    }
                }

                //m400
                $m400 = "";

                if (isset($horse->forms->form[0]) && isset($horse->forms->form[0]->positions)) {
                    $settling_down_numeric = isset($horse->forms->form[0]->positions->attributes()['m400']) ? (int)$horse->forms->form[0]->positions->attributes()['m400'] : null;

                    if ($settling_down_numeric !== null) {
                        $m400 = $this->convertToEnglish($settling_down_numeric);
                    }
                }
                //
                //finish positiom
                $finish = "";

                if (isset($horse->forms->form[0]) && isset($horse->forms->form[0]->positions)) {
                    $settling_down_numeric = isset($horse->forms->form[0]->positions->attributes()['finish']) ? (int)$horse->forms->form[0]->positions->attributes()['finish'] : null;

                    if ($settling_down_numeric !== null) {
                        $finish = $this->convertToEnglish($settling_down_numeric);
                    }
                }
                //
               
               // beaten_margin
                if (isset($horse->forms->form[0]) && isset($horse->forms->form[0]->beaten_margin)) {
                    $beaten_margin_tag = isset($horse->forms->form[0]->beaten_margin)
                        ? $horse->forms->form[0]->beaten_margin
                        : " ";
                
                       
                   

                    $decimal = $this->getDecimalPart($beaten_margin_tag);
                    $fraction = $this->decimalToFraction($decimal);
                    //dd($decimal);
                        
                    $beaten_margin_tag = $fraction[0] . '/' . $fraction[1];
                }
                //

                //first othe runers hose name 
                if (isset($horse->forms->form[0]->other_runners->other_runner[0])) {
                    // Extract data from the first form in other_runners
                    $firstFormOtherRunner = $horse->forms->form[0]->other_runners->other_runner[0];

                    $otherRunnerHorseName = $firstFormOtherRunner->attributes()['horse'];
                } else {
                    $otherRunnerHorseName = '';
                }
                //
                //taract name 


                $track_name = '';

                if (isset($horse->forms->form[0]) && isset($horse->forms->form[0]->track)) {
                    $track_name = (string)$horse->forms->form[0]->track->attributes()['track_3char_abbrev'];
                }
                //

                //race number 
                $race_number = '';

                if (isset($horse->forms->form[0]) && isset($horse->forms->form[0]->race)) {
                    $race_number = (string)$horse->forms->form[0]->race->attributes()['number'];
                }
                //

                //race date
                $race_date = '';

                    if (isset($horse->forms->form[0]) && isset($horse->forms->form[0]->meeting_date)) {
                        $full_date = (string)$horse->forms->form[0]->meeting_date;

                        // Specify the format of the date
                        $date_object = DateTime::createFromFormat('d/m/Y', $full_date);

                        if ($date_object !== false) {
                            $race_date = $date_object->format('d/m');
                        }
                    }


                //

                //jokey name

                $jokey_name = '';

                if (isset($horse->forms->form[0]) && isset($horse->forms->form[0]->jockey)) {
                    $jokey_name = (string)$horse->forms->form[0]->jockey->attributes()['name'];
                    $jokey_name = mb_convert_case($jokey_name, MB_CASE_UPPER, 'UTF-8');
                    // Now $jockeyName contains the jockey's name in uppercase
                }
                
                //$horseName = ucwords($horse->attributes()['name']);
                //$horseName = mb_convert_case($horse->attributes()['name'], MB_CASE_TITLE, 'UTF-8');
                $horseName = strtoupper($horse->attributes()['name']);

                //end new comm

                $output .= "\n@HossD:\t{$therdFormFinishPosition}\t{$secondFormFinishPosition}\t";

                if (isset($horse->forms->form)) {
                    $output .= number_format((int)$horse->forms->form->finish_position);
                } else {
                    $output .= "--";
                }

               
                $output .= "($recordId)\t\t{$horseName} ({$horse->attributes()['country']}) <B>\t{$horse->attributes()['age']}-{$horse->weight->attributes()['allocated']}{$horse->barrier} <B>{$horse->jockey->attributes()['name']} {$horse->barrier}\t{$horse->finish_position}";
                
                $output .= "\n@Own:\t\t\t\t\t{$horse->owners} {$horse->trainer->attributes()['name']} ";
              
                //$output .= "\n@Own:\t{$settling_downAttribute}\t{$m1200Attribute}\t{$m800Attribute}\t\t{$horse->owners} {$horse->trainer->attributes()['name']} ";
                $output .= "\n@NewsComm:\t{$horse->finish_position}\t{$horse->barrier}\t{$horse->finish_position}\t<I>{$settling_down} on settling, {$m400} at two furl., {$finish} by {$beaten_margin_tag} len. bhd {$otherRunnerHorseName} ({$track_name} {$race_number}) {$race_date} ({$jokey_name})";

                $recordId = $recordId + 1;

                //

                //
            }


            //
            //          // Initialize an array to store betting information
            // $bettingInfoArray = [];

            // foreach ($race->horses->horse as $horse) {
            //     $payment_order = $horse->market->attributes()['price'];

            //     list($numerator, $denominator) = explode('/', $payment_order);

            //     // Perform the calculation
            //     $calculation_result = $numerator / $denominator;

            //     // Append the calculated result to the array
            //     $calculation_result =  $calculation_result * 10 ;

            //     $horseName = $horse->attributes()['name'];

            //     // Append the horse's information to the array
            //     $bettingInfoArray[] = "{$calculation_result} {$horseName}";
            // }

            // // Join the array elements into a single string using commas
            // $bettingInfoString = implode(', ', $bettingInfoArray);

            // // Add the @Runners and @Bett:B/F: sections to the output
            // $output .= "\n@Runners:" . count($race->horses->horse) . " runners";
            // $output .= "\n@Bett:B/F: {$bettingInfoString}";

            // Initialize an array to store betting information and calculation results
            $bettingInfoArray = [];

            foreach ($race->horses->horse as $horse) {
                $payment_order = $horse->market->attributes()['price'];

                list($numerator, $denominator) = explode('/', $payment_order);

                // Perform the calculation
                $calculation_result = $numerator / $denominator;

                // Multiply by 10
                $calculation_result = ceil($calculation_result *= 10);

                $horseName = $horse->attributes()['name'];

                // Append the horse's information to the array
                $bettingInfoArray[] = [
                    'horseName' => $horseName,
                    'calculationResult' => $calculation_result
                ];
            }

            // Custom sorting function
            usort($bettingInfoArray, function ($a, $b) {
                return $a['calculationResult'] <=> $b['calculationResult'];
            });

            // Initialize an array to store the sorted strings
            $sortedBettingInfo = [];

            // Create a new string with the sorted results
            foreach ($bettingInfoArray as $info) {
                $sortedBettingInfo[] = "{$info['calculationResult']}/10 {$info['horseName']}";
            }

            // Join the array elements into a single string using commas
            $bettingInfoString = implode(', ', $sortedBettingInfo);

            // Add the @Runners and @Bett:B/F: sections to the output
            $output .= "\n@Runners:" . count($race->horses->horse) . " runners";
            $output .= "\n@Bett:B/F: {$bettingInfoString}";

            //


            // $output .= "\n@Runners:" . count($race->horses->horse) . " runners";
            // $output .= "\n@Bett:B/F:\t";




            // if ($race->records && $race->records->track_record) {
            //     foreach ($race->records->track_record as $trackRecord) {
            //         $horse = $trackRecord->horse;

            //         if ($horse && $horse->jockey && $horse->jockey->attributes()) {
            //             $output .= "\n\n@HossD:\t{$horse->barrier}\t{$horse->barrier}\t{$horse->barrier}\t({$horse->finish_position})\t\t{$horse->attributes()['name']} ({$horse->country}) <B>\t{$horse->weight_carried}-{$horse->barrier} <B>{$horse->jockey->attributes()['name']} ({$horse->barrier})\t{$horse->finish_position}";

            //             if ($horse->owners) {
            //                 $output .= "\n@Own:\t{$horse->barrier}\t{$horse->barrier}\t{$horse->barrier}\t\t{$horse->owners}\t{$horse->trainer->attributes()['name']}";
            //             }

            //             $output .= "\n@NewsComm:\t{$horse->finish_position}\t{$horse->barrier}\t{$horse->finish_position}\t<I>{$horse->form_comments} {$trackRecord->meeting_date} ({$horse->jockey->attributes()['name']})";
            //         }
            //     }
            // }

            // if ($race->runners && $race->runners->attributes()['count']) {
            //     $output .= "\n@Runners:{$race->runners->attributes()['count']} runners";
            // }

            // $bettingInfo = $race->betting_info;
            // if ($bettingInfo) {
            //     $output .= "\n@Bett:B/F: {$bettingInfo}";
            // }
        }




        $txtFilename = $filename . '.txt';
        Storage::put('txt/' . $txtFilename, $output);

        return response($output)
            ->header('Content-Type', 'text/plain')
            ->header('Content-Disposition', "attachment; filename=$txtFilename");
    }
    //

    private function metersToMiles($distanceInMetres)
    {

        $distance = '';

        $distanceInMiles = $distanceInMetres / 1609.34;

        list($distanceWhole, $distanceDecimals) = explode('.', (string)$distanceInMiles);

        $distanceDecimals = '0.' . $distanceDecimals;

        $distanceFurlongs = round(floatval($distanceDecimals) * 7.99998);
        //7.99998

        if (intval($distanceWhole) >= 1) {
            $distance .= $distanceWhole . " Miles ";
        }

        if ($distanceFurlongs >= 1) {
            $distance .= $distanceFurlongs . " Furlongs ";
        }

        return trim($distance);
    }

    //new comm
    // Function to convert numeric position to English ordinal
    private function convertToEnglishOrdinal($number)
    {
        $ordinal = "";
        $last_digit = $number % 10;
        $second_last_digit = floor($number / 10) % 10;

        if ($second_last_digit == 1) {
            $ordinal .= "th";
        } else {
            switch ($last_digit) {
                case 1:
                    $ordinal .= "st";
                    break;
                case 2:
                    $ordinal .= "nd";
                    break;
                case 3:
                    $ordinal .= "rd";
                    break;
                default:
                    $ordinal .= "th";
            }
        }

        // Convert numeric position to word ordinal
        $ordinalNumber = new NumberFormatter("en", NumberFormatter::SPELLOUT);
        $ordinalWord = ucfirst($ordinalNumber->format($number)); // Make the first letter uppercase

        return $ordinalWord . $ordinal;
    }


    //


    private function convertToEnglish($number)
    {
        $ordinal = "";
        $last_digit = $number % 10;
        $second_last_digit = floor($number / 10) % 10;

        if ($second_last_digit == 1) {
            $ordinal .= "th";
        } else {
            switch ($last_digit) {
                case 1:
                    $ordinal .= "st";
                    break;
                case 2:
                    $ordinal .= "nd";
                    break;
                case 3:
                    $ordinal .= "rd";
                    break;
                default:
                    $ordinal .= "th";
            }
        }

        // Convert numeric position to word ordinal
        $ordinalNumber = new NumberFormatter("en", NumberFormatter::SPELLOUT);
        $ordinalWord = $ordinalNumber->format($number);

        return $ordinalWord . $ordinal;
    }

    //conver dashama to baga
    function decimalToFraction($decimal, $precision = 10) {
        $scale = bcpow(10, $precision);
        
        $numerator = bcmul($decimal, $scale);
        $gcd = $this->gcd($numerator, $scale);
    
        $numerator = bcdiv($numerator, $gcd);
        $denominator = bcdiv($scale, $gcd);
    
        return [$numerator, $denominator];
    }
    
    // Helper function to calculate the greatest common divisor (GCD)
    function gcd($a, $b) {
        while (bccomp($b, '0') !== 0) {
            $remainder = bcmod($a, $b);
            $a = $b;
            $b = $remainder;
        }
        return $a;
    }

    // getting dashama part of a number
    // function getDecimalPart($number) {
    //     return bcsub($number, bcmul(floor($number), '1', 10), 10);
    // }

    //
    function getDecimalPart($number) {
        // Convert SimpleXMLElement to a float
        $numberFloat = (float)$number;
    
        // Perform the operation on the float value
        return bcsub($numberFloat, bcmul(floor($numberFloat), '1', 10), 10);
    }
    //
    
 
}
