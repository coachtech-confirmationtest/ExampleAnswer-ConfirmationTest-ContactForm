/**
 * 電話番号フィールドの結合処理
 */

export function initPhoneField() {
    const tel1 = document.getElementById('tel1');
    const tel2 = document.getElementById('tel2');
    const tel3 = document.getElementById('tel3');
    const telHidden = document.getElementById('tel');
    const form = document.querySelector('form');

    if (!tel1 || !tel2 || !tel3 || !telHidden || !form) {
        return;
    }

    function updateTel() {
        const telValue = tel1.value + tel2.value + tel3.value;
        telHidden.value = telValue.replace(/^-|-$/g, '').replace(/--+/g, '-');
    }

    tel1.addEventListener('input', updateTel);
    tel2.addEventListener('input', updateTel);
    tel3.addEventListener('input', updateTel);
    form.addEventListener('submit', updateTel);
}
