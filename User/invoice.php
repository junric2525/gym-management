<?php
// =======================================================================
// PHP SCRIPT START - DATA RETRIEVAL AND VARIABLE DEFINITION
// =======================================================================

// =======================================================================
// PHP SCRIPT START - TIMEZONE CORRECTION
// =======================================================================

// Set the timezone to Manila (Philippines Standard Time)
date_default_timezone_set('Asia/Manila');

// 1. START SESSION AND CHECK LOGIN
session_start();
if (!isset($_SESSION['user_id'])) {
    // Redirect unauthenticated users to the login page
    header('Location: ../login.php');
    exit();
}

// Get the logged-in user's ID
$user_id = $_SESSION['user_id']; 

// 2. INCLUDE DATABASE CONNECTION
// Assuming '../backend/db.php' initializes a mysqli connection named $conn
if (file_exists('../backend/db.php')) {
    require_once '../backend/db.php';
    // Use the null coalescing operator (??) for safer error logging
    if (!isset($conn) || $conn->connect_error) {
        error_log("Database Connection FAILED: " . $conn->connect_error ?? "Connection object missing.");
        die("FATAL ERROR: Could not connect to the database.");
    }
} else {
    die("FATAL ERROR: db.php not found.");
}

// Initialize array to hold fetched invoices
$all_invoices = [];
$members_id = null;
// $debug_output has been removed

// 3. FETCH MEMBER ID
try {
    // Get the members_id associated with the logged-in user_id from the membership table.
    $sql_member_id = "SELECT members_id FROM membership WHERE user_id = ?";
    $stmt_member_id = $conn->prepare($sql_member_id);
    
    if ($stmt_member_id === false) {
        throw new Exception("Error preparing member ID query: " . $conn->error);
    }
    
    $stmt_member_id->bind_param("i", $user_id);
    $stmt_member_id->execute();
    $result_member_id = $stmt_member_id->get_result();
    
    if ($result_member_id->num_rows > 0) {
        $row = $result_member_id->fetch_assoc();
        $members_id = $row['members_id'];
    }
    $stmt_member_id->close();

} catch (Exception $e) {
    // Log the error but don't stop the page load unless critical
    error_log("Error fetching member ID: " . $e->getMessage());
}

// 4. FETCH INVOICE DATA from the centralized 'invoices' table
if ($members_id) { 
    try {
        // *** CENTRALIZED QUERY AGAINST THE 'INVOICES' TABLE ***
        $sql_invoices = "
            SELECT 
                invoice_id,
                members_id AS member_reference, 
                item_name AS plan_name,
                item_type AS invoice_type,
                gcash_reference AS gcash_reference_number,
                payment_date AS start_date,
                end_date AS end_date 
            FROM invoices
            WHERE members_id = ?
            ORDER BY payment_date DESC
        ";
        
        $stmt_invoices = $conn->prepare($sql_invoices);
        if ($stmt_invoices === false) {
            throw new Exception("Error preparing centralized invoice query: " . $conn->error);
        }

        $stmt_invoices->bind_param("i", $members_id);
        $stmt_invoices->execute();
        $result_invoices = $stmt_invoices->get_result();

        while ($invoice_record = $result_invoices->fetch_assoc()) {
            // Data is standardized by the SQL query aliases, so we just append it
            $all_invoices[] = $invoice_record;
        }
        $stmt_invoices->close();

    } catch (Exception $e) {
        error_log("PHP EXCEPTION during invoice fetch: " . $e->getMessage());
    }
}

// Close the connection
$conn->close();

