<?php
/**
 * Change Password Page
 */

session_start();
require_once '../src/config/config.php';
require_once '../src/Core/Database.php';
require_once '../src/Core/Auth.php';

use App\Core\Auth;

// Ensure user is logged in and needs to change password
if (!Auth::isLoggedIn() || !Auth::needsPasswordChange()) {
    header("Location: login.php");
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if ($new_password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } elseif (strlen($new_password) < MIN_PASSWORD_LENGTH) {
        $error = 'Password must be at least ' . MIN_PASSWORD_LENGTH . ' characters long.';
    } elseif ($new_password === DEFAULT_PASSWORD) {
        $error = 'You cannot use the default password.';
    } else {
        $auth = new Auth();
        $result = $auth->changePassword(Auth::getUserId(), $new_password);

        if ($result['success']) {
            $success = $result['message'];

            // Redirect based on role
            $role = Auth::getRole();
            if (in_array($role, [ROLE_ADMIN, ROLE_HR])) {
                header("Location: index.php");
            } else {
                header("Location: attendance.php");
            }
            exit;
        } else {
            $error = $result['message'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Password - <?php echo APP_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/flowbite/2.3.0/flowbite.min.css" rel="stylesheet" />
</head>
<body class="bg-gradient-to-br from-orange-50 to-orange-100 dark:from-gray-900 dark:to-gray-800 flex items-center justify-center min-h-screen">
    <div class="w-full max-w-md">
        <div class="bg-white rounded-xl shadow-2xl dark:border dark:bg-gray-800 dark:border-gray-700 p-8">
            <!-- Icon and Title -->
            <div class="text-center mb-8">
                <div class="flex justify-center mb-4">
                    <div class="flex items-center justify-center h-12 w-12 rounded-full bg-orange-100 dark:bg-orange-900">
                        <svg class="h-6 w-6 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                        </svg>
                    </div>
                </div>
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">Update Password</h2>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    For security reasons, you must change your default password before continuing.
                </p>
            </div>

            <!-- Error Alert -->
            <?php if ($error): ?>
                <div class="p-4 mb-6 text-sm text-red-800 rounded-lg bg-red-50 dark:bg-gray-700 dark:text-red-400 border border-red-200 dark:border-red-700" role="alert">
                    <span class="font-medium">Error!</span> <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <!-- Success Alert -->
            <?php if ($success): ?>
                <div class="p-4 mb-6 text-sm text-green-800 rounded-lg bg-green-50 dark:bg-gray-700 dark:text-green-400 border border-green-200 dark:border-green-700" role="alert">
                    <span class="font-medium">Success!</span> <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>

            <!-- Change Password Form -->
            <form method="POST" action="change-password.php" class="space-y-5">
                <!-- New Password Input -->
                <div>
                    <label for="new_password" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
                        New Password
                    </label>
                    <input 
                        type="password" 
                        name="new_password" 
                        id="new_password" 
                        class="bg-gray-50 border border-gray-300 text-gray-900 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white" 
                        required 
                        minlength="8"
                        placeholder="••••••••">
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                        Must be at least 8 characters long
                    </p>
                </div>

                <!-- Confirm Password Input -->
                <div>
                    <label for="confirm_password" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
                        Confirm Password
                    </label>
                    <input 
                        type="password" 
                        name="confirm_password" 
                        id="confirm_password" 
                        class="bg-gray-50 border border-gray-300 text-gray-900 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white" 
                        required 
                        minlength="8"
                        placeholder="••••••••">
                </div>

                <!-- Password Requirements -->
                <div class="p-4 bg-blue-50 dark:bg-gray-700 rounded-lg border border-blue-200 dark:border-blue-700">
                    <p class="text-sm font-medium text-blue-900 dark:text-blue-300 mb-2">Password Requirements:</p>
                    <ul class="text-xs text-blue-700 dark:text-blue-400 space-y-1">
                        <li>✓ Minimum 8 characters</li>
                        <li>✓ Cannot use default password</li>
                        <li>✓ Must match confirmation</li>
                    </ul>
                </div>

                <!-- Submit Button -->
                <button 
                    type="submit" 
                    class="w-full text-white bg-gradient-to-r from-orange-500 to-orange-600 hover:from-orange-600 hover:to-orange-700 focus:ring-4 focus:outline-none focus:ring-orange-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center dark:bg-orange-600 dark:hover:bg-orange-700 dark:focus:ring-orange-800 transition duration-200">
                    Update & Continue
                </button>
            </form>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/flowbite/2.3.0/flowbite.min.js"></script>
</body>
</html>
