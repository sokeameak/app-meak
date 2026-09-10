<?php
// includes/payment_modal.php - Interactive Quick Payment & Create Invoice Modal
?>
<style>
#paymentModal.modal {
    display: none;
    position: fixed !important;
    top: 0 !important;
    left: 0 !important;
    right: 0 !important;
    bottom: 0 !important;
    width: 100vw !important;
    height: 100vh !important;
    background: rgba(15, 23, 42, 0.75) !important;
    backdrop-filter: blur(5px) !important;
    -webkit-backdrop-filter: blur(5px) !important;
    z-index: 999999 !important;
    overflow-y: auto !important;
    padding: 20px !important;
    align-items: center !important;
    justify-content: center !important;
    box-sizing: border-box !important;
}
#paymentModal.modal.show {
    display: flex !important;
}
#paymentModal .modal-dialog {
    background: #ffffff !important;
    border-radius: 14px !important;
    width: 100% !important;
    max-width: 530px !important;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.3) !important;
    overflow: hidden !important;
    position: relative !important;
    margin: auto !important;
    animation: payModalSlideIn 0.22s ease-out !important;
}
@keyframes payModalSlideIn {
    from { opacity: 0; transform: translateY(-16px) scale(0.97); }
    to { opacity: 1; transform: translateY(0) scale(1); }
}
</style>

