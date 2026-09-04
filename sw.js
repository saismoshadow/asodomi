/* ============================================================
   ASODOMI – Service Worker (PWA)
   - Pagine: rete prima, cache come riserva, offline.html al peggio
   - Risorse statiche: cache prima + aggiornamento in background
   ============================================================ */
'use strict';

const VERSION = 'asodomi-v1';
const OFFLINE_URL = '/offline.html';

// Risorse precaricate all'installazione
const PRECACHE = [
    OFFLINE_URL,
    '/assets/css/styles.css',
    '/assets/js/main.js',
    '/assets/img/logo.png',
    '/assets/img/icon-192.png',
    '/assets/img/icon-512.png',
];

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(VERSION)
            .then((cache) => cache.addAll(PRECACHE))
            .then(() => self.skipWaiting())
    );
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys()
            .then((keys) => Promise.all(
                keys.filter((k) => k !== VERSION).map((k) => caches.delete(k))
            ))
            .then(() => self.clients.claim())
    );
});

self.addEventListener('fetch', (event) => {
    const req = event.request;

    // Solo GET nello stesso dominio
    if (req.method !== 'GET' || new URL(req.url).origin !== self.location.origin) {
        return;
    }

    // Navigazioni tra pagine: rete prima, poi cache, poi offline
    if (req.mode === 'navigate') {
        event.respondWith(
            fetch(req)
                .then((res) => {
                    const copy = res.clone();
                    caches.open(VERSION).then((c) => c.put(req, copy));
                    return res;
                })
                .catch(() =>
                    caches.match(req, { ignoreSearch: true })
                        .then((hit) => hit || caches.match(OFFLINE_URL))
                )
        );
        return;
    }

    // Statici: cache subito + aggiornamento in background
    if (/\/assets\/.+\.(css|js|png|svg|jpg|webp|woff2?)$/i.test(new URL(req.url).pathname)) {
        event.respondWith(
            caches.match(req, { ignoreSearch: true }).then((hit) => {
                const network = fetch(req)
                    .then((res) => {
                        if (res.ok) {
                            const copy = res.clone();
                            caches.open(VERSION).then((c) => c.put(req, copy));
                        }
                        return res;
                    })
                    .catch(() => hit);
                return hit || network;
            })
        );
    }
});
