<?php
/**
 * Profile Admin Class
 * 
 * Handles the display and saving of physical measurement fields in WordPress admin.
 */

namespace AthleteDashboard\Features\Profile\Admin;

/**
 * Profile_Admin class
 */
class Profile_Admin {

    /**
     * Initialize the admin hooks
     */
    public function init(): void {
        add_action('show_user_profile', array($this, 'render_physical_fields'));
        add_action('edit_user_profile', array($this, 'render_physical_fields'));
        add_action('personal_options_update', array($this, 'save_physical_fields'));
        add_action('edit_user_profile_update', array($this, 'save_physical_fields'));
    }

    /**
     * Render physical measurement fields
     * 
     * @param \WP_User $user The user object.
     */
    public function render_physical_fields($user): void {
        $height = get_user_meta($user->ID, 'height', true);
        $weight = get_user_meta($user->ID, 'weight', true);
        $chest = get_user_meta($user->ID, 'chest', true);
        $waist = get_user_meta($user->ID, 'waist', true);
        $hips = get_user_meta($user->ID, 'hips', true);
        ?>
        <h3><?php esc_html_e('Physical Information', 'athlete-dashboard'); ?></h3>
        <table class="form-table">
            <tr>
                <th><label for="height"><?php esc_html_e('Height (cm)', 'athlete-dashboard'); ?></label></th>
                <td>
                    <input type="number" step="0.01" name="physical[height]" id="height" value="<?php echo esc_attr($height); ?>" class="regular-text" />
                </td>
            </tr>
            <tr>
                <th><label for="weight"><?php esc_html_e('Weight (kg)', 'athlete-dashboard'); ?></label></th>
                <td>
                    <input type="number" step="0.01" name="physical[weight]" id="weight" value="<?php echo esc_attr($weight); ?>" class="regular-text" />
                </td>
            </tr>
            <tr>
                <th><label for="chest"><?php esc_html_e('Chest (cm)', 'athlete-dashboard'); ?></label></th>
                <td>
                    <input type="number" step="0.01" name="physical[chest]" id="chest" value="<?php echo esc_attr($chest); ?>" class="regular-text" />
                </td>
            </tr>
            <tr>
                <th><label for="waist"><?php esc_html_e('Waist (cm)', 'athlete-dashboard'); ?></label></th>
                <td>
                    <input type="number" step="0.01" name="physical[waist]" id="waist" value="<?php echo esc_attr($waist); ?>" class="regular-text" />
                </td>
            </tr>
            <tr>
                <th><label for="hips"><?php esc_html_e('Hips (cm)', 'athlete-dashboard'); ?></label></th>
                <td>
                    <input type="number" step="0.01" name="physical[hips]" id="hips" value="<?php echo esc_attr($hips); ?>" class="regular-text" />
                </td>
            </tr>
        </table>
        <?php
    }

    /**
     * Save physical measurement fields
     * 
     * @param int $user_id The user ID.
     * @return bool|void
     */
    public function save_physical_fields($user_id) {
        if (!current_user_can('edit_user', $user_id)) {
            return false;
        }

        if (!isset($_POST['physical'])) {
            return;
        }

        $physical_data = wp_unslash($_POST['physical']);
        
        // Sanitize and validate the data
        $sanitized_data = array(
            'height' => isset($physical_data['height']) ? (float) $physical_data['height'] : null,
            'weight' => isset($physical_data['weight']) ? (float) $physical_data['weight'] : null,
            'chest' => isset($physical_data['chest']) ? (float) $physical_data['chest'] : null,
            'waist' => isset($physical_data['waist']) ? (float) $physical_data['waist'] : null,
            'hips' => isset($physical_data['hips']) ? (float) $physical_data['hips'] : null,
        );

        // Update user meta
        foreach ($sanitized_data as $key => $value) {
            if ($value !== null) {
                update_user_meta($user_id, $key, $value);
            }
        }

        // Trigger history update through the service
        $physical_service = new \AthleteDashboard\Features\Profile\Services\Physical_Service();
        $physical_service->update_physical_data($user_id, array_merge($sanitized_data, array(
            'units' => array(
                'height' => 'cm',
                'weight' => 'kg',
                'measurements' => 'cm'
            ),
            'preferences' => array(
                'showMetric' => true
            )
        )));
    }
} 