// =======================================================================
// HTML OUTPUT START 
// =======================================================================
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice History</title>
    <link rel="stylesheet" href="../assets/css/invoice.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@100..900&display=swap">
    <style>
        
        /* Main Invoice Area */
        .invoice-container {
            flex-grow: 1;
            padding: 40px 20px;
            background-color: white;
            max-width: 900px;
            margin: 40px auto;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }

        .invoice-container h1 {
            color: var(--secondary-color);
            font-size: 2rem;
            margin-bottom: 20px;
            border-bottom: 3px solid var(--primary-color);
            padding-bottom: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .close-btn {
            background: none;
            border: none;
            font-size: 2rem;
            color: var(--secondary-color);
            cursor: pointer;
            padding: 0;
            transition: color 0.2s;
        }

        .close-btn:hover {
            color: var(--primary-color);
        }

        .invoice-list {
            display: grid;
            gap: 20px;
        }

        .invoice-card {
            background-color: var(--light-bg);
            border: 1px solid #ddd;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.05);
            display: grid;
            /* Adaptive layout: columns change based on screen size */
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 15px;
            align-items: center;
            position: relative;
        }
        
        .invoice-card::before {
            content: attr(data-invoice-type); /* Use the invoice type attribute */
            position: absolute;
            top: 10px;
            right: 10px;
            font-size: 0.8rem;
            font-weight: bold;
            color: var(--primary-color);
            background: rgba(247, 160, 26, 0.1);
            padding: 4px 8px;
            border-radius: 4px;
        }

        .invoice-card.membership-fee::before {
            background: rgba(40, 167, 69, 0.1);
            color: var(--success-color);
        }
        
        .invoice-card.subscription-payment::before {
            background: rgba(23, 162, 184, 0.1);
            color: var(--info-color);
        }

        .invoice-card p {
            margin: 0;
            font-size: 0.95rem;
        }

        .invoice-card strong {
            display: block;
            font-weight: 600;
            color: #555;
            margin-bottom: 2px;
            font-size: 0.85rem;
            text-transform: uppercase;
        }
        
        .invoice-card span {
            color: var(--secondary-color);
            font-weight: 500;
        }
        

        .info-message {
            text-align: center;
            padding: 30px;
            background-color: #fff3cd;
            border: 1px solid #ffeeba;
            color: #856404;
            border-radius: 8px;
            font-size: 1.1rem;
        }
        
        
    </style>
</head>
<body>

    <header class="header">
        <div class="container header-flex">
            <div class="logo">
                <img src="../assets/img/logo.png" onerror="this.onerror=null;this.src='https://placehold.co/30x30/f7a01a/ffffff?text=CG'" alt="Logo" class="logo-img" />
                <h1 class="logo-text">Charles Gym</h1>
            </div>
            <nav class="nav-desktop">
                <a href="profile.php"><i class="fas fa-user"></i> Profile</a>
                <a href="../Guest/index.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </nav>
        </div>
    </header> 
    
    <div class="container">
        </div>


    <main class="invoice-container">
        <h1>
            Invoice History (Member ID: <?php echo htmlspecialchars($members_id ?? 'N/A'); ?>)
            <button id="closeInvoiceBtn" class="close-btn" onclick="window.location.href='profile.php'">&times;</button>
        </h1>
        
        <div id="invoiceList" class="invoice-list">
            <?php if (!empty($all_invoices)): ?>
                <?php foreach ($all_invoices as $invoice): 
                    // Use standardized keys from the combined result set (via SQL aliases)
                    $invoice_id = htmlspecialchars($invoice['invoice_id'] ?? 'N/A');
                    $member_ref = htmlspecialchars($invoice['member_reference'] ?? 'N/A');
                    $plan_name = htmlspecialchars($invoice['plan_name'] ?? 'N/A');
                    $invoice_type = htmlspecialchars($invoice['invoice_type'] ?? 'Invoice');
                    $gcash_ref = htmlspecialchars($invoice['gcash_reference_number'] ?? 'N/A');
                    // Check if date is set and not a default MySQL empty value
                    $start_date = !empty($invoice['start_date']) && $invoice['start_date'] != '0000-00-00 00:00:00' ? htmlspecialchars($invoice['start_date']) : 'N/A';
                    $end_date = !empty($invoice['end_date']) && $invoice['end_date'] != '0000-00-00' ? htmlspecialchars($invoice['end_date']) : 'N/A';
                    
                    // Determine class for styling based on invoice_type
                    $card_class = (strpos($invoice_type, 'Membership Fee') !== false || strpos($invoice_type, 'Membership Renewal') !== false) ? 'membership-fee' : 
                                  ((strpos($invoice_type, 'Subscription Payment') !== false) ? 'subscription-payment' : '');
                ?>
                <div class="invoice-card <?php echo $card_class; ?>" data-invoice-type="<?php echo $invoice_type; ?>">
                    <?php if ($member_ref !== 'N/A'): ?>
                        <p><strong>Member ID:</strong> <span><?php echo $member_ref; ?></span></p> 
                    <?php endif; ?>

                    <p><strong>Payment Item:</strong> <span><?php echo $plan_name; ?></span></p> 
                    <p><strong>Invoice ID:</strong> <span>#<?php echo $invoice_id; ?></span></p>
                    
                    <p><strong>Date Paid:</strong> <span><?php echo $start_date; ?></span></p>
                    
                    <p><strong>Expiration Date:</strong> <span><?php echo $end_date; ?></span></p>

                    <p><strong>GCash Reference:</strong> <span><?php echo $gcash_ref; ?></span></p>
                    
                </div>
                <?php endforeach; ?>
            <?php elseif (!$members_id): ?>
                <div class="info-message">
                    <i class="fas fa-exclamation-circle"></i> You must complete membership registration before viewing invoices.
                </div>
            <?php else: ?>
                <div class="info-message">
                    <i class="fas fa-info-circle"></i> No approved payment records found.
                </div>
            <?php endif; ?>
        </div>
    </main>


    <footer class="footer">
        <div class="container footer-grid">
            <div class="footer-about">
                <h3>CHARLES GYM</h3>
                <p>World-class fitness training in a supportive and motivating environment.</p>
            </div>
            <div class="footer-links">
                <h4>Quick Links</h4>
                <a href="user.php#home">Home</a>
                <a href="user.php#about">About Us</a>
                <a href="user.php#services">Services</a>
            </div>
            <div class="footer-contact">
                <h4>Contact Us</h4>
                <p><i class="fas fa-map-marker-alt"></i> Unit 21, Landsdale Tower, QC</p>
                <p><i class="fas fa-phone"></i> (555) 123-4567</p>
                <p><i class="fa-brands fa-google"></i> charlesgym@gmail.com</p>
            </div>
        </div>
        <div class="footer-bottom">© <span id="footerYear"></span> Charles Gym. All rights reserved.</div>
    </footer>


    <script>
        // Ensure the footer year is updated
        document.getElementById('footerYear').textContent = new Date().getFullYear();
    </script>
</body>
</html>