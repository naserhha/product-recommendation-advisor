<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Create database table on plugin activation
function perfume_advisor_create_tables() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();
    
    $table_name = $wpdb->prefix . 'perfume_survey';
    
    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        user_id bigint(20) DEFAULT NULL,
        answers longtext NOT NULL,
        created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY  (id),
        KEY user_id (user_id)
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

// Save survey response
function perfume_advisor_save_response($user_id, $answers) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'perfume_survey';
    
    return $wpdb->insert(
        $table_name,
        array(
            'user_id' => $user_id,
            'answers' => json_encode($answers),
            'created_at' => current_time('mysql')
        ),
        array('%d', '%s', '%s')
    );
}

// Get user's last survey response
function perfume_advisor_get_user_last_response($user_id) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'perfume_survey';
    
    $response = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $table_name WHERE user_id = %d ORDER BY created_at DESC LIMIT 1",
        $user_id
    ));
    
    if ($response) {
        $response->answers = json_decode($response->answers, true);
    }
    
    return $response;
}

// Get all survey responses
function perfume_advisor_get_all_responses($limit = 10, $offset = 0) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'perfume_survey';
    
    $responses = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM $table_name ORDER BY created_at DESC LIMIT %d OFFSET %d",
        $limit,
        $offset
    ));
    
    foreach ($responses as &$response) {
        $response->answers = json_decode($response->answers, true);
    }
    
    return $responses;
}

// Get total number of responses
function perfume_advisor_get_total_responses() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'perfume_survey';
    
    return $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
}

// Get responses by date range
function perfume_advisor_get_responses_by_date_range($start_date, $end_date) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'perfume_survey';
    
    $responses = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM $table_name WHERE DATE(created_at) BETWEEN %s AND %s ORDER BY created_at DESC",
        $start_date,
        $end_date
    ));
    
    foreach ($responses as &$response) {
        $response->answers = json_decode($response->answers, true);
    }
    
    return $responses;
}

// Delete old responses
function perfume_advisor_delete_old_responses($days = 30) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'perfume_survey';
    
    return $wpdb->query($wpdb->prepare(
        "DELETE FROM $table_name WHERE created_at < DATE_SUB(NOW(), INTERVAL %d DAY)",
        $days
    ));
}

// Get response statistics
function perfume_advisor_get_response_stats() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'perfume_survey';
    
    $stats = array(
        'total' => $wpdb->get_var("SELECT COUNT(*) FROM $table_name"),
        'today' => $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE DATE(created_at) = %s",
            current_time('Y-m-d')
        )),
        'this_week' => $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE YEARWEEK(created_at) = YEARWEEK(%s)",
            current_time('mysql')
        )),
        'this_month' => $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE MONTH(created_at) = MONTH(%s) AND YEAR(created_at) = YEAR(%s)",
            current_time('mysql'),
            current_time('mysql')
        ))
    );
    
    return $stats;
} 