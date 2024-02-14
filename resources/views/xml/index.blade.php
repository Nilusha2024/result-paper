<!-- resources/views/xml/index.blade.php -->
@extends('layouts.app')
<head>
    <style>
        body {
            background-image: url('https://images.pexels.com/photos/15716877/pexels-photo-15716877/free-photo-of-a-jockey-riding-a-horse-during-a-competition.jpeg');
            background-size: cover;
        }

        .list-group-item-info {
            width: 800px;
            overflow: hidden;
            white-space: nowrap;
            text-overflow: ellipsis;
        }
    </style>
</head>

@section('content')
    <br>
    <div class="container">
        <h2 class="text-center">XML Files</h2>
    </div>
    <br>
    <ul>
        @foreach ($xmlFiles as $file)
        <div class="d-flex justify-content-center">
            <ol class="list-group ">
                <li class="list-group-item list-group-item-info d-flex justify-content-between align-items-center">
                    {{ $file }}
                    <div class="d-flex flex-row-reverse">
                        <a href="{{ url('/xml/bardownload/' . basename($file)) }}">
                            <button type="button" class="btn btn-success">Download</button></a>
                    </div>
                </li>
                <br>
            </ol>
        </div>
        @endforeach
    </ul>
@endsection
