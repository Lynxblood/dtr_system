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
            
            <!-- Local Tailwind + Flowbite CSS -->
            <link rel="stylesheet" href="<?php echo APP_URL; ?>/public/assets/css/tailwind.css">
            
            <!-- Simple-DataTables -->
            <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/simple-datatables@9.0.3/dist/style.css">
            
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
                        <a href="<?php 
                            $homeUrl = APP_URL . '/public/';
                            if ($user['role'] === ROLE_ADMIN) {
                                $homeUrl .= 'index.php';
                            } elseif ($user['role'] === ROLE_HR) {
                                $homeUrl .= 'hr.php';
                            } elseif ($user['role'] === ROLE_PRESIDENT) {
                                $homeUrl .= 'president.php';
                            } elseif ($user['role'] === ROLE_HEAD) {
                                $homeUrl .= 'head.php';
                            } else {
                                $homeUrl .= 'employee.php';
                            }
                            echo $homeUrl;
                        ?>" class="flex items-center">
                            <span class="text-2xl font-bold text-gray-900 dark:text-white">
                                <?php echo htmlspecialchars($appName); ?>
                            </span>
                        </a>
                    </div>
                    
                    <div class="flex items-center gap-4">
                        <!-- Navigation Menu -->
                        <div class="hidden md:flex items-center space-x-1">
                            <a href="<?php 
                                $homeUrl = APP_URL . '/public/';
                                if ($user['role'] === ROLE_ADMIN) {
                                    $homeUrl .= 'index.php';
                                } elseif ($user['role'] === ROLE_HR) {
                                    $homeUrl .= 'hr.php';
                                } elseif ($user['role'] === ROLE_PRESIDENT) {
                                    $homeUrl .= 'president.php';
                                } elseif ($user['role'] === ROLE_HEAD) {
                                    $homeUrl .= 'head.php';
                                } else {
                                    $homeUrl .= 'employee.php';
                                }
                                echo $homeUrl;
                            ?>" class="px-3 py-2 text-sm font-medium text-gray-700 hover:text-gray-900 hover:bg-gray-100 dark:text-gray-300 dark:hover:text-white dark:hover:bg-gray-700 rounded-lg transition">
                                Dashboard
                            </a>
                            <a href="<?php echo APP_URL; ?>/public/certificates.php" class="px-3 py-2 text-sm font-medium text-gray-700 hover:text-gray-900 hover:bg-gray-100 dark:text-gray-300 dark:hover:text-white dark:hover:bg-gray-700 rounded-lg transition">
                                Certificates
                            </a>
                        </div>

                        <!-- Mobile menu button -->
                        <button id="mobileMenuButton" type="button" class="md:hidden inline-flex items-center justify-center p-2 text-gray-600 hover:text-gray-900 bg-gray-100 dark:bg-gray-700 dark:text-gray-300 rounded-lg transition">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                            </svg>
                        </button>

                        <!-- Mobile Navigation Menu -->
                        <div id="mobileMenu" class="hidden md:hidden absolute top-full left-0 right-0 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 shadow-lg">
                            <div class="px-4 py-3 space-y-2">
                                <a href="<?php 
                                    $homeUrl = APP_URL . '/public/';
                                    if ($user['role'] === ROLE_ADMIN) {
                                        $homeUrl .= 'index.php';
                                    } elseif ($user['role'] === ROLE_HR) {
                                        $homeUrl .= 'hr.php';
                                    } elseif ($user['role'] === ROLE_PRESIDENT) {
                                        $homeUrl .= 'president.php';
                                    } elseif ($user['role'] === ROLE_HEAD) {
                                        $homeUrl .= 'head.php';
                                    } else {
                                        $homeUrl .= 'employee.php';
                                    }
                                    echo $homeUrl;
                                ?>" class="block px-3 py-2 text-sm font-medium text-gray-700 hover:text-gray-900 hover:bg-gray-100 dark:text-gray-300 dark:hover:text-white dark:hover:bg-gray-700 rounded-lg transition">
                                    Dashboard
                                </a>
                                <a href="<?php echo APP_URL; ?>/public/certificates.php" class="block px-3 py-2 text-sm font-medium text-gray-700 hover:text-gray-900 hover:bg-gray-100 dark:text-gray-300 dark:hover:text-white dark:hover:bg-gray-700 rounded-lg transition">
                                    Certificates
                                </a>
                            </div>
                        </div>

                        <div class="relative">
                            <button id="notificationsButton" type="button" class="inline-flex items-center justify-center p-2 text-gray-600 hover:text-gray-900 bg-gray-100 dark:bg-gray-700 dark:text-gray-300 rounded-full transition">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                    <path d="M10 2a6 6 0 00-6 6v3.586l-.707.707A1 1 0 004 14h12a1 1 0 00.707-1.707L16 11.586V8a6 6 0 00-6-6z" />
                                    <path d="M8 16a2 2 0 104 0H8z" />
                                </svg>
                                <span id="notificationCount" class="hidden ml-2 inline-flex items-center justify-center h-5 min-w-[1.25rem] rounded-full bg-red-600 px-2 text-xs font-semibold text-white"></span>
                            </button>
                            <div id="notificationsPanel" class="hidden absolute right-0 mt-2 w-80 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-lg overflow-hidden z-50">
                                <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                                    <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">Notifications</p>
                                </div>
                                <div id="notificationsList" class="max-h-80 overflow-y-auto"></div>
                                <div class="px-4 py-3 border-t border-gray-200 dark:border-gray-700 text-xs text-gray-500 dark:text-gray-400">
                                    Notifications are only for your account.
                                </div>
                            </div>
                        </div>
                        <div class="text-sm text-gray-700 dark:text-gray-300">
                            <span>Logged in as: <strong class="text-gray-900 dark:text-white"><?php echo htmlspecialchars($user['username']); ?></strong></span>
                            <span class="ml-2 px-2 py-1 text-xs bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300 rounded-full">
                                <?php echo htmlspecialchars(ucfirst($user['role'])); ?>
                            </span>
                        </div>
                        <a href="<?php echo APP_URL; ?>/public/logout.php" class="inline-flex items-center px-3 py-2 text-sm font-medium text-white bg-red-600 hover:bg-red-700 rounded-lg transition">
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
            <script src="<?php echo APP_URL; ?>/public/assets/vendor/jquery/jquery.min.js"></script>
            <script src="https://cdn.jsdelivr.net/npm/simple-datatables@9.0.3/dist/umd/simple-datatables.js"></script>
            <script src="<?php echo APP_URL; ?>/public/assets/vendor/flowbite/flowbite.min.js"></script>
            <script src="<?php echo APP_URL; ?>/public/assets/js/main.js"></script>
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    const button = document.getElementById('notificationsButton');
                    const panel = document.getElementById('notificationsPanel');
                    const countEl = document.getElementById('notificationCount');
                    const listEl = document.getElementById('notificationsList');

                    window.NOTIFICATIONS = window.NOTIFICATIONS || [];
                    const unreadCount = window.NOTIFICATIONS.filter(n => n.is_read === 0 || n.is_read === '0').length;

                    if (countEl) {
                        if (unreadCount > 0) {
                            countEl.textContent = unreadCount;
                            countEl.classList.remove('hidden');
                        } else {
                            countEl.classList.add('hidden');
                        }
                    }

                    if (listEl) {
                        if (window.NOTIFICATIONS.length === 0) {
                            listEl.innerHTML = '<div class="px-4 py-4 text-sm text-gray-500 dark:text-gray-400">No notifications.</div>';
                        } else {
                            listEl.innerHTML = window.NOTIFICATIONS.map(function(notification) {
                                return '<div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700 ' + (notification.is_read == 0 ? 'bg-blue-50 dark:bg-gray-900' : '') + '">' +
                                    '<p class="text-sm text-gray-900 dark:text-gray-100">' + notification.message + '</p>' +
                                    '<p class="text-xs text-gray-500 dark:text-gray-400 mt-1">' + notification.created_at + '</p>' +
                                '</div>';
                            }).join('');
                        }
                    }

                    if (button && panel) {
                        button.addEventListener('click', function(event) {
                            event.stopPropagation();
                            panel.classList.toggle('hidden');
                        });

                        document.addEventListener('click', function(event) {
                            if (!event.target.closest('#notificationsPanel') && !event.target.closest('#notificationsButton')) {
                                panel.classList.add('hidden');
                            }
                        });
                    }

                    // Mobile menu toggle
                    const mobileMenuButton = document.getElementById('mobileMenuButton');
                    const mobileMenu = document.getElementById('mobileMenu');

                    if (mobileMenuButton && mobileMenu) {
                        mobileMenuButton.addEventListener('click', function(event) {
                            event.stopPropagation();
                            mobileMenu.classList.toggle('hidden');
                        });

                        document.addEventListener('click', function(event) {
                            if (!event.target.closest('#mobileMenu') && !event.target.closest('#mobileMenuButton')) {
                                mobileMenu.classList.add('hidden');
                            }
                        });
                    }
                });
            </script>
        </body>
        </html>
        <?php
    }
}
