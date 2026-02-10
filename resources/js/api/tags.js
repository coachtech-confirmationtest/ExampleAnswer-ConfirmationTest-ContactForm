/**
 * タグ関連のAPI
 */

import { ApiBase } from './base.js';

export const TagsApi = {
    async getTags() {
        const response = await ApiBase.get('/tags');
        return response.data || [];
    },

    async createTag(data) {
        await ApiBase.post('/tags', data);
    },

    async updateTag(id, data) {
        await ApiBase.put(`/tags/${id}`, data);
    },

    async deleteTag(id) {
        await ApiBase.delete(`/tags/${id}`);
    },
};
