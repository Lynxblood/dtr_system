<?php
/**
 * Login Page
 */

session_start();
require_once '../src/config/config.php';
require_once '../src/Core/Database.php';
require_once '../src/Core/Auth.php';

use App\Core\Auth;
use App\Core\Database;

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = 'Username and password are required.';
    } else {
        $auth = new Auth();
        $user = $auth->verify($username, $password);

        if ($user) {
            $success = 'Login successful. Redirecting...';

            // Check if password change is required
            if ($user['requires_password_change'] == 1) {
                header("Location: change-password.php");
                exit;
            }

            // Route based on role
            if ($user['role'] === ROLE_ADMIN) {
                header("Location: index.php");
            } elseif ($user['role'] === ROLE_HR) {
                header("Location: hr.php");
            } else {
                header("Location: employee.php");
            }
            exit;
        } else {
            $error = 'Invalid username or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In - <?php echo APP_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/flowbite/2.3.0/flowbite.min.css" rel="stylesheet" />
</head>
<body class="bg-gradient-to-br from-blue-50 to-blue-100 dark:from-gray-900 dark:to-gray-800 flex items-center justify-center min-h-screen">
    <div class="w-full max-w-md">
        <!-- Card with shadow -->
        <div class="bg-white rounded-xl shadow-2xl dark:border dark:bg-gray-800 dark:border-gray-700 p-8">
            <!-- Logo/Title -->
            <div class="text-center mb-8">
                <h1 class="text-4xl font-bold bg-gradient-to-r from-blue-600 to-blue-800 bg-clip-text text-transparent mb-2">
                    <?php echo APP_NAME; ?>
                </h1>
                <p class="text-gray-600 dark:text-gray-400">Employee Portal</p>
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

            <!-- Login Form -->
            <form method="POST" action="login.php" class="space-y-5">
                <!-- Username Input -->
                <div>
                    <label for="username" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
                        Username / Employee ID
                    </label>
                    <input 
                        type="text" 
                        name="username" 
                        id="username" 
                        class="bg-gray-50 border border-gray-300 text-gray-900 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white" 
                        placeholder="Enter your username"
                        required 
                        autofocus>
                </div>

                <!-- Password Input -->
                <div>
                    <label for="password" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
                        Password
                    </label>
                    <input 
                        type="password" 
                        name="password" 
                        id="password" 
                        class="bg-gray-50 border border-gray-300 text-gray-900 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white" 
                        placeholder="••••••••"
                        required>
                </div>

                <!-- Submit Button -->
                <button 
                    type="submit" 
                    class="w-full text-white bg-gradient-to-r from-blue-600 to-blue-800 hover:from-blue-700 hover:to-blue-900 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800 transition duration-200">
                    Sign In
                </button>
            </form>

            <!-- Footer -->
            <p class="text-xs text-gray-500 dark:text-gray-400 text-center mt-8">
                For security reasons, use your unique login credentials provided by your organization.
            </p>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/flowbite/2.3.0/flowbite.min.js"></script>
</body>
</html>
