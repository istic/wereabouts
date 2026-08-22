@extends('layouts.app')

@push('head')
    @vite('resources/js/venue-map.js')
@endpush

@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-12 mb-4">
            <a href="{{ route('home') }}">&larr; Back to all venues</a>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12 mb-3">
            <h1 class="h4">Venue map</h1>
            @if($pending > 0)
                <p class="text-muted small mb-1">
                    @if($pending === 1)
                        1 venue is still being located &mdash; reload the page in a moment to see it.
                    @else
                        {{ $pending }} venues are still being located &mdash; reload the page in a moment to see them.
                    @endif
                </p>
            @endif
            @if($unmapped > 0)
                <p class="text-muted small mb-0">
                    @if($unmapped === 1)
                        1 venue could not be placed on the map automatically.
                    @else
                        {{ $unmapped }} venues could not be placed on the map automatically.
                    @endif
                </p>
            @endif
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div id="venues-map" data-points='@json($points)' style="height: 70vh;"></div>
        </div>
    </div>
</div>
@endsection
