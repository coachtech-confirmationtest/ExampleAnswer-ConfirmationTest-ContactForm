/**
 * バリデーションメッセージの統一管理
 * サーバーサイドのContactRequestと一致させる
 */

export const ValidationMessages = {
    first_name: {
        required: '姓を入力してください'
    },
    last_name: {
        required: '名を入力してください'
    },
    gender: {
        required: '性別を選択してください'
    },
    email: {
        required: 'メールアドレスを入力してください',
        email: 'メールアドレスの形式で入力してください'
    },
    tel: {
        required: '電話番号を入力してください',
        pattern: '電話番号はハイフンなしの10〜11桁で入力してください'
    },
    address: {
        required: '住所を入力してください'
    },
    category_id: {
        required: 'お問い合わせの種類を選択してください'
    },
    detail: {
        required: 'お問い合わせ内容を入力してください',
        maxLength: 'お問い合わせ内容は120文字以内で入力してください'
    }
};

/**
 * フィールド名とバリデーションタイプからメッセージを取得
 * @param {string} field - フィールド名
 * @param {string} type - バリデーションタイプ (required, email, pattern, maxLength)
 * @returns {string} エラーメッセージ
 */
export function getValidationMessage(field, type = 'required') {
    return ValidationMessages[field]?.[type] || `${field}のバリデーションエラー`;
}
