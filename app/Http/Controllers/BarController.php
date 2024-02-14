<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use DateTime;
// use NumberFormatter;
use SimpleXMLElement;


class BarController extends Controller
{
//    public function show()
//     {
//         $xmlFiles = Storage::files('xml/bar');
//         return view('xml.bar', compact('xmlFiles'));
//     } 

//show fils 

public function show()
{
    $xmlFiles = Storage::files('xml/bar');

    // Extract dates from file names
    $dates = [];
    foreach ($xmlFiles as $file) {
        preg_match('/(\d{8})/', $file, $matches);
        if (isset($matches[1])) {
            $dates[] = $matches[1];
        }
    }

    // Sort dates in descending order
    rsort($dates);

    // Take the two latest dates
    $latestDates = array_slice($dates, 0, 2);

    // Filter files based on the selected dates
    $filteredFiles = array_filter($xmlFiles, function ($file) use ($latestDates) {
        preg_match('/(\d{8})/', $file, $matches);
        return in_array($matches[1], $latestDates);
    });

    return view('xml.bar', compact('filteredFiles'));
}


    //BAR Form
    public function bar_form($filename)
    {
        $xmlContent = Storage::get('xml/bar/' . $filename);

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
            foreach ($race->classes->class_id  as $class_id) {
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
                        // $totalValue = number_format((int)$race->prizes->prize[6]['value']);

                        // $totalValue = null;

                        //     foreach ($race->prizes->prize as $prize) {
                        //         if ($prize['type'] == 'total_value') {
                        //             $totalValue = $prize['value'];
                        //             break;
                        //         }
                        //     }


                        $totalValue = null;

                        foreach ($race->prizes->prize as $prize) {
                            if ($prize['type'] == 'total_value') {
                                $totalValue = number_format((int)$prize['value']);
                                break;
                            }
                        }

                        // $output .= "\n@Race:{$race->track->attributes()['track_3char_abbrev']}/{$race->attributes()['number']}<&te><&tb(45,28,a)*C>$formattedStartTime ($modifiedStartTime) <&te>{$race->attributes()['name']} ($class) <B>({$race->restrictions->attributes()['age']}) \${$totalValue}";

                        //age
                        $ageAttribute = $race->restrictions->attributes()['age'];
                        $ageOutput = '';

                        if ($ageAttribute !== null) {
                            if (strpos($ageAttribute, '+') !== false) {
                                // If the age attribute contains '+', extract the numeric part
                                $numericAge = (int) filter_var($ageAttribute, FILTER_SANITIZE_NUMBER_INT);

                                // If there is a numeric part, include it in the output with 'yo Up'
                                if ($numericAge > 0) {
                                    $ageOutput = "({$numericAge}yo Up)";
                                } else {
                                    // If no numeric part, default to 'Up'
                                    $ageOutput = 'Up';
                                }
                            } else {
                                // Otherwise, output the age as is  
                                $ageOutput = "($ageAttribute Only)";
                            }
                        }

                        // Ensure that $ageOutput is not null, and if it is, set it to '()'
                        $ageOutput = ($ageOutput !== null) ? $ageOutput : ' ';

                        //
                        //distence 

                        // $classWithSpace = $class . ' ' . $class_id;

                        $distanceInMetres  = $race->distance->attributes()['metres'];
                        $distance = $this->metersToMiles($distanceInMetres);
                        $output .= "\n@Race:{$race->track->attributes()['track_3char_abbrev']}R/{$race->attributes()['number']} $formattedStartTime ($modifiedStartTime) {$race->attributes()['name']} ($class$class_id) <B>{$ageOutput} {$distance} \${$totalValue}";

                        //
                    }
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

                // if (isset($horse->forms->form[1])) {
                //     $secondFormFinishPosition = number_format((int)$horse->forms->form[1]->finish_position);
                // }

                // if (isset($horse->forms->form[2])) {
                //     $therdFormFinishPosition = number_format((int)$horse->forms->form[2]->finish_position);
                // }

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
                //beaten_margin
                if (isset($horse->forms->form[0]) && isset($horse->forms->form[0]->beaten_margin)) {
                    $beaten_margin_tag = isset($horse->forms->form[0]->beaten_margin)
                        ? $horse->forms->form[0]->beaten_margin
                        : " ";

                    //$beaten_margin_tag = $this->convertDecimalToFraction($beaten_margin_tag);
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

                //New comme past data               

                $newcome_pase_1 = null;

                    foreach ($horse->statistics->statistic as $statistic) {
                        if ((string)$statistic['type'] == 'all') {
                            $newcome_pase_1 = (int)$statistic['total'];
                            break;
                        }
                    } 
                    $newcome_pase_1 = ($newcome_pase_1 === 0) ? '--' : $newcome_pase_1;   
                    
                    

                    $newcome_pase_2 = null;

                    foreach ($horse->statistics->statistic as $statistic) {
                        if ((string)$statistic['type'] == 'all') {
                            $newcome_pase_2 = (int)$statistic['firsts'];
                            break;
                        }
                    } 
                    $newcome_pase_2 = ($newcome_pase_2 === 0) ? '--' : $newcome_pase_2;  

                    $newcome_seconds = null;

                        foreach ($horse->statistics->statistic as $statistic) {
                            if ((string)$statistic['type'] == 'all') {
                                $newcome_seconds = (int)$statistic['seconds'];
                                break;
                            }
                        } 
                        

                        $newcome_thirds = null;

                        foreach ($horse->statistics->statistic as $statistic) {
                            if ((string)$statistic['type'] == 'all') {
                                $newcome_thirds = (int)$statistic['thirds']; // Change here to 'thirds'
                                break;
                            }
                        } 
                        

                        $newcome_pase_3 = ($newcome_seconds + $newcome_thirds);
                        $newcome_pase_3 = ($newcome_pase_3 === 0) ? '--' : $newcome_pase_3;  





                //

                //hose name 
               
                $horseName = strtoupper($horse->attributes()['name']);


                //

                //Last form status             
                $lastformstaus = str_split(substr($horse->last_four_starts, -3));

                while (count($lastformstaus) < 3) {
                    array_unshift($lastformstaus, '--');
                }


                $output .= "\n@HossD:\t{$lastformstaus[0]}\t{$lastformstaus[1]}\t{$lastformstaus[2]}\t";

                
                //finish_position
                $finish = $horse->finish_position ?? '--';
                $barriera = $horse->barrier ?? '--';
                $horseposition = $horse->finish_position ?? '--';

                //
                //add icon to the first 
                 $icon_1 = isset($horse->selection) ? $horse->selection : ' ';

                if ($icon_1 == 1) {
                    $icon_1 = 'K';
                    // Use the character code for ♣
                    //$icon_1 = html_entity_decode('&#0167;', ENT_COMPAT, 'UTF-8');
                } elseif ($icon_1 == 2) {
                    $icon_1 = 'H';
                    // Use the character code for ♥
                    //$icon_1 = html_entity_decode('&#0169;', ENT_COMPAT, 'UTF-8');
                } else {
                    $icon_1 = '';
                }
                
                // $icon_1 = isset($horse->selection) ? $horse->selection : ' ';

                //     if ($icon_1 == 1) {
                //         $icon_1 = html_entity_decode('&#9827;', ENT_COMPAT, 'UTF-8'); // ♣
                //     } elseif ($icon_1 == 2) {
                //         $icon_1 = html_entity_decode('&#9829;', ENT_COMPAT, 'UTF-8'); // ♥
                //     } else {
                //         $icon_1 = '';
                //     }
                

                // $icon_1 = isset($horse->selection) ? $horse->selection : '';

                //         if ($icon_1 == 1) {
                //             $icon_1 = '♣';
                //         } elseif ($icon_1 == 2) {
                //             $icon_1 = '♥';
                //         }

                //         // Apply the style directly to $icon_1
                //         $icon_1 = '<span style="font-size: 9pt;">' . $icon_1 . '</span>';


                                  

                

                   if (!empty($icon_1)) {
                    // $output .= " ($recordId) {$icon_1}  {$horseName} ({$horse->attributes()['country']}) <B>\t{$horse->attributes()['age']}-{$horse->weight->attributes()['allocated']} 2 <B> \t {$horse->jockey->attributes()['name']} {$horse->barrier}\t{$horse->finish_position}";
                    $output .= " ($recordId) {$icon_1}  {$horseName} ({$horse->attributes()['country']}) <B>\t{$horse->attributes()['age']}-{$horse->weight->attributes()['allocated']} 2 <B> \t {$horse->jockey->attributes()['name']} {$horse->barrier}\t{$horse->finish_position}";
                } else {
                    $output .= " ($recordId) \t\t{$horseName} ({$horse->attributes()['country']}) <B>\t{$horse->attributes()['age']}-{$horse->weight->attributes()['allocated']} 2 <B> \t {$horse->jockey->attributes()['name']} {$horse->barrier}\t{$horse->finish_position}";
                }

                $output .= "\n@Own:\t--\t--\t--\t\t{$horse->owners}\t{$horse->trainer->attributes()['name']} ";

                //$output .= "\n@Own:\t{$settling_downAttribute}\t{$m1200Attribute}\t{$m800Attribute}\t\t{$horse->owners} {$horse->trainer->attributes()['name']} ";
                $output .= "\n@NewsComm:\t{$newcome_pase_1}\t{$newcome_pase_2}\t{$newcome_pase_3}\t<I> {$horse->form_comments}";


                $recordId = $recordId + 1;

            }


            //
            //          // Initialize an array to store betting information
            // $bettingInfoArray = [];

            // foreach ($race->horses->horse as $horse) {
            //     $payment_order = $horse->market->attributes()['price'];



            //     $horseName = $horse->attributes()['name'];

            //     // Append the horse's information to the array
            //     $bettingInfoArray[] = "{$payment_order} {$horseName}";
            // }

            // // Join the array elements into a single string using commas
            // $bettingInfoString = implode(', ', $bettingInfoArray);

            // $bettingInfoArray = [];

            // foreach ($race->horses->horse as $horse) {
            //     // Extract the odds from the format "@Bett:B/F: 5/1"
            //     $paymentOrder = (string)$horse->market->attributes()['price'];
            //     $paymentOrders = (string)$horse->market->attributes()['price_decimal'];
            //     $horseName = $horse->attributes()['name'];

            //     // Append the horse's information to the array
            //     $bettingInfoArray[] = [
            //         'odds' => $paymentOrder,
            //         'decimal' => $paymentOrders,
            //         'name' => $horseName,
            //     ];
            // }

            //batting forcarst 

            // $bettingInfoArray = [];

            // foreach ($race->horses->horse as $horse) {
            //     // Check if the 'market' element and its attributes exist
            //     if (isset($horse->market) && isset($horse->market->attributes()['price'])) {
            //         // Extract the odds from the format "@Bett:B/F: 5/1"
            //         $paymentOrder = (string)$horse->market->attributes()['price'];
            //     } else {
            //         // Set a default value or handle the case where attributes are not present
            //         $paymentOrder = "--";
            //     }

            //     // Check if the 'market' element and its attributes exist
            //     if (isset($horse->market) && isset($horse->market->attributes()['price_decimal'])) {
            //         $paymentOrders = (string)$horse->market->attributes()['price_decimal'];
            //     } else {
            //         // Set a default value or handle the case where attributes are not present
            //         $paymentOrders = "--";
            //     }

            //     $horseName = $horse->attributes()['name'];

            //     // Append the horse's information to the array
                
            //     $bettingInfoArray[] = [
            //         'odds' => $paymentOrder,
            //         'decimal' => $paymentOrders,
            //         'name' => $horseName,
            //     ];
            // }


            // // Custom function to compare odds and sort the array in ascending order
            // usort($bettingInfoArray, function ($a, $b) {
            //     return version_compare($a['decimal'], $b['decimal']);
            // });

            // // Output the sorted array
            // $sortedBettingInfo = array_map(function ($info) {
            //     return "{$info['odds']} {$info['name']}";
            // }, $bettingInfoArray);

            // // Convert the array to a string
            // $sortedBettingInfoString = implode(', ', $sortedBettingInfo);
            
            //
            $bettingInfoArray = [];

            foreach ($race->horses->horse as $horse) {
                // Check if the 'market' element and its attributes exist
                if (isset($horse->market) && isset($horse->market->attributes()['price'])) {
                    // Extract the odds from the format "@Bett:B/F: 5/1"
                    $paymentOrder = (string)$horse->market->attributes()['price'];
            
                    // Check if the 'market' element and its attributes exist
                    if (isset($horse->market) && isset($horse->market->attributes()['price_decimal'])) {
                        $paymentOrders = (string)$horse->market->attributes()['price_decimal'];
            
                        $horseName = $horse->attributes()['name'];
            
                        // Append the horse's information to the array only if $paymentOrder has a value
                        if ($paymentOrder !== "") {
                            $bettingInfoArray[] = [
                                'odds' => $paymentOrder,
                                'decimal' => $paymentOrders,
                                'name' => $horseName,
                            ];
                        }
                    }
                }
            }
            
            // Custom function to compare odds and sort the array in ascending order
            usort($bettingInfoArray, function ($a, $b) {
                return version_compare($a['decimal'], $b['decimal']);
            });
            
            // Output the sorted array
            $sortedBettingInfo = array_map(function ($info) {
                return "{$info['odds']} {$info['name']}";
            }, $bettingInfoArray);
            
            // Convert the array to a string
            $sortedBettingInfoString = implode(', ', $sortedBettingInfo);
            
            // Output the result
            //

            // Add the @Runners and @Bett:B/F: sections to the output
            $output .= "\n@Runners:" . count($race->horses->horse) . " runners";
            $output .= "\n@Bett:B/F: {$sortedBettingInfoString}";
        }




