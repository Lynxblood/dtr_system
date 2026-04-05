<?php
/**
 * Card Component
 * 
 * Reusable card component
 */

namespace App\Components;

class Card
{
    /**
     * Render card opening
     */
    public static function open($title, $description = '')
    {
        ?>
        <div class="p-6 bg-white border border-gray-200 rounded-lg shadow dark:bg-gray-800 dark:border-gray-700">
            <h5 class="mb-2 text-xl font-bold tracking-tight text-gray-900 dark:text-white">
                <?php echo htmlspecialchars($title); ?>
            </h5>
            <?php if ($description): ?>
                <p class="mb-4 text-sm text-gray-700 dark:text-gray-400">
                    <?php echo htmlspecialchars($description); ?>
                </p>
            <?php endif; ?>
        <?php
    }

    /**
     * Render card closing
     */
    public static function close()
    {
        ?>
        </div>
        <?php
    }

    /**
     * Render complete card
     */
    public static function render($title, $content, $description = '')
    {
        self::open($title, $description);
        echo $content;
        self::close();
    }
}
