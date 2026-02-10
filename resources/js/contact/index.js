/**
 * お問い合わせフォーム用のJavaScript（初期化と統合）
 */

import { initPhoneField } from './phone-field-handler.js';
import { initCategorySelect } from './category-select-loader.js';
import { initTagSelect } from './tag-select-loader.js';
import { ContactsApi } from '../api/contacts.js';
import { CategoriesApi } from '../api/categories.js';
import { TagsApi } from '../api/tags.js';
import { getValidationMessage } from '../validation-messages.js';

document.addEventListener('DOMContentLoaded', function() {
    initPhoneField();
    initCategorySelect();
    initTagSelect();
    initFormValidation();
    initConfirmSection();
});

// バリデーションルール定義
const validationRules = {
    first_name: {
        required: true
    },
    last_name: {
        required: true
    },
    gender: {
        required: true
    },
    email: {
        required: true,
        email: true
    },
    tel: {
        required: true,
        pattern: /^[0-9]{10,11}$/
    },
    address: {
        required: true
    },
    category_id: {
        required: true
    },
    detail: {
        required: true,
        maxLength: 120
    }
};

/**
 * 電話番号を結合して取得
 * @param {FormData} formData - フォームデータ
 * @returns {string} 結合された電話番号
 */
function getCombinedTel(formData) {
    const tel1 = formData.get('tel1') || '';
    const tel2 = formData.get('tel2') || '';
    const tel3 = formData.get('tel3') || '';
    return tel1 + tel2 + tel3;
}

/**
 * フィールドの値を取得（電話番号の場合は結合）
 * @param {FormData} formData - フォームデータ
 * @param {string} field - フィールド名
 * @returns {string} フィールドの値
 */
function getFieldValue(formData, field) {
    if (field === 'tel') {
        return getCombinedTel(formData);
    }
    return formData.get(field) || '';
}

/**
 * 必須チェック
 * @param {string} value - チェックする値
 * @param {string} field - フィールド名
 * @returns {string|null} エラーメッセージ（エラーがない場合はnull）
 */
function validateRequired(value, field) {
    if (!value || value.trim() === '') {
        return getValidationMessage(field, 'required');
    }
    return null;
}

/**
 * メールアドレス形式チェック
 * @param {string} value - チェックする値
 * @param {string} field - フィールド名
 * @returns {string|null} エラーメッセージ（エラーがない場合はnull）
 */
function validateEmail(value, field) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(value)) {
        return getValidationMessage(field, 'email');
    }
    return null;
}

/**
 * パターンチェック
 * @param {string} value - チェックする値
 * @param {RegExp} pattern - 正規表現パターン
 * @param {string} field - フィールド名
 * @returns {string|null} エラーメッセージ（エラーがない場合はnull）
 */
function validatePattern(value, pattern, field) {
    if (!pattern.test(value)) {
        return getValidationMessage(field, 'pattern');
    }
    return null;
}

/**
 * 最大文字数チェック
 * @param {string} value - チェックする値
 * @param {number} maxLength - 最大文字数
 * @param {string} field - フィールド名
 * @returns {string|null} エラーメッセージ（エラーがない場合はnull）
 */
function validateMaxLength(value, maxLength, field) {
    if (value.length > maxLength) {
        return getValidationMessage(field, 'maxLength');
    }
    return null;
}

/**
 * 単一フィールドのバリデーション
 * @param {FormData} formData - フォームデータ
 * @param {string} field - フィールド名
 * @param {Object} rule - バリデーションルール
 * @returns {string|null} エラーメッセージ（エラーがない場合はnull）
 */
function validateField(formData, field, rule) {
    const value = getFieldValue(formData, field);

    // 必須チェック
    if (rule.required) {
        const requiredError = validateRequired(value, field);
        if (requiredError) {
            return requiredError;
        }
    }

    // 値が空の場合は以降のチェックをスキップ
    if (!value || value.trim() === '') {
        return null;
    }

    // メールアドレス形式チェック
    if (rule.email) {
        const emailError = validateEmail(value, field);
        if (emailError) {
            return emailError;
        }
    }

    // パターンチェック
    if (rule.pattern) {
        const patternError = validatePattern(value, rule.pattern, field);
        if (patternError) {
            return patternError;
        }
    }

    // 最大文字数チェック
    if (rule.maxLength) {
        const maxLengthError = validateMaxLength(value, rule.maxLength, field);
        if (maxLengthError) {
            return maxLengthError;
        }
    }

    return null;
}

