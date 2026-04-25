/**
 * Fuerte-WP Login Security Admin JavaScript
 *
 * @since 1.7.0
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        initLoginLogsTable();
        bindEvents();

        // Check for page hash in URL
        var hash = window.location.hash;
        if (hash && hash.match(/^#page-(\d+)$/)) {
            var pageFromHash = parseInt(hash.replace('#page-', ''));
            loadLoginLogs(pageFromHash);
        }
    });

    /**
     * Initialize the login logs table.
     */
    function initLoginLogsTable() {
        loadLoginLogs(1);
    }

    /**
     * Bind event handlers.
     */
    function bindEvents() {
        // Export attempts
        $('#fuertewp-export-attempts').on('click', function(e) {
            e.preventDefault();
            exportAttempts();
        });

        // Clear logs
        $('#fuertewp-clear-logs').on('click', function(e) {
            e.preventDefault();
            if (confirm(fuertewp_login_admin.i18n.confirm_clear || 'Are you sure?')) {
                clearLogs();
            }
        });

        // Reset lockouts
        $('#fuertewp-reset-lockouts').on('click', function(e) {
            e.preventDefault();
            if (confirm(fuertewp_login_admin.i18n.confirm_reset || 'Are you sure?')) {
                resetLockouts();
            }
        });
    }

    /**
     * Load login logs via AJAX.
     */
    function loadLoginLogs(page) {
        $.ajax({
            url: fuertewp_login_admin.ajax_url,
            type: 'POST',
            data: {
                action: 'fuertewp_get_login_logs',
                nonce: fuertewp_login_admin.nonce,
                page: page
            },
            success: function(response) {
                if (response.success) {
                    $('#fuertewp-logs-table-container').html(response.data.html);
                    bindTableEvents();

                    // Update URL hash
                    if (page > 1) {
                        window.location.hash = 'page-' + page;
                    } else {
                        // Remove hash if on first page
                        if (window.location.hash) {
                            history.pushState('', document.title, window.location.pathname + window.location.search);
                        }
                    }
                } else {
                    $('#fuertewp-logs-table-container').html('<p>' + (response.data || 'Error loading logs') + '</p>');
                }
            },
            error: function() {
                $('#fuertewp-logs-table-container').html('<p>Error loading logs.</p>');
            }
        });
    }

    /**
     * Bind events to table pagination and actions.
     */
    function bindTableEvents() {
        // Pagination links
        $('.fuertewp-pagination a').on('click', function(e) {
            e.preventDefault();
            var page = $(this).data('page');
            loadLoginLogs(page);
        });

        // Unblock single entry buttons
        $('.fuertewp-unblock-single').on('click', function(e) {
            e.preventDefault();
            var button = $(this);
            var ip = button.data('ip');
            var username = button.data('username');
            var id = button.data('id');

            if (confirm('Are you sure you want to unblock this IP (' + ip + ') for user ' + username + '?')) {
                unblockSingle(button, ip, username, id);
            }
        });
    }

    /**
     * Unblock individual login attempt.
     */
    function unblockSingle(button, ip, username, id) {
        button.prop('disabled', true).text('Unblocking...');

        $.ajax({
            url: fuertewp_login_admin.ajax_url,
            type: 'POST',
            data: {
                action: 'fuertewp_unblock_single',
                nonce: fuertewp_login_admin.nonce,
                ip: ip,
                username: username,
                id: id
            },
            success: function(response) {
                if (response.success) {
                    alert(response.data.message);
                    // Reload the current page to refresh the table
                    var currentPage = $('.fuertewp-pagination .current').data('page') || 1;
                    loadLoginLogs(currentPage);
                } else {
                    alert(response.data || 'Error unblocking entry');
                    button.prop('disabled', false).text('Unblock');
                }
            },
            error: function() {
                alert('Error unblocking entry');
                button.prop('disabled', false).text('Unblock');
            }
        });
    }

    /**
     * Export login attempts to CSV.
     */
    function exportAttempts() {
        // Create a form and submit it for download
        var form = $('<form>')
            .attr('method', 'POST')
            .attr('action', fuertewp_login_admin.ajax_url);

        $('<input>')
            .attr('type', 'hidden')
            .attr('name', 'action')
            .attr('value', 'fuertewp_export_attempts')
            .appendTo(form);

        $('<input>')
            .attr('type', 'hidden')
            .attr('name', 'nonce')
            .attr('value', fuertewp_login_admin.nonce)
            .appendTo(form);

        $('body').append(form);
        form.submit();
        form.remove();
    }

    /**
     * Clear all login logs.
     */
    function clearLogs() {
        $.ajax({
            url: fuertewp_login_admin.ajax_url,
            type: 'POST',
            data: {
                action: 'fuertewp_clear_login_logs',
                nonce: fuertewp_login_admin.nonce
            },
            success: function(response) {
                if (response.success) {
                    alert(response.data.message);
                    loadLoginLogs(1);
                    location.reload(); // Reload to update stats
                } else {
                    alert(response.data || 'Error clearing logs');
                }
            },
            error: function() {
                alert('Error clearing logs');
            }
        });
    }

    /**
     * Reset all lockouts.
     */
    function resetLockouts() {
        $.ajax({
            url: fuertewp_login_admin.ajax_url,
            type: 'POST',
            data: {
                action: 'fuertewp_reset_lockouts',
                nonce: fuertewp_login_admin.nonce
            },
            success: function(response) {
                if (response.success) {
                    alert(response.data.message);
                    loadLoginLogs(1);
                    location.reload(); // Reload to update stats
                } else {
                    alert(response.data || 'Error resetting lockouts');
                }
            },
            error: function() {
                alert('Error resetting lockouts');
            }
        });
    }

})(jQuery);
