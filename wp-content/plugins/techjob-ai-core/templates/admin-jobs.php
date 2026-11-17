<?php defined('ABSPATH') || exit;
if (isset($_GET['edit'])) {
    $id = absint($_GET['edit']); $job = $id ? get_post($id) : null;
    if ($id && (!$job || $job->post_type !== 'tj_job')) { tj_empty('Job not found'); return; }
    $companies = []; foreach (get_posts(['post_type' => 'tj_company', 'numberposts' => -1]) as $company) { $companies[$company->ID] = tj_title($company->ID); }
    if (!$companies && !$id) { ?>
        <section class="tj-panel tj-admin-prerequisite" role="status">
            <span class="tj-admin-prerequisite-icon"><?php tj_admin_icon('building'); ?></span>
            <div><h2><?php tj_e('Tạo hồ sơ công ty trước', 'Create a company first'); ?></h2><p><?php tj_e('Tin tuyển dụng cần gắn với một công ty. Tạo công ty để tiếp tục đăng tin.', 'A job posting must belong to a company. Create one to continue.'); ?></p></div>
            <a class="tj-admin-button tj-admin-button--primary" href="<?php echo esc_url(tj_url('quan-tri/cong-ty', ['edit' => 'new'])); ?>"><?php tj_admin_icon('plus'); ?><span><?php tj_e('Tạo công ty', 'Create company'); ?></span></a>
        </section>
    <?php return; }
    ?><section class="tj-panel"><?php tj_form_start('save_job', 'tj-stack'); tj_hidden('id', $id); ?><div class="tj-form-grid"><?php
    tj_input('title', tj_t('Tiêu đề', 'Title'), $job->post_title ?? '', 'text', true);
    tj_input('title_en', tj_t('Tiêu đề (tiếng Anh, tùy chọn)', 'Title (English, optional)'), tj_meta($id, 'title_en'));
    tj_select('company_id', tj_t('Công ty', 'Company'), $companies, tj_meta($id, 'company_id'));
    tj_select('status', tj_t('Trạng thái', 'Status'), ['draft' => tj_t('Nháp', 'Draft'), 'publish' => tj_t('Đang tuyển', 'Published'), 'private' => tj_t('Đã đóng', 'Closed')], $job->post_status ?? 'draft');
    tj_input('salary_min', tj_t('Lương tối thiểu (VND/tháng)', 'Minimum salary (VND/month)'), tj_meta($id, 'salary_min', 0), 'number'); tj_input('salary_max', tj_t('Lương tối đa (VND/tháng)', 'Maximum salary (VND/month)'), tj_meta($id, 'salary_max', 0), 'number');
    tj_input('deadline', tj_t('Hạn nộp', 'Deadline'), tj_meta($id, 'deadline'), 'date'); tj_select('job_type', tj_t('Loại hình làm việc', 'Job type'), ['full-time' => 'Full-time', 'part-time' => 'Part-time', 'internship' => 'Internship', 'remote' => 'Remote'], tj_meta($id, 'job_type', 'full-time'));
    ?></div><div class="tj-form-grid tj-job-taxonomies"><?php
    $selected_skills = $id ? wp_get_post_terms($id, 'tj_skill', ['fields' => 'ids']) : [];
    $selected_skills = is_wp_error($selected_skills) ? [] : $selected_skills;
    $skills = tj_term_options('skill', false);
    $skill_names = array_intersect_key($skills, array_flip($selected_skills));
    $empty_label = tj_t('Chọn kỹ năng', 'Select skills');
    ?>
    <div class="tj-field">
        <span id="tj-job-skills-label"><?php tj_e('Kỹ năng', 'Skills'); ?></span>
        <details class="tj-skill-picker" data-empty-label="<?php echo esc_attr($empty_label); ?>">
            <summary aria-labelledby="tj-job-skills-label tj-job-skills-value"><span id="tj-job-skills-value" class="tj-skill-value"><?php echo esc_html($skill_names ? implode(', ', $skill_names) : $empty_label); ?></span></summary>
            <div class="tj-skill-options">
                <?php if (!$skills) { ?><p class="tj-muted"><?php tj_e('Thêm kỹ năng trong mục Danh mục & kỹ năng.', 'Add skills in Categories & skills.'); ?></p><?php } ?>
                <?php foreach ($skills as $skill_id => $skill_name) { ?>
                    <label class="tj-skill-option"><input type="checkbox" name="skill[]" value="<?php echo (int)$skill_id; ?>" <?php checked(in_array((int)$skill_id, $selected_skills, true)); ?>><span><?php echo esc_html($skill_name); ?></span></label>
                <?php } ?>
            </div>
        </details>
    </div>
    <?php foreach (['category' => tj_t('Nhóm nghề', 'Category'), 'position' => tj_t('Vị trí', 'Position'), 'location' => tj_t('Địa điểm', 'Location')] as $tax => $label) {
        $selected = $id ? wp_get_post_terms($id, 'tj_' . $tax, ['fields' => 'ids']) : [];
        tj_select($tax, $label, tj_term_options($tax, false), is_wp_error($selected) ? [] : $selected);
    }
    ?></div><div class="tj-row"><label class="tj-check"><input type="checkbox" name="negotiable" value="1" <?php checked(tj_meta($id, 'negotiable')); ?>> <?php tj_e('Lương thỏa thuận', 'Negotiable salary'); ?></label><label class="tj-check"><input type="checkbox" name="featured" value="1" <?php checked(tj_meta($id, 'featured')); ?>> <?php tj_e('Tin nổi bật', 'Featured'); ?></label></div>
    <p class="tj-muted"><?php tj_e('Để trống bản dịch để hiển thị nội dung tiếng Việt.', 'Leave translations empty to display the Vietnamese content.'); ?></p>
    <div class="tj-form-grid"><?php
    tj_editor('description', tj_t('Mô tả (tiếng Việt)', 'Description (Vietnamese)'), $job->post_content ?? '');
    tj_editor('description_en', tj_t('Mô tả (tiếng Anh, tùy chọn)', 'Description (English, optional)'), tj_meta($id, 'description_en'));
    tj_editor('requirements', tj_t('Yêu cầu (tiếng Việt)', 'Requirements (Vietnamese)'), tj_meta($id, 'requirements'));
    tj_editor('requirements_en', tj_t('Yêu cầu (tiếng Anh, tùy chọn)', 'Requirements (English, optional)'), tj_meta($id, 'requirements_en'));
    ?></div><div class="tj-row"><?php tj_button('Lưu tin tuyển dụng', 'Save job'); ?><a href="<?php echo esc_url(tj_url('quan-tri/viec-lam')); ?>"><?php tj_e('Quay lại', 'Back'); ?></a></div></form></section><?php
} else {
    $jobs = tj_jobs($_GET, true); ?>
    <div class="tj-row tj-between"><form method="get" class="tj-filter-row"><?php tj_hidden('lang', tj_lang()); tj_input('q', tj_t('Tìm tiêu đề / công ty', 'Search title / company'), sanitize_text_field(wp_unslash($_GET['q'] ?? ''))); tj_button('Tìm kiếm', 'Search'); ?></form><a class="tj-button" href="<?php echo esc_url(tj_url('quan-tri/viec-lam', ['edit' => 'new'])); ?>">+ <?php tj_e('Thêm tin tuyển dụng', 'Add job'); ?></a></div><div class="tj-table-wrap"><table class="tj-table"><thead><tr><th><?php tj_e('Tin tuyển dụng', 'Job'); ?></th><th><?php tj_e('Công ty', 'Company'); ?></th><th><?php tj_e('Lương', 'Salary'); ?></th><th><?php tj_e('Trạng thái', 'Status'); ?></th><th><?php tj_e('Hạn nộp', 'Deadline'); ?></th><th></th></tr></thead><tbody><?php foreach ($jobs['posts'] as $job) { ?><tr><td><a href="<?php echo esc_url(tj_job_url($job->ID)); ?>"><?php echo esc_html(tj_title($job->ID)); ?></a></td><td><?php echo esc_html(tj_company_name($job->ID)); ?></td><td><?php echo esc_html(tj_salary($job->ID)); ?></td><td><span class="tj-badge"><?php echo esc_html($job->post_status === 'draft' ? tj_t('Nháp', 'Draft') : (tj_job_open($job->ID) ? tj_t('Đang tuyển', 'Open') : tj_t('Đã đóng/hết hạn', 'Closed/expired'))); ?></span></td><td><?php echo esc_html(tj_meta($job->ID, 'deadline') ?: '—'); ?></td><td><div class="tj-row"><a href="<?php echo esc_url(tj_url('quan-tri/viec-lam', ['edit' => $job->ID])); ?>"><?php tj_e('Sửa', 'Edit'); ?></a><?php tj_form_start('delete_job', 'tj-inline tj-confirm'); tj_hidden('id', $job->ID); tj_button('Xóa', 'Delete', 'danger'); echo '</form>'; ?></div></td></tr><?php } ?></tbody></table></div><?php if (!$jobs['posts']) { tj_empty(tj_t('Chưa có tin tuyển dụng.', 'No jobs yet.')); } tj_pagination((int)$jobs['pages']);
}