/**
 * フォーム全体のバリデーション
 * @returns {{isValid: boolean, errors: Object, formData: FormData}}
 */
function validateForm() {
    const form = document.getElementById('contact-form');
    const formData = new FormData(form);
    const errors = {};

    // 各フィールドをバリデーション
    for (const [field, rule] of Object.entries(validationRules)) {
        const error = validateField(formData, field, rule);
        if (error) {
            errors[field] = error;
        }
    }

    return { isValid: Object.keys(errors).length === 0, errors, formData };
}

/**
 * エラー表示関数
 * @param {Object} errors - エラーオブジェクト（フィールド名: エラーメッセージ）
 */
function showErrors(errors) {
    // 既存のエラーをクリア
    document.querySelectorAll('.validation-error').forEach(el => el.remove());

    // 新しいエラーを表示
    for (const [field, message] of Object.entries(errors)) {
        let parent = null;

        if (field === 'tel') {
            // 電話番号の場合はtel3の親要素を取得
            const tel3 = document.getElementById('tel3');
            parent = tel3?.closest('.md\\:col-span-2');
        } else {
            // その他のフィールド
            const input = document.querySelector(`[name="${field}"]`);
            parent = input?.closest('.md\\:col-span-2') || input?.parentElement;
        }

        if (parent) {
            const errorDiv = document.createElement('div');
            errorDiv.className = 'mt-1 text-sm text-red-600 validation-error';
            errorDiv.textContent = message;
            parent.appendChild(errorDiv);
        }
    }
}

// フォームバリデーション初期化
function initFormValidation() {
    const confirmBtn = document.getElementById('confirm-btn');
    if (!confirmBtn) return;

    confirmBtn.addEventListener('click', function(e) {
        e.preventDefault();
        
        const validation = validateForm();
        
        if (!validation.isValid) {
            showErrors(validation.errors);
            return;
        }

        // バリデーション成功時、確認画面を表示
        showConfirmSection(validation.formData);
    });
}

/**
 * 電話番号をフォーマット
 * @param {string} tel - 電話番号（数字のみ）
 * @returns {string} フォーマットされた電話番号
 */
function formatTel(tel) {
    const telLength = tel.length;
    if (telLength >= 11) {
        return tel.substring(0, 3) + '-' + tel.substring(3, 7) + '-' + tel.substring(7);
    } else if (telLength >= 10) {
        return tel.substring(0, 2) + '-' + tel.substring(2, 6) + '-' + tel.substring(6);
    }
    return tel;
}

