# StPiGh Shopware Antispy

**Weniger automatische Datenübertragung an Shopware. Bewusst entscheiden, welche externen Dienste dein Shop benötigt.**

Das kostenlose Open-Source-Plugin schränkt Telemetrie, Nutzungsdatenübertragung und ausgewählte externe Verbindungen in selbst gehosteten Shopware-6.7-Shops ein. Store- und App-Verbindungen sind zunächst gesperrt und lassen sich teilweise freigeben.

Deutsch | [English](README.en.md) · [Technische Details](docs/TECHNICAL-NOTES.md) · [Änderungen](CHANGELOG.md) · [Fehler melden](https://github.com/StefanPilzLtd/StPiGhShopwareAntispy/issues)

## Passt das Plugin zu deinem Shop?

Sinnvoll für Betreiber, die ihren selbst gehosteten Shop unabhängig verwalten und die aufgeführten Verbindungen bewusst einschränken möchten. Du brauchst Zugriff auf Shop-Dateien, Kommandozeile und Administration-Build oder einen technischen Dienstleister.

**Vor dem Einsatz beachten:**

- **Updates selbst überwachen:** Die Update-Prüfung der Administration wird blockiert. „Kein Update verfügbar“ bestätigt mit aktivem Plugin keinen aktuellen Shop. Plane Core-Updates über deinen Deployment-/Composer-Prozess und beobachte die [offiziellen Release Notes](https://developer.shopware.com/release-notes/).
- **Apps und Integrationen prüfen:** App-Registrierung, Webhooks und Aktionsbuttons können ausfallen. Bei Installation und Aktivierung werden außerdem aktive Apps anhand ihrer Namen, Pfade oder übersetzten Bezeichnungen deaktiviert, unter anderem bei Treffern auf `analytics` oder `gmv`. Das kann Drittanbieter-Apps betreffen; sie werden später nicht automatisch reaktiviert.
- **Verträge vorher klären:** Usage-Data-/GMV-Verbindungen bleiben auch mit Ausnahmen gesperrt. Prüfe vor Aktivierung die [Meldepflichten und benötigten Dienste](#community-edition-account-und-verträge).
- **Zuerst in Staging prüfen:** Teste Administration, Bestellung, Zahlung, Versand, ERP und Apps mit deiner konkreten Erweiterungskombination.

## Was wird eingeschränkt?

| Bereich | Verhalten bei aktivem Plugin | Folge für den Betrieb |
|---|---|---|
| Nutzungsdaten und Telemetrie | Zugehörige Subscriber, Handler und Tasks werden entkoppelt; Usage-Data-Client wird lokal beantwortet. | Automatische Übertragung über diese Wege wird unterbunden. |
| Shopware Services und Analytics | Registry-Verbindung wird blockiert; passende Apps werden bei Installation/Aktivierung deaktiviert. | Betroffene Service- und Analysefunktionen stehen nicht wie gewohnt bereit. |
| Store und App-System | Eingebundene HTTP-Clients sind standardmäßig gesperrt. | Store-Downloads, Lizenzabfragen und App-Verbindungen benötigen Ausnahmen; weitere Sperren können trotzdem greifen. |
| Administration im Browser | Direkte externe `fetch`-/XHR-/Beacon-Aufrufe werden blockiert; App-Iframes sind zunächst auf dieselbe Origin begrenzt. | Externe Admin-Integrationen können betroffen sein. |
| Core-Update und Übersetzungen | Admin-Update-Prüfung und täglicher Task `translation.update` werden blockiert. | Updates und Übersetzungsaktualisierung selbst organisieren. |
| Gemeinsamer Symfony-HTTP-Client | Filtert `shopware.com`, `shopware.io`, `shopware.de`, `shopware.store`, `fos.gg`, `cloudflare.com` und deren Subdomains. | Auch Aufrufe anderer Plugins über diesen Client können mit lokalem HTTP 403 scheitern. Der FroshTools-Systemzeitcheck wird zusätzlich deaktiviert. |

**Der Schutz ist auf die eingebundenen Wege begrenzt.** Das Plugin ist keine Firewall für den gesamten Shop. Eigene HTTP-Clients anderer Plugins, Weiterleitungen und andere Browser-Ressourcen sind nicht vollständig kontrolliert. Externe Bilder und Skripte werden nicht generell blockiert. Storefront-Tracking und Cookie-Einwilligungen werden nicht verwaltet. Daraus folgt weder ein DSGVO-Nachweis noch vollständiger Schutz vor jeder Datenübertragung.

## Voraussetzungen und Prüfstand

- Selbst gehostetes Shopware **6.7.x** mit Administration; kein Installationsweg für Shopware SaaS.
- Die [Plugin-Metadaten](composer.json) erlauben PHP ab **8.2 innerhalb von PHP 8**. Zusätzlich gelten die Anforderungen deiner konkreten Shopware-Version und ihrer Abhängigkeiten. Das ist keine geprüfte PHP-Versionsmatrix.
- Das [Changelog zu 1.2.0](CHANGELOG.md#120---2026-11-17) nennt eine frühere Netzwerkprüfung mit Shopware **6.7.16.0**. Das ist keine Freigabe für jede 6.7-Version oder Erweiterungskombination.
- Das Repository enthält Administration-JavaScript als Quellcode, aber keine vorkompilierten Administration-Dateien. **Ein Administration-Build ist erforderlich.**

## Installation aus dem Repository

Zuerst in einer Testumgebung ausführen. Aktuelle Sicherung bereithalten und aktive Apps sowie eigene Store-API-/Task-Einstellungen notieren: Das Plugin speichert keinen vollständigen Vorher-Zustand.

1. Im **Shopware-Hauptverzeichnis**, in dem `bin/console` liegt:

   ```sh
   git clone https://github.com/StefanPilzLtd/StPiGhShopwareAntispy.git custom/plugins/StPiGhShopwareAntispy
   ```

   Alternativ den [Quellcode als ZIP](https://github.com/StefanPilzLtd/StPiGhShopwareAntispy/archive/refs/heads/main.zip) so entpacken, dass `custom/plugins/StPiGhShopwareAntispy/composer.json` existiert. GitHubs Quellcode-ZIP ist kein fertig gebautes Shopware-Upload-Paket.

2. Registrieren und installieren. **Bereits die Installation kann Apps und Einstellungen verändern.**

   ```sh
   bin/console plugin:refresh
   bin/console plugin:install --activate StPiGhShopwareAntispy
   bin/console cache:clear
   ```

3. Administration mit dem Build-Prozess deines Shops neu erstellen. In Projekten mit dem Standardskript:

   ```sh
   bin/build-administration.sh
   ```

   Dafür werden PHP und die zum Shop passende Node.js-/npm-Umgebung benötigt. Bei verwalteten Deployments den vorhandenen Build-Prozess nutzen. Administration anschließend im Browser vollständig neu laden. Ohne Build fehlt der JavaScript-Teil des Schutzes.

## Ausnahmen einstellen

Unter **Erweiterungen → Meine Erweiterungen → StPiGh Shopware Antispy → Konfiguration** für **alle Verkaufskanäle** einstellen. Die serverseitigen Prüfungen lesen die globale Konfiguration.

| Einstellung | Wirkung und Grenze |
|---|---|
| **Shopware-Store-Kommunikation erlauben** | Gibt die dekorierten Store-, Download- und First-Run-Wizard-Clients frei. `core.store.apiUri` wird auf `https://api.shopware.com` gesetzt, beim Ausschalten auf `http://127.0.0.1`. Die separate Symfony-Host-Sperre, der Admin-Updatecheck, Usage Data und Services bleiben eingeschränkt. Auch `tracking/events` wird auf dem freigegebenen Store-Client durchgereicht. |
| **Erlaubte App-Server-Ursprünge** | Gibt einzelne Origins für App-System-Client und App-Iframes frei, etwa `https://apps.example.com`. Mehrere Einträge durch Leerzeichen, Kommas oder Zeilenumbrüche trennen. Schema, Host und ausdrücklich angegebener Port müssen passen; Subdomains brauchen eigene Einträge. Pfade begrenzen die Freigabe nicht. |
| **App-Server-Kommunikation erlauben** | Gibt den App-System-Client allgemein frei und erlaubt HTTPS-App-Iframes. Eine einzelne Origin-Liste ist enger begrenzt. Direkte externe Browser-Aufrufe aus der Administration bleiben in beiden Fällen blockiert. |

Client-Ausnahmen werden zur Laufzeit gelesen; dafür ist kein erneuter Administration-Build nötig. Administration nach Änderungen neu laden, damit neue Iframe-Freigaben greifen. Bei nicht lesbarer Konfiguration bleiben die konfigurierbaren Clients gesperrt. Ausnahmen reaktivieren keine Apps und stellen entfernte Tasks oder Handler nicht wieder her.

## Nach der Installation prüfen

1. Administration und Erweiterungsverwaltung öffnen. Der Header `X-StPiGh-Shopware-Antispy: active` an Admin-Antworten bestätigt den Subscriber, keine vollständige Netzwerksperre.
2. In den Browser-Entwicklertools unter **Netzwerk** die Administration neu laden und relevante Abläufe ausführen. Lokale Antworten können erfolgreich aussehen; HTTP 200 allein bestätigt keine externe Verbindung.
3. App-Zustände, Webhooks und vollständigen Bestellablauf einschließlich Zahlungs-/Versandanbindung prüfen. Benötigte Origins gezielt freigeben und erneut testen.
4. Ausgehenden **Serververkehr** bei Bedarf mit Hosting-/Netzwerkwerkzeugen kontrollieren. Der Browser zeigt keine serverseitigen Verbindungen.

Bei Problemen die [technischen Grenzen](docs/TECHNICAL-NOTES.md) prüfen oder das Plugin deaktivieren.

## Deaktivieren und entfernen

Im Shopware-Hauptverzeichnis:

```sh
bin/console plugin:deactivate StPiGhShopwareAntispy
bin/console cache:clear
bin/build-administration.sh
```

Bei abweichenden Deployment-Prozessen deren Administration-Build verwenden. Anschließend den Browser vollständig neu laden. Für die anschließende Deinstallation:

```sh
bin/console plugin:uninstall StPiGhShopwareAntispy
```

Deaktivierung und Deinstallation setzen `core.store.apiUri` auf den **Standardwert** `https://api.shopware.com`, löschen `core.usageData.lastEntitySyncRunDate` und setzen vier betroffene Tasks auf `scheduled` mit sofort fälligem Ausführungszeitpunkt. **Frühere individuelle Werte oder zuvor inaktive Tasks werden nicht rekonstruiert.** Deaktivierte Apps bleiben deaktiviert. Auch `--keep-user-data` verhindert die Rücknahme im Uninstall-Hook nicht. Eigene Einstellungen kontrollieren und benötigte Apps einzeln reaktivieren.

## Community Edition, Account und Verträge

Die Community Edition lässt sich laut Shopware auch ohne Account und Store betreiben. Die Open-Source-Lizenz und Verträge über zusätzliche Dienste sind getrennt zu prüfen. Das Plugin kündigt keinen Vertrag, löscht keinen Account und ersetzt keine Lizenz oder vereinbarte GMV-Meldung.

Shopwares [GMV-Mitteilung vom 14.04.2026](https://www.shopware.com/de/news/automatisiertes-gmv-reporting/) nimmt CE-Nutzer ohne Account-/Store-Nutzung vom automatischen Tracking aus. Für verbundene CE-Shops und Plan-Kunden nennt sie Meldepflichten. Weil das Plugin die betreffenden Verbindungen unabhängig von Store-Ausnahmen sperrt, diese Pflichten vor Aktivierung klären.

Bei einem Wechsel ohne Account: Verträge, Laufzeiten und Erweiterungslizenzen erfassen; Kündigung und Account-Schließung mit Shopware abstimmen und bestätigen lassen; Updates und benötigte Ersatzdienste organisieren. Bloßes Abmelden oder Blockieren beendet keine Verträge. Einmalkauf und Miete haben unterschiedliche Nutzungsrechte.

Quellen, geprüft am **26.11.2026**: [Fair Usage FAQ](https://www.shopware.com/de/news/fair-usage-policy-community-edition/), [GMV-Reporting](https://www.shopware.com/de/news/automatisiertes-gmv-reporting/), [Shopware-AGB, insbesondere Ziffern 4, 9, 10 und 22](https://www.shopware.com/de/agb/). Maßgeblich sind die jeweiligen Vereinbarungen; diese Übersicht ist keine individuelle Rechtsberatung.

## Hilfe und Entwicklung

Ein unabhängiges Community-Projekt von **Stefan Pilz**. Keine offizielle Shopware-Erweiterung. Kostenlos unter der [MIT-Lizenz](LICENSE), ohne zugesicherte Eignung für jede Shop-Konfiguration.

[Fehler melden](https://github.com/StefanPilzLtd/StPiGhShopwareAntispy/issues): Plugin-, Shopware- und PHP-Version, betroffene Integration und Schritte zum Nachstellen angeben. Zugangsdaten, Kundendaten und private Logs vor dem Veröffentlichen entfernen.

Für Einrichtung, eigene Shopware-Plugins, Themes und Schnittstellen: [stefanpilz.ltd](https://stefanpilz.ltd) · [plugins@stefanpilz.ltd](mailto:plugins@stefanpilz.ltd) · [GitHub](https://github.com/StefanPilzLtd).
