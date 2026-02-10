/**
 * カテゴリセレクトの初期化
 */

import { CategoriesApi } from '../api/categories.js';

export async function initCategorySelect() {
    const categorySelect = document.getElementById('category-select');
    if (!categorySelect) {
        return;
    }

    try {
        const categories = await CategoriesApi.getCategories();
        const categoriesArray = Array.isArray(categories) ? categories : (categories?.data || []);

        if (categoriesArray.length === 0) {
            return;
        }

        const oldCategoryId = categorySelect.getAttribute('data-old-value') || '';

        categoriesArray.forEach(category => {
            if (!category?.id || !category?.content) {
                return;
            }

            const option = document.createElement('option');
            option.value = category.id;
            option.textContent = category.content;
            if (oldCategoryId && oldCategoryId == category.id) {
                option.selected = true;
            }
            categorySelect.appendChild(option);
        });
    } catch (error) {
        console.error('カテゴリの読み込みに失敗しました:', error);
        showCategoryError(categorySelect);
    }
}

function showCategoryError(selectElement) {
    if (!selectElement?.parentElement) {
        return;
    }

    const existingError = selectElement.parentElement.querySelector('.category-error');
    if (existingError) {
        return;
    }

    const errorDiv = document.createElement('div');
    errorDiv.className = 'mt-1 text-sm text-red-600 category-error';
    errorDiv.textContent = 'カテゴリの読み込みに失敗しました。ページを再読み込みしてください。';
    selectElement.parentElement.appendChild(errorDiv);
}
