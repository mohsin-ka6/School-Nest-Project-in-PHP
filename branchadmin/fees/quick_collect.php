<?php
$page_title = "Quick Fee Collection";
require_once '../../config.php';
require_once '../../functions.php';

check_role('branchadmin');

require_once '../../header.php';
?>

<?php require_once '../../sidebar_branchadmin.php'; ?>
<?php require_once '../../navbar.php'; ?>

<div class="container-fluid px-4">
    <h1 class="mt-4"><?php echo $page_title; ?></h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="../dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item active">Quick Collect</li>
    </ol>

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card mb-4 shadow-sm">
                <div class="card-header"><i class="fas fa-search me-1"></i> Find Invoice</div>
                <div class="card-body">
                    <p class="text-muted">Enter an Invoice # or scan a barcode to find the fee details.</p>
                    <div class="input-group input-group-lg">
                        <input type="text" id="search-term" class="form-control" placeholder="Invoice # or Barcode..." autofocus>
                        <button class="btn btn-primary" id="search-btn" title="Search"><i class="fas fa-search"></i></button>
                        <button class="btn btn-info" id="scan-btn" title="Scan with Camera"><i class="fas fa-camera"></i></button>
                    </div>
                </div>
            </div>

            <div id="result-area" class="d-none">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span><i class="fas fa-file-invoice me-1"></i> Invoice Details</span>
                        <span id="invoice-status-badge" class="badge"></span>
                    </div>
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-md-3 text-center">
                                <img id="student-photo" src="" class="rounded-circle mb-2" style="width: 100px; height: 100px; object-fit: cover;">
                                <h6 id="student-name" class="mb-0"></h6>
                                <p id="student-class" class="text-muted small"></p>
                            </div>
                            <div class="col-md-9">
                                <table class="table table-sm table-bordered">
                                    <tr><th>Invoice #</th><td id="invoice-id"></td></tr>
                                    <tr><th>Month</th><td id="invoice-month"></td></tr>
                                    <tr><th>Total Amount</th><td id="total-amount"></td></tr>
                                    <tr><th>Amount Paid</th><td id="amount-paid"></td></tr>
                                    <tr class="table-danger"><th class="fw-bold">Amount Due</th><td id="due-amount" class="fw-bold"></td></tr>
                                </table>
                                <div class="d-grid" id="action-button-area">
                                    <!-- Button will be inserted here by JS -->
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div id="error-area" class="alert alert-danger d-none text-center">
                <i class="fas fa-exclamation-triangle fa-2x mb-2"></i><br>
                <span id="error-message"></span>
            </div>

            <div id="reader-container" class="mt-3 d-none">
                <div class="mb-2" id="camera-select-container" style="max-width: 350px;">
                    <select id="camera-select" class="form-select form-select-sm"></select>
                </div>
                <div id="reader" style="width: 100%;"></div>
            </div>

        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('search-term');
    const searchBtn = document.getElementById('search-btn');
    const resultArea = document.getElementById('result-area');
    const errorArea = document.getElementById('error-area');
    const errorMessage = document.getElementById('error-message');
    let searchTimeout;
    const scanBtn = document.getElementById('scan-btn');
    const readerContainer = document.getElementById('reader-container');
    const cameraSelect = document.getElementById('camera-select');
    const cameraSelectContainer = document.getElementById('camera-select-container');

    function performSearch() {
        const term = searchInput.value.trim();
        if (term === '') {
            resultArea.classList.add('d-none');
            errorArea.classList.add('d-none');
            return;
        }

        fetch(`<?php echo BASE_URL; ?>/api/get_invoice_details.php?term=${term}`)
            .then(response => response.json())
            .then(data => {
                if (data && !data.error && data.id) {
                    displayResults(data);
                } else {
                    displayError(data.error || 'Invoice not found.');
                }
            })
            .catch(err => displayError('An error occurred while searching.'));
    }

    function displayError(message) {
        resultArea.classList.add('d-none');
        errorArea.classList.remove('d-none');
        errorMessage.textContent = message;
    }

    function displayResults(data) {
        errorArea.classList.add('d-none');
        resultArea.classList.remove('d-none');

        document.getElementById('student-photo').src = data.photo ? `<?php echo BASE_URL; ?>/${data.photo}` : `<?php echo BASE_URL; ?>/assets/images/default_avatar.png`;
        document.getElementById('student-name').textContent = data.student_name;
        document.getElementById('student-class').textContent = `${data.class_name} - ${data.section_name}`;
        document.getElementById('invoice-id').textContent = data.id;
        document.getElementById('invoice-month').textContent = new Date(data.invoice_month + '-02').toLocaleString('default', { month: 'long', year: 'numeric' });
        document.getElementById('total-amount').textContent = parseFloat(data.total_amount).toFixed(2);
        document.getElementById('amount-paid').textContent = parseFloat(data.amount_paid).toFixed(2);
        document.getElementById('due-amount').textContent = parseFloat(data.due_amount).toFixed(2);

        const statusBadge = document.getElementById('invoice-status-badge');
        statusBadge.textContent = data.status.replace('_', ' ').toUpperCase();
        statusBadge.className = 'badge';
        if (data.status === 'paid') statusBadge.classList.add('bg-success');
        else if (data.status === 'partially_paid') statusBadge.classList.add('bg-warning', 'text-dark');
        else statusBadge.classList.add('bg-danger');

        const actionArea = document.getElementById('action-button-area');
        actionArea.innerHTML = (data.due_amount > 0) 
            ? `<a href="collect_fees.php?id=${data.id}" class="btn btn-success btn-lg">Proceed to Collect Payment</a>`
            : `<button class="btn btn-success btn-lg" disabled>Invoice Fully Paid</button>`;
    }

    // --- Barcode Scanner Logic ---
    let html5QrCode = null;

    function onScanSuccess(decodedText, decodedResult) {
        searchInput.value = decodedText;
        stopScanner();
        performSearch();
    }

    function stopScanner() {
        if (html5QrCode && html5QrCode.isScanning) {
            html5QrCode.stop().then(() => {
                readerContainer.classList.add('d-none');
                scanBtn.innerHTML = '<i class="fas fa-camera"></i>';
                scanBtn.classList.replace('btn-danger', 'btn-info');
                scanBtn.title = 'Scan with Camera';
            }).catch(err => console.error("Failed to stop QR Code scanning.", err));
        }
    }

    function startScannerWithCamera(cameraId) {
        if (html5QrCode.isScanning) {
            html5QrCode.stop();
        }
        const config = {
            fps: 10,
            qrbox: { width: 300, height: 150 }, // A wider box is better for 1D barcodes
            formatsToSupport: [ Html5QrcodeSupportedFormats.CODE_128 ]
        };
        html5QrCode.start(
            cameraId, 
            config,
            onScanSuccess, 
            (errorMessage) => { /* ignore errors */ }
        ).catch(err => {
            displayError(`Could not start camera. Please grant permissions.`);
            stopScanner();
        });
    }

    function setupAndStartScanner() {
        html5QrCode = new Html5Qrcode("reader");
        Html5Qrcode.getCameras().then(cameras => {
            if (cameras && cameras.length) {
                cameraSelect.innerHTML = '';
                let backCameraId = null;
                cameras.forEach(camera => {
                    if (camera.label.toLowerCase().includes('back')) backCameraId = camera.id;
                    cameraSelect.innerHTML += `<option value="${camera.id}">${camera.label}</option>`;
                });
                
                cameraSelectContainer.classList.toggle('d-none', cameras.length <= 1);
                if (backCameraId) cameraSelect.value = backCameraId;

                readerContainer.classList.remove('d-none');
                scanBtn.innerHTML = '<i class="fas fa-stop-circle"></i>';
                scanBtn.classList.replace('btn-info', 'btn-danger');
                scanBtn.title = 'Stop Scanning';

                startScannerWithCamera(cameraSelect.value);
            } else {
                displayError("No cameras found on this device.");
            }
        }).catch(err => displayError("Could not access cameras. Please grant permissions."));
    }

    searchBtn.addEventListener('click', performSearch);
    searchInput.addEventListener('keyup', function(e) {
        clearTimeout(searchTimeout);
        if (e.key === 'Enter') {
            performSearch();
        } else {
            searchTimeout = setTimeout(performSearch, 300);
        }
    });

    scanBtn.addEventListener('click', function() {
        if (html5QrCode && html5QrCode.isScanning) {
            stopScanner();
        } else {
            setupAndStartScanner();
        }
    });

    cameraSelect.addEventListener('change', () => {
        if (html5QrCode && html5QrCode.isScanning) startScannerWithCamera(cameraSelect.value);
    });
});
</script>

<!-- Barcode Scanner Library -->
<script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>

<?php require_once '../../footer.php'; ?>
