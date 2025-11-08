import * as L from 'leaflet';
import { GestureHandling } from 'leaflet-gesture-handling';
import 'leaflet/dist/leaflet.css';
import 'leaflet-gesture-handling/dist/leaflet-gesture-handling.css';

const markerIcons = [
    L.divIcon({
        iconSize: [ 36, 36 ],
        iconAnchor: [ 18, 36 ],
        popupAnchor: [ 0, -18 ],
        className: 'marker',
    }),
    L.divIcon({
        iconSize: [ 36, 36 ],
        iconAnchor: [ 18, 36 ],
        popupAnchor: [ 0, -18 ],
        className: 'marker -colourtwo',
    }),
    L.divIcon({
        iconSize: [ 36, 36 ],
        iconAnchor: [ 18, 36 ],
        popupAnchor: [ 0, -18 ],
        className: 'marker -colourthree',
    }),
];

L.Map.addInitHook('addHandler', 'gestureHandling', GestureHandling);

export default function(container: HTMLElement): void {
    let points: Point[] = [];
    let routes: RoutePoint[][] = [];

    const pointsSelector = container.dataset.points;
    if (pointsSelector !== undefined) {
        const pointsData = document.querySelector(pointsSelector);
        if (pointsData !== null) {
            points = JSON.parse(pointsData.textContent);
        }
    }

    const routesSelector = container.dataset.routes;
    if (routesSelector !== undefined) {
        const routesData = document.querySelector(routesSelector);
        if (routesData !== null) {
            routes = JSON.parse(routesData.textContent);
        }
    }

    const centre = JSON.parse(container.dataset.centre ?? '[ 0, 0 ]');
    const zoom = parseInt(container.dataset.zoom ?? '5', 10);

    // @ts-ignore gestureHandling comes from the leaflet-gesture-handling plugin
    const map = L.map(container, { gestureHandling: true }).setView(centre, zoom);
    L.tileLayer('/map-tiles/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright" target="_blank">OpenStreetMap</a> contributors'
    }).addTo(map);

    points.forEach(point => {
        let content = '<h5>' + point.title + '</h5>';
        if (point.caption !== undefined) {
            content += '<p>' + point.caption + '</p>';
        }

        L.marker([ point.lat, point.lng ], { icon: markerIcons[(point.colour ?? 1) - 1] })
            .bindPopup(content, { className: 'popup' })
            .addTo(map)
        ;
    });

    routes.forEach(route => {
        const polyline = L.polyline(route, { color: 'red' }).addTo(map);
        map.fitBounds(polyline.getBounds());
    });
}

interface Point {
    lat: number;
    lng: number;
    title: string;
    caption?: string;
    colour?: number;
}

interface RoutePoint {
    lat: number;
    lng: number;
    alt: number;
    when: string;
}
