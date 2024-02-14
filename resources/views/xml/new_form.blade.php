<!-- resources/views/xml/index.blade.php -->
@extends('layouts.app')

@section('content')
<h2>XML Files</h2>
<ul>
    @foreach ($xmlFiles as $file)
        <li>
            {{ $file }}
            <a href="{{ url("/xml/downloads/" . basename($file)) }}">Download</a>
        </li>
    @endforeach
</ul>
@endsection
