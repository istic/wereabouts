// Shared between venue-filter.js (the venue listing's cards) and
// venue-map.js (the all-venues map's markers), so both pages filter by
// exactly the same rules against exactly the same fields.

const FILTER_IDS = [
    'filter-name',
    'filter-location',
    'filter-capacity',
    'filter-status',
    'filter-public-transport',
    'filter-disabled-bathrooms',
    'filter-step-free',
];

function normalize(value) {
    return value.trim().toLowerCase();
}

// Returns null when the filter bar isn't present on this page.
export function readFilterState() {
    const nameInput = document.getElementById('filter-name');
    if (!nameInput) {
        return null;
    }

    return {
        name: normalize(nameInput.value),
        location: normalize(document.getElementById('filter-location').value),
        minCapacity: Number.parseFloat(document.getElementById('filter-capacity').value),
        status: document.getElementById('filter-status').value,
        publicTransport: document.getElementById('filter-public-transport').checked,
        disabledBathrooms: document.getElementById('filter-disabled-bathrooms').checked,
        stepFree: document.getElementById('filter-step-free').checked,
    };
}

/**
 * @param venue {{name: string, location: ?string, capacity: number, open: boolean, publicTransport: boolean, disabledBathrooms: boolean, stepFree: boolean}}
 * @param filters return value of readFilterState()
 */
export function matchesFilters(venue, filters) {
    if (filters.name && !venue.name.toLowerCase().includes(filters.name)) {
        return false;
    }

    if (filters.location && !(venue.location || '').toLowerCase().includes(filters.location)) {
        return false;
    }

    if (!Number.isNaN(filters.minCapacity) && venue.capacity < filters.minCapacity) {
        return false;
    }

    if (filters.status === 'open' && !venue.open) {
        return false;
    }

    if (filters.status === 'closed' && venue.open) {
        return false;
    }

    if (filters.publicTransport && !venue.publicTransport) {
        return false;
    }

    if (filters.disabledBathrooms && !venue.disabledBathrooms) {
        return false;
    }

    if (filters.stepFree && !venue.stepFree) {
        return false;
    }

    return true;
}

// Calls onChange whenever any filter input changes; a no-op if the filter
// bar isn't present on this page.
export function bindFilterInputs(onChange) {
    for (const id of FILTER_IDS) {
        const el = document.getElementById(id);
        if (!el) {
            return;
        }

        const eventName = el.tagName === 'SELECT' || el.type === 'checkbox' ? 'change' : 'input';
        el.addEventListener(eventName, onChange);
    }
}

// Calls onReset (after resetting the inputs themselves) when the reset
// button is clicked; a no-op if the filter bar isn't present on this page.
export function bindFilterReset(onReset) {
    const resetButton = document.getElementById('filter-reset');
    if (!resetButton) {
        return;
    }

    resetButton.addEventListener('click', () => {
        document.getElementById('filter-name').value = '';
        document.getElementById('filter-location').value = '';
        document.getElementById('filter-capacity').value = '';
        document.getElementById('filter-status').value = 'open';
        document.getElementById('filter-public-transport').checked = false;
        document.getElementById('filter-disabled-bathrooms').checked = false;
        document.getElementById('filter-step-free').checked = false;
        onReset();
    });
}
