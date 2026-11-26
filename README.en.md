# StPiGh Shopware Antispy

**Reduce automatic data transfers to Shopware and decide which external services your shop needs.**

This free, open-source plugin restricts telemetry, usage-data transfers and selected external connections in self-hosted Shopware 6.7 shops. Store and app connections are blocked by default, with optional exceptions.

[Deutsch](README.md) | English · [Technical details](docs/TECHNICAL-NOTES.md) · [Changelog](CHANGELOG.md) · [Report a problem](https://github.com/StefanPilzLtd/StPiGhShopwareAntispy/issues)

## Is this suitable for your shop?

Intended for operators who maintain their self-hosted shop independently and want to restrict the connections listed below. You need access to shop files, the command line and the Administration build process, or a technical service provider.

**Before using the plugin:**

- **Monitor updates yourself:** The Administration update check is blocked. “No update available” does not establish that your shop is current. Maintain core updates through your deployment/Composer process and follow the [official release notes](https://developer.shopware.com/release-notes/).
- **Check apps and integrations:** App registration, webhooks and action buttons may stop working. Installation and activation also deactivate active apps whose names, paths or translated labels match patterns including `analytics` or `gmv`. Third-party apps can be affected and will not be reactivated automatically.
- **Check contractual obligations:** Usage-data/GMV connections stay blocked even with exceptions. Review [reporting obligations and required services](#community-edition-accounts-and-contracts) before activation.
- **Test on staging first:** Check Administration, orders, payments, shipping, ERP and apps with your actual extension combination.

## What is restricted?

| Area | Behavior while active | Operational effect |
|---|---|---|
| Usage data and telemetry | Related subscribers, handlers and tasks are detached; the usage-data client returns local responses. | Automatic transfers through these paths are suppressed. |
| Shopware Services and Analytics | Registry communication is blocked; matching apps are deactivated on installation/activation. | Affected service and analytics features become unavailable. |
| Store and app system | Intercepted HTTP clients are blocked by default. | Store downloads, license requests and app connections need exceptions; other restrictions may still apply. |
| Administration browser | Direct external `fetch`, XHR and beacon calls are blocked; app iframes initially allow only the same origin. | External Administration integrations may be affected. |
| Core updates and translations | Administration update checks and the daily `translation.update` task are blocked. | Arrange updates and translation maintenance independently. |
| Shared Symfony HTTP client | Filters `shopware.com`, `shopware.io`, `shopware.de`, `shopware.store`, `fos.gg`, `cloudflare.com` and their subdomains. | Other plugins using this client may receive a local HTTP 403. The FroshTools system-time check is also disabled. |

**Protection is limited to the intercepted paths.** This is not a firewall for the entire shop. Independent HTTP clients, redirects and other browser resources are not fully controlled. External images and scripts are not generally blocked. Storefront tracking and cookie consent are outside its scope. These measures do not establish GDPR compliance or complete prevention of data transfers.

## Requirements and verification status

- Self-hosted **Shopware 6.7.x** with Administration; not an installation option for Shopware SaaS.
- The [plugin metadata](composer.json) allows PHP **8.2 and later PHP 8 versions**. Your exact Shopware release and its dependencies impose additional requirements. This does not establish a tested PHP version matrix.
- The [1.2.0 changelog](CHANGELOG.md#120---2026-11-17) records a previous network check with **Shopware 6.7.16.0**. It does not establish compatibility with every 6.7 release or extension combination.
- The repository includes Administration JavaScript source, without precompiled Administration assets. **An Administration build is required.**

## Install from source

Use a test environment first. Have a current backup and record active apps and custom Store API/task settings: the plugin does not save a complete snapshot for rollback.

1. From the **Shopware project root** containing `bin/console`:

   ```sh
   git clone https://github.com/StefanPilzLtd/StPiGhShopwareAntispy.git custom/plugins/StPiGhShopwareAntispy
   ```

   Alternatively, extract the [source ZIP](https://github.com/StefanPilzLtd/StPiGhShopwareAntispy/archive/refs/heads/main.zip) so that `custom/plugins/StPiGhShopwareAntispy/composer.json` exists. GitHub's source ZIP is not a prebuilt Shopware upload package.

2. Register and install. **Installation itself can already change app states and settings.**

   ```sh
   bin/console plugin:refresh
   bin/console plugin:install --activate StPiGhShopwareAntispy
   bin/console cache:clear
   ```

3. Rebuild the Administration using your shop's build process. For projects with the standard script:

   ```sh
   bin/build-administration.sh
   ```

   This needs PHP and the Node.js/npm environment required by your shop. Use the existing build pipeline for managed deployments. Fully reload the Administration in your browser afterward. Without the build, the JavaScript protection is missing.

## Configure exceptions

Open **Extensions → My extensions → StPiGh Shopware Antispy → Configuration** and configure **all sales channels**. Server-side checks read the global configuration.

| Setting | Effect and limits |
|---|---|
| **Allow Shopware Store communication** | Allows the decorated Store, download and First Run Wizard clients. Sets `core.store.apiUri` to `https://api.shopware.com`; disabling it sets `http://127.0.0.1`. The separate Symfony host filter, Administration update check, usage-data and service restrictions remain. `tracking/events` requests through the allowed Store client are also forwarded. |
| **Allowed app server origins** | Allows individual origins for the app-system client and app iframes, such as `https://apps.example.com`. Separate entries with spaces, commas or line breaks. Scheme, host and explicitly specified port must match; subdomains need their own entries. Paths do not restrict an exception. |
| **Allow app server communication** | Allows the app-system client generally and permits HTTPS app iframes. An individual origin list is narrower. Direct external browser calls from the Administration remain blocked in either case. |

Client exceptions are read at runtime and do not require another Administration build. Reload the Administration after changes so that updated iframe permissions apply. Unreadable configuration keeps configurable clients blocked. Exceptions do not reactivate apps or restore detached tasks or handlers.

## Check after installation

1. Open the Administration and extension manager. The `X-StPiGh-Shopware-Antispy: active` response header confirms the Administration subscriber, not complete network blocking.
2. Reload and exercise relevant workflows with your browser's **Network** panel open. Local responses can appear successful; HTTP 200 alone does not confirm an external connection.
3. Check app states, webhooks and a complete order including payment/shipping integrations. Add required origins selectively and retest.
4. Inspect outbound **server traffic** using hosting/network tools where needed. Browser tools cannot show server-side connections.

For integration problems, check the [technical limits](docs/TECHNICAL-NOTES.md) or deactivate the plugin.

## Deactivate and uninstall

From the Shopware project root:

```sh
bin/console plugin:deactivate StPiGhShopwareAntispy
bin/console cache:clear
bin/build-administration.sh
```

Use your deployment's Administration build if different, then fully reload the browser. To uninstall afterward:

```sh
bin/console plugin:uninstall StPiGhShopwareAntispy
```

Both deactivation and uninstallation set `core.store.apiUri` to the **default** `https://api.shopware.com`, delete `core.usageData.lastEntitySyncRunDate` and mark four affected tasks as `scheduled`, due immediately. **Previous custom values and previously inactive task states are not reconstructed.** Deactivated apps stay inactive. `--keep-user-data` does not skip this rollback in the uninstall hook. Review custom settings and reactivate needed apps individually.

## Community Edition, accounts and contracts

Shopware says Community Edition can operate without Account or Store services. Review the open-source license separately from contracts for additional services. This plugin does not cancel contracts, delete accounts or replace licenses or agreed GMV reports.

Shopware's [14 April 2026 GMV announcement](https://www.shopware.com/de/news/automatisiertes-gmv-reporting/) excludes CE users without Account/Store usage from automatic tracking and describes reporting obligations for connected CE shops and plan customers. Resolve applicable obligations before activation: this plugin blocks those connections regardless of Store exceptions.

When moving away from an Account, inventory contracts, terms and extension licenses; obtain confirmation of termination/account closure from Shopware; arrange updates and replacement services. Logging out or blocking traffic does not terminate contracts. Purchases and rentals have different usage rights.

Sources checked **26 November 2026**: [Fair Usage FAQ](https://www.shopware.com/de/news/fair-usage-policy-community-edition/), [GMV reporting](https://www.shopware.com/de/news/automatisiertes-gmv-reporting/), [Shopware terms, particularly sections 4, 9, 10 and 22](https://www.shopware.com/de/agb/). Your applicable agreements govern; this overview is not individual legal advice.

## Help and development

An independent community project by **Stefan Pilz**, unaffiliated with Shopware. Free under the [MIT license](LICENSE), without a warranty of suitability for every shop configuration.

[Report problems](https://github.com/StefanPilzLtd/StPiGhShopwareAntispy/issues) with plugin, Shopware and PHP versions, affected integration and reproduction steps. Remove credentials, customer data and private logs before posting.

For setup, custom Shopware plugins, themes and integrations: [stefanpilz.ltd](https://stefanpilz.ltd) · [plugins@stefanpilz.ltd](mailto:plugins@stefanpilz.ltd) · [GitHub](https://github.com/StefanPilzLtd).
