<?php

namespace App\Services;

use App\Models\EngResultMeeting;
use Illuminate\Support\Facades\DB;

// English Feed Selection Service
// ------------------------------

class EngMeetingService
{
    // Logic written only for the EVE tag
    public function getTimeTags($meeting)
    {
        $tags = "";

        $matchingMeetings = EngResultMeeting::whereDate('date', $meeting->date)
            ->select(
                'tbl_eng_result_meeting.code',
                'tbl_eng_result_meeting.name',
                DB::raw('MIN(tbl_eng_result_event.time) AS earliest_start_time')
            )
            ->where('tbl_eng_result_meeting.name', $meeting->name)
            ->join('tbl_eng_result_event', 'tbl_eng_result_meeting.code', '=', 'tbl_eng_result_event.meeting_code')
            ->groupBy('tbl_eng_result_meeting.code', 'tbl_eng_result_meeting.name')
            ->orderBy('earliest_start_time')
            ->get();

        foreach ($matchingMeetings as $matchingMeeting) {
            if ($matchingMeeting->code == $meeting->code && $matchingMeeting != $matchingMeetings[0]) {
                $tags .= 'EVE';
            }
        }

        return $tags;
    }
}
