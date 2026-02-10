/**
 * カテゴリ関連のAPI
 */

import { ApiBase } from './base.js';

export const CategoriesApi = {
    async getCategories() {
        const response = await ApiBase.get('/categories');
        return response.data;
    },
};
