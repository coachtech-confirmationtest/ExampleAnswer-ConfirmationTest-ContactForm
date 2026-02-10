/**
 * API呼び出し用の共通ユーティリティ（ベースクラス）
 */

export const ApiBase = {
    baseUrl: '/api',

    async request(endpoint, options = {}) {
        const url = `${this.baseUrl}${endpoint}`;
        const defaultOptions = {
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        };

        const csrfToken = document.querySelector('meta[name="csrf-token"]');
        if (csrfToken) {
            defaultOptions.headers['X-CSRF-TOKEN'] = csrfToken.getAttribute('content');
        }

        const mergedOptions = {
            ...defaultOptions,
            ...options,
            headers: {
                ...defaultOptions.headers,
                ...(options.headers || {}),
            },
            credentials: 'same-origin',
        };

        try {
            const response = await fetch(url, mergedOptions);
            
            if (!response.ok) {
                // エラーレスポンスの内容を取得
                const contentType = response.headers.get('content-type');
                let errorData = null;
                
                if (contentType && contentType.includes('application/json')) {
                    try {
                        errorData = await response.json();
                    } catch (e) {
                        // JSONパースに失敗した場合は無視
                    }
                }
                
                const error = new Error(errorData?.message || `HTTP error! status: ${response.status}`);
                error.status = response.status;
                error.data = errorData;
                throw error;
            }

            if (response.status === 204) {
                return null;
            }

            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                return null;
            }

            return await response.json();
        } catch (error) {
            console.error(`API request failed: ${endpoint}`, error);
            throw error;
        }
    },

    async get(endpoint, params = {}) {
        const queryString = new URLSearchParams(params).toString();
        const url = queryString ? `${endpoint}?${queryString}` : endpoint;
        return this.request(url, { method: 'GET' });
    },

    async post(endpoint, data = {}) {
        return this.request(endpoint, {
            method: 'POST',
            body: JSON.stringify(data),
        });
    },

    async put(endpoint, data = {}) {
        return this.request(endpoint, {
            method: 'PUT',
            body: JSON.stringify(data),
        });
    },

    async delete(endpoint) {
        return this.request(endpoint, { method: 'DELETE' });
    },
};
