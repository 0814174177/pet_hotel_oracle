(function () {
    class ApiHookError extends Error {
        constructor(message, status, payload) {
            super(message);
            this.name = "ApiHookError";
            this.status = status;
            this.payload = payload;
        }
    }

    function createStore(initialState) {
        const listeners = new Set();
        const store = {
            state: { ...initialState },
            setState(nextState) {
                store.state = { ...store.state, ...nextState };
                listeners.forEach((listener) => listener(store.state));
            },
            subscribe(listener) {
                if (typeof listener !== "function") {
                    return function unsubscribe() {};
                }

                listeners.add(listener);
                listener(store.state);

                return function unsubscribe() {
                    listeners.delete(listener);
                };
            },
        };

        return store;
    }

    function readMeta(name) {
        return document.querySelector(`meta[name="${name}"]`)?.getAttribute("content") || "";
    }

    function getAuthToken() {
        return "";
    }

    function setAuthToken() {
        return "";
    }

    function clearAuthToken() {
        return "";
    }

    function isFormData(payload) {
        return typeof FormData !== "undefined" && payload instanceof FormData;
    }

    function buildHeaders(extraHeaders = {}, payload = null) {
        const csrfToken = readMeta("csrf-token");
        const headers = {
            Accept: "application/json",
            ...extraHeaders,
        };

        if (!isFormData(payload) && !headers["Content-Type"]) {
            headers["Content-Type"] = "application/json";
        }

        if (csrfToken && !headers["X-CSRF-TOKEN"]) {
            headers["X-CSRF-TOKEN"] = csrfToken;
        }

        return headers;
    }

    async function parseResponse(response) {
        const contentType = response.headers.get("content-type") || "";

        if (response.status === 204) {
            return null;
        }

        if (contentType.includes("application/json")) {
            return response.json();
        }

        return response.text();
    }

    function getErrorMessage(status, payload) {
        if (payload && typeof payload === "object") {
            if (payload.message) {
                return payload.message;
            }

            if (payload.errors) {
                const firstError = Object.values(payload.errors).flat()[0];

                if (firstError) {
                    return firstError;
                }
            }
        }

        return `Lỗi hệ thống: ${status}`;
    }

    async function request(url, options = {}) {
        const response = await fetch(url, {
            credentials: options.credentials || "same-origin",
            ...options,
            headers: buildHeaders(options.headers, options.body),
        });
        const payload = await parseResponse(response);

        if (!response.ok) {
            throw new ApiHookError(getErrorMessage(response.status, payload), response.status, payload);
        }

        return payload;
    }

    function useGetData(url, immediate = true, options = {}) {
        const store = createStore({
            data: null,
            loading: false,
            error: null,
        });

        async function fetchData(nextUrl = url, requestOptions = {}) {
            store.setState({ loading: true, error: null });

            try {
                const result = await request(nextUrl, {
                    method: "GET",
                    ...options.request,
                    ...requestOptions,
                });

                store.setState({ data: result, loading: false, error: null });
                options.onSuccess?.(result);

                return result;
            } catch (error) {
                store.setState({ error: error.message, loading: false });
                options.onError?.(error);

                throw error;
            }
        }

        if (typeof options.onChange === "function") {
            store.subscribe(options.onChange);
        }

        if (immediate) {
            queueMicrotask(() => fetchData().catch(() => {}));
        }

        return {
            get data() {
                return store.state.data;
            },
            get loading() {
                return store.state.loading;
            },
            get error() {
                return store.state.error;
            },
            get state() {
                return store.state;
            },
            subscribe: store.subscribe,
            refetch: fetchData,
        };
    }

    function useSendData(url, options = {}) {
        const store = createStore({
            data: null,
            loading: false,
            error: null,
        });

        async function sendData(payload = {}, method = "POST", requestOptions = {}) {
            store.setState({ loading: true, error: null });

            try {
                const body = isFormData(payload) ? payload : JSON.stringify(payload);
                const result = await request(url, {
                    method,
                    body,
                    ...options.request,
                    ...requestOptions,
                });

                store.setState({ data: result, loading: false, error: null });
                options.onSuccess?.(result);

                return result;
            } catch (error) {
                store.setState({ error: error.message, loading: false });
                options.onError?.(error);

                throw error;
            }
        }

        if (typeof options.onChange === "function") {
            store.subscribe(options.onChange);
        }

        return {
            get data() {
                return store.state.data;
            },
            get loading() {
                return store.state.loading;
            },
            get error() {
                return store.state.error;
            },
            get state() {
                return store.state;
            },
            subscribe: store.subscribe,
            sendData,
        };
    }

    window.PetHotelAjax = {
        request,
        useGetData,
        useSendData,
        setAuthToken,
        getAuthToken,
        clearAuthToken,
        ApiHookError,
    };
})();