// 確認画面表示
async function showConfirmSection(formData) {
    const formSection = document.getElementById('form-section');
    const confirmSection = document.getElementById('confirm-section');
    const pageTitle = document.getElementById('page-title');

    if (!formSection || !confirmSection || !pageTitle) return;

    // 電話番号を結合
    const tel = getCombinedTel(formData);

    // 性別ラベル
    const genderLabels = { '1': '男性', '2': '女性', '3': 'その他' };

    // 電話番号フォーマット
    const telFormatted = formatTel(tel);

    // カテゴリ名を取得
    let categoryName = '';
    const categoryId = formData.get('category_id');
    if (categoryId) {
        try {
            const categories = await CategoriesApi.getCategories();
            const category = categories.find(c => c.id == categoryId);
            categoryName = category ? category.content : '';
        } catch (error) {
            console.error('カテゴリの取得に失敗しました:', error);
        }
    }

    // 確認画面に値を設定
    document.getElementById('confirm-name').textContent = 
        `${formData.get('first_name')} ${formData.get('last_name')}`;
    document.getElementById('confirm-gender').textContent = 
        genderLabels[formData.get('gender')] || 'その他';
    document.getElementById('confirm-email').textContent = formData.get('email');
    document.getElementById('confirm-tel').textContent = telFormatted;
    document.getElementById('confirm-address').textContent = formData.get('address');
    
    const building = formData.get('building');
    const buildingRow = document.getElementById('confirm-building-row');
    if (building && building.trim()) {
        document.getElementById('confirm-building').textContent = building;
        buildingRow.classList.remove('hidden');
    } else {
        buildingRow.classList.add('hidden');
    }
    
    document.getElementById('confirm-category').textContent = categoryName;

    // タグ名を取得して表示
    const tagIds = formData.getAll('tag_ids[]');
    const tagsRow = document.getElementById('confirm-tags-row');
    if (tagIds.length > 0) {
        try {
            const tags = await TagsApi.getTags();
            const selectedTagNames = tags
                .filter(tag => tagIds.includes(String(tag.id)))
                .map(tag => tag.name);
            document.getElementById('confirm-tags').textContent = selectedTagNames.join(', ');
            tagsRow.classList.remove('hidden');
        } catch (error) {
            console.error('タグの取得に失敗しました:', error);
            tagsRow.classList.add('hidden');
        }
    } else {
        tagsRow.classList.add('hidden');
    }

    document.getElementById('confirm-detail').textContent = formData.get('detail');

    // 表示切り替え
    formSection.classList.add('hidden');
    confirmSection.classList.remove('hidden');
    pageTitle.textContent = 'Confirm';
    
    // ページトップにスクロール
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

// 確認画面の初期化
function initConfirmSection() {
    const backBtn = document.getElementById('back-btn');
    const submitBtn = document.getElementById('submit-btn');

    // 戻るボタン
    if (backBtn) {
        backBtn.addEventListener('click', function() {
            const formSection = document.getElementById('form-section');
            const confirmSection = document.getElementById('confirm-section');
            const pageTitle = document.getElementById('page-title');

            if (formSection && confirmSection && pageTitle) {
                formSection.classList.remove('hidden');
                confirmSection.classList.add('hidden');
                pageTitle.textContent = 'Contact';
                
                // エラーメッセージをクリア
                document.querySelectorAll('.validation-error').forEach(el => el.remove());
                
                // ページトップにスクロール
                window.scrollTo({ top: 0, behavior: 'smooth' });
            }
        });
    }

    // 送信ボタン
    if (submitBtn) {
        submitBtn.addEventListener('click', async function(e) {
            e.preventDefault();
            
            const form = document.getElementById('contact-form');
            const formData = new FormData(form);
            
            // 電話番号を結合
            const tel = getCombinedTel(formData);

            // 送信用データを準備
            const tagIds = formData.getAll('tag_ids[]').map(id => parseInt(id));
            const data = {
                first_name: formData.get('first_name'),
                last_name: formData.get('last_name'),
                gender: parseInt(formData.get('gender')),
                email: formData.get('email'),
                tel: tel,
                address: formData.get('address'),
                building: formData.get('building') || '',
                category_id: parseInt(formData.get('category_id')),
                tag_ids: tagIds,
                detail: formData.get('detail')
            };

            const originalText = submitBtn.textContent;
            submitBtn.disabled = true;
            submitBtn.textContent = '送信中...';

            try {
                await ContactsApi.createContact(data);
                // 成功したらthanks画面にリダイレクト
                window.location.href = '/thanks';
            } catch (error) {
                console.error('Error:', error);
                let errorMessage = '送信に失敗しました。';
                
                // バリデーションエラーの場合
                if (error.data && error.data.errors) {
                    const errorMessages = Object.values(error.data.errors).flat();
                    errorMessage = errorMessages.join('\n');
                } else if (error.data && error.data.message) {
                    errorMessage = error.data.message;
                } else if (error.message) {
                    errorMessage = error.message;
                }
                
                alert(errorMessage);
                submitBtn.disabled = false;
                submitBtn.textContent = originalText;
            }
        });
    }
}
