@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-left">
      @foreach ($venues as $venue)
        <div class="col-md-12 mb-4 col-lg-6 col-xl-6">
            <div class="card">
                <div class="card-header">{{ $venue['Venue Name'] }}</div>

                <div class="card-body">
                  {{--
                      "Venue Name" => "Abney Scout and Guide Centre"
                      "Location" => "Cheadle, nr Stockport"
                      "Capacity" => "sleeps 32"
                      "Types of Spaces" => "Indoor bunks, hall, firepit"
                      "Public Transport" => "Yes"
                      "Step free access" => "All spaces"
                      "Disabled bathrooms?" => "No"
                      "Internet?" => "Good mobile data coverage"
                      "Kitchen" => ""
                      "Issues" => "No mould but the bedrooms are super dusty and the air quality is v low. Would not surprise me if there was black mould or similar hiding under beds."
                      "Further description of indoor spaces" => ""
                      "Aspects" => ""
                      "Price data (cost + data of recorded cost)" => "£570 for two nights, extra £50 for someone to clean it"
                        --}}
                  
                  
                    <div class="row">
                        <label for="Venue_Location" class="col-sm-5 col-form-label">Location</label>
                        <div class="col-sm-5">
                          <a id="Venue_Location" target="_blank" href="https://maps.google.com/?q={{ urlencode($venue['Venue Name'] . " " . $venue['Location']) }}">{{ $venue['Location'] }}</a>
                        </div>
                    </div>
                    <div class="row">
                        <label for="Venue_Capacity" class="col-sm-5 col-form-label">Capacity</label>
                        <div class="col-sm-5">
                          <span id="Venue_Capacity">{{ $venue['Capacity'] }}</span>
                        </div>
                    </div>
                    <div class="row">
                        <label for="Venue_Types_of_Spaces" class="col-sm-5 col-form-label">Types of Spaces</label>
                        <div class="col-sm-5">
                          <span id="Venue_Types_of_Spaces">{{ $venue['Types of Spaces'] }}</span>
                        </div>
                    </div>
                    <div class="row">
                        <label for="Venue_Public_Transport" class="col-sm-5 col-form-label">Public Transport</label>
                        <div class="col-sm-5">
                          <span id="Venue_Public_Transport">{{ $venue['Public Transport'] }}</span>
                        </div>
                    </div>
                    <div class="row">
                        <label for="Venue_Step_free_access" class="col-sm-5 col-form-label">Step free access</label>
                        <div class="col-sm-5">
                          <span id="Venue_Step_free_access">{{ $venue['Step free access'] }}</span>
                        </div>
                    </div>
                    <div class="row">
                        <label for="Venue_Disabled_bathrooms" class="col-sm-5 col-form-label">Disabled bathrooms?</label>
                        <div class="col-sm-5">
                          <span id="Venue_Disabled_bathrooms">{{ $venue['Disabled bathrooms?'] }}</span>
                        </div>
                    </div>
                    <div class="row">
                        <label for="Venue_Internet" class="col-sm-5 col-form-label">Internet?</label>
                        <div class="col-sm-5">
                          <span id="Venue_Internet">{{ $venue['Internet?'] }}</span>
                        </div>
                    </div>
                    <div class="row">
                        <label for="Venue_Kitchen" class="col-sm-5 col-form-label">Kitchen</label>
                        <div class="col-sm-5">
                          <span id="Venue_Kitchen">{{ $venue['Kitchen'] }}</span>
                        </div>
                    </div>
                    <div class="row">
                        <label for="Venue_Issues" class="col-sm-5 col-form-label">Issues</label>
                        <div class="col-sm-5">
                          <span id="Venue_Issues">{{ $venue['Issues'] }}</span>
                        </div>
                    </div>
                    <div class="row">
                        <label for="Venue_Further_description_of_indoor_spaces" class="col-sm-5 col-form-label">Further description of indoor spaces</label>
                        <div class="col-sm-5">
                          <span id="Venue_Further_description_of_indoor_spaces">{{ $venue['Further description of indoor spaces'] }}</span>
                        </div>
                    </div>
                    <div class="row">
                        <label for="Venue_Aspects" class="col-sm-5 col-form-label">Aspects</label>
                        <div class="col-sm-5">
                          <span id="Venue_Aspects">{!! auto_link($venue['Aspects']) !!}</span>
                        </div>
                    </div>
                    <div class="row">
                        <label for="Venue_Price_data" class="col-sm-5 col-form-label">Price data (cost + data of recorded cost)</label>
                        <div class="col-sm-5">
                          <span id="Venue_Price_data">{{ $venue['Price data (cost + data of recorded cost)'] }}</span>
                        </div>
                    </div>

                  {{-- @foreach ($venue as $key => $value)
                    @if ($key === 'data')
                      @continue
                    @endif
                    <div class="row">
                        <label for="{{ $key }}" class="col-sm-5 col-form-label">{{ ucfirst(str_replace('_', ' ', $key)) }}</label>
                        <div class="col-sm-5">
                          <input type="text" readonly class="form-control-plaintext" id="{{ $key }}" value="{{ $value }}">
                        </div>
                    </div>
                  @endforeach --}}
                  {{-- @php
                    dump($venue);
                  @endphp --}}
                </div>
            </div>
        </div>
      @endforeach
    </div>
</div>
@endsection