        $txtFilename = $filename . '.txt';
        Storage::put('txt/' . $txtFilename, $output);

        return response($output)
            ->header('Content-Type', 'text/plain')
            ->header('Content-Disposition', "attachment; filename=$txtFilename");
    }
    //

    // private function metersToMiles($distanceInMetres)
    // {

    //     $distance = '';

    //     $distanceInMiles = $distanceInMetres / 1600;
    //     //1609.34

    //     list($distanceWhole, $distanceDecimals) = explode('.', (string)$distanceInMiles);

    //     $distanceDecimals = '0.' . $distanceDecimals;

    //     $distanceFurlongs = round(floatval($distanceDecimals) * 7.99998);
    //     //7.99998

    //     if (intval($distanceWhole) >= 1) {
    //         $distance .= $distanceWhole . " Miles ";
    //     }

    //     if ($distanceFurlongs >= 1) {
    //         $distance .= $distanceFurlongs . " Furlongs ";
    //     }

    //     return trim($distance);
    // }

    private function metersToMiles($distanceInMetres)
    {
        $distance = '';

        $distanceInMiles = $distanceInMetres / 1600; // Convert meters to miles
        $distanceInFurlongs = $distanceInMiles / 0.00497096; // Convert miles to furlongs

        $distanceWholeMiles = floor($distanceInMiles);
        $distanceFurlongs = round(($distanceInMiles - $distanceWholeMiles) * 7.99998);

        if ($distanceWholeMiles >= 1) {
            $distance .= $distanceWholeMiles . " Miles ";
        }

        if ($distanceFurlongs >= 1) {
            $distance .= $distanceFurlongs . " Furlongs ";
        }

        return trim($distance);
    }


    //new comm
    // Function to convert numeric position to English ordinal
    // private function convertToEnglishOrdinal($number)
    // {
    //     $ordinal = "";
    //     $last_digit = $number % 10;
    //     $second_last_digit = floor($number / 10) % 10;

    //     if ($second_last_digit == 1) {
    //         $ordinal .= "th";
    //     } else {
    //         switch ($last_digit) {
    //             case 1:
    //                 $ordinal .= "st";
    //                 break;
    //             case 2:
    //                 $ordinal .= "nd";
    //                 break;
    //             case 3:
    //                 $ordinal .= "rd";
    //                 break;
    //             default:
    //                 $ordinal .= "th";
    //         }
    //     }

    //     // Convert numeric position to word ordinal
    //     $ordinalNumber = new NumberFormatter("en", NumberFormatter::SPELLOUT);
    //     $ordinalWord = ucfirst($ordinalNumber->format($number)); // Make the first letter uppercase

    //     return $ordinalWord . $ordinal;
    // }
