<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Aus Result Paper') }}
        </h2>
    </x-slot>


    <!DOCTYPE html>
    <html lang="en">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta http-equiv="X-UA-Compatible" content="ie=edge">
        <title>Feeds</title>

        {{-- temp fonts --}}
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,600&display=swap" rel="stylesheet" />

        {{-- temp styles : move this sometime later --}}
        <style>
            html {
                --tw-bg-opacity: 1;
                line-height: 1.5;
                font-family: Figtree, sans-serif;
            }

            body {
                color: whitesmoke;
                background-image: url("data:image/svg+xml,%3Csvg width='30' height='30' viewBox='0 0 30 30' fill='none' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M1.22676 0C1.91374 0 2.45351 0.539773 2.45351 1.22676C2.45351 1.91374 1.91374 2.45351 1.22676 2.45351C0.539773 2.45351 0 1.91374 0 1.22676C0 0.539773 0.539773 0 1.22676 0Z' fill='rgba(255,255,255,0.07)'/%3E%3C/svg%3E");
                background-color: rgb(17 24 39 / var(--tw-bg-opacity))
            }

            #feeds {
                background-color: #1a202e8d;
                border: 1px solid #262b39;
                border-radius: 10px;
                width: 90%;
                margin: 2rem auto;
                padding: 0.5rem 2rem;

            }

            .feed {
                box-sizing: border-box;
                background: #262b39;
                border: 1px solid #262b39;
                border-radius: 10px;
                width: 100%;
                padding: 1rem 2rem;
                padding-top: 0;
            }

            .feed-grid {
                display: grid;
                grid-template-columns: repeat(2, 1fr);
                gap: 1rem;
                margin: 1rem 0;
            }

            .btn {
                border: none;
                border-radius: 5px;
                cursor: pointer;
                text-transform: uppercase;
                font-weight: bold;
                font-family: Figtree, sans-serif;
                letter-spacing: 0.5px;
                padding: 10px 20px;
                margin: 0.5rem 0;
                transition: 0.1s;
            }

            p {
                color: #9da3ae;
            }

            .feed>p {
                width: 75%;
            }

            .btn:hover {
                transform: scale(105%);
            }

            .btn-primary {
                color: white;
                background-color: #5a65ea;
            }

            .success-text-sm {
                color: #5a65ea;
                font-size: 0.75rem;
                margin: 5px 0;
            }

            .error-text-sm {
                color: #dd524c;
                font-size: 0.75rem;
                margin: 5px 0;
            }

            .divider {
                background: #262b39;
                height: 2px;
                width: 100%;
            }
        </style>

    </head>

    <body>

        <div id="feeds">
            <h1>Result Paper - Feeds</h1>

            <div class="divider"></div>

            <div class="feed-grid">

                {{-- Aussie feed --}}
                <div class="feed">
                    <h3>Aussie feed</h3>
                    <p>Reads the Aussie current day race results XML from the feed and stores it in the
                        database. This is
                        mandatory before downloading the text files</p>

                    <form action="{{ route('store_aussie_feed') }}" method="POST">
                        @csrf
                        @method('post')
                        <button type="submit" id="btn-aussie-feed-download" class="btn btn-primary">
                            DOWNLOAD
                        </button>
                    </form>

                    @if (session('aussie_download_status'))
                        @if (session('aussie_download_status') == 1)
                            <p class="success-text-sm">
                                Successfully downloaded! Time elapased: {{ session('aussie_execution_time') }} seconds
                            </p>
                        @else
                            <p class="error-text-sm">
                                Download failed!
                            </p>
                        @endif
                    @endif
                </div>

                {{-- English feed --}}
                <div class="feed">
                    <h3>English feed</h3>
                    <p>Reads the English current day race results XML from the feed and stores it in the
                        database. This is
                        mandatory before downloading the text files</p>

                    <form action="{{ route('store_english_feed') }}" method="POST">
                        @csrf
                        @method('post')
                        <button type="submit" id="btn-english-feed-download" class="btn btn-primary">
                            DOWNLOAD
                        </button>
                    </form>

                    @if (session('english_download_status'))
                        @if (session('english_download_status') == 1)
                            <p class="success-text-sm">
                                Successfully downloaded! Time elapased: {{ session('english_execution_time') }} seconds
                            </p>
                        @else
                            <p class="error-text-sm">
                                Download failed!
                            </p>
                        @endif
                    @endif
                </div>


            </div>


        </div>


    </body>

    </html>
</x-app-layout>
