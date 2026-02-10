/**
 * 管理画面用のタグ CRUD
 */

import { TagsApi } from '../api/tags.js';

function getElements() {
    return {
        form: document.getElementById('tag-form'),
        nameInput: document.getElementById('tag-name-input'),
        submitButton: document.getElementById('tag-submit-btn'),
        cancelButton: document.getElementById('tag-cancel-btn'),
        errorBox: document.getElementById('tag-form-error'),
        tbody: document.getElementById('tags-tbody'),
    };
}

function setLoadingRow(tbody, message) {
    if (!tbody) return;
    tbody.innerHTML = `
        <tr>
            <td class="px-6 py-4 text-sm text-gray-500" colspan="2">${message}</td>
        </tr>
    `;
}

function renderTags(tbody, tags) {
    if (!tbody) return;
    if (!Array.isArray(tags) || tags.length === 0) {
        setLoadingRow(tbody, 'タグが登録されていません');
        return;
    }

    tbody.innerHTML = tags.map(tag => `
        <tr class="border-b border-gray-100">
            <td class="px-6 py-3 text-sm text-gray-800">${tag.name}</td>
            <td class="px-6 py-3 text-sm text-right space-x-2">
                <button
                    type="button"
                    class="tag-edit-button px-3 py-1 border border-[#ddd8d3] text-[#9a938c] rounded hover:bg-gray-50"
                    data-tag-id="${tag.id}"
                    data-tag-name="${tag.name}"
                >
                    編集
                </button>
                <button
                    type="button"
                    class="tag-delete-button px-3 py-1 border border-red-200 text-red-600 rounded hover:bg-red-50"
                    data-tag-id="${tag.id}"
                >
                    削除
                </button>
            </td>
        </tr>
    `).join('');
}

function getErrorMessage(error) {
    if (error?.data?.errors) {
        const messages = Object.values(error.data.errors).flat();
        return messages.join('\n');
    }

    if (error?.data?.message) {
        return error.data.message;
    }

    return error?.message || '処理に失敗しました。';
}

export function initTagManagement() {
    const elements = getElements();
    if (!elements.form || !elements.tbody) return;

    let editingTagId = null;

    async function refreshTags() {
        setLoadingRow(elements.tbody, '読み込み中...');
        try {
            const tags = await TagsApi.getTags();
            renderTags(elements.tbody, tags);
        } catch (error) {
            console.error('Failed to load tags:', error);
            setLoadingRow(elements.tbody, 'タグの読み込みに失敗しました');
        }
    }

    function resetForm() {
        editingTagId = null;
        elements.nameInput.value = '';
        elements.errorBox.textContent = '';
        elements.submitButton.textContent = '追加';
        elements.cancelButton.classList.add('hidden');
    }

    elements.form.addEventListener('submit', async function(event) {
        event.preventDefault();
        if (elements.submitButton.disabled) return;

        const name = elements.nameInput.value.trim();
        if (!name) {
            elements.errorBox.textContent = 'タグ名を入力してください。';
            return;
        }

        elements.errorBox.textContent = '';
        elements.submitButton.disabled = true;
        elements.submitButton.textContent = editingTagId ? '更新中...' : '追加中...';

        try {
            if (editingTagId) {
                await TagsApi.updateTag(editingTagId, { name });
            } else {
                await TagsApi.createTag({ name });
            }
            resetForm();
            refreshTags();
        } catch (error) {
            console.error('Failed to save tag:', error);
            elements.errorBox.textContent = getErrorMessage(error);
        } finally {
            elements.submitButton.disabled = false;
            elements.submitButton.textContent = editingTagId ? '更新' : '追加';
        }
    });

    elements.cancelButton.addEventListener('click', function() {
        resetForm();
    });

    elements.tbody.addEventListener('click', async function(event) {
        const editButton = event.target.closest('.tag-edit-button');
        const deleteButton = event.target.closest('.tag-delete-button');

        if (editButton) {
            editingTagId = editButton.dataset.tagId;
            elements.nameInput.value = editButton.dataset.tagName || '';
            elements.submitButton.textContent = '更新';
            elements.cancelButton.classList.remove('hidden');
            elements.nameInput.focus();
            return;
        }

        if (deleteButton) {
            const tagId = deleteButton.dataset.tagId;
            if (!tagId) return;
            if (!confirm('このタグを削除しますか？')) return;
            try {
                await TagsApi.deleteTag(tagId);
                if (editingTagId === tagId) {
                    resetForm();
                }
                refreshTags();
            } catch (error) {
                console.error('Failed to delete tag:', error);
                alert(getErrorMessage(error));
            }
        }
    });

    refreshTags();
}