//with out number format 
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
    $ordinalWord = $this->convertNumberToWord($number);

    return ucfirst($ordinalWord) . $ordinal;
}

private function convertNumberToWord($number)
{
    $ones = array(
        1 => 'one', 2 => 'two', 3 => 'three', 4 => 'four', 5 => 'five',
        6 => 'six', 7 => 'seven', 8 => 'eight', 9 => 'nine', 10 => 'ten',
        // Add more as needed
    );

    $teens = array(
        11 => 'eleven', 12 => 'twelve', 13 => 'thirteen', 14 => 'fourteen',
        15 => 'fifteen', 16 => 'sixteen', 17 => 'seventeen', 18 => 'eighteen',
        19 => 'nineteen',
    );

    $tens = array(
        2 => 'twenty', 3 => 'thirty', 4 => 'forty', 5 => 'fifty',
        6 => 'sixty', 7 => 'seventy', 8 => 'eighty', 9 => 'ninety',
    );

    $words = array();

    if ($number >= 100) {
        $words[] = $ones[floor($number / 100)] . ' hundred';
        $number %= 100;
    }

    if ($number >= 20) {
        $words[] = $tens[floor($number / 10)];
        $number %= 10;
    }

    if ($number > 0) {
        if ($number < 10) {
            $words[] = $ones[$number];
        } elseif ($number >= 11 && $number <= 19) {
            $words[] = $teens[$number];
        }
    }

    return implode(' ', $words);
}



    //

    //number converto english 
    // private function convertToEnglish($number)
    // {
    //     $ordinal = "";
    //     $last_digit = $number % 10;
    //     $second_last_digit = floor($number / 10) % 10;

    //     if ($second_last_digit == 1) {
    //         $ordinal .= "th";
    //     } else {
    //         switch ($last_digit) {
    //             case 1:
    //                 $ordinal .= "st";
    //                 break;
    //             case 2:
    //                 $ordinal .= "nd";
    //                 break;
    //             case 3:
    //                 $ordinal .= "rd";
    //                 break;
    //             default:
    //                 $ordinal .= "th";
    //         }
    //     }

    //     // Convert numeric position to word ordinal
    //     $ordinalNumber = new NumberFormatter("en", NumberFormatter::SPELLOUT);
    //     $ordinalWord = $ordinalNumber->format($number);

    //     return $ordinalWord . $ordinal;
    // }

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
    $ordinalWord = $this->convertNumberToWord($number);

    return ucfirst($ordinalWord) . $ordinal;
}




    //conver dashama to baga 
    private function convertDecimalToFraction($decimal)
    {
        $decimalValue = floatval($decimal); // Convert to float

        $whole = floor($decimalValue);
        $fraction = $decimalValue - $whole;

        $tolerance = 1.0e-9;

        $numerator = 0;
        $denominator = 1;
        $previousDenominator = 0;
        $approximation = 0;

        do {
            // Check if the fraction is zero to prevent division by zero
            if ($fraction == 0) {
                break;
            }

            $fraction = 1 / $fraction;

            $approximation = floor($fraction);

            $numerator = $approximation * $denominator + $previousDenominator;
            $previousDenominator = $denominator;
            $denominator = $approximation;

            $fraction -= $approximation; // Adjust fraction for the next iteration
        } while (abs($fraction) > $tolerance);

        if ($whole > 0) {
            return "$whole $numerator/$denominator";
        } else {
            return "$numerator/$denominator";
        }
    }
}
