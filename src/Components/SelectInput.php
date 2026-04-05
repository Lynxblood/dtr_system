<?php
/**
 * SelectInput Component
 * 
 * Reusable select input with Flowbite styling
 */

namespace App\Components;

class SelectInput
{
    /**
     * Render select input
     */
    public static function render($name, $label, $options, $selected = '', $required = false, $attrs = '')
    {
        $requiredAttr = $required ? 'required' : '';
        ?>
        <div>
            <label for="<?php echo htmlspecialchars($name); ?>" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
                <?php echo htmlspecialchars($label); ?>
                <?php if ($required): ?>
                    <span class="text-red-600">*</span>
                <?php endif; ?>
            </label>
            <select 
                name="<?php echo htmlspecialchars($name); ?>" 
                id="<?php echo htmlspecialchars($name); ?>"
                <?php echo $requiredAttr; ?>
                <?php echo $attrs; ?>
                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
                <option value="" disabled selected>-- Select <?php echo htmlspecialchars($label); ?> --</option>
                <?php foreach ($options as $value => $text): ?>
                    <option value="<?php echo htmlspecialchars($value); ?>" <?php echo $selected === $value ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($text); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php
    }
}
