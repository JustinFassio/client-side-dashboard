<?php
/**
 * Template for the Workout Generator settings page
 */

// Ensure this file is being included by a parent file
defined('ABSPATH') || die('Direct access to this file is disabled.');
?>

<div class="wrap">
    <h1>Workout Generator Settings</h1>
    <form action="options.php" method="post">
        <?php
        settings_fields('workout_generator_options');
        do_settings_sections('workout_generator');
        submit_button();
        ?>
    </form>
</div> 