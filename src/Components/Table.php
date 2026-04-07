<?php
/**
 * Table Component
 * 
 * Reusable table component with Simple-DataTables integration
 */

namespace App\Components;

class Table
{
    /**
     * Render table with Simple-DataTables
     */
    public static function render($id, $headers, $rows, $sortBy = 0, $sortOrder = 'desc')
    {
        ?>
        <div class="relative overflow-x-auto shadow-md sm:rounded-lg">
            <table id="<?php echo htmlspecialchars($id); ?>" class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                    <tr>
                        <?php foreach ($headers as $header): ?>
                            <th scope="col" class="px-6 py-3"><?php echo htmlspecialchars($header); ?></th>
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

                const dataTable = new simpleDatatables.DataTable('#<?php echo htmlspecialchars($id); ?>', {
                    perPage: 10,
                    perPageSelect: [10, 25, 50, 100],
                    sortable: true,
                    searchable: true,
                    fixedHeight: false,
                    columns: [
                        { select: <?php echo intval($sortBy); ?>, sort: "<?php echo htmlspecialchars($sortOrder); ?>" }
                    ],
                    labels: {
                        placeholder: "Search...",
                        perPage: "{select} entries per page",
                        noRows: "No entries to show",
                        info: "Showing {start} to {end} of {rows} entries"
                    }
                });
            });
        </script>
        <?php
    }
}
