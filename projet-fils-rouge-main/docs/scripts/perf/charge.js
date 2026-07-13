// ===========================================================================
// Test de charge k6 — Site web Cyna
//
// Usage :
//   BASE_URL=http://localhost:8080 k6 run docs/scripts/perf/charge.js
//   # via Docker :
//   docker run --rm -i grafana/k6 run - < docs/scripts/perf/charge.js
//
// Adapter les chemins aux routes réelles avant exécution.
// Ne jamais tirer de charge sur la production sans fenêtre planifiée.
// ===========================================================================
import http from 'k6/http';
import { check, sleep, group } from 'k6';

const BASE = __ENV.BASE_URL || 'http://localhost:8080';

export const options = {
  scenarios: {
    charge_nominale: {
      executor: 'ramping-vus',
      startVUs: 0,
      stages: [
        { duration: '1m', target: 50 }, // montée
        { duration: '5m', target: 50 }, // palier
        { duration: '1m', target: 0 },  // descente
      ],
    },
  },
  thresholds: {
    http_req_duration: ['avg<2000', 'p(95)<3000'], // < 2 s moyen, < 3 s P95
    http_req_failed: ['rate<0.01'],                 // < 1 % d'erreurs
  },
};

export default function () {
  group('S1 - Navigation', () => {
    const home = http.get(`${BASE}/`);
    check(home, { 'accueil 200': (r) => r.status === 200 });
    sleep(1);
    const cat = http.get(`${BASE}/categories`);
    check(cat, { 'categories 200': (r) => r.status === 200 });
    sleep(1);
  });

  group('S2 - Recherche', () => {
    const res = http.get(`${BASE}/recherche?q=soc`);
    check(res, { 'recherche 200': (r) => r.status === 200 });
    sleep(1);
  });
}
