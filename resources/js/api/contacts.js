/**
 * コンタクト関連のAPI
 */

import { ApiBase } from './base.js';

export const ContactsApi = {
    async getContacts(params = {}) {
        const response = await ApiBase.get('/contacts', params);
        return response;
    },

    async getContact(id) {
        const response = await ApiBase.get(`/contacts/${id}`);
        return response.data;
    },

    async createContact(data) {
        await ApiBase.post('/contacts', data);
    },

    async deleteContact(id) {
        await ApiBase.delete(`/contacts/${id}`);
    },
};
