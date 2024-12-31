<?php
/*
Template Name: Dashboard
*/

get_header(); // Gets Divi header
?>

<div id="main-content">
    <div class="container">
        <div id="dashboard-root">
            <!-- React will mount here -->
            <?php if (WP_DEBUG): ?>
                <div id="debug-info" style="background: #f5f5f5; padding: 20px; margin: 20px 0; border: 1px solid #ddd;">
                    <h3>Debug Information</h3>
                    <pre>
Template File: <?php echo get_page_template(); ?>
Is Dashboard Template: <?php echo is_page_template('dashboard/templates/dashboard.php') ? 'Yes' : 'No'; ?>
WP_DEBUG: <?php echo WP_DEBUG ? 'Enabled' : 'Disabled'; ?>
Current Template: <?php echo get_page_template(); ?>
Theme Directory: <?php echo get_stylesheet_directory(); ?>
Script Path: <?php echo get_stylesheet_directory_uri() . '/build/main.js'; ?>
Script Exists: <?php echo file_exists(get_stylesheet_directory() . '/build/main.js') ? 'Yes' : 'No'; ?>
athleteDashboardData: <?php echo wp_json_encode(array(
    'nonce' => wp_create_nonce('wp_rest'),
    'siteUrl' => get_site_url(),
    'apiUrl' => rest_url(),
    'userId' => get_current_user_id()
)); ?>
                    </pre>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php if (WP_DEBUG): ?>
<script>
    console.log('Debug Info:', {
        dashboardRoot: document.getElementById('dashboard-root'),
        wpDebug: <?php echo WP_DEBUG ? 'true' : 'false' ?>,
        templateFile: '<?php echo get_page_template() ?>',
        wpData: window.wp?.data,
        athleteDashboardData: window.athleteDashboardData,
        scriptPath: '<?php echo get_stylesheet_directory_uri() . '/build/main.js' ?>',
        scriptExists: <?php echo file_exists(get_stylesheet_directory() . '/build/main.js') ? 'true' : 'false' ?>
    });
</script>
<?php endif; ?>

<?php get_footer(); // Gets Divi footer ?>