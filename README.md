# StPiGh Shopware Antispy

Blocks Shopware telemetry, usage data, store/tracking clients, product analytics, GMV/analytics apps and third‑party calls from the Administration.

**Fail‑closed by design: everything is blocked by default.** Individual channels with a legitimate use can be re‑enabled per plugin configuration (opt‑in).

![Shopware](https://img.shields.io/badge/Shopware-6.7-189eff)
![PHP](https://img.shields.io/badge/PHP-8.2%20–%208.5-777bb4)
![License](https://img.shields.io/badge/license-MIT-green)

## Always blocked (not configurable)

- Usage Data / entity sync (GMV, orders, catalogue sizes, …)
- Telemetry (plugin, app, cache, message queue, DAL telemetry)
- Product Analytics / analytics gateway
- Service Registry & Shopware service apps (deactivated on install)
- Tracking events of the store client
- Cross‑origin requests from the Administration (fetch / XHR / sendBeacon)

## Configurable exceptions (default: off)

*Extensions → My extensions → StPiGh Shopware Antispy → Configuration*:

| Setting | Effect when enabled |
|---|---|
| **Allow Shopware store communication** | Makes `api.shopware.com` reachable again: update notifications, licence checks, store downloads, First Run Wizard. `core.store.apiUri` is restored automatically (and parked back to `http://127.0.0.1` when disabled). Note: extension icons then come from the store CDN (AWS) again. |
| **Allow app server communication** | Outgoing app‑system requests (registration, webhooks, action buttons) and app modules (iframes) in the Administration. Needed when the shop uses third‑party apps with their own backend. |
| **Allowed app server origins** | Exact, comma/newline‑separated origins such as `https://apps.example.com`. Allows only these app servers while general app‑server communication stays blocked. |

Switches take effect immediately (runtime check, fail‑closed) — no `cache:clear` needed. If the configuration cannot be read, the request is blocked.

## Requirements

- Shopware 6.7
- PHP 8.2 – 8.5

## Installation

```bash
bin/console plugin:refresh
bin/console plugin:install --activate StPiGhShopwareAntispy
bin/console cache:clear
```

## Deactivate / uninstall

On deactivate or uninstall the shop state is restored: `core.store.apiUri` back to `https://api.shopware.com`, blocked scheduled tasks re‑enabled, `core.usageData.lastEntitySyncRunDate` removed. Deactivated service apps are intentionally **not** re‑enabled automatically.

## About the author

Built and maintained by **Stefan Pilz** — freelance **Shopware developer**.

I develop **custom Shopware 6 plugins & themes on commission**, plus API integrations, performance optimization, SEO/GEO and ongoing shop maintenance. Available for freelance projects and long‑term collaborations.

- 🌐 **Website:** [stefanpilz.ltd](https://stefanpilz.ltd)
- ✉️ **Contact:** [contact@stefanpilz.ltd](mailto:contact@stefanpilz.ltd)
- 🧩 **Plugin & project inquiries:** [plugins@stefanpilz.ltd](mailto:plugins@stefanpilz.ltd)
- 🐙 **GitHub:** [@StefanPilzLtd](https://github.com/StefanPilzLtd)

If this plugin is useful to you, a ⭐ on GitHub is appreciated.

## License

MIT — see [LICENSE](LICENSE).
