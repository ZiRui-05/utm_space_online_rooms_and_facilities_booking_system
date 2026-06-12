<?php
$paymentReceiptUploadUrl = $paymentReceiptUploadUrl ?? '../../api/booking/upload_receipt.php';
?>
<style>
    .btn-payment {
        background: var(--primary-color);
        color: var(--white);
        border: none;
        padding: 7px 12px;
        border-radius: 4px;
        font-size: 12px;
        font-weight: 700;
        cursor: pointer;
        white-space: nowrap;
        transition: background 0.3s;
    }

    .btn-payment:hover {
        background: var(--primary-hover);
    }

    .btn-payment:disabled {
        cursor: not-allowed;
        opacity: 0.65;
    }

    .payment-modal-backdrop {
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, 0.45);
        display: none;
        align-items: center;
        justify-content: center;
        padding: 20px;
        z-index: 9999;
    }

    .payment-modal {
        width: min(520px, 100%);
        background: var(--white);
        border-radius: 8px;
        box-shadow: 0 18px 40px rgba(0, 0, 0, 0.22);
        padding: 24px;
    }

    .payment-modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 16px;
        margin-bottom: 16px;
    }

    .payment-modal-header h3 {
        margin: 0;
        font-size: 18px;
        color: var(--text-dark);
    }

    .payment-modal-close {
        width: 32px;
        height: 32px;
        border: none;
        border-radius: 50%;
        background: var(--bg-light);
        color: var(--text-dark);
        cursor: pointer;
        font-size: 20px;
        line-height: 1;
    }

    .payment-instruction {
        color: var(--text-light);
        font-size: 14px;
        line-height: 1.6;
        margin-bottom: 18px;
    }

    .payment-reference {
        background: var(--bg-light);
        border: 1px solid var(--border-light);
        border-radius: 6px;
        padding: 12px;
        margin-bottom: 18px;
        font-size: 13px;
        color: var(--text-dark);
    }

    .payment-modal-actions {
        display: flex;
        align-items: center;
        gap: 12px;
        flex-wrap: wrap;
    }

    .payment-upload-status {
        color: var(--text-light);
        font-size: 12px;
    }
</style>

<div class="payment-modal-backdrop" id="payment-modal-backdrop" aria-hidden="true">
    <div class="payment-modal" role="dialog" aria-modal="true" aria-labelledby="payment-modal-title">
        <div class="payment-modal-header">
            <h3 id="payment-modal-title">Complete Payment</h3>
            <button type="button" class="payment-modal-close" id="payment-modal-close" aria-label="Close payment dialog">&times;</button>
        </div>
        <p class="payment-instruction">
            You may manually make a payment by transferring to official bank account number:
            <strong>202620270382</strong> with reference:
            <strong>"SPACEBOOK" + "booking_id"</strong>
        </p>
        <div class="payment-reference" id="payment-reference-text"></div>
        <input type="file" id="payment-receipt-input" accept="image/jpeg,image/png,image/webp,application/pdf" hidden>
        <div class="payment-modal-actions">
            <button type="button" class="btn-payment" id="payment-upload-button">Upload Receipt</button>
            <span class="payment-upload-status" id="payment-upload-status">JPG, PNG, WEBP, or PDF. Max 5MB.</span>
        </div>
    </div>
</div>

<script>
(function () {
    const uploadUrl = <?= json_encode($paymentReceiptUploadUrl, JSON_UNESCAPED_SLASHES) ?>;
    const allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'application/pdf'];
    const maxBytes = 5 * 1024 * 1024;
    const defaultStatus = 'JPG, PNG, WEBP, or PDF. Max 5MB.';
    let activeBookingId = null;
    let callbacks = {};
    let uploading = false;

    const backdrop = document.getElementById('payment-modal-backdrop');
    const closeButton = document.getElementById('payment-modal-close');
    const uploadButton = document.getElementById('payment-upload-button');
    const receiptInput = document.getElementById('payment-receipt-input');
    const referenceText = document.getElementById('payment-reference-text');
    const uploadStatus = document.getElementById('payment-upload-status');

    function open(bookingId, nextCallbacks = {}) {
        const normalizedBookingId = Number(bookingId);
        if (!Number.isInteger(normalizedBookingId) || normalizedBookingId <= 0) {
            throw new Error('A valid booking ID is required to open the payment dialog.');
        }

        activeBookingId = normalizedBookingId;
        callbacks = nextCallbacks || {};
        receiptInput.value = '';
        uploadStatus.textContent = defaultStatus;
        referenceText.innerHTML =
            `<strong>Booking ID:</strong> ${normalizedBookingId}<br><strong>Suggested reference:</strong> SPACEBOOK${normalizedBookingId}`;
        backdrop.style.display = 'flex';
        backdrop.setAttribute('aria-hidden', 'false');
        closeButton.focus();
    }

    function close(options = {}) {
        if (uploading) return;

        const onClose = callbacks.onClose;
        activeBookingId = null;
        callbacks = {};
        receiptInput.value = '';
        backdrop.style.display = 'none';
        backdrop.setAttribute('aria-hidden', 'true');

        if (!options.silent && typeof onClose === 'function') {
            onClose();
        }
    }

    async function uploadReceipt(file) {
        if (!file || !activeBookingId || uploading) return;

        if (!allowedTypes.includes(file.type)) {
            uploadStatus.textContent = 'Invalid file type. Please choose JPG, PNG, WEBP, or PDF.';
            alert('Receipt must be JPG, PNG, WEBP, or PDF.');
            receiptInput.value = '';
            return;
        }

        if (file.size <= 0 || file.size > maxBytes) {
            uploadStatus.textContent = 'Invalid file size. Maximum file size is 5MB.';
            alert('Receipt file must be 5MB or smaller.');
            receiptInput.value = '';
            return;
        }

        uploading = true;
        uploadButton.disabled = true;
        closeButton.disabled = true;
        uploadStatus.textContent = 'Uploading receipt...';

        const bookingId = activeBookingId;
        const formData = new FormData();
        formData.append('id', String(bookingId));
        formData.append('receipt', file);

        let result;
        try {
            const response = await fetch(uploadUrl, {
                method: 'POST',
                credentials: 'same-origin',
                body: formData
            });
            result = await response.json();

            if (!response.ok || !result.success) {
                uploadStatus.textContent = 'Upload failed. Please try again.';
                alert(result.message || 'Failed to upload receipt.');
                receiptInput.value = '';
                return;
            }
        } catch (error) {
            uploadStatus.textContent = 'Upload failed. Please try again.';
            alert('Failed to upload receipt. Please try again.');
            receiptInput.value = '';
            return;
        } finally {
            uploading = false;
            uploadButton.disabled = false;
            closeButton.disabled = false;
        }

        uploadStatus.textContent = 'Receipt uploaded. Pending verification.';
        if (typeof callbacks.onUploadSuccess === 'function') {
            try {
                await callbacks.onUploadSuccess(result, bookingId);
            } catch (error) {
                console.error('Payment receipt success callback failed.', error);
            }
        }
    }

    closeButton.addEventListener('click', () => close());
    uploadButton.addEventListener('click', () => {
        if (activeBookingId && !uploading) receiptInput.click();
    });
    receiptInput.addEventListener('change', event => uploadReceipt(event.target.files[0]));

    window.PaymentReceiptModal = { open, close };
})();
</script>
