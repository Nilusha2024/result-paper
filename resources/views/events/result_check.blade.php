<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Event Check') }}
        </h2>
    </x-slot>

    <body class="bg-[#1a202e] font-[Figtree] p-10 dark:text-white">

        <div class="flex flex-col gap-3 p-5 rounded-lg">
            <form method="post" action="{{ route('download-events') }}" id="eventCheckForm"
                onsubmit="return validateForm()">
                @csrf

                @php
                    $eventsByNames = $events->groupBy('event_name');
                @endphp

                <div class="grid grid-cols-5 gap-6"> <!-- Use grid-cols-5 for 5 columns -->
                    @foreach ($eventsByNames as $eventName => $eventsGroup)
                        <div class=""> <!-- Add text-white class here -->
                            <h3 class="text-lg font-semibold">{{ $eventName }}</h3>
                            <ul class="list-disc pl-4">
                                @foreach ($eventsGroup as $event)
                                    <label class="flex items-center">
                                        <input type="checkbox" name="selectedEvents[]" value="{{ $event->id }}"
                                            class="mr-2">
                                        <span class="text-sm">{{ $event->event_name }} ({{ $event->race_num }})</span>
                                    </label>
                                @endforeach
                            </ul>
                        </div>
                    @endforeach
                </div>
                <br>
                <div id="warningMessage" class="text-red-500 mb-2 hidden">Please check at least one event to download.
                </div>

                <div class="flex space-x-2">
                    <button type="button" onclick="toggleCheckAll()"
                        class="bg-blue-500 text-white px-2 py-1 rounded-md flex items-center">
                        <span class="inline-block w-4 h-4 border border-white rounded-md mr-2 focus:outline-none">
                            <svg class="w-full h-full text-white" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M0 11l2-2 5 5L18 3l2 2L7 18z"></path>
                            </svg>
                        </span>
                        Check All
                    </button>
                    <button type="submit" class="bg-green-500 text-white px-4 py-2 rounded-md">Download Selected
                        Events</button>
                </div>

            </form>
        </div>

        <script>
            function toggleCheckAll() {
                var checkboxes = document.getElementsByName('selectedEvents[]');
                for (var i = 0; i < checkboxes.length; i++) {
                    checkboxes[i].checked = !checkboxes[i].checked;
                }
                validateForm(); // Check validation after toggling checkboxes
            }

            function validateForm() {
                var checkboxes = document.getElementsByName('selectedEvents[]');
                var warningMessage = document.getElementById('warningMessage');

                // Check if at least one checkbox is checked
                var atLeastOneChecked = Array.from(checkboxes).some(checkbox => checkbox.checked);

                // Show/hide warning message based on the validation result
                warningMessage.style.display = atLeastOneChecked ? 'none' : 'block';

                // Return true to submit the form if at least one checkbox is checked
                return atLeastOneChecked;
            }
        </script>
    </body>
</x-app-layout>
