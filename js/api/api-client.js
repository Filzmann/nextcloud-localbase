(function() {
    class ApiClient {
        constructor(options = {}) {
            this.appId = options.appId || '';
            this.basePath = options.basePath || (this.appId ? `/apps/${this.appId}` : '');
            this.fetcher = options.fetcher || window.fetch.bind(window);
            this.generateUrl = options.generateUrl || ((path) => OC.generateUrl(path));
            this.requestToken = options.requestToken || (() => OC.requestToken);
            this.errorMessage = options.errorMessage || ((data, status) => data && data.message ? data.message : `HTTP ${status}`);
        }

        async request(path, options = {}) {
            const response = await this.fetcher(this.generateUrl(this.basePath + path), {
                ...options,
                headers: {
                    'Content-Type': 'application/json',
                    requesttoken: this.requestToken(),
                    ...(options.headers || {})
                }
            });

            const data = this.parseResponseText(await response.text());
            if (!response.ok) {
                const error = new Error(this.errorMessage(data, response.status));
                error.data = data;
                error.status = response.status;
                throw error;
            }

            return data;
        }

        parseResponseText(text) {
            try {
                return text ? JSON.parse(text) : {};
            } catch (e) {
                return { raw: text };
            }
        }

        encode(value) {
            return ApiClient.encode(value);
        }

        static encode(value) {
            return encodeURIComponent(String(value));
        }
    }

    window.LocalBase = window.LocalBase || {};
    window.LocalBase.api = window.LocalBase.api || {};
    window.LocalBase.api.ApiClient = ApiClient;
})();
