<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>English Text Download</title>

    {{-- font imports --}}
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,600&display=swap" rel="stylesheet" />

    {{-- tailwind cdn --}}
    <script src="https://cdn.tailwindcss.com"></script>

</head>

<body class="bg-[#1a202e] font-[Figtree] p-10 text-white">

    <div class="flex flex-col gap-3 bg-slate-700 p-5 rounded-lg">

        <h1 class="text-2xl font-bold">English Text Downloads</h1>
        <p>Select the date for which you want to download the processed English result text</p>

        <div class="flex gap-2">
            <input id="target-date-selector" name="target-date" type="date"
                class="rounded-md bg-slate-800 text-gray-300 p-2" required />
            <button id="btn-load-result" class="p-3 font-bold bg-indigo-500 rounded-md hover:scale-105 transition">Load
                Result</button>
        </div>

    </div>

    {{-- result listing host div --}}
    <div id="result-listing-host" class="mt-5">
    </div>

    {{-- axios import --}}
    <script src="https://unpkg.com/axios/dist/axios.min.js"></script>

    {{-- JS --}}
    <script>

        document.addEventListener('DOMContentLoaded', function() {

            let baseURL = `http://${window.location.hostname}:${window.location.port}/result-paper/public/`;
            axios.defaults.baseURL = baseURL;

            let targetDateSelector = document.getElementById('target-date-selector');
            let btnLoadResult = document.getElementById('btn-load-result');

            let resultListingHost = document.getElementById('result-listing-host');

            // event listeners
            // ---------------

            btnLoadResult.addEventListener('click', function() {
                loadResult(targetDateSelector.value);
            })

            // delegated listeners
            // -------------------

            resultListingHost.addEventListener('click', function(event) {

                // download button listener
                // ------------------------
                if (event.target.tagName === 'BUTTON' && event.target.id === 'btn-download-result') {
                    event.preventDefault();
                    downloadResult();
                }

                // meeting checkbox listener
                // -------------------------
                if (event.target.tagName === 'INPUT' && event.target.type === 'checkbox' && event.target
                    .name === 'meetings[]') {
                    onMeetingCheckToggle(event.target);
                }

            });



        });

        // implementations
        // ---------------

        function onMeetingCheckToggle(target) {
            let meetingId = target.value;
            let eventsForMeeting = document.getElementsByClassName('meeting-' + meetingId + '-event');

            for (let i = 0; i < eventsForMeeting.length; i++) {
                eventsForMeeting[i].checked = target.checked;
            }

        }


        // sends a request to load the result
        function loadResult(date) {
            axios.get('/english_result_listing', {
                    params: {
                        targetDate: date,
                    }
                })
                .then((result) => {
                    let hostContainer = document.getElementById('result-listing-host');
                    hostContainer.innerHTML = result.data;
                }).catch((err) => {
                    console.log("Oof, didn't work :(");
                });
        }

        // sends a request to download the results
        function downloadResult() {

            let resultDownloadForm = document.getElementById('result-download-form');

            let events = document.getElementsByName('events[]');
            let meetings = document.getElementsByName('meetings[]');

            let droppedEvents = [];
            let droppedMeetings = [];

            for (let i = 0; i < events.length; i++) {
                if (!events[i].checked) {
                    droppedEvents.push(events[i].value);
                }
            }

            for (let i = 0; i < meetings.length; i++) {
                if (!meetings[i].checked) {
                    droppedMeetings.push(meetings[i].value);
                }
            }

            // make a json strings out of the dropped events and meetings
            let jsonStringDroppedEvents = JSON.stringify(droppedEvents);
            let jsonStringDroppedMeetings = JSON.stringify(droppedMeetings);

            // and put them in form elements
            let droppedEventListInput = document.createElement('input');
            droppedEventListInput.type = 'hidden';
            droppedEventListInput.name = 'dropped-events';
            droppedEventListInput.value = jsonStringDroppedEvents;

            let droppedMeetingListInput = document.createElement('input');
            droppedMeetingListInput.type = 'hidden';
            droppedMeetingListInput.name = 'dropped-meetings';
            droppedMeetingListInput.value = jsonStringDroppedMeetings;

            // append
            resultDownloadForm.appendChild(droppedEventListInput);
            resultDownloadForm.appendChild(droppedMeetingListInput);

            // submit
            resultDownloadForm.submit();

        }
    </script>


</body>



</html>
