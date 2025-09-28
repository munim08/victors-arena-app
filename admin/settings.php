<?php
require_once 'common/header.php';

$success_msg = '';
$error_msg = '';

// Function to safely get a setting value
function get_setting($conn, $key) {
    $stmt = $conn->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
    $stmt->bind_param("s", $key);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        return $row['setting_value'];
    }
    return null;
}

// Function to safely update a setting value
function update_setting($conn, $key, $value) {
    // This query will insert a new setting if it doesn't exist, or update it if it does.
    $stmt = $conn->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
    $stmt->bind_param("ss", $key, $value);
    return $stmt->execute();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action_success = false;
    // Update UPI ID
    if (isset($_POST['upi_id'])) {
        $upi_id = trim($_POST['upi_id']);
        if (update_setting($conn, 'admin_upi_id', $upi_id)) {
            $action_success = true;
        } else {
            $error_msg = 'Failed to update UPI ID.';
        }
    }

    // Handle QR Code Upload
    if (isset($_FILES['qr_code']) && $_FILES['qr_code']['error'] == 0) {
        $upload_dir = "../uploads/";
        // Create the uploads directory if it doesn't exist
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        $file_extension = strtolower(pathinfo($_FILES["qr_code"]["name"], PATHINFO_EXTENSION));
        $safe_filename = "admin_qr_code." . $file_extension;
        $target_file = $upload_dir . $safe_filename;
        
        // Allow certain file formats
        $allowed_types = ["jpg", "png", "jpeg"];
        if (!in_array($file_extension, $allowed_types)) {
            $error_msg = "Sorry, only JPG, JPEG, & PNG files are allowed.";
        } else {
            if (move_uploaded_file($_FILES["qr_code"]["tmp_name"], $target_file)) {
                if (update_setting($conn, 'admin_qr_code', $safe_filename)) {
                    $action_success = true;
                } else {
                    $error_msg = 'Failed to save QR code path to database.';
                }
            } else {
                $error_msg = "Sorry, there was an error uploading your file.";
            }
        }
    }
    
    if($action_success && !$error_msg) {
        $success_msg = 'Settings saved successfully!';
    }
}

// Fetch current settings to display in the form
$current_upi_id = get_setting($conn, 'admin_upi_id');
$current_qr_code = get_setting($conn, 'admin_qr_code');
?>

<div class="space-y-6">
    <h2 class="text-2xl font-bold">App Settings</h2>

    <?php if ($error_msg): ?><div class="bg-red-500 text-white p-3 rounded-lg text-sm"><?php echo $error_msg; ?></div><?php endif; ?>
    <?php if ($success_msg): ?><div class="bg-green-500 text-white p-3 rounded-lg text-sm"><?php echo $success_msg; ?></div><?php endif; ?>
    
    <form action="settings.php" method="POST" enctype="multipart/form-data" class="bg-gray-700 p-4 rounded-lg space-y-4">
        <h3 class="font-semibold text-lg">Payment Details (For Deposits)</h3>
        <div>
            <label for="upi_id" class="block text-sm font-medium text-gray-300">Your UPI ID</label>
            <input type="text" name="upi_id" id="upi_id" value="<?php echo htmlspecialchars($current_upi_id ?? ''); ?>" placeholder="yourname@bank" class="mt-1 block w-full bg-gray-800 border-gray-600 rounded-md p-2 text-white">
        </div>
        <div>
            <label for="qr_code" class="block text-sm font-medium text-gray-300">Upload QR Code Image</label>
            <input type="file" name="qr_code" class="mt-1 block w-full text-sm text-gray-400 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
            <?php if ($current_qr_code): ?>
                <div class="mt-4">
                    <p class="text-sm text-gray-300">Current QR Code:</p>
                    <img src="../uploads/<?php echo htmlspecialchars($current_qr_code); ?>" alt="Admin QR Code" class="mt-2 rounded-lg max-w-xs border-2 border-gray-600">
                </div>
            <?php endif; ?>
        </div>
        <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3 px-4 rounded-md">Save Settings</button>
    </form>
</div>

<?php require_once 'common/bottom.php'; ?>