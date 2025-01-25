jQuery(document).ready(function($) {
    const widget = $('.cache-stats-widget');
    const clearButton = $('#clear-cache');
    const refreshButton = $('#refresh-stats');

    // Helper function to show loading state
    function showLoading() {
        widget.addClass('loading');
        clearButton.prop('disabled', true);
        refreshButton.prop('disabled', true);
    }

    // Helper function to hide loading state
    function hideLoading() {
        widget.removeClass('loading');
        clearButton.prop('disabled', false);
        refreshButton.prop('disabled', false);
    }

    // Helper function to update stats display
    function updateStats(stats) {
        Object.entries(stats).forEach(([key, value]) => {
            const item = widget.find(`.stat-item:contains("${key}")`);
            if (item.length) {
                const valueSpan = item.find('.stat-value');
                valueSpan.text(value);
                
                // Add status-specific styling
                if (key === 'Cache Status') {
                    valueSpan.attr('data-status', value.toLowerCase());
                }
            }
        });
    }

    // Handle clear cache button click
    clearButton.on('click', function(e) {
        e.preventDefault();
        showLoading();

        $.ajax({
            url: cacheStatsWidgetSettings.ajaxUrl,
            type: 'POST',
            data: {
                action: 'clear_cache',
                nonce: cacheStatsWidgetSettings.nonce
            },
            success: function(response) {
                if (response.success) {
                    updateStats(response.data);
                } else {
                    alert('Failed to clear cache: ' + response.data.message);
                }
            },
            error: function() {
                alert('Failed to clear cache. Please try again.');
            },
            complete: hideLoading
        });
    });

    // Handle refresh stats button click
    refreshButton.on('click', function(e) {
        e.preventDefault();
        showLoading();

        $.ajax({
            url: cacheStatsWidgetSettings.ajaxUrl,
            type: 'POST',
            data: {
                action: 'refresh_cache_stats',
                nonce: cacheStatsWidgetSettings.nonce
            },
            success: function(response) {
                if (response.success) {
                    updateStats(response.data);
                } else {
                    alert('Failed to refresh stats: ' + response.data.message);
                }
            },
            error: function() {
                alert('Failed to refresh stats. Please try again.');
            },
            complete: hideLoading
        });
    });

    // Add status-specific styling on initial load
    widget.find('.stat-item').each(function() {
        const label = $(this).find('.stat-label').text();
        if (label.includes('Cache Status')) {
            const value = $(this).find('.stat-value');
            value.attr('data-status', value.text().toLowerCase());
        }
    });
}); 