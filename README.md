# StPiGh Shopware Antispy

Shopware-6.7-Plugin zum Blockieren von Shopware-Services, Usage Data,
Store-/Tracking-Clients, Product Analytics, GMV/Analytics-Apps und
Third-Party-Aufrufen aus der Administration.

**Default: alles blockiert.** Einzelne Kanäle mit legitimem Nutzen lassen
sich per Plugin-Konfiguration gezielt wieder erlauben (Opt-in).

## Was immer blockiert bleibt (nicht konfigurierbar)

- Usage Data / Entity Sync (GMV, Bestellungen, Kataloggrößen, ...)
- Telemetrie (Plugin-, App-, Cache-, MessageQueue-, DAL-Telemetry)
- Product Analytics / Analytics Gateway
- Service Registry & Shopware-Service-Apps (werden bei Installation deaktiviert)
- Tracking-Events des Store-Clients
- Cross-Origin-Requests aus der Administration (fetch/XHR/sendBeacon)

## Konfigurierbare Ausnahmen (Standard: aus)

Einstellungen unter *Erweiterungen → Meine Erweiterungen → StPiGh Shopware
Antispy → Konfiguration*:

| Schalter | Wirkung wenn aktiviert |
| --- | --- |
| Shopware-Store-Kommunikation erlauben | api.shopware.com wieder erreichbar: Update-Benachrichtigungen, Lizenzprüfungen, Store-Downloads, First Run Wizard. `core.store.apiUri` wird automatisch wiederhergestellt bzw. beim Ausschalten wieder auf `http://127.0.0.1` geparkt. Hinweis: Plugin-Icons in der Erweiterungsliste kommen dann wieder vom Store-CDN (AWS). |
| App-Server-Kommunikation erlauben | Ausgehende Requests des App-Systems (Registrierung, Webhooks, Action Buttons) und App-Module (iframes) in der Administration. Nötig, wenn der Shop Dritt-Apps mit eigenem Backend nutzt. |
| Erlaubte App-Server-Ursprünge | Exakte, getrennte Ursprünge wie `https://apps.example.com`. Erlaubt nur diese App-Server, während die allgemeine App-Server-Kommunikation blockiert bleibt. |

Die Schalter greifen sofort (Laufzeitprüfung, fail-closed) — kein
`cache:clear` nötig. Ist die Konfiguration nicht lesbar, wird blockiert.

## Deaktivieren / Deinstallieren

Beim Deaktivieren oder Deinstallieren wird der Shop-Zustand zurückgesetzt:
`core.store.apiUri` auf `https://api.shopware.com`, blockierte Scheduled
Tasks wieder aktiv, `core.usageData.lastEntitySyncRunDate` entfernt.
Deaktivierte Service-Apps werden bewusst **nicht** automatisch reaktiviert.

## Installation

```bash
bin/console plugin:refresh
bin/console plugin:install --activate StPiGhShopwareAntispy
bin/console cache:clear
bin/build-administration.sh
```
