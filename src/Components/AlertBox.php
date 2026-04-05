<?php
/**
 * AlertBox Component
 * 
 * Reusable alert messages
 */

namespace App\Components;

class AlertBox
{
    /**
     * Render success alert
     */
    public static function success($message)
    {
        ?>
        <div class="p-4 mb-4 text-sm text-green-800 rounded-lg bg-green-50 dark:bg-gray-800 dark:text-green-400 border border-green-200 dark:border-green-700" role="alert">
            <span class="font-medium">Success!</span> <?php echo htmlspecialchars($message); ?>
        </div>
        <?php
    }

    /**
     * Render error alert
     */
    public static function error($message)
    {
        ?>
        <div class="p-4 mb-4 text-sm text-red-800 rounded-lg bg-red-50 dark:bg-gray-800 dark:text-red-400 border border-red-200 dark:border-red-700" role="alert">
            <span class="font-medium">Error!</span> <?php echo htmlspecialchars($message); ?>
        </div>
        <?php
    }

    /**
     * Render warning alert
     */
    public static function warning($message)
    {
        ?>
        <div class="p-4 mb-4 text-sm text-yellow-800 rounded-lg bg-yellow-50 dark:bg-gray-800 dark:text-yellow-400 border border-yellow-200 dark:border-yellow-700" role="alert">
            <span class="font-medium">Warning!</span> <?php echo htmlspecialchars($message); ?>
        </div>
        <?php
    }

    /**
     * Render info alert
     */
    public static function info($message)
    {
        ?>
        <div class="p-4 mb-4 text-sm text-blue-800 rounded-lg bg-blue-50 dark:bg-gray-800 dark:text-blue-400 border border-blue-200 dark:border-blue-700" role="alert">
            <span class="font-medium">Info!</span> <?php echo htmlspecialchars($message); ?>
        </div>
        <?php
    }

    /**
     * Render alert container for AJAX responses
     */
    public static function container($id = 'alertContainer')
    {
        ?>
        <div id="<?php echo htmlspecialchars($id); ?>" class="mb-4"></div>
        <?php
    }

    /**
     * Render alert based on type
     */
    public static function show($type, $message)
    {
        switch ($type) {
            case 'success':
                self::success($message);
                break;
            case 'error':
                self::error($message);
                break;
            case 'warning':
                self::warning($message);
                break;
            case 'info':
                self::info($message);
                break;
            default:
                self::info($message);
        }
    }
}
