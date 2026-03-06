<?php
$page_title = "Print ID Cards (Back)";
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

$card_count = $card_data['count'] ?? 0;
$branch_id = $card_data['branch_id'] ?? 0;
$session_id = $card_data['session_id'] ?? 0;

if (!$card_count || !$branch_id || !$session_id) {
    die("Missing required data (count, branch, or session).");
}

// Fetch branch and session details
try {
    $stmt_branch = $db->prepare("SELECT name, address, phone FROM branches WHERE id = ?");
    $stmt_branch->execute([$branch_id]);
    $branch_details = $stmt_branch->fetch();

    $stmt_session = $db->prepare("SELECT end_date FROM academic_sessions WHERE id = ?");
    $stmt_session->execute([$session_id]);
    $session_details = $stmt_session->fetch();
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

if (!$branch_details || !$session_details) {
    die("Could not retrieve branch or session details.");
}

$valid_till_date = date('d-M-Y', strtotime($session_details['end_date']));
$branch_address = htmlspecialchars($branch_details['address']);
$branch_phone = htmlspecialchars($branch_details['phone']);
$logo_url = SITE_LOGO ? BASE_URL . '/' . SITE_LOGO : ''; // Use site logo for the back

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
        /* CSS from user's example for card back */
        .box {
            position: relative;
            width: 3.7in;
            height: 2.2in;
            float: left;
            padding: 7.7px;
            margin: 10px;
            border: 1px solid #ccc;
            box-sizing: border-box;
            font-family: Arial, sans-serif;
            font-weight: bold;
            overflow: hidden;
        }
        
        /* Page layout styles from print_id_card.php */
        body { background: rgb(204,204,204); }
        page {
            background: white;
            display: block;
            margin: 0 auto;
            margin-bottom: 0.5cm;
            box-shadow: 0 0 0.5cm rgba(0,0,0,0.5);
            overflow: hidden; /* To contain floats */
        }
        page[size="A4"] { width: 21cm; min-height: 29.7cm; }
        page[size="A4"][layout="landscape"] { width: 29.7cm; min-height: 21cm; }
        page[size="Letter"] { width: 21.59cm; min-height: 27.94cm; }
        page[size="Legal"] { width: 21.59cm; min-height: 35.56cm; }

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
            <h4 class="mb-0"><i class="fas fa-print me-2"></i>Print Preview & Settings (Card Backs)</h4>
        </div>
        <div class="card-body">
            <p>Found <strong><?php echo $card_count; ?></strong> card backs to print.</p>
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
        <?php for ($i = 0; $i < $card_count; $i++): ?>
        <div class="box">
            <div style="margin-left: 2%;">
                <div style="background: #000000;width: 100%;height: 22px;border: 2px solid #000000;border-radius: 5px;margin-top: -8px;"></div>
                <div style="width: 100%;text-align: center;">
                    <?php if ($logo_url): ?>
                    <img src="<?php echo $logo_url; ?>" style="height: 83px; width: 88px; margin-top: 3px; object-fit: contain;" />
                    <?php endif; ?>
                </div>
                <div style="width: 100%;font-size: 11px; padding: 3px;margin-top: -7px;"> 
                    <div style="text-align: center;"><?php echo $branch_address; ?></div>
                </div>
                <div style="font-size: 11px;margin-top: -3px; text-align: center;">
                    Contact No: <?php echo $branch_phone; ?>
                </div>
                <div style="text-align: center;margin-bottom: 34px;">
                    <p style="text-align: center; color: black;font-size: 11px;margin-bottom: 0px;margin-top: 3px;">In case of loss this card kindly return it to the school address.</p>
                    <p style="text-align: center; color: black;font-size: 11px;margin-top: 0px;">Valid till: &nbsp;&nbsp;<?php echo $valid_till_date; ?></p>
                </div>
            </div>
            <div style="background: #000000;width: 97%;height: 22px;border: 2px solid #000000;border-radius: 5px;margin-top: -27px;margin-left: 8px;"></div>
        </div>
        <?php endfor; ?>
    </page>
</div>

<!-- This style block will be dynamically updated by JS for @page rules -->
<style id="page-style">
    @media print { @page { size: A4 portrait; } }
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