<!-- Quick Payment & Invoice Modal -->
<div id="paymentModal" class="modal" onclick="if(event.target === this) closePaymentModal();">
    <div class="modal-dialog">
        <div class="modal-header" style="background: linear-gradient(135deg, #1e3a8a, #2563eb); color: white; padding: 16px 20px;">
            <h3 style="color: white; font-size: 16px; margin: 0; display: flex; align-items: center; gap: 8px;">
                <i class="fa-solid fa-file-invoice-dollar" style="color: #34d399; font-size: 18px;"></i>
                <span>បង្កើតវិក្កយបត្រ / បង់ថ្លៃសិក្សា (Invoice & Payment)</span>
            </h3>
            <button type="button" class="modal-close" style="color: rgba(255,255,255,0.85); background: none; border: none; font-size: 22px; cursor: pointer; line-height: 1;" onclick="closePaymentModal()">&times;</button>
        </div>

        <div class="modal-body" style="padding: 18px 20px;">
            <!-- Loading Indicator -->
            <div id="payModalLoading" style="text-align: center; padding: 40px 10px;">
                <i class="fa-solid fa-circle-notch fa-spin" style="font-size: 32px; color: var(--secondary);"></i>
                <p style="margin-top: 12px; color: var(--text-muted); font-size: 14px;">កំពុងទាញយកទិន្នន័យ...</p>
            </div>

            <!-- Error Box -->
            <div id="payModalError" class="alert alert-danger" style="display: none;"></div>

            <!-- Content Area (Hidden while loading) -->
            <div id="payModalContent" style="display: none;">
                <!-- Student Header Card -->
                <div style="display: flex; gap: 14px; align-items: center; background: #f8fafc; padding: 12px; border-radius: var(--radius-md); border: 1px solid var(--border-color); margin-bottom: 14px;">
                    <img id="payStudentPhoto" src="" alt="Photo" style="width: 52px; height: 52px; object-fit: cover; border-radius: 50%; border: 2px solid #cbd5e1;" onerror="this.src='<?php echo base_url('logo/meakea.png'); ?>';">
                    <div style="flex: 1;">
                        <h4 id="payStudentName" style="font-size: 17px; font-weight: 700; color: var(--primary); margin: 0 0 4px 0;"></h4>
                        <div style="display: flex; flex-wrap: wrap; gap: 6px; font-size: 12px;">
                            <span id="payStudentSchool" class="badge badge-info"></span>
                            <span id="payStudentTime" class="badge badge-warning"></span>
                        </div>
                        <div id="payStudentCourse" style="font-size: 12px; color: var(--text-muted); margin-top: 4px;"></div>
                    </div>
                </div>

                <!-- Financial Status Summary (3 Stats) -->
                <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; text-align: center; margin-bottom: 16px;">
                    <div style="background: #fffbeb; border: 1px solid #fde68a; padding: 10px 6px; border-radius: var(--radius-sm);">
                        <span style="font-size: 11px; color: #92400e; display: block; font-weight: 600;">តម្លៃសិក្សា (Fee)</span>
                        <strong id="payTotalFee" style="font-size: 16px; color: #b45309;">$0.00</strong>
                    </div>
                    <div style="background: #ecfdf5; border: 1px solid #a7f3d0; padding: 10px 6px; border-radius: var(--radius-sm);">
                        <span style="font-size: 11px; color: #065f46; display: block; font-weight: 600;">បានបង់ (Paid)</span>
                        <strong id="payTotalPaid" style="font-size: 16px; color: #10b981;">$0.00</strong>
                    </div>
                    <div id="payRemainBox" style="background: #fef2f2; border: 1px solid #fecaca; padding: 10px 6px; border-radius: var(--radius-sm);">
                        <span style="font-size: 11px; color: #991b1b; display: block; font-weight: 600;">នៅខ្វះ (Remain)</span>
                        <strong id="payTotalRemain" style="font-size: 16px; color: #ef4444;">$0.00</strong>
                    </div>
                </div>

                <!-- Existing Unpaid Invoices Section (if any) -->
                <div id="payUnpaidSection" style="display: none; margin-bottom: 16px; background: #fffbeb; border: 1px solid #fde68a; border-radius: var(--radius-md); padding: 12px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                        <span style="font-weight: 700; color: #92400e; font-size: 13px;">
                            <i class="fa-solid fa-triangle-exclamation"></i> វិក្កយបត្រមិនទាន់ទូទាត់ (Unpaid)
                        </span>
                    </div>
                    <div id="payUnpaidList" style="display: flex; flex-direction: column; gap: 6px;"></div>
                </div>

                <!-- Payment Form -->
                <form id="quickPayForm" onsubmit="handleQuickPaySubmit(event)">
                    <input type="hidden" id="formStudentId" name="student_id" value="">
                    <input type="hidden" id="formStudentName" name="student_name" value="">
                    <input type="hidden" id="formSchoolId" name="school_id" value="">
                    <input type="hidden" id="formStudyTime" name="study_time" value="">

                    <div class="form-group" style="margin-bottom: 12px;">
                        <label for="formPayAmount" style="font-size: 13px; font-weight: 700; color: #1e293b; display: flex; justify-content: space-between;">
                            <span>ចំនួនទឹកប្រាក់ ($) <span style="color: #ef4444;">*</span></span>
                            <span id="quickPayFillAll" style="font-size: 11px; color: #2563eb; cursor: pointer; text-decoration: underline;" onclick="fillMaxRemain()">បង់គ្រប់ចំនួនខ្វះ</span>
                        </label>
                        <div style="position: relative;">
                            <span style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); font-weight: 700; color: #64748b;">$</span>
                            <input type="number" step="0.01" id="formPayAmount" name="amount" class="form-control" required style="padding-left: 28px; font-size: 16px; font-weight: 700; color: #065f46;" placeholder="0.00">
                        </div>
                    </div>

                    <div class="form-group" style="margin-bottom: 12px;">
                        <label for="formPayDesc" style="font-size: 13px; font-weight: 600; color: #1e293b;">បរិយាយ / កំណត់ចំណាំ (Description)</label>
                        <input type="text" id="formPayDesc" name="description" class="form-control" placeholder="ឧ. បង់ថ្លៃសិក្សាវគ្គកុំព្យូទ័រ, សៀវភៅ..." value="បង់ថ្លៃសិក្សា" style="font-size: 13px;">
                    </div>

                    <div class="form-group" style="margin-bottom: 14px;">
                        <label for="formPayStatus" style="font-size: 13px; font-weight: 600; color: #1e293b;">ស្ថានភាពវិក្កយបត្រ (Invoice Status)</label>
                        <select id="formPayStatus" name="status" class="form-control" style="font-size: 13px; font-weight: 600;">
                            <option value="Paid" selected>Paid (បានបង់រួច - បង្កើត & ទូទាត់ភ្លាម)</option>
                            <option value="Unpaid">Unpaid (មិនទាន់បង់ - កត់ត្រាជំពាក់)</option>
                            <option value="Pending">Pending (រង់ចាំការទូទាត់)</option>
                        </select>
                    </div>

                    <div style="display: flex; gap: 8px; margin-top: 16px;">
                        <button type="submit" id="btnSubmitPayment" class="btn btn-success" style="flex: 1; padding: 11px; font-size: 14px; font-weight: 700;">
                            <i class="fa-solid fa-floppy-disk"></i> រក្សាទុកវិក្កយបត្រ / បង់ប្រាក់ (Save Invoice)
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="modal-footer" style="padding: 12px 20px; background: #f8fafc; border-top: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center;">
            <a id="modalFullInvoiceLink" href="<?php echo base_url('invoice/invoice.php'); ?>" class="btn btn-primary btn-sm" style="font-size: 13px; font-weight: 600;">
                <i class="fa-solid fa-arrow-up-right-from-square"></i> ទំព័រវិក្កយបត្រពេញលេញ
            </a>
            <button type="button" class="btn btn-secondary btn-sm" onclick="closePaymentModal()">បិទ (Close)</button>
        </div>
    </div>
