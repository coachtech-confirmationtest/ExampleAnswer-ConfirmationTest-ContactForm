/**
 * コンタクト一覧の取得とテーブル表示
 */

import { getUrlParams, buildUrlWithParams } from './url-params-manager.js';
import { getGenderText } from './gender-helper.js';
import { ContactsApi } from '../api/contacts.js';

let setupDetailButtonsCallback = null;

export function setDetailButtonCallback(callback) {
    setupDetailButtonsCallback = callback;
}

function formatTags(tags) {
    if (!tags || tags.length === 0) return '';
    return tags.map(tag =>
        `<span class="inline-block px-2 py-0.5 bg-amber-100 text-amber-800 text-xs rounded mr-1 mb-1">${tag.name}</span>`
    ).join('');
}

function createContactRow(contact) {
    return `
        <tr class="bg-white hover:bg-gray-50">
            <td class="px-6 py-4 text-sm text-gray-700">${contact.last_name} ${contact.first_name}</td>
            <td class="px-6 py-4 text-sm text-gray-700">${getGenderText(contact.gender)}</td>
            <td class="px-6 py-4 text-sm text-gray-700">${contact.email}</td>
            <td class="px-6 py-4 text-sm text-gray-700">${contact.category?.content || ''}</td>
            <td class="px-6 py-4 text-sm text-gray-700">${formatTags(contact.tags)}</td>
            <td class="px-6 py-4 text-sm">
                <button class="detail-button px-4 py-1 border border-[#ddd8d3] text-[#9a938c] bg-white rounded hover:bg-gray-50"
                    data-contact-id="${contact.id}">詳細</button>
            </td>
        </tr>
    `;
}

export async function loadContacts() {
    const contactsTbody = document.getElementById('contacts-tbody');
    const paginationContainer = document.getElementById('pagination-container');
    if (!contactsTbody) return;

    const currentParams = getUrlParams();

    try {
        const params = {
            keyword: currentParams.keyword,
            gender: currentParams.gender !== '0' ? currentParams.gender : undefined,
            category_id: currentParams.category_id || undefined,
            date: currentParams.date || undefined,
            page: currentParams.page,
        };

        Object.keys(params).forEach(key => {
            if (!params[key]) delete params[key];
        });

        const response = await ContactsApi.getContacts(params);
        const contacts = response.data || response;
        const paginationLinks = response.meta?.links || response.links || null;

        if (!Array.isArray(contacts) || contacts.length === 0) {
            contactsTbody.innerHTML = `
                <tr>
                    <td colspan="6" class="px-6 py-4 text-center text-sm text-gray-500">データがありません</td>
                </tr>
            `;
        } else {
            contactsTbody.innerHTML = contacts.map(createContactRow).join('');
        }

        if (paginationLinks?.length > 0 && paginationContainer) {
            paginationContainer.innerHTML = paginationLinks.map(link => {
                const isPrev = link.label.includes('Previous') || link.label.includes('&laquo;') || link.label === '«';
                const isNext = link.label.includes('Next') || link.label.includes('&raquo;') || link.label === '»';
                const isActive = link.active ? 'bg-[#82746a] text-white' : 'text-[#9a938c]';
                const symbol = isPrev ? '&lt;' : (isNext ? '&gt;' : link.label);

                if (link.url) {
                    const href = buildUrlWithParams(currentParams, link.url);
                    const classes = isPrev || isNext
                        ? 'px-2 py-1 text-[#9a938c] hover:bg-gray-100 rounded'
                        : `px-2 py-1 rounded ${isActive} hover:bg-gray-100 text-sm`;
                    return `<a href="${href}" class="${classes}">${symbol}</a>`;
                } else {
                    return `<span class="px-2 py-1 text-gray-300 cursor-not-allowed text-sm">${symbol}</span>`;
                }
            }).join('');
        } else if (paginationContainer) {
            paginationContainer.innerHTML = '';
        }

        if (setupDetailButtonsCallback) {
            setupDetailButtonsCallback();
        }
    } catch (error) {
        console.error('Error loading contacts:', error);
        contactsTbody.innerHTML = `
            <tr>
                <td colspan="6" class="px-6 py-4 text-center text-sm text-red-600">データの読み込みに失敗しました</td>
            </tr>
        `;
    }
}
