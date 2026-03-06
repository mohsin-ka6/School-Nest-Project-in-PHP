<?php
$page_title = "Print ID Cards";
require_once '../../config.php';
require_once '../../functions.php';

check_role('branchadmin');

// Data is expected to be in a POST request, base64 encoded to handle special characters.
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['card_data'])) {
    die("No card data provided.");
}

$card_data_json = base64_decode($_POST['card_data']);
$card_data = json_decode($card_data_json, true);

if (json_last_error() !== JSON_ERROR_NONE || !is_array($card_data)) {
    die("Invalid card data format.");
}

$cards_html = $card_data['html'] ?? '';
$card_count = $card_data['count'] ?? 0;

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        /* --- Page layout styles from user example --- */
        body {
            background: rgb(204,204,204); 
        }
        page {
            background: white;
            display: block;
            margin: 0 auto;
            margin-bottom: 0.5cm;
            box-shadow: 0 0 0.5cm rgba(0,0,0,0.5);
        }
        page[size="A4"] {  
            width: 21cm;
            min-height: 29.7cm; 
        }
        page[size="A4"][layout="landscape"] {
            width: 29.7cm;
            min-height: 21cm;  
        }
        page[size="Letter"] {
            width: 21.59cm;
            min-height: 27.94cm;
        }
        page[size="Legal"] {
            width: 21.59cm;
            min-height: 35.56cm;
        }

        /* --- Copied styles from id_card_generator.php for consistent rendering --- */
        .id-card-grid { padding: 1cm; }
        /* The .id-card is now the floating box */
        .id-card { float: left; color: #000; font-family: Arial, sans-serif; page-break-inside: avoid; }
        .id-card-header { text-align: center; padding: 10px; }
        .id-card-header img.logo { height: 40px; }
        .id-card-header h6, .id-card-header p { margin: 0; font-weight: bold; }
        .id-card-body { padding: 10px; }
        .id-card-photo { text-align: center; margin-bottom: 10px; }
        .id-card-photo img { width: 90px; height: 110px; object-fit: cover; border: 2px solid #eee; }
        .id-card-details { font-size: 0.8rem; }
        .id-card-details p { margin-bottom: 4px; }
        .id-card-details strong { color: #333; }
        .id-card-footer { text-align: center; padding: 5px; }
        .id-card-footer img.barcode { height: 35px; }

        /* Template 1: Student Portrait */
        .id-card.student-portrait { width: 250px; height: 400px; }
        .id-card.student-portrait .id-card-header { background-color: #0d6efd; color: #fff; }

        /* Template 2: Student Landscape (New Design) */
        .id-card.student-landscape {
            position: relative;
            width: 3.7in;
            height: 2.2in;
            padding: 7.7px;
            margin: 10px;
            background-size: cover;
            background-position: center;
            font-family: 'Arial', sans-serif;
            text-transform: uppercase;
            font-weight: bold;
            box-sizing: border-box;
            overflow: hidden;
        }
        .id-card.student-landscape .card-top-header {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            padding: 5px 10px;
            color: #fff;
            z-index: 3;
            line-height: 1.2;
            display: flex;
            align-items: center;
            gap: 8px;
            box-sizing: border-box;
        }
        .id-card.student-landscape .card-top-header .header-logo { height: 30px; width: 30px; flex-shrink: 0; }
        .id-card.student-landscape .card-top-header .header-text { text-align: left; }
        .id-card.student-landscape .card-top-header .school-main-name { font-weight: bold; font-size: 10px; }
        .id-card.student-landscape .card-top-header .school-branch-name { font-size: 8px; opacity: 0.9; }
        .id-card.student-landscape::before {
            content: '';
            position: absolute;
            top: 0; left: 0;
            width: 100%; height: 100%;
            background-image: var(--bg-logo-url);
            background-size: contain;
            background-position: center;
            background-repeat: no-repeat;
            opacity: 0.3;
            z-index: 1;
        }
        .id-card.student-landscape .card-content-wrapper { width: 100%; height: 100%; display: flex; position: relative; z-index: 2; }
        .id-card.student-landscape .left-section { width: 34%; position: relative; }
        .id-card.student-landscape .circular-image { width: 80px; height: 80px; object-fit: cover; margin-top: 32px; margin-left: 33px; border: 2px solid #fff; }
        .id-card.student-landscape .photo-shape-round { border-radius: 50%; }
        .id-card.student-landscape .photo-shape-box { border-radius: 8px; }
        .id-card.student-landscape .qr-image { position: absolute; bottom: 0; left: 0; width: 65px; height: 60px; margin: 17px; }
        .id-card.student-landscape .right-section { width: 66%; margin-top: 45px; padding-left: 5px; }
        .id-card.student-landscape .details-grid { width: 100%; display: flex; justify-content: space-between; line-height: 1.8; margin-top: 10px; margin-left: 3px; font-size: 8px; }
        .id-card.student-landscape .details-labels, .id-card.student-landscape .details-values { width: 50%; white-space: nowrap; }
        .id-card.student-landscape .barcode-container { margin-top: 8px; text-align: center; }
        .id-card.student-landscape .barcode-image { height: 30px; width: 80%; }
        .id-card.student-landscape .principal-signature { width: 45px; height: 30px; float: right; margin-top: 10px; margin-right: 13px; }

        /* --- Print-specific styles --- */
        @media print {
            .no-print { display: none !important; }
            body, page {
                margin: 0;
                box-shadow: none;
                border: none;
                background: #fff;
            }
            @page {
                size: A4; /* Default, can be changed */
                margin: 0;
            }
        }
        #page-style { display: none; }
    </style>
</head>
<body>

<div class="container my-4 no-print">
    <div class="card">
        <div class="card-header">
            <h4 class="mb-0"><i class="fas fa-print me-2"></i>Print Preview & Settings</h4>
        </div>
        <div class="card-body">
            <p>Found <strong><?php echo $card_count; ?></strong> cards to print.</p>
            <div class="row">
                <div class="col-md-4">
                    <label for="page_size" class="form-label">Page Size</label>
                    <select id="page_size" class="form-select">
                        <option value="A4">A4</option>
                        <option value="Letter">Letter</option>
                        <option value="Legal">Legal</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="page_layout" class="form-label">Layout</label>
                    <select id="page_layout" class="form-select">
                        <option value="portrait">Portrait</option>
                        <option value="landscape">Landscape</option>
                    </select>
                </div>
            </div>
            <div class="mt-3">
                <button onclick="window.print()" class="btn btn-primary"><i class="fas fa-print me-1"></i> Print Now</button>
                <a href="id_card_generator.php" class="btn btn-secondary">Go Back</a>
            </div>
        </div>
    </div>
</div>

<div class="print-area">
    <page size="A4" layout="portrait" id="print-page">
        <?php echo str_replace('class="id-card ', 'class="id-card ', $cards_html); ?>
    </page>
</div>

<!-- This style block will be dynamically updated by JS for @page rules -->
<style id="page-style">
    @media print {
        @page {
            size: A4 portrait;
        }
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const pageSizeSelect = document.getElementById('page_size');
    const pageLayoutSelect = document.getElementById('page_layout');
    const printPage = document.getElementById('print-page');
    const pageStyle = document.getElementById('page-style');

    function updatePageStyle() {
        const size = pageSizeSelect.value;
        const layout = pageLayoutSelect.value;
        printPage.setAttribute('size', size);
        printPage.setAttribute('layout', layout);
        pageStyle.innerHTML = `@media print { @page { size: ${size} ${layout}; margin: 1cm; } }`;
    }

    pageSizeSelect.addEventListener('change', updatePageStyle);
    pageLayoutSelect.addEventListener('change', updatePageStyle);

    // Initial call
    updatePageStyle();
});
</script>

</body>
</html>