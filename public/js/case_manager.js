/* Gợi ý đầu mối theo chuyên môn nhân sự hiện có, luôn để người tạo quyết định. */
document.addEventListener('DOMContentLoaded', () => {
    const select = document.getElementById('case_manager_id');
    if (select) {
        const title = document.getElementById('title');
        const workflow = document.getElementById('workflow_template_id');
        const hint = document.getElementById('case-manager-hint');
        const button = document.getElementById('case-manager-suggest');
        const normalize = (value) => value.normalize('NFD').replace(/[\u0300-\u036f]/g, '').replace(/đ/g, 'd').replace(/Đ/g, 'D').toLowerCase();
        let suggested = '';
        const update = () => {
            const text = normalize(`${title?.value || ''} ${workflow?.selectedOptions[0]?.text || ''}`);
            const candidates = Array.from(select.options).filter((option) => option.value).map((option) => ({
                option,
                score: (option.dataset.specialties || '').split(/[,;\n]/).map((value) => normalize(value.trim()))
                    .filter((value) => value.length > 1 && text.includes(value)).length,
            })).filter((entry) => entry.score > 0).sort((a, b) => b.score - a.score);
            const unique = candidates[0] && (!candidates[1] || candidates[0].score > candidates[1].score);
            suggested = unique ? candidates[0].option.value : '';
            hint.textContent = unique ? `Gợi ý: ${candidates[0].option.text}, dựa trên chuyên môn nhân sự.`
                : 'Người quản lý theo dõi toàn bộ vụ được giao và phân người thực hiện.';
            button.hidden = !suggested || select.value === suggested;
        };
        title?.addEventListener('input', update);
        workflow?.addEventListener('change', update);
        if (window.jQuery && workflow) { window.jQuery(workflow).on('select2:select', update); }
        select.addEventListener('change', update);
        button.addEventListener('click', () => {
            select.value = suggested;
            if (window.jQuery) { window.jQuery(select).trigger('change'); }
            update();
        });
        update();
    }
    const caseTable = document.getElementById('cases-table-container');
    // Cùng một trạng thái lựa chọn cho giao quản lý và xóa, kể cả khi bảng vừa được tải lại bằng AJAX.
    const syncSelection = () => {
        if (!caseTable) { return; }
        const bar = caseTable.querySelector('.case-selection-bar');
        if (!bar) { return; }
        const boxes = Array.from(caseTable.querySelectorAll('.case-record-check'));
        const count = boxes.filter((box) => box.checked).length;
        const summary = bar.querySelector('[data-case-selected-count]');
        bar.hidden = count === 0;
        if (summary && summary.textContent !== String(count)) { summary.textContent = String(count); }
        const checkAll = caseTable.querySelector('#check-all');
        if (checkAll) {
            checkAll.checked = boxes.length > 0 && count === boxes.length;
            checkAll.indeterminate = count > 0 && count < boxes.length;
        }
        if (count === 0) { bar.querySelector('form')?.reset(); }
    };
    document.addEventListener('change', (event) => {
        if (!caseTable?.contains(event.target)) { return; }
        if (event.target.matches('#check-all')) {
            caseTable.querySelectorAll('.case-record-check').forEach((box) => { box.checked = event.target.checked; });
        }
        if (event.target.matches('#check-all, .case-record-check')) { syncSelection(); }
    });
    document.addEventListener('click', (event) => {
        if (!caseTable?.contains(event.target) || !event.target.closest('.case-selection-clear')) { return; }
        caseTable.querySelectorAll('.case-record-check').forEach((box) => { box.checked = false; });
        syncSelection();
    });
    if (caseTable) {
        new MutationObserver(syncSelection).observe(caseTable, { childList: true, subtree: true });
        syncSelection();
    }
    // Chỉ đưa ID của những vụ đang chọn vào POST, máy chủ tiếp tục kiểm tra quyền như trước.
    document.addEventListener('submit', (event) => {
        const form = event.target.closest('#case-manager-bulk-form');
        if (!form) { return; }
        form.querySelectorAll('[data-case-manager-id]').forEach((field) => field.remove());
        const selected = caseTable?.querySelectorAll('.case-record-check:checked') ?? [];
        if (!selected.length) {
            event.preventDefault();
            window.alert('Hãy chọn ít nhất một vụ việc.');
            return;
        }
        selected.forEach((checkbox) => {
            const field = document.createElement('input');
            field.type = 'hidden'; field.name = 'case_ids[]'; field.value = checkbox.value;
            field.dataset.caseManagerId = 'true'; form.append(field);
        });
    });
});
