# Technical scope and implementation notes

[Deutsch: Betreiberanleitung](../README.md) · [English: operator guide](../README.en.md)

These notes describe version 1.2.0's implementation. They are a code review, not a fresh end-to-end certification or a guarantee of complete outbound traffic suppression.

## Server-side interception

- [DisableShopwareSpyFeaturesPass](../src/DependencyInjection/CompilerPass/DisableShopwareSpyFeaturesPass.php) removes event, Messenger and scheduled-task tags from matching service IDs/classes. It does not delete the services or prevent every direct call to them. All tagged metric transport factories are detached, including third-party factories.
- [services.xml](../src/Resources/config/services.xml) decorates `shopware.store_client`, `shopware.frw_client`, `shopware.store_download_client` and `shopware.app_system.guzzle`. [BlockingGuzzleClient](../src/Http/BlockingGuzzleClient.php) forwards requests to the original client when an exception permits them. This includes Store tracking requests when the Store exception is enabled.
- The usage-data gateway and service-registry clients use [BlockingHttpClient](../src/Http/BlockingHttpClient.php). The compiler pass also injects this client into the core update API client. An empty version list makes the Administration report no available update; recovery-tool downloads through that client receive a local 404.
- [HostFilteringHttpClient](../src/Http/HostFilteringHttpClient.php) filters direct target hosts for callers of the shared Symfony `http_client`. Its domain list includes subdomains. It returns local 403 responses independently of Store/app exceptions. Nonmatching targets pass through.
- The host filter checks the URL supplied to `request()`, or a per-request `base_uri` for relative URLs. It does not validate every redirect destination or resolve every inherited client/base-URI option. It is not an egress security boundary.
- Code that opens its own Guzzle, cURL, socket or other independent connection can bypass these client replacements. The FroshTools system-time checker is specifically untagged because it creates its own connection.
- No dedicated plugin audit log or complete list of suppressed transfers is provided. Many blocked clients return synthetic HTTP 200 JSON responses; a successful status does not prove remote delivery.

## Browser protection and exceptions

[AdminPrivacySubscriber](../src/Subscriber/AdminPrivacySubscriber.php) sets `connect-src 'self'`, `frame-src 'self'` by default, `object-src 'none'` and `base-uri 'self'` on matching Administration responses. It also sets referrer and permissions policies, and clears selected registry/analytics URLs in the index.

The subscriber sets the Content-Security-Policy header rather than merging an existing policy. Review any additional CSP requirements imposed by the shop or reverse proxy. It does not restrict all image/script sources. Proxy caches can affect which header is actually delivered.

The built [Administration JavaScript](../src/Resources/app/administration/src/main.js) wraps `fetch`, XHR and `sendBeacon` for external origins and patches known Shopware telemetry functions. Malformed URLs are not rejected by the JavaScript URL check. The PHP response header does not establish that this JavaScript has been built or loaded.

App exceptions change iframe permissions and the decorated server-side app client. They do not relax `connect-src 'self'` or the JavaScript wrappers in the parent Administration. An allowed external iframe has its own browsing context; these wrappers do not control every connection inside it.

[AntispyConfig](../src/Service/AntispyConfig.php) normalizes app origins to scheme, lowercased host and explicitly provided port. Paths are discarded. An explicit `:443` is retained, so use consistent origins and test the actual app requests. Wildcards are not supported. The allow-list checks an initial URI, not all subsequent redirects.

## Persistent changes

[PrivacyStateInstaller](../src/Service/PrivacyStateInstaller.php) runs during installation and activation:

- Parks `core.store.apiUri` on `http://127.0.0.1` unless Store communication is allowed.
- Sets `core.usageData.lastEntitySyncRunDate` to `2999-12-31 23:59:59.000`.
- Marks these tasks inactive with a far-future next execution time:
  - `usage_data.entity_data.collect`
  - `in-app-purchase.update`
  - `services.install`
  - `translation.update`
- Deactivates currently active apps with matching names, paths or translated labels. Name patterns include `analytics`, `gmv` and Shopware Services variants; path patterns include `services.shopware` and `registry.services.shopware`. These are substring matches, not publisher verification.

The Store toggle updates the global Store API URI through [ConfigToggleSubscriber](../src/Subscriber/ConfigToggleSubscriber.php). The other persistent state is not restored by allowing Store or app communication.

Deactivation/uninstallation writes default state rather than restoring a snapshot: default Store API URI, deletion of the usage-data timestamp, and the four tasks scheduled immediately. Apps remain inactive. This rollback also runs when uninstalling with `--keep-user-data`. Review pre-existing custom state yourself.

## Compatibility evidence

`composer.json` declares Shopware core/Administration `~6.7.0` and a PHP constraint equivalent to `>=8.2 <9.0`. These constraints describe permitted dependencies, not a completed compatibility matrix.

The [1.2.0 changelog](../CHANGELOG.md#120---2026-11-17) reports a previous tcpdump check on Shopware 6.7.16.0. The repository does not contain a reproducible automated test suite or packet-capture evidence for that entry. Installation, removal, checkout and integrations still need checking on the operator's staging system after Shopware/plugin changes.

The [official Administration guide](https://developer.shopware.com/docs/guides/plugins/plugins/administration/module-component-management/add-custom-component.html) explains rebuilding Administration changes. Use the build tooling already provided by your Shopware project.
