/**
 * 検索フォームの処理
 */

import { buildParamsFromForm, buildUrlWithParams } from './url-params-manager.js';

export function initSearchForm() {
    document.querySelector('form[action="/admin/search"]')?.addEventListener('submit', (e) => {
        e.preventDefault();
        const formData = new FormData(e.target);
        const params = buildParamsFromForm(formData);
        const url = buildUrlWithParams(params, null);
        window.location.href = url;
    });
}
