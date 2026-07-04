(function stpighShopwareAntispy() {
    const sameOrigin = window.location.origin;
    function isBlockedUrl(input) {
        try {
            const rawUrl = typeof input === 'string'
                ? input
                : input instanceof URL
                    ? input.href
                    : input && input.url
                        ? input.url
                        : '';
            const url = new URL(rawUrl, window.location.href);

            if (url.origin === sameOrigin) {
                return false;
            }

            return true;
        } catch (e) {
            return false;
        }
    }

    function blockedResponse() {
        return Promise.resolve(new Response(JSON.stringify({ blocked: true }), {
            status: 200,
            headers: {
                'Content-Type': 'application/json',
                'X-StPiGh-Shopware-Antispy': 'blocked',
            },
        }));
    }

    const nativeFetch = window.fetch;
    window.fetch = function fetchWithAntispy(input, init) {
        if (isBlockedUrl(input)) {
            return blockedResponse();
        }

        return nativeFetch.apply(this, arguments);
    };

    const nativeOpen = window.XMLHttpRequest && window.XMLHttpRequest.prototype.open;
    const nativeSend = window.XMLHttpRequest && window.XMLHttpRequest.prototype.send;
    if (nativeOpen && nativeSend) {
        window.XMLHttpRequest.prototype.open = function openWithAntispy(method, url) {
            this.__stpighBlocked = isBlockedUrl(url);

            if (this.__stpighBlocked) {
                this.__stpighResponseUrl = url;
                return;
            }

            return nativeOpen.apply(this, arguments);
        };

        window.XMLHttpRequest.prototype.send = function sendWithAntispy() {
            if (!this.__stpighBlocked) {
                return nativeSend.apply(this, arguments);
            }

            Object.defineProperty(this, 'readyState', { value: 4, configurable: true });
            Object.defineProperty(this, 'status', { value: 200, configurable: true });
            Object.defineProperty(this, 'responseText', { value: '{"blocked":true}', configurable: true });
            Object.defineProperty(this, 'responseURL', { value: this.__stpighResponseUrl || '', configurable: true });
            this.onreadystatechange && this.onreadystatechange();
            this.onload && this.onload();
        };
    }

    if (window.navigator && typeof window.navigator.sendBeacon === 'function') {
        const nativeSendBeacon = window.navigator.sendBeacon.bind(window.navigator);
        window.navigator.sendBeacon = function sendBeaconWithAntispy(url, data) {
            if (isBlockedUrl(url)) {
                return true;
            }

            return nativeSendBeacon(url, data);
        };
    }

    const disableTelemetry = () => {
        if (!window.Shopware || !Shopware.Telemetry) {
            return false;
        }

        Shopware.Telemetry.initialize = function initializeBlockedTelemetry() {};
        Shopware.Telemetry.track = function trackBlockedTelemetry() {};
        Shopware.Telemetry.identify = function identifyBlockedTelemetry() {};

        return true;
    };

    const sanitizeContext = () => {
        if (!window.Shopware || !Shopware.Store) {
            return;
        }

        const context = Shopware.Store.get('context');
        if (!context) {
            return;
        }

        if (context.api) {
            context.api.serviceRegistryUrl = '';
        }

        if (context.app) {
            context.app.analyticsGatewayUrl = '';
        }

        disableTelemetry();
    };

    window.addEventListener('DOMContentLoaded', sanitizeContext, { once: true });
    window.setTimeout(sanitizeContext, 0);

    const telemetryPatchInterval = window.setInterval(() => {
        if (disableTelemetry()) {
            window.clearInterval(telemetryPatchInterval);
        }
    }, 50);
    window.setTimeout(() => window.clearInterval(telemetryPatchInterval), 5000);
})();
