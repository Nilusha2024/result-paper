<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Aus Result Paper') }}
        </h2>
    </x-slot>

    <!DOCTYPE html>
    <html lang="en">

    <head>
        <meta charset="utf-8">

        <title>Result Paper Events List</title>
        <meta name="viewport" content="width=device-width, initial-scale=1">
    </head>
    <section class="flex flex-col md:flex-row h-screen items-center">

        <div class="bg-indigo-600 hidden lg:block w-full md:w-1/2 xl:w-2/3 h-screen">
            <img src="https://leisuresportsholdings.com/revolution/assets/slider-02/4fd9a-bg.jpg" alt=""
                class="w-full h-full object-cover">
        </div>

        <div
            class="bg-white w-full md:max-w-md lg:max-w-full md:mx-auto md:mx-0 md:w-1/2 xl:w-1/3 h-screen px-6 lg:px-16 xl:px-12
              flex items-center justify-center">

            <div class="w-full h-100">

                <h3 class="text-xl md:text-2xl font-bold leading-tight mt-12">Result Paper Events List</h3>
                <p class="text-muted mt-2 mb-5">click on the download button to generate the text
                    file</p>
                <form class="mt-6" action="{{ route('events.downloadAll') }}" method="post">
                    @csrf
                    <div class="form-group">
                        <label for="from_date" class="block text-gray-700">Select the Date:</label>
                        <input type="date"
                            class="w-full px-4 py-3 rounded-lg bg-gray-200 mt-2 border focus:border-blue-500 focus:bg-white focus:outline-none"
                            name="from_date" id="from_date">
                    </div>
                    <button type="submit"
                        class="w-full block bg-indigo-500 hover:bg-indigo-400 focus:bg-indigo-400 text-white font-semibold rounded-lg
                    px-4 py-3 mt-6">Download</button>
                </form>
            </div>
        </div>

    </section>

    </html>
</x-app-layout>
