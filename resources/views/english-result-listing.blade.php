{{-- eng result listing --}}
<div class="bg-slate-700 rounded-lg p-5">

    {{-- header + download form --}}
    <form id="result-download-form" action="{{ route('download_eng') }}" method="POST">
        @csrf
        <input type="hidden" id="target-date" name="target-date" type="date" value="{{ $targetDate }}">
        <div class="flex justify-between items-center mb-5">
            <h1 class="text-2xl font-bold">
                {{ $targetDate }} : Result Listing
            </h1>
            <button type="submit" id="btn-download-result"
                class="p-3 font-bold bg-indigo-500 rounded-md hover:scale-105 transition">Download</button>
        </div>
    </form>


    {{-- meetings & events grid :D --}}
    <div class="grid grid-cols-3 gap-5">
        @foreach ($meetings as $meeting)
            <div name="meeting-card" class="p-3 rounded-lg bg-slate-800 border-4 border-slate-600">
                <div class="flex justify-between items-center">
                    <h3 class="text-2xl font-bold">{{ $meeting->name }}</h3>
                    <input type="checkbox" name="meetings[]" class="scale-150" value="{{ $meeting->id }}" checked />
                </div>

                <div class="w-full bg-slate-600 h-0.5 my-2"></div>

                @foreach ($meeting->getRelations()['events'] as $event)
                    <div class="flex justify-between">
                        <div class="flex justify-between w-3/4">
                            <div class="">
                                <span>({{ $event->num }})</span>
                                <span>{{ $event->name }}</span>
                            </div>
                            <span>{{ $event->time->format('g.i') }}</span>
                        </div>
                        <span>
                            <input type="checkbox" name="events[]" class="meeting-{{ $meeting->id }}-event"
                                value="{{ $event->id }}" checked />
                        </span>
                    </div>
                @endforeach

            </div>
        @endforeach
    </div>

</div>
