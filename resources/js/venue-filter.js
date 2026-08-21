function normalize(value) {
    return value.trim().toLowerCase();
}

function initVenueFilters() {
    const list = document.getElementById('venue-list');
    if (!list) {
        return;
    }

    const cards = Array.from(list.querySelectorAll('.venue-card'));
    const emptyState = document.getElementById('venue-empty-state');
    const countLabel = document.getElementById('filter-count');

    const nameInput = document.getElementById('filter-name');
    const locationInput = document.getElementById('filter-location');
    const capacityInput = document.getElementById('filter-capacity');
    const statusSelect = document.getElementById('filter-status');
    const publicTransportCheckbox = document.getElementById('filter-public-transport');
    const disabledBathroomsCheckbox = document.getElementById('filter-disabled-bathrooms');
    const stepFreeCheckbox = document.getElementById('filter-step-free');
    const resetButton = document.getElementById('filter-reset');

    const inputs = [
        nameInput,
        locationInput,
        capacityInput,
        statusSelect,
        publicTransportCheckbox,
        disabledBathroomsCheckbox,
        stepFreeCheckbox,
    ];

    function matches(card) {
        const name = normalize(nameInput.value);
        if (name && !card.dataset.name.includes(name)) {
            return false;
        }

        const location = normalize(locationInput.value);
        if (location && !card.dataset.location.includes(location)) {
            return false;
        }

        const minCapacity = Number.parseInt(capacityInput.value, 10);
        if (!Number.isNaN(minCapacity) && Number.parseInt(card.dataset.capacity, 10) < minCapacity) {
            return false;
        }

        if (statusSelect.value === 'open' && card.dataset.open !== '1') {
            return false;
        }
        if (statusSelect.value === 'closed' && card.dataset.open !== '0') {
            return false;
        }

        if (publicTransportCheckbox.checked && card.dataset.publicTransport !== '1') {
            return false;
        }

        if (disabledBathroomsCheckbox.checked && card.dataset.disabledBathrooms !== '1') {
            return false;
        }

        if (stepFreeCheckbox.checked && card.dataset.stepFree !== '1') {
            return false;
        }

        return true;
    }

    function applyFilters() {
        let visibleCount = 0;

        for (const card of cards) {
            const visible = matches(card);
            card.classList.toggle('d-none', !visible);
            if (visible) {
                visibleCount += 1;
            }
        }

        if (emptyState) {
            emptyState.classList.toggle('d-none', visibleCount !== 0);
        }

        if (countLabel) {
            countLabel.textContent = `Showing ${visibleCount} of ${cards.length} venues`;
        }
    }

    for (const input of inputs) {
        const eventName = input.tagName === 'SELECT' || input.type === 'checkbox' ? 'change' : 'input';
        input.addEventListener(eventName, applyFilters);
    }

    if (resetButton) {
        resetButton.addEventListener('click', () => {
            nameInput.value = '';
            locationInput.value = '';
            capacityInput.value = '';
            statusSelect.value = 'open';
            publicTransportCheckbox.checked = false;
            disabledBathroomsCheckbox.checked = false;
            stepFreeCheckbox.checked = false;
            applyFilters();
        });
    }

    applyFilters();
}

document.addEventListener('DOMContentLoaded', initVenueFilters);
