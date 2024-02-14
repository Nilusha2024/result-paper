@extends('layouts.app')

@section('content')
    <h1>{{ $event->event_name }}</h1>
    <p>{{ $event->description }}</p>
    
    <h2>Competitors:</h2>
    <ul>
        @foreach ($event->competitors as $competitor)
            <li>
                {{ $competitor->name }} (Position: {{ $competitor->finish_position }})

                <!-- Display Competitor Prices -->
                <ul>
                    @foreach ($competitor->prices as $price)
                        <li>Price Type: {{ $price->price_type }} - Price: ${{ $price->odds }}</li>
                    @endforeach
                </ul>
            </li>
        @endforeach
    </ul>
    
    <h2>Dividends:</h2>
    <ul>
        @foreach ($event->dividends as $dividend)
            <li>Type: {{ $dividend->dividend_type }} - Amount: ${{ $dividend->dividend_amount }}</li>
        @endforeach
    </ul>
    
    <h2>Pools:</h2>
    <ul>
        @foreach ($event->pools as $pool)
            <li>Pool Type: {{ $pool->pool_type }} - Total: ${{ $pool->pool_total }}</li>
        @endforeach
    </ul>

    <a href="{{ route('events.index') }}">Back to All Events</a>
@endsection
