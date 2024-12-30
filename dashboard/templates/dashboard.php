<?php
/* Template Name: Dashboard */

// Ensure the template is being loaded
if (!defined('ABSPATH')) exit;

// Debug information
if (WP_ENV === 'development') {
    error_log('Dashboard template loaded');
}
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php wp_head(); ?>
    <?php if (WP_ENV === 'development'): ?>
    <script>
        console.log('Dashboard template loaded');
        console.log('Environment:', '<?php echo WP_ENV; ?>');
    </script>
    <?php endif; ?>
</head>
<body <?php body_class('dashboard-page'); ?>>
    <?php wp_body_open(); ?>
    
    <!-- React mount point -->
    <div id="dashboard-root">
        <div id="dev-loading" style="font-family: Arial; text-align: center; padding: 20px;">
            Loading Dashboard...
        </div>
    </div>

    <?php wp_footer(); ?>
    
    <?php if (WP_ENV === 'development'): ?>
    <script>
        // Verify React mount point
        console.log('Mount point exists:', !!document.getElementById('dashboard-root'));
    </script>
    <?php endif; ?>
</body>
</html> 