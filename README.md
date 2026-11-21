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

## Operating Shopware Community Edition without a Shopware Account

**Shopware 6 Community Edition (CE) can be operated independently of the Shopware Account and Extension Store.** Shopware explicitly confirms this in its [Fair Usage Policy FAQ](https://www.shopware.com/de/news/fair-usage-policy-community-edition/). The CE is licensed under the [MIT license](https://opensource.org/licenses/MIT) (see section 22.1 of the [Shopware terms](https://www.shopware.com/de/agb/)). A Shopware Account is **not a technical or licensing prerequisite** for running the open-source CE.

This matters because **a license to use the CE and a contract to use Shopware's Account/Store services are distinct**:

| Situation | What it means |
|---|---|
| CE without an Account or Store usage | The open-source shop remains operable. Shopware explicitly says these CE users are excluded from automatic GMV tracking. |
| CE connected to a Shopware Account and using its services | Contractual Fair Usage / GMV reporting rules can apply; blocking network traffic does **not** terminate these obligations. |
| CE above EUR 1 million GMV in the last 365 days | Shopware's terms (section 4.2) explicitly envisage CE use **without** an Account. Account/Store access may require a paid plan. |
| Previously purchased extensions | Shopware says one-time-purchase extensions remain usable, subject to their license terms; Store updates are no longer accessible without an Account. |
| Rented / subscription extensions | Existing rental contracts must be reviewed and terminated separately. Continued use after expiry is not authorized merely because the CE remains usable. |
| Core maintenance | CE core updates can still be performed independently (for example via Composer); the operator remains responsible for security and updates. |

Sources: [Shopware Fair Usage Policy FAQ (12 March 2025)](https://www.shopware.com/de/news/fair-usage-policy-community-edition/), [Shopware GMV reporting update (14 April 2026)](https://www.shopware.com/de/news/automatisiertes-gmv-reporting/), [Shopware Terms (updated 10 June 2026), §§ 4, 9, 10, 22, 55](https://www.shopware.com/de/agb/).

### Closing a Shopware Account: practical checklist

1. **Check contracts first:** Identify any Rise/Evolve/Beyond plan, extension rentals, third-party app agreements, licenses and paid services. Account closure does not automatically end independent contracts.
2. **Give notice to Shopware:** For contracts of indefinite duration, section 10.1 of the terms generally states **one month's notice to the end of a calendar month**, unless other agreed terms apply. Section 10.5 requires written notice; section 1.4 expressly allows electronic written declarations without a handwritten signature unless the stricter statutory *Schriftform* is required.
3. **Request the exact termination date and confirmation:** Shopware's [Community Customer FAQ](https://docs.shopware.com/de/shopware-account/faqs/communitycustomer-faq/) directs Account deletion/closure requests to its sales contact form. Where prior bookings exist, legal retention obligations may mean an Account can only be blocked rather than fully deleted.
4. **Review extensions and integrations:** Verify which licensed plugins and app backends must be replaced, removed or maintained independently before removing the Account connection.
5. **Only then configure network restrictions:** After the relevant agreements have actually ended, review which Shopware services the shop no longer needs and test administration, checkout, updates, third-party integrations, scheduled tasks and staging/production behavior.

### Important legal and technical limitations

- **This plugin is not an Account-cancellation or contract-termination mechanism.** It must not be used to misrepresent compliance with existing reporting, license or subscription obligations. Shopware's 2026 GMV article specifically excludes **CE customers who do not use the Account or Store** from automatic tracking; this should not be generalized to customers who still have relevant contracts.
- **This version blocks GMV/usage-data communication unconditionally.** Operators with a connected Account or other applicable contractual obligations should not activate it without resolving those obligations first.
- **The plugin also blocks Shopware's Administration update check**, Store communication and numerous cross-origin calls. **Monitor Shopware security releases independently** and test third-party extensions carefully; enabling one exception does not guarantee that every integration is restored.
- **Deactivation does not automatically reactivate apps disabled during installation.** Review extension states and scheduled tasks before and after removal.
- These notes summarize public Shopware documentation, **not individual legal advice**. The operator is responsible for verifying the terms applicable to their Account, subscriptions and extensions.

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
