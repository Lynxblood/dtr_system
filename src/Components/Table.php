<?php
/**
 * Table Component
 * 
 * Reusable table component with DataTables integration
 */

namespace App\Components;

class Table
{
    /**
     * Render table with DataTables
     */
    public static function render($id, $headers, $rows, $sortBy = 0, $sortOrder = 'desc')
    {
        ?>
        <div class="overflow-x-auto">
            <table id="<?php echo htmlspecialchars($id); ?>" class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                <thead class="text-xs text-gray-700 uppercase bg-gray-100 dark:bg-gray-700 dark:text-gray-400">
                    <tr>
                        <?php foreach ($headers as $header): ?>
                            <th class="px-6 py-3"><?php echo htmlspecialchars($header); ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $row): ?>
                        <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                            <?php foreach ($row as $cell): ?>
                                <td class="px-6 py-4">
                                    <?php echo is_array($cell) ? $cell['html'] : htmlspecialchars($cell); ?>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <script>
            document.addEventListener('DOMContentLoaded', function() {
                if (!document.getElementById('<?php echo htmlspecialchars($id); ?>')) return;
                
                $('#<?php echo htmlspecialchars($id); ?>').DataTable({
                    "pageLength": 10,
                    "lengthMenu": [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
                    "order": [[<?php echo intval($sortBy); ?>, "<?php echo htmlspecialchars($sortOrder); ?>"]],
                    "language": {
                        "search": "Filter records:",
                        "lengthMenu": "Show _MENU_ entries per page"
                    }
                });
            });
        </script>
        <?php
    }
}
