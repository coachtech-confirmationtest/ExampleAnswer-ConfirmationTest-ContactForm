/**
 * タグチェックボックスの初期化
 */

import { TagsApi } from '../api/tags.js';

export async function initTagSelect() {
    const tagContainer = document.getElementById('tag-checkboxes');
    if (!tagContainer) {
        return;
    }

    try {
        const tags = await TagsApi.getTags();
        const tagsArray = Array.isArray(tags) ? tags : (tags?.data || []);

        if (tagsArray.length === 0) {
            return;
        }

        tagsArray.forEach(tag => {
            if (!tag?.id || !tag?.name) {
                return;
            }

            const label = document.createElement('label');
            label.className = 'flex items-center cursor-pointer';

            const checkbox = document.createElement('input');
            checkbox.type = 'checkbox';
            checkbox.name = 'tag_ids[]';
            checkbox.value = tag.id;
            checkbox.className = 'w-4 h-4 text-[#6b5744] border-gray-300 rounded focus:ring-[#6b5744]';

            const span = document.createElement('span');
            span.className = 'ml-2 text-gray-700';
            span.textContent = tag.name;

            label.appendChild(checkbox);
            label.appendChild(span);
            tagContainer.appendChild(label);
        });
    } catch (error) {
        console.error('タグの読み込みに失敗しました:', error);
        showTagError(tagContainer);
    }
}

function showTagError(container) {
    if (!container?.parentElement) {
        return;
    }

    const existingError = container.parentElement.querySelector('.tag-error');
    if (existingError) {
        return;
    }

    const errorDiv = document.createElement('div');
    errorDiv.className = 'mt-1 text-sm text-red-600 tag-error';
    errorDiv.textContent = 'タグの読み込みに失敗しました。ページを再読み込みしてください。';
    container.parentElement.appendChild(errorDiv);
}
