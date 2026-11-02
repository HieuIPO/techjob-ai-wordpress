<?php
defined('ABSPATH') || exit;
function tj_register_content(): void {
    foreach (['tj_job' => 'Việc làm', 'tj_company' => 'Công ty'] as $type => $label) {
        register_post_type($type, ['label' => $label, 'public' => false, 'show_ui' => false, 'supports' => ['title', 'editor'], 'map_meta_cap' => true]);
    }
    foreach (['skill' => 'Kỹ năng', 'category' => 'Nhóm nghề', 'position' => 'Vị trí', 'location' => 'Địa điểm'] as $slug => $label) {
        register_taxonomy('tj_' . $slug, 'tj_job', ['label' => $label, 'public' => false, 'show_ui' => false, 'hierarchical' => $slug === 'category']);
    }
}
function tj_activate(): void {
    global $wpdb;
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    $collation = $wpdb->get_charset_collate();
    $definitions = [
        'files' => "id bigint unsigned NOT NULL AUTO_INCREMENT, user_id bigint unsigned NOT NULL, original_name varchar(255) NOT NULL, stored_name varchar(100) NOT NULL, mime varchar(100) NOT NULL, size bigint unsigned NOT NULL, created_at datetime NOT NULL, PRIMARY KEY  (id), KEY user_id (user_id)",
        'applications' => "id bigint unsigned NOT NULL AUTO_INCREMENT, job_id bigint unsigned NOT NULL, user_id bigint unsigned NOT NULL, file_id bigint unsigned NOT NULL, candidate_name varchar(160) NOT NULL, email varchar(160) NOT NULL, phone varchar(40) NOT NULL, cover_letter text NOT NULL, status varchar(30) NOT NULL DEFAULT 'submitted', note text NOT NULL, created_at datetime NOT NULL, updated_at datetime NOT NULL, PRIMARY KEY  (id), UNIQUE KEY candidate_job (user_id,job_id), KEY status (status), KEY job_id (job_id)",
        'history' => "id bigint unsigned NOT NULL AUTO_INCREMENT, application_id bigint unsigned NOT NULL, actor_id bigint unsigned NOT NULL, old_status varchar(30) NOT NULL, new_status varchar(30) NOT NULL, note text NOT NULL, created_at datetime NOT NULL, PRIMARY KEY  (id), KEY application_id (application_id)",
        'interviews' => "id bigint unsigned NOT NULL AUTO_INCREMENT, application_id bigint unsigned NOT NULL, interviewer_id bigint unsigned NOT NULL, scheduled_at datetime NOT NULL, location varchar(255) NOT NULL, result varchar(30) NOT NULL DEFAULT 'pending', note text NOT NULL, cancelled tinyint NOT NULL DEFAULT 0, active_slot tinyint DEFAULT 1, created_at datetime NOT NULL, PRIMARY KEY  (id), UNIQUE KEY active_application_time (application_id,scheduled_at,active_slot), KEY scheduled_at (scheduled_at)",
        'saved' => "id bigint unsigned NOT NULL AUTO_INCREMENT, user_id bigint unsigned NOT NULL, job_id bigint unsigned NOT NULL, created_at datetime NOT NULL, PRIMARY KEY  (id), UNIQUE KEY user_job (user_id,job_id)",
        'notifications' => "id bigint unsigned NOT NULL AUTO_INCREMENT, user_id bigint unsigned NOT NULL, message text NOT NULL, message_en text NOT NULL, target varchar(255) NOT NULL, is_read tinyint NOT NULL DEFAULT 0, created_at datetime NOT NULL, PRIMARY KEY  (id), KEY recipient (user_id,is_read)",
        'audit' => "id bigint unsigned NOT NULL AUTO_INCREMENT, actor_id bigint unsigned NOT NULL, action varchar(60) NOT NULL, entity_id bigint unsigned NOT NULL, created_at datetime NOT NULL, PRIMARY KEY  (id)",
        'ai_sessions' => "id bigint unsigned NOT NULL AUTO_INCREMENT, user_id bigint unsigned NOT NULL, mode varchar(20) NOT NULL, skill varchar(80) NOT NULL, level varchar(40) NOT NULL, language varchar(5) NOT NULL, messages longtext NOT NULL, step tinyint NOT NULL DEFAULT 0, created_at datetime NOT NULL, updated_at datetime NOT NULL, PRIMARY KEY  (id), KEY user_id (user_id)",
        'limits' => "bucket varchar(191) NOT NULL, hits int unsigned NOT NULL DEFAULT 0, PRIMARY KEY  (bucket)",
    ];
    foreach ($definitions as $table => $columns) {
        dbDelta('CREATE TABLE ' . $wpdb->prefix . 'tj_' . $table . " (\n" . str_replace(', ', ",\n", $columns) . "\n) ENGINE=InnoDB $collation;");
    }
    $interviews = $wpdb->prefix . 'tj_interviews';
    if ($wpdb->get_row("SHOW INDEX FROM $interviews WHERE Key_name='application_time'")) { $wpdb->query("ALTER TABLE $interviews DROP INDEX application_time"); }
    $wpdb->query("UPDATE $interviews SET active_slot=NULL WHERE cancelled=1");
    add_role('tj_candidate', 'Ứng viên', ['read' => true]);
    tj_register_content();
    tj_register_routes();
    flush_rewrite_rules();
    update_option('tj_schema_version', 2);
}
