/**
 * モーダルの表示と削除処理
 */

import { getGenderText } from './gender-helper.js';
import { loadContacts } from './contact-list-renderer.js';
import { ContactsApi } from '../api/contacts.js';

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

const modal = document.getElementById('detail-modal');
const closeModal = document.getElementById('close-modal');

function formatTagsForModal(tags) {
    if (!tags || tags.length === 0) return '-';
    return tags.map(tag =>
        `<span class="inline-block px-2 py-0.5 bg-amber-100 text-amber-800 text-xs rounded mr-1">${tag.name}</span>`
    ).join('');
}

function createModalContent(contact) {
    const fields = [
        { label: 'お名前', value: `${contact.last_name} ${contact.first_name}` },
        { label: '性別', value: getGenderText(contact.gender) },
        { label: 'メールアドレス', value: contact.email },
        { label: '電話番号', value: contact.tel || '' },
        { label: '住所', value: contact.address || '' },
        { label: '建物名', value: contact.building || '' },
        { label: 'お問い合わせの種類', value: contact.category?.content || '' },
        { label: 'タグ', value: formatTagsForModal(contact.tags), isHtml: true },
        { label: 'お問い合わせ内容', value: contact.detail || '', isPreWrap: true },
    ];

    return `
        <div class="space-y-4">
            ${fields.map(field => `
                <div class="grid grid-cols-3 pb-2">
                    <div class="font-medium text-gray-700">${field.label}</div>
                    <div class="col-span-2 text-gray-900 ${field.isPreWrap ? 'whitespace-pre-wrap' : ''}">${field.isHtml ? field.value : escapeHtml(field.value)}</div>
                </div>
            `).join('')}
            <div class="flex justify-end mt-6">
                <button type="button" class="delete-button px-6 py-2 bg-red-600 text-white rounded hover:bg-red-700"
                    data-contact-id="${contact.id}">削除</button>
            </div>
        </div>
    `;
}

async function deleteContact(contactId) {
    if (!confirm('本当に削除しますか？')) return;

    try {
        await ContactsApi.deleteContact(contactId);
        closeModalHandler();
        loadContacts();
    } catch (error) {
        console.error('Error deleting contact:', error);
        alert('削除に失敗しました');
    }
}

function setupDeleteButton() {
    const deleteButton = document.querySelector('.delete-button');
    if (deleteButton) {
        deleteButton.addEventListener('click', function() {
            const contactId = this.dataset.contactId;
            deleteContact(contactId);
        });
    }
}

export function setupDetailButtons() {
    document.querySelectorAll('.detail-button').forEach(button => {
        button.addEventListener('click', async function() {
            const contactId = this.dataset.contactId;
            if (!modal) return;
            const modalBody = modal.querySelector('.modal__body');
            if (!modalBody) return;
            try {
                const contact = await ContactsApi.getContact(contactId);
                modalBody.innerHTML = createModalContent(contact);
                modal.classList.remove('hidden');
                modal.classList.add('flex');
                setupDeleteButton();
            } catch (error) {
                console.error('Error fetching contact:', error);
                alert('データの取得に失敗しました');
            }
        });
    });
}

export function closeModalHandler() {
    if (!modal) return;
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}

export function initModal() {
    if (closeModal) {
        closeModal.addEventListener('click', closeModalHandler);
    }
    window.addEventListener('click', (e) => {
        if (e.target === modal) closeModalHandler();
    });
}
