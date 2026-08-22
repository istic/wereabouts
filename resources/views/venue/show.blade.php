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

    <div class="row justify-content-left">
        <div class="col-md-12 col-lg-8 col-xl-6">
            <div class="card">
                <div class="card-header">{{ $venue->name }}</div>

                <div class="card-body">
                    @include('venue._details', ['venue' => $venue])
                    @include('venue._map', ['venue' => $venue, 'points' => $points])
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
