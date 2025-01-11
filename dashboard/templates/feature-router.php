/**
 * Feature router template for the Athlete Dashboard.
 *
 * @package Athlete_Dashboard
 */

// Get the current feature from the URL.
$current_feature = get_query_var('feature', 'overview');

// Load the appropriate feature template.
$template_path = get_template_directory() . "/features/{$current_feature}/template.php";

// Check if the template exists.
if (file_exists($template_path)) {
    include $template_path;
} else {
    // Fall back to the overview template if the requested feature doesn't exist.
    include get_template_directory() . '/features/overview/template.php';
}
