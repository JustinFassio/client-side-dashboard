<?php
/* Template Name: Dashboard */

// Ensure the template is being loaded
if (!defined('ABSPATH')) exit;

// Debug information
if (WP_ENV === 'development') {
    error_log('Dashboard template loaded at: ' . date('Y-m-d H:i:s'));
}

// Force display errors in development
if (WP_ENV === 'development') {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
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
        console.log('Template file:', '<?php echo __FILE__; ?>');
    </script>
    <?php endif; ?>
</head>
<body <?php body_class('dashboard-page'); ?>>
    <?php wp_body_open(); ?>
    
    <!-- React mount point -->
    <div id="dashboard-root">
        <div id="dev-loading" style="font-family: Arial; text-align: center; padding: 20px; background: #f5f5f5; margin: 20px; border-radius: 4px;">
            Loading Dashboard...
            <?php if (WP_ENV === 'development'): ?>
                <div style="font-size: 12px; color: #666; margin-top: 10px;">
                    Development Mode Active
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php wp_footer(); ?>
    
    <?php if (WP_ENV === 'development'): ?>
    <script>
        // Verify React mount point
        console.log('Mount point exists:', !!document.getElementById('dashboard-root'));
        // Log script loading
        console.log('Enqueued scripts:', <?php echo json_encode(wp_scripts()->queue); ?>);
    </script>
    <?php endif; ?>
</body>
</html> 