<?php
/**
 * Header Component
 * 
 * Reusable navigation header for the application
 */

namespace App\Components;

use App\Core\Auth;

class Header
{
    public static function render($title = '')
    {
        $user = Auth::getUser();
        $appName = APP_NAME;
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title><?php echo htmlspecialchars($title ? $title . ' - ' . $appName : $appName); ?></title>
            
            <!-- Tailwind CSS -->
            <script src="https://cdn.tailwindcss.com"></script>
            
            <!-- Flowbite CSS -->
            <link href="https://cdnjs.cloudflare.com/ajax/libs/flowbite/2.3.0/flowbite.min.css" rel="stylesheet" />
            
            <!-- DataTables -->
            <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.tailwindcss.min.css">
            
            <!-- Custom CSS -->
            <link rel="stylesheet" href="<?php echo APP_URL; ?>/public/assets/css/main.css">
            <script>
                window.APP_URL = '<?php echo APP_URL; ?>';
            </script>
        </head>
        <body class="bg-gray-50 dark:bg-gray-900 antialiased">
            
            <!-- Navbar -->
            <nav class="bg-white border-b border-gray-200 px-4 py-2.5 dark:bg-gray-800 dark:border-gray-700 sticky top-0 z-40 shadow-sm">
                <div class="flex flex-wrap justify-between items-center max-w-7xl mx-auto">
                    <div class="flex justify-start items-center">
                        <a href="<?php echo APP_URL; ?>/public/index.php" class="flex items-center">
                            <span class="text-2xl font-bold bg-gradient-to-r from-blue-600 to-blue-800 bg-clip-text text-transparent">
                                <?php echo htmlspecialchars($appName); ?>
                            </span>
                        </a>
                    </div>
                    
                    <div class="flex items-center gap-4">
                        <div class="text-sm text-gray-700 dark:text-gray-300">
                            <span>Logged in as: <strong class="text-gray-900 dark:text-white"><?php echo htmlspecialchars($user['username']); ?></strong></span>
                            <span class="ml-2 px-2 py-1 text-xs bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300 rounded-full">
                                <?php echo htmlspecialchars(ucfirst($user['role'])); ?>
                            </span>
                        </div>
                        <a href="<?php echo APP_URL; ?>public/logout.php" class="inline-flex items-center px-3 py-2 text-sm font-medium text-white bg-red-600 hover:bg-red-700 rounded-lg transition">
                            Logout
                        </a>
                    </div>
                </div>
            </nav>

            <main class="p-4 h-auto pt-6 max-w-7xl mx-auto">
        <?php
    }

    public static function close()
    {
        ?>
            </main>

            <!-- Footer -->
            <footer class="bg-white border-t border-gray-200 dark:bg-gray-800 dark:border-gray-700 p-4 mt-8">
                <div class="max-w-7xl mx-auto text-center text-sm text-gray-500 dark:text-gray-400">
                    <p>&copy; <?php echo date('Y'); ?> <?php echo APP_NAME; ?>. All rights reserved.</p>
                </div>
            </footer>

            <!-- Scripts -->
            <script src="https://cdnjs.cloudflare.com/ajax/libs/flowbite/2.3.0/flowbite.min.js"></script>
            <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
            <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
            <script src="https://cdn.datatables.net/1.13.6/js/dataTables.tailwindcss.min.js"></script>
            <script src="<?php echo APP_URL; ?>/public/assets/js/main.js"></script>
        </body>
        </html>
        <?php
    }
}
