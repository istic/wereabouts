import { bindFilterInputs, bindFilterReset, matchesFilters, readFilterState } from './venue-filters';

function cardToVenue(card) {
    return {
        name: card.dataset.name,
        location: card.dataset.location,
        capacity: Number.parseInt(card.dataset.capacity, 10),
        open: card.dataset.open === '1',
        publicTransport: card.dataset.publicTransport === '1',
        disabledBathrooms: card.dataset.disabledBathrooms === '1',
        stepFree: card.dataset.stepFree === '1',
    };
}

function initVenueFilters() {
    const list = document.getElementById('venue-list');
    if (!list) {
        return;
    }

    const cards = Array.from(list.querySelectorAll('.venue-card'));
    const emptyState = document.getElementById('venue-empty-state');
    const countLabel = document.getElementById('filter-count');

    function applyFilters() {
        const filters = readFilterState();
        let visibleCount = 0;

        for (const card of cards) {
            const visible = matchesFilters(cardToVenue(card), filters);
            card.classList.toggle('d-none', !visible);
            if (visible) {
                visibleCount += 1;
            }
        }

        emptyState.classList.toggle('d-none', visibleCount !== 0);
        countLabel.textContent = `Showing ${visibleCount} of ${cards.length} venues`;
    }

    bindFilterInputs(applyFilters);
    bindFilterReset(applyFilters);

    applyFilters();
}

document.addEventListener('DOMContentLoaded', initVenueFilters);