</div>

<script>
var currentModalStudentRemain = 0;

function openPaymentModal(studentId, studentName) {
    try {
        var modal = document.getElementById('paymentModal');
        var loading = document.getElementById('payModalLoading');
        var errorBox = document.getElementById('payModalError');
        var content = document.getElementById('payModalContent');

        if (!modal) {
            window.location.href = '<?php echo base_url('invoice/invoice.php?student_id='); ?>' + (studentId || '');
            return;
        }

        modal.classList.add('show');
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';

        loading.style.display = 'block';
        errorBox.style.display = 'none';
        content.style.display = 'none';

        var sId = parseInt(studentId);
        var fullInvoiceLink = document.getElementById('modalFullInvoiceLink');
        if (fullInvoiceLink) {
            fullInvoiceLink.href = '<?php echo base_url('invoice/invoice.php?student_id='); ?>' + (isNaN(sId) ? '' : sId);
        }

        var url = '<?php echo base_url('invoice/quick_pay.php'); ?>?ajax=1';
        if (!isNaN(sId) && sId > 0) {
            url += '&student_id=' + sId;
        } else if (studentName && String(studentName).trim() !== '') {
            url += '&name=' + encodeURIComponent(studentName.trim());
        } else if (typeof studentId === 'string' && studentId.trim() !== '') {
            url += '&name=' + encodeURIComponent(studentId.trim());
        }

        fetch(url, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(function(res) {
            if (!res.ok) throw new Error('HTTP ' + res.status);
            return res.json();
        })
        .then(function(data) {
            loading.style.display = 'none';
            if (!data.success) {
                errorBox.innerHTML = '<strong>' + (data.message || 'Error loading student info') + '</strong><br><a href="<?php echo base_url('invoice/invoice.php?student_id='); ?>' + (sId || '') + '" class="btn btn-primary btn-sm" style="margin-top: 10px; display: inline-flex; align-items: center; gap: 6px;"><i class="fa-solid fa-arrow-up-right-from-square"></i> ទៅកាន់ទំព័រវិក្កយបត្រពេញលេញ</a>';
                errorBox.style.display = 'block';
                return;
            }

            content.style.display = 'block';
            var stu = data.student;
            var study = data.study;
            var fin = data.financials;

            currentModalStudentRemain = parseFloat(fin.remain || 0);

            // Update full invoice link
            if (fullInvoiceLink) {
                fullInvoiceLink.href = '<?php echo base_url('invoice/invoice.php?student_id='); ?>' + stu.id;
            }

            // Header info
            document.getElementById('payStudentName').textContent = stu.student_name + ' (#' + stu.id + ')';
            document.getElementById('payStudentSchool').textContent = stu.school_name || 'សាខា';
            
            var photoUrl = stu.photo ? '<?php echo base_url('uploads/'); ?>' + stu.photo : '<?php echo base_url('logo/meakea.png'); ?>';
            document.getElementById('payStudentPhoto').src = photoUrl;

            if (study) {
                document.getElementById('payStudentTime').textContent = study.time || 'N/A';
                document.getElementById('payStudentTime').style.display = 'inline-block';
                document.getElementById('payStudentCourse').textContent = study.course ? ('វគ្គ៖ ' + study.course) : '';
                document.getElementById('formStudyTime').value = study.time || '';
            } else {
                document.getElementById('payStudentTime').style.display = 'none';
                document.getElementById('payStudentCourse').textContent = 'មិនទាន់មានវគ្គសិក្សា';
                document.getElementById('formStudyTime').value = '';
            }

            // Financial stats
            document.getElementById('payTotalFee').textContent = '$' + parseFloat(fin.total_price || 0).toFixed(2);
            document.getElementById('payTotalPaid').textContent = '$' + parseFloat(fin.total_paid || 0).toFixed(2);
            document.getElementById('payTotalRemain').textContent = '$' + currentModalStudentRemain.toFixed(2);

            var remainBox = document.getElementById('payRemainBox');
            if (currentModalStudentRemain <= 0) {
                remainBox.style.background = '#ecfdf5';
                remainBox.style.borderColor = '#a7f3d0';
                document.getElementById('payTotalRemain').style.color = '#10b981';
            } else {
                remainBox.style.background = '#fef2f2';
                remainBox.style.borderColor = '#fecaca';
                document.getElementById('payTotalRemain').style.color = '#ef4444';
            }

            // Hidden form values
            document.getElementById('formStudentId').value = stu.id;
            document.getElementById('formStudentName').value = stu.student_name;
            document.getElementById('formSchoolId').value = stu.school_id;

            // Prefill amount: If there is remaining balance, prefill it; else prefill course price or empty
            var defaultAmount = currentModalStudentRemain > 0 ? currentModalStudentRemain : (study ? study.price : 0);
            document.getElementById('formPayAmount').value = defaultAmount > 0 ? parseFloat(defaultAmount).toFixed(2) : '';

            // Unpaid Invoices list
            var unpaidSection = document.getElementById('payUnpaidSection');
            var unpaidList = document.getElementById('payUnpaidList');
            unpaidList.innerHTML = '';

            if (data.unpaid_invoices && data.unpaid_invoices.length > 0) {
                unpaidSection.style.display = 'block';
                data.unpaid_invoices.forEach(function(inv) {
                    var item = document.createElement('div');
                    item.style.cssText = 'display: flex; justify-content: space-between; align-items: center; background: white; padding: 8px 12px; border-radius: 6px; border: 1px solid #fde68a;';
                    item.innerHTML = `
                        <div>
                            <strong style="color: #92400e;">#INV-${inv.id}</strong>
                            <span style="font-size: 12px; color: #64748b; margin-left: 6px;">${inv.description || 'វគ្គសិក្សា'}</span>
                            <div style="font-size: 13px; font-weight: 700; color: #b45309;">$${parseFloat(inv.amount).toFixed(2)}</div>
                        </div>
                        <button type="button" class="btn btn-sm btn-success" onclick="payExistingInvoice(${inv.id}, '${inv.amount}')" style="padding: 4px 10px; font-size: 12px;">
                            <i class="fa-solid fa-check"></i> បង់វិក្កយបត្រនេះ
                        </button>
                    `;
                    unpaidList.appendChild(item);
                });
            } else {
                unpaidSection.style.display = 'none';
            }
        })
        .catch(function(err) {
            loading.style.display = 'none';
            errorBox.innerHTML = 'កំហុសបណ្តាញ៖ មិនអាចទាញយកទិន្នន័យបានទេ។<br><a href="<?php echo base_url('invoice/invoice.php?student_id='); ?>' + (sId || '') + '" class="btn btn-primary btn-sm" style="margin-top: 10px; display: inline-flex; align-items: center; gap: 6px;"><i class="fa-solid fa-arrow-up-right-from-square"></i> ទៅកាន់ទំព័រវិក្កយបត្រពេញលេញ</a>';
            errorBox.style.display = 'block';
        });
    } catch (e) {
        console.error('Modal error:', e);
        window.location.href = '<?php echo base_url('invoice/invoice.php?student_id='); ?>' + (studentId || '');
    }
}

function fillMaxRemain() {
    if (currentModalStudentRemain > 0) {
        document.getElementById('formPayAmount').value = currentModalStudentRemain.toFixed(2);
    }
}

function closePaymentModal() {
    var modal = document.getElementById('paymentModal');
    if (modal) {
        modal.classList.remove('show');
        modal.style.display = 'none';
        document.body.style.overflow = '';
    }
}

function payExistingInvoice(invoiceId, amount) {
    if (!confirm('តើអ្នកពិតជាចង់កំណត់វិក្កយបត្រ #' + invoiceId + ' ($' + parseFloat(amount).toFixed(2) + ') ថាបានបង់រួច (Paid) មែនទេ?')) {
        return;
    }

    var formData = new FormData();
    formData.append('action', 'pay_invoice');
    formData.append('invoice_id', invoiceId);
    formData.append('ajax', '1');

    fetch('<?php echo base_url('invoice/quick_pay.php'); ?>', {
        method: 'POST',
        body: formData,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(function(res) { return res.json(); })
    .then(function(data) {
        if (data.success) {
            alert('✓ ' + data.message);
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(function(err) {
        alert('កំហុសក្នុងការតភ្ជាប់៖ ' + err);
    });
}

function handleQuickPaySubmit(event) {
    event.preventDefault();
    var amount = parseFloat(document.getElementById('formPayAmount').value);
    if (isNaN(amount) || amount <= 0) {
        alert('សូមបញ្ចូលចំនួនទឹកប្រាក់ដែលត្រូវបង់ឱ្យបានត្រឹមត្រូវ (> 0)!');
        return;
    }

    var btn = document.getElementById('btnSubmitPayment');
    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin"></i> កំពុងរក្សាទុក...';

    var form = document.getElementById('quickPayForm');
    var formData = new FormData(form);
    formData.append('action', 'create_payment');
    formData.append('ajax', '1');

    fetch('<?php echo base_url('invoice/quick_pay.php'); ?>', {
        method: 'POST',
        body: formData,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(function(res) { return res.json(); })
    .then(function(data) {
        btn.disabled = false;
        btn.innerHTML = '<i class="fa-solid fa-floppy-disk"></i> រក្សាទុកវិក្កយបត្រ / បង់ប្រាក់ (Save Invoice)';
        if (data.success) {
            alert('✓ ' + data.message);
            location.reload();
        } else {
            alert('កំហុស៖ ' + data.message);
        }
    })
    .catch(function(err) {
        btn.disabled = false;
        btn.innerHTML = '<i class="fa-solid fa-floppy-disk"></i> រក្សាទុកវិក្កយបត្រ / បង់ប្រាក់ (Save Invoice)';
        alert('កំហុសក្នុងការតភ្ជាប់៖ ' + err);
    });
}

// Close modal on Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closePaymentModal();
    }
});
</script>
