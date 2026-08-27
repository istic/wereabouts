@php
    $countId ??= 'filter-count';
    $countText ??= '';
@endphp
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
            <span id="{{ $countId }}" class="text-muted small" role="status" aria-live="polite" aria-atomic="true">{{ $countText }}</span>
            <button type="button" id="filter-reset" class="btn btn-outline-secondary btn-sm">Reset filters</button>
        </div>
    </div>
</div>
