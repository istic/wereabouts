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

function initVenuesMap() {
    const container = document.getElementById('venues-map');
    if (!container) {
        return;
    }

    const points = JSON.parse(container.dataset.points || '[]');

    const map = L.map(container);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
    }).addTo(map);

    if (points.length === 0) {
        map.setView(UK_CENTRE, 5);

        return;
    }

    const markers = points.map((point) => {
        const marker = L.marker([point.lat, point.lng], {
            opacity: point.open ? 1 : 0.55,
        }).addTo(map);

        marker.bindPopup(buildPopupContent(point));

        return marker;
    });

    map.fitBounds(L.featureGroup(markers).getBounds().pad(0.15));
}

document.addEventListener('DOMContentLoaded', initVenuesMap);
