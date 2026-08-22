import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import markerIcon2x from 'leaflet/dist/images/marker-icon-2x.png';
import markerIcon from 'leaflet/dist/images/marker-icon.png';
import markerShadow from 'leaflet/dist/images/marker-shadow.png';

// Leaflet's default marker icon URLs assume a classic (non-bundled) asset
// layout; point it at the versioned URLs Vite generated for these images.
delete L.Icon.Default.prototype._getIconUrl;
L.Icon.Default.mergeOptions({
    iconRetinaUrl: markerIcon2x,
    iconUrl: markerIcon,
    shadowUrl: markerShadow,
});

const UK_CENTRE = [54.5, -3];
const SINGLE_VENUE_ZOOM = 15;
const POLL_INTERVAL_MS = 4000;
const DEFAULT_TILE_URL = 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png';
const DEFAULT_TILE_ATTRIBUTION =
    '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors';

function buildPopupContent(point) {
    const wrapper = document.createElement('div');

    const link = document.createElement('a');
    link.href = point.url;
    link.textContent = point.name;
    wrapper.appendChild(link);

    if (!point.open) {
        const status = document.createElement('span');
        status.className = 'text-muted';
        status.textContent = ' (closed)';
        wrapper.appendChild(status);
    }

    return wrapper;
}

function statusText(unmapped, pending) {
    const parts = [];

    if (pending > 0) {
        parts.push(pending === 1 ? '1 venue is still being located…' : `${pending} venues are still being located…`);
    }

    if (unmapped > 0) {
        parts.push(
            unmapped === 1
                ? '1 venue could not be placed on the map automatically.'
                : `${unmapped} venues could not be placed on the map automatically.`,
        );
    }

    return parts.join(' ');
}

function renderMarkers(map, markerLayer, points) {
    markerLayer.clearLayers();

    if (points.length === 0) {
        return;
    }

    const markers = points.map((point) => {
        const marker = L.marker([point.lat, point.lng], {
            opacity: point.open ? 1 : 0.55,
        }).bindPopup(buildPopupContent(point));

        markerLayer.addLayer(marker);

        return marker;
    });

    if (markers.length === 1) {
        map.setView([points[0].lat, points[0].lng], SINGLE_VENUE_ZOOM);
    } else {
        map.fitBounds(L.featureGroup(markers).getBounds().pad(0.15));
    }
}

async function fetchPoints(url) {
    const response = await fetch(url, { headers: { Accept: 'application/json' } });

    if (!response.ok) {
        throw new Error(`Failed to load map data (${response.status})`);
    }

    return response.json();
}

async function loadAndScheduleNext(map, markerLayer, statusEl, url) {
    let data;

    try {
        data = await fetchPoints(url);
    } catch {
        if (statusEl) {
            statusEl.textContent = 'Could not load the map data. Try reloading the page.';
        }

        return;
    }

    renderMarkers(map, markerLayer, data.points || []);

    if (statusEl) {
        statusEl.textContent = statusText(data.unmapped || 0, data.pending || 0);
    }

    if ((data.pending || 0) > 0) {
        setTimeout(() => loadAndScheduleNext(map, markerLayer, statusEl, url), POLL_INTERVAL_MS);
    }
}

function initVenuesMap() {
    const container = document.getElementById('venues-map');
    if (!container) {
        return;
    }

    const pointsUrl = container.dataset.pointsUrl;
    if (!pointsUrl) {
        return;
    }

    const statusEl = document.getElementById('map-status');

    const map = L.map(container).setView(UK_CENTRE, 5);
    const markerLayer = L.layerGroup().addTo(map);

    L.tileLayer(container.dataset.tileUrl || DEFAULT_TILE_URL, {
        maxZoom: 19,
        attribution: container.dataset.tileAttribution || DEFAULT_TILE_ATTRIBUTION,
    }).addTo(map);

    loadAndScheduleNext(map, markerLayer, statusEl, pointsUrl);
}

document.addEventListener('DOMContentLoaded', initVenuesMap);
