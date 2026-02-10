/**
 * CSVエクスポート機能のハンドラー
 */

export function initExport() {
    const exportBtn = document.getElementById('export-btn');
    if (!exportBtn) return;

    exportBtn.addEventListener('click', () => {
        const params = new URLSearchParams(window.location.search);
        window.location.href = '/contacts/export?' + params.toString();
    });
}
