<?php
defined('ABSPATH') || exit;
function tj_table(string $name): string {
    global $wpdb;
    if (!in_array($name, ['files', 'applications', 'history', 'interviews', 'saved', 'notifications', 'audit', 'ai_sessions', 'limits'], true)) { throw new InvalidArgumentException('Unknown table'); }
    return $wpdb->prefix . 'tj_' . $name;
}
function tj_now(): string { return current_time('mysql', true); }
function tj_admin(): bool { return current_user_can('manage_options'); }
function tj_candidate(): bool { return in_array('tj_candidate', wp_get_current_user()->roles, true); }
function tj_audit(string $action, int $id): void { global $wpdb; $wpdb->insert(tj_table('audit'), ['actor_id' => get_current_user_id(), 'action' => $action, 'entity_id' => $id, 'created_at' => tj_now()]); }
function tj_notify(int $user, string $vi, string $en, string $target): void {
    global $wpdb;
    $wpdb->insert(tj_table('notifications'), ['user_id' => $user, 'message' => $vi, 'message_en' => $en, 'target' => $target, 'created_at' => tj_now()]);
}
function tj_job_open(int $id): bool { return get_post_type($id) === 'tj_job' && tj_open_policy((string)get_post_status($id), (string)tj_meta($id, 'deadline'), wp_date('Y-m-d')); }
function tj_job_url(int $id): string { return tj_url('viec-lam/' . get_post_field('post_name', $id)); }
function tj_company_name(int $job): string { $id = (int)tj_meta($job, 'company_id'); return $id ? tj_title($id) : 'TechJob AI'; }
function tj_salary(int $id): string {
    if (tj_meta($id, 'negotiable')) { return tj_t('Thỏa thuận', 'Negotiable'); }
    return number_format((int)tj_meta($id, 'salary_min') / 1000000, 0) . ' – ' . number_format((int)tj_meta($id, 'salary_max') / 1000000, 0) . tj_t(' triệu / tháng', 'M VND / month');
}
function tj_terms(int $id, string $tax): string {
    $terms = wp_get_post_terms($id, 'tj_' . $tax);
    return is_wp_error($terms) ? '' : implode(', ', array_map('tj_term_name', $terms));
}
function tj_jobs(array $filter = [], bool $admin = false): array {
    $meta = [];
    if (!$admin) { $meta[] = ['relation' => 'OR', ['key' => 'tj_deadline', 'value' => wp_date('Y-m-d'), 'compare' => '>='], ['key' => 'tj_deadline', 'value' => '', 'compare' => '=']]; }
    if (!empty($filter['min_salary']) || !empty($filter['max_salary'])) {
        $meta[] = ['key' => 'tj_negotiable', 'value' => '0'];
        if (!empty($filter['min_salary'])) { $meta[] = ['key' => 'tj_salary_max', 'value' => absint($filter['min_salary']), 'compare' => '>=', 'type' => 'NUMERIC']; }
        if (!empty($filter['max_salary'])) { $meta[] = ['key' => 'tj_salary_min', 'value' => absint($filter['max_salary']), 'compare' => '<=', 'type' => 'NUMERIC']; }
    }
    if (!empty($filter['job_type'])) { $meta[] = ['key' => 'tj_job_type', 'value' => sanitize_key($filter['job_type'])]; }
    $tax = [];
    foreach (['skill', 'category', 'position', 'location'] as $key) { if (!empty($filter[$key])) { $tax[] = ['taxonomy' => 'tj_' . $key, 'field' => 'term_id', 'terms' => absint($filter[$key])]; } }
    $args = ['post_type' => 'tj_job', 'post_status' => $admin ? ['publish', 'draft', 'private'] : 'publish', 'posts_per_page' => 9, 'paged' => max(1, absint($filter['pg'] ?? 1)), 'meta_query' => $meta, 'tax_query' => $tax, 'orderby' => 'date', 'order' => 'DESC'];
    if (!empty($filter['q'])) {
        // Query all matching translated titles/content/company names, then paginate in WP_Query.
        $q = mb_strtolower(sanitize_text_field($filter['q']));
        $all = get_posts(['post_type' => 'tj_job', 'post_status' => $args['post_status'], 'numberposts' => -1]);
        $ids = [];
        foreach ($all as $job) {
            $haystack = $job->post_title . ' ' . $job->post_content . ' ' . tj_meta($job->ID, 'title_en') . ' ' . tj_meta($job->ID, 'description_en') . ' ' . tj_company_name($job->ID);
            if (str_contains(mb_strtolower($haystack), $q)) { $ids[] = $job->ID; }
        }
        $args['post__in'] = $ids ?: [0];
    }
    if (!empty($filter['saved_user'])) {
        global $wpdb;
        $args['post__in'] = $wpdb->get_col($wpdb->prepare('SELECT job_id FROM ' . tj_table('saved') . ' WHERE user_id=%d', absint($filter['saved_user']))) ?: [0];
    }
    $query = new WP_Query($args);
    return ['posts' => $query->posts, 'total' => $query->found_posts, 'pages' => $query->max_num_pages];
}
function tj_rows(string $table, int $limit = 50, ?int $user = null): array {
    global $wpdb;
    $sql = 'SELECT * FROM ' . tj_table($table);
    if ($user !== null) { $sql .= $wpdb->prepare(' WHERE user_id=%d', $user); }
    return $wpdb->get_results($sql . $wpdb->prepare(' ORDER BY id DESC LIMIT %d', min(100, $limit)), ARRAY_A);
}
function tj_row(string $table, int $id): ?array { global $wpdb; return $wpdb->get_row($wpdb->prepare('SELECT * FROM ' . tj_table($table) . ' WHERE id=%d', $id), ARRAY_A); }
function tj_limit(string $key, int $limit, int $window): bool {
    global $wpdb;
    $bucket = hash('sha256', $key) . ':' . (int)floor(time() / $window);
    $table = tj_table('limits');
    $wpdb->query($wpdb->prepare("INSERT INTO $table (bucket,hits) VALUES (%s,1) ON DUPLICATE KEY UPDATE hits=hits+1", $bucket));
    return (int)$wpdb->get_var($wpdb->prepare("SELECT hits FROM $table WHERE bucket=%s", $bucket)) <= $limit;
}
