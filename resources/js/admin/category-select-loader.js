/**
 * カテゴリセレクトボックスの表示
 */

import { getUrlParams } from './url-params-manager.js';
import { CategoriesApi } from '../api/categories.js';

export async function loadCategories() {
    const categorySelect = document.getElementById('category-select');
    if (!categorySelect) return;

    const currentParams = getUrlParams();

    try {
        const categories = await CategoriesApi.getCategories();
        if (!Array.isArray(categories) || categories.length === 0) return;

        categories.forEach(category => {
            if (!category?.id || !category?.content) return;
            const option = document.createElement('option');
            option.value = category.id;
            option.textContent = category.content;
            if (currentParams.category_id == category.id) option.selected = true;
            categorySelect.appendChild(option);
        });

        if (!currentParams.category_id && categorySelect.options.length > 0) {
            categorySelect.options[0].selected = true;
        }
    } catch (error) {
        console.error('Error loading categories:', error);
    }
}
