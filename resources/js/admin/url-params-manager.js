/**
 * URLパラメータの管理
 */

/**
 * フォームデータからパラメータオブジェクトを構築
 */
export function buildParamsFromForm(formData) {
    return {
        keyword: formData.get('keyword') || '',
        gender: formData.get('gender') || '0',
        category_id: formData.get('category_id') || '',
        date: formData.get('date') || '',
        page: '1', // 検索時は1ページ目に戻る
    };
}

export function getUrlParams() {
    const urlParams = new URLSearchParams(window.location.search);
    return {
        keyword: urlParams.get('keyword') || '',
        gender: urlParams.get('gender') || '0',
        category_id: urlParams.get('category_id') || '',
        date: urlParams.get('date') || '',
        page: urlParams.get('page') || '1',
    };
}

export function buildUrlWithParams(currentParams, pageUrl) {
    const params = new URLSearchParams();
    
    // 検索条件を設定
    if (currentParams.keyword) params.set('keyword', currentParams.keyword);
    if (currentParams.gender && currentParams.gender !== '0') params.set('gender', currentParams.gender);
    if (currentParams.category_id) params.set('category_id', currentParams.category_id);
    if (currentParams.date) params.set('date', currentParams.date);
    
    // pageUrlからpageパラメータを取得（ページネーションリンクの場合）
    if (pageUrl) {
        try {
            const url = new URL(pageUrl, window.location.origin);
            const pageParam = url.searchParams.get('page');
            if (pageParam) {
                params.set('page', pageParam);
            }
        } catch (e) {
            // pageUrlが無効な場合は無視
            console.warn('Invalid pageUrl:', pageUrl);
        }
    }
    
    return `/admin?${params.toString()}`;
}

export function setFormValues(params) {
    const keywordInput = document.getElementById('keyword-input');
    const genderSelect = document.getElementById('gender-select');
    const categorySelect = document.getElementById('category-select');
    const dateInput = document.getElementById('date-input');

    if (keywordInput) keywordInput.value = params.keyword;
    if (genderSelect) genderSelect.value = params.gender;
    if (categorySelect && params.category_id) {
        categorySelect.value = params.category_id;
    }
    if (dateInput) dateInput.value = params.date;
}
