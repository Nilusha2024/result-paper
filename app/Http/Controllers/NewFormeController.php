<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use DateTime;

class NewFormeController extends Controller
{
    public function show()
    {
        $xmlFiles = Storage::files('xml/xmls');
        return view('xml.new_form', compact('xmlFiles'));
    }

    public function new_form($filename)
    {
        $xmlContent = Storage::get('xml/xmls/' . $filename);

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
                    //    $ageAttribute = $race->restrictions->attributes()['age'];
                    //    $ageOutput = '';

                    //    if (strpos($ageAttribute, '+') !== false) {
                    //        // If the age attribute contains '+', extract the numeric part
                    //        $numericAge = (int) filter_var($ageAttribute, FILTER_SANITIZE_NUMBER_INT);

                    //        // If there is a numeric part, include it in the output with 'Up'
                    //        if ($numericAge > 0) {
                    //            $ageOutput = "{$numericAge}yo Up";
                    //        } else {
                    //            // If no numeric part, default to 'Up'
                    //            $ageOutput = 'Up';
                    //        }
                    //    } else{
                    //        // Otherwise, output the age as is  

                    //         $ageOutput = "{$ageAttribute} Only";


                    //    }

                    //distence 

                    $distanceInMetres  = $race->distance->attributes()['metres'];
                    $distance = $this->metersToMiles($distanceInMetres);
                    //  
                    $output .= "\n@Race:{$race->track->attributes()['track_3char_abbrev']}/{$race->attributes()['number']}<&te><&tb(45,28,a)*C>$formattedStartTime ($modifiedStartTime) <&te>{$race->attributes()['name']} ($class) <B> $distance  \${$totalValue}";

                    //
                }
            }



            $recordId = 1;

            foreach ($race->horses->horse as $horse) {

                if (isset($horse->forms->form[1])) {
                    $secondFormFinishPosition = number_format((int)$horse->forms->form[1]->finish_position);
                    if (isset($horse->forms->form[2])) {
                        $therdFormFinishPosition = number_format((int)$horse->forms->form[2]->finish_position);

                        $output .= "\n@HossD:\t{$therdFormFinishPosition}\t{$secondFormFinishPosition}\t{$horse->forms->form->finish_position}\t($recordId)\t\t{$horse->attributes()['name']} ({$horse->attributes()['country']}) <B>\t{$horse->weight_carried}-{$horse->barrier} <B>{$horse->jockey->attributes()['name']} {$horse->barrier}\t{$horse->finish_position}";
                        $output .= "\n@Own:\t{$horse->barrier}\t{$horse->barrier}\t{$horse->barrier}\t\t{$horse->owners}";
                        $output .= "\n@NewsComm:\t{$horse->finish_position}\t{$horse->barrier}\t{$horse->finish_position}\t<I>{$horse->form_comments}";
                    }
                }

                $recordId = $recordId + 1;
            }

            // $output .= "\n@Runners:" . count($race->horses->horse) . " runners";
            // $output .= "\n@Bett:B/F:";

            //
            $bettingInfoArray = [];

            foreach ($race->horses->horse as $horse) {
                // Check if market information is available
                if ($horse->market) {
                    $payment_order = $horse->market->attributes()['price'];
            
                    list($numerator, $denominator) = explode('/', $payment_order);
            
                    // Perform the calculation
                    $calculation_result = $numerator / $denominator;
            
                    // Multiply by 10 and round up
                    $calculation_result = ceil($calculation_result * 10);
            
                    $horseName = $horse->attributes()['name'];
            
                    // Append the horse's information to the array
                    $bettingInfoArray[] = [
                        'horseName' => $horseName,
                        'calculationResult' => $calculation_result
                    ];
                }
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

        $distanceInMiles = $distanceInMetres / 1609.344;
        $distanceWhole =intval($distanceInMiles) ;

        //dd($distanceWhole);
        $distanceDecimals= $distanceInMetres % 1609.344;

       //list($distanceWhole, $distanceDecimals) = explode('.', (string)$distanceInMiles);

       // $distanceDecimals = '0.' . $distanceDecimals;

        $distanceFurlongs = round(floatval($distanceDecimals) * 0.00497096);
        //7.99998

        if (intval($distanceWhole) >= 1) {
            $distance .= $distanceWhole . " Miles ";
        }

        if ($distanceFurlongs >= 1) {
            $distance .= $distanceFurlongs . " Furlongs ";
        }

        return trim($distance);
    }
}
