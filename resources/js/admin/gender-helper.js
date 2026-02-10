/**
 * 共通のヘルパー関数
 */

export function getGenderText(gender) {
    return gender == 1 ? '男性' : (gender == 2 ? '女性' : 'その他');
}
