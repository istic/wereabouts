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
            <p id="map-status" class="text-muted small mb-0" role="status" aria-live="polite"></p>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div
                id="venues-map"
                data-points-url="{{ route('map.points') }}"
                data-tile-url="{{ config('app.map_tile_url_template') }}"
                data-tile-attribution="{{ config('app.map_tile_attribution') }}"
                style="height: 70vh;"
            ></div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <ul id="map-unmapped" class="text-muted small mt-3"></ul>
        </div>
    </div>
</div>
@endsection
