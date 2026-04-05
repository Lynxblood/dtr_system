<?php
// change_password.php
session_start();
require_once 'db.php';

// Ensure the user is logged in and actually needs to change their password
if (!isset($_SESSION['user_id']) || !isset($_SESSION['force_password_change'])) {
    header("Location: login.php");
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if ($new_password !== $confirm_password) {
        $error = "Passwords do not match.";
    } elseif (strlen($new_password) < 8) {
        $error = "Password must be at least 8 characters long.";
    } elseif ($new_password === 'ilove@BASC1') {
        $error = "You cannot use the default password.";
    } else {
        // Update password and remove the flag
        $hashed = password_hash($new_password, PASSWORD_BCRYPT);
        $stmt = $pdo->prepare("UPDATE users SET password = ?, requires_password_change = 0 WHERE id = ?");
        $stmt->execute([$hashed, $_SESSION['user_id']]);

        // Clear the flag from session
        unset($_SESSION['force_password_change']);

        // Redirect based on role
        if (in_array($_SESSION['role'], ['admin', 'hr'])) {
            header("Location: index.php");
        } else {
            header("Location: my_attendance.php");
        }
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Change Password</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 dark:bg-gray-900 flex items-center justify-center h-screen">
    <div class="w-full max-w-md bg-white rounded-lg shadow dark:border dark:bg-gray-800 dark:border-gray-700 p-8">
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white text-center mb-2">Update Password</h2>
        <p class="text-sm text-gray-500 dark:text-gray-400 text-center mb-6">For security reasons, you must change your default password before continuing.</p>
        
        <?php if($error): ?>
            <div class="p-4 mb-4 text-sm text-red-800 rounded-lg bg-red-50 dark:bg-gray-700 dark:text-red-400" role="alert">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="change_password.php" class="space-y-4">
            <div>
                <label for="new_password" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">New Password</label>
                <input type="password" name="new_password" id="new_password" class="bg-gray-50 border border-gray-300 text-gray-900 rounded-lg focus:ring-blue-600 focus:border-blue-600 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white" required minlength="8">
            </div>
            <div>
                <label for="confirm_password" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Confirm Password</label>
                <input type="password" name="confirm_password" id="confirm_password" class="bg-gray-50 border border-gray-300 text-gray-900 rounded-lg focus:ring-blue-600 focus:border-blue-600 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white" required minlength="8">
            </div>
            <button type="submit" class="w-full text-white bg-blue-600 hover:bg-blue-700 focus:ring-4 font-medium rounded-lg text-sm px-5 py-2.5 text-center">Update & Continue</button>
        </form>
    </div>
</body>
</html>