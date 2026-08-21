@extends('layouts.app')

@section('content')
<div class="container">

    <div class="row">
        <div class="col-md-12 alert alert-primary">
            <h1 class="text-center">Work In Progress!</h1>
            <p class="text-center">This is the first version of this project, with many things left to do.</p>
            <ul>
              <li>If you have a venue to add, please use the <a href="https://forms.gle/DVntrUyNNpKAoPzf9">submission form</a>.</li>
              <li> If you have a suggestion or bug to report, please use the <a href="https://github.com/istic/wereabouts/issues">issue tracker</a>.</li>
              <li>If you have a correction for the spreadsheet, please add it as a comment to the row on <a href="https://docs.google.com/spreadsheets/d/1p2CS7rxPWyMs7g4OHH287j6iIPGkZb3VA7VRLBr0okA">the master spreadsheet</a>.</li>
              <li>Don't see a site on the list and it's a public building? You might be able to find it through <a href="https://www.accessable.co.uk">AccessAble</a>.</li>
              <li>The spreadsheet this is based on is being maintained by Hazel Anneke Dixon.</li>
              <li>The site is being run by Aquarion</li>
            </ul>
        </div>
</div>
    <div class="row">
        <div class="col-md-12 mb-4">
            <div class="card" id="venue-filters">
                <div class="card-body">
                    <h2 class="h5 mb-3">Filter venues</h2>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label for="filter-name" class="form-label">Venue name</label>
                            <input type="search" id="filter-name" class="form-control" placeholder="Search by name&hellip;">
                        </div>
                        <div class="col-md-4">
                            <label for="filter-location" class="form-label">Location</label>
                            <input type="search" id="filter-location" class="form-control" placeholder="Search by location&hellip;">
                        </div>
                        <div class="col-md-4">
                            <label for="filter-capacity" class="form-label">Minimum capacity</label>
                            <input type="number" min="0" id="filter-capacity" class="form-control" placeholder="Any">
                        </div>
                        <div class="col-md-4">
                            <label for="filter-status" class="form-label">Status</label>
                            <select id="filter-status" class="form-select">
                                <option value="all">All venues</option>
                                <option value="open" selected>Open venues only</option>
                                <option value="closed">Closed venues only</option>
                            </select>
                        </div>
                        <div class="col-md-8 d-flex flex-wrap align-items-end column-gap-4">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="filter-public-transport">
                                <label class="form-check-label" for="filter-public-transport">Public transport nearby</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="filter-disabled-bathrooms">
                                <label class="form-check-label" for="filter-disabled-bathrooms">Accessible bathrooms</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="filter-step-free">
                                <label class="form-check-label" for="filter-step-free">Step-free access</label>
                            </div>
                        </div>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mt-3">
                        <span id="filter-count" class="text-muted small"></span>
                        <button type="button" id="filter-reset" class="btn btn-outline-secondary btn-sm">Reset filters</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row justify-content-left" id="venue-list">
      @foreach ($venues as $venue)
        <div class="col-md-12 mb-4 col-lg-6 col-xl-6 venue-card"
             data-name="{{ strtolower($venue->name) }}"
             data-location="{{ strtolower($venue->location ?? '') }}"
             data-capacity="{{ $venue->capacityCount }}"
             data-open="{{ $venue->open ? '1' : '0' }}"
             data-public-transport="{{ str_starts_with(strtolower(trim($venue->publicTransport ?? '')), 'y') ? '1' : '0' }}"
             data-disabled-bathrooms="{{ str_starts_with(strtolower(trim($venue->disabledBathrooms ?? '')), 'y') ? '1' : '0' }}"
             data-step-free="{{ str_starts_with(strtolower(trim($venue->stepFreeAccess ?? '')), 'y') ? '1' : '0' }}">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <a href="{{ route('venue.show', $venue->slug) }}">{{ $venue->name }}</a>
                    @unless ($venue->open)
                        <span class="badge bg-secondary">Closed</span>
                    @endunless
                </div>

                <div class="card-body">
                    @include('venue._details', ['venue' => $venue])
                </div>
            </div>
        </div>
      @endforeach
      <div id="venue-empty-state" class="col-md-12 text-center text-muted py-5 d-none">
          No venues match your filters.
      </div>
    </div>
</div>
@endsection
