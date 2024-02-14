<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Xml Fils') }}
        </h2>
    </x-slot>

    <script>
       function redirectToUploadPage() {
            window.location.href = 'http://localhost/result-paper/public/upload';
        }
    </script>



    {{-- <div class="navbar">
        <h2 class="center-text">XML Files</h2>
        <div class="d-flex flex-row-reverse">
            <button type="button" class="btn btn-success" onclick="redirectToUploadPage()">Upload</button>
        </div>
    </div> --}}

    <div class="page-container text-white">
        <div class="list-container px-20 py-5">
            <ul>
                @foreach ($filteredFiles as $file)
                    <div class="d-flex justify-content-center">
                        <ol class="list-group">
                            <li class="bg-slate-800 p-5 mx-20 rounded-md flex justify-between items-center">
                                {{ basename($file) }}
                                <div class="flex gap-2">
                                    <a href="{{ url('/xml/bardownload/' . basename($file)) }}">
                                        <button type="button" class="bg-green-600 text-white p-2 rounded-md font-bold">Download</button>
                                    </a>
                                    <form action="{{ route('deleteBarForm') }}" method="POST">
                                        @csrf
                                        <input type="hidden" name="file_name" value="{{ basename($file) }}">
                                        <button type="submit" class="bg-red-600 text-white p-2 rounded-md font-bold">Delete File</button>
                                    </form>                                        
                                </div>
                            </li>
                            <br>
                        </ol>
                    </div>
                @endforeach
            </ul>
        </div>
    </div>

</x-app-layout>