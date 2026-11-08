<?php
defined('ABSPATH') || exit;
$filter = array_map(
    fn($value) => is_scalar($value) ? sanitize_text_field(wp_unslash($value)) : '',
    $_GET
);
$jobs = tj_jobs($filter + ['home' => $route === '']);
?>
<section class="hero-search-section tj-career-hero">
    <div class="tj-hero-inner">
        <div class="tj-hero-layout">
            <div class="tj-hero-copy">
                <h1 data-tj-reveal>
                    <?php tj_e('Giỏi công nghệ.', 'Build your skills.'); ?>
                    <span><?php tj_e('Chọn đúng cơ hội.', 'Find your opportunity.'); ?></span>
                </h1>
                <p class="lead" data-tj-reveal>
                    <?php tj_e(
                        'Tìm việc IT, quản lý CV và luyện phỏng vấn theo kỹ năng.',
                        'Find IT jobs, manage your CV and practice skill-based interviews.'
                    ); ?>
                </p>
                <div class="tj-hero-cta" data-tj-reveal>
                    <a class="tj-hero-cta-primary" href="#tj-panel-jobs"><?php tj_e('Bắt đầu tìm việc', 'Start your search'); ?></a>
                    <a class="tj-hero-cta-secondary" href="<?php echo esc_url(tj_url('dang-ky')); ?>"><?php tj_e('Tạo hồ sơ', 'Create a profile'); ?></a>
                </div>
            </div>

            <figure class="tj-hero-visual" data-tj-reveal>
                <img src="<?php echo esc_url(TJ_URL . 'assets/images/techjob-hero-candidate-20261103.jpg'); ?>" width="1536" height="1024" alt="<?php echo esc_attr(tj_t('Hai người cùng làm việc với máy tính xách tay trong không gian sáng.', 'Two people working together on a laptop in a bright workspace.')); ?>" fetchpriority="high" decoding="async">
            </figure>

            <div class="tj-hero-tools" data-tj-tabs data-ui="candidate-tools">
                <div class="tj-hero-tabs" role="tablist" aria-label="<?php tj_e('Chọn tính năng', 'Choose a feature'); ?>" data-tj-reveal>
                    <button id="tj-tab-jobs" type="button" role="tab" aria-selected="true" aria-controls="tj-panel-jobs" tabindex="0" data-tj-tab="jobs">
                        <span class="tj-tab-kind"><?php tj_e('Khám phá', 'Explore'); ?></span>
                        <strong><?php tj_e('Tìm việc IT', 'Find IT jobs'); ?></strong>
                        <span class="tj-tab-description"><?php tj_e('Lọc theo chức danh, kỹ năng và địa điểm.', 'Filter by role, skill and location.'); ?></span>
                    </button>
                    <button id="tj-tab-cv" type="button" role="tab" aria-selected="false" aria-controls="tj-panel-cv" tabindex="-1" data-tj-tab="cv">
                        <span class="tj-tab-kind"><?php tj_e('Chuẩn bị', 'Prepare'); ?></span>
                        <strong><?php tj_e('Hồ sơ & CV', 'Profile & CV'); ?></strong>
                        <span class="tj-tab-description"><?php tj_e('Cập nhật thông tin và quản lý CV.', 'Update your details and manage your CV.'); ?></span>
                    </button>
                    <button id="tj-tab-interview" type="button" role="tab" aria-selected="false" aria-controls="tj-panel-interview" tabindex="-1" data-tj-tab="interview">
                        <span class="tj-tab-kind"><?php tj_e('Thực hành', 'Practice'); ?></span>
                        <strong><?php tj_e('Phỏng vấn AI', 'AI interview'); ?></strong>
                        <span class="tj-tab-description"><?php tj_e('Thử 5 câu hỏi theo kỹ năng và cấp độ.', 'Try five questions by skill and level.'); ?></span>
                    </button>
                </div>

                <section id="tj-panel-jobs" class="tj-hero-tab-panel" role="tabpanel" aria-labelledby="tj-tab-jobs" data-tj-panel="jobs">
                    <form class="hero-search-box-wrap tj-hero-search" action="<?php echo esc_url(home_url('/viec-lam/')); ?>" method="get" data-tj-reveal>
                        <input type="hidden" name="lang" value="<?php echo esc_attr(tj_lang()); ?>">
                        <div class="tj-hero-search-field">
                            <label for="tj-hero-keyword"><?php tj_e('Công việc bạn tìm kiếm', 'Your next role'); ?></label>
                            <input id="tj-hero-keyword" name="q" value="<?php echo esc_attr($filter['q'] ?? ''); ?>" placeholder="<?php tj_e('Chức danh, kỹ năng hoặc công ty', 'Title, skill or company'); ?>">
                        </div>
                        <div class="tj-hero-search-field tj-hero-location">
                            <label for="tj-hero-location"><?php tj_e('Nơi bạn muốn làm việc', 'Where you want to work'); ?></label>
                            <select id="tj-hero-location" name="location">
                                <?php foreach (tj_term_options('location') as $value => $label) { ?>
                                    <option value="<?php echo esc_attr($value); ?>" <?php selected((string)($filter['location'] ?? ''), (string)$value); ?>>
                                        <?php echo esc_html($value === '' ? tj_t('Tất cả địa điểm', 'All locations') : $label); ?>
                                    </option>
                                <?php } ?>
                            </select>
                        </div>
                        <button class="search-btn-green" type="submit"><?php tj_e('Tìm việc ngay', 'Find jobs'); ?></button>
                    </form>

                    <div class="tj-quick-search" data-tj-reveal>
                        <span><?php tj_e('Kỹ năng phổ biến', 'Popular skills'); ?></span>
                        <?php foreach (['Java', 'Python', 'React', 'DevOps', 'SQL'] as $keyword) { ?>
                            <a href="<?php echo esc_url(tj_url('viec-lam', ['q' => $keyword])); ?>" aria-label="<?php echo esc_attr(sprintf(tj_t('Tìm việc làm %s', 'Search %s jobs'), $keyword)); ?>"><?php echo esc_html($keyword); ?></a>
                        <?php } ?>
                    </div>
                </section>

                <section id="tj-panel-cv" class="tj-hero-tab-panel" role="tabpanel" aria-labelledby="tj-tab-cv" data-tj-panel="cv" hidden>
                    <div class="tj-hero-action-panel">
                        <div>
                            <strong><?php tj_e('Hồ sơ nghề nghiệp của bạn', 'Your career profile'); ?></strong>
                            <p><?php tj_e('Cập nhật thông tin và quản lý CV trong không gian ứng viên.', 'Update your details and manage your CV in your candidate workspace.'); ?></p>
                        </div>
                        <a class="tj-hero-panel-cta" href="<?php echo esc_url(tj_url('tai-khoan/ho-so')); ?>"><?php tj_e('Quản lý hồ sơ', 'Manage profile'); ?></a>
                    </div>
                </section>

                <section id="tj-panel-interview" class="tj-hero-tab-panel" role="tabpanel" aria-labelledby="tj-tab-interview" data-tj-panel="interview" hidden>
                    <div class="tj-hero-action-panel">
                        <div>
                            <strong><?php tj_e('Luyện phỏng vấn theo kỹ năng', 'Practice for your next interview'); ?></strong>
                            <p><?php tj_e('Thử một phiên gồm 5 câu hỏi theo kỹ năng và cấp độ của bạn.', 'Start a five-question session matched to your skill and level.'); ?></p>
                        </div>
                        <button class="tj-hero-panel-cta" type="button" data-tj-practice><?php tj_e('Bắt đầu luyện', 'Start practice'); ?></button>
                    </div>
                </section>
            </div>
        </div>
    </div>
</section>

<?php if ($route === '') { ?>
    <section class="tracking-card tj-container">
        <div>
            <h3 data-tj-reveal><?php tj_e('Hành trình ứng tuyển của bạn', 'Your application journey'); ?></h3>
            <p data-tj-reveal><?php tj_e('Theo dõi hồ sơ, nhận lịch phỏng vấn và chuẩn bị cho cơ hội tiếp theo.', 'Track applications, receive interview invitations and prepare for your next opportunity.'); ?></p>
        </div>
        <a class="tracking-btn" data-tj-reveal href="<?php echo esc_url(tj_url('tai-khoan/ung-tuyen')); ?>"><?php tj_e('Xem hồ sơ của tôi', 'My applications'); ?></a>
    </section>
<?php } ?>

<div class="job-board-layout-container">
    <div class="job-board-grid">
        <aside class="filter-sidebar">
            <div class="filter-sidebar-header" data-tj-reveal>
                <h3><?php tj_e('Bộ lọc tìm kiếm', 'Search filters'); ?></h3>
                <a href="<?php echo esc_url(tj_url('viec-lam')); ?>"><?php tj_e('Xóa lọc', 'Clear'); ?></a>
            </div>
            <form method="get" action="<?php echo esc_url(home_url('/viec-lam/')); ?>" class="tj-stack" data-ui="job-filters" data-tj-reveal>
                <?php tj_hidden('lang', tj_lang()); ?>
                <?php tj_hidden('q', $filter['q'] ?? ''); ?>
                <?php foreach (['skill' => tj_t('Kỹ năng', 'Skill'), 'category' => tj_t('Nhóm nghề', 'Category'), 'location' => tj_t('Địa điểm', 'Location')] as $tax => $label) {
                    tj_select($tax, $label, tj_term_options($tax), $filter[$tax] ?? '');
                } ?>
                <?php tj_select('job_type', tj_t('Loại hình', 'Job type'), ['' => tj_t('Tất cả', 'All'), 'full-time' => tj_t('Toàn thời gian', 'Full-time'), 'part-time' => tj_t('Bán thời gian', 'Part-time'), 'internship' => tj_t('Thực tập', 'Internship'), 'remote' => 'Remote'], $filter['job_type'] ?? ''); ?>
                <?php tj_input('min_salary', tj_t('Lương từ (VND/tháng)', 'Minimum (VND/month)'), $filter['min_salary'] ?? '', 'number'); ?>
                <?php tj_input('max_salary', tj_t('Đến (VND/tháng)', 'Maximum (VND/month)'), $filter['max_salary'] ?? '', 'number'); ?>
                <?php tj_button('Áp dụng bộ lọc', 'Apply filters'); ?>
            </form>
        </aside>

        <section class="jobs-list-column">
            <div class="jobs-list-header">
                <h2 data-tj-reveal><?php echo esc_html(tj_t('Cơ hội việc làm IT', 'IT job opportunities')); ?></h2>
                <span class="tj-muted" data-tj-reveal><?php echo esc_html($jobs['total']); ?> <?php tj_e('vị trí tuyển dụng', 'open positions'); ?></span>
            </div>
            <div class="tj-stack">
                <?php if (!$jobs['posts']) {
                    tj_empty(tj_t('Không có việc làm phù hợp. Hãy thử bộ lọc khác.', 'No matching jobs. Try other filters.'));
                }
                foreach ($jobs['posts'] as $job) {
                    tj_job_card($job);
                } ?>
            </div>
            <?php tj_pagination((int)$jobs['pages']); ?>
        </section>
    </div>
</div>

<?php if ($route === '') { $featured_companies = get_posts(['post_type' => 'tj_company', 'numberposts' => 3, 'meta_key' => 'tj_featured', 'meta_value' => '1']); ?>
    <section id="companies" class="tj-container tj-company-section<?php echo $featured_companies ? '' : ' tj-company-section-empty'; ?>">
        <div class="tj-company-intro" data-tj-reveal>
            <span class="tj-eyebrow"><?php tj_e('Nơi bạn thuộc về', 'A place to belong'); ?></span>
            <h2><?php tj_e('Khám phá công ty hàng đầu', 'Explore leading companies'); ?></h2>
            <p><?php tj_e('Tìm hiểu văn hóa và cơ hội nghề nghiệp từ các doanh nghiệp uy tín.', 'Discover workplace culture and career opportunities from trusted employers.'); ?></p>
            <a class="tj-company-discover" href="<?php echo esc_url(tj_url('cong-ty')); ?>"><?php tj_e('Khám phá công ty', 'Explore companies'); ?> <span aria-hidden="true">→</span></a>
        </div>
        <div class="tj-company-grid">
            <?php if (!$featured_companies) { ?>
                <div class="tj-company-empty-card" data-tj-reveal>
                    <span class="tj-company-empty-mark" aria-hidden="true">✳</span>
                    <strong><?php tj_e('Công ty nổi bật đang được cập nhật', 'Featured companies are being updated'); ?></strong>
                    <span><?php tj_e('Danh sách sẽ hiển thị tại đây khi có hồ sơ công ty được công khai.', 'Company profiles will appear here when they are published.'); ?></span>
                </div>
            <?php } ?>
            <?php foreach ($featured_companies as $company) {
                $company_jobs = get_posts(['post_type' => 'tj_job', 'post_status' => 'publish', 'numberposts' => -1, 'meta_key' => 'tj_company_id', 'meta_value' => $company->ID]); ?>
                <a class="tj-panel tj-company-card" data-tj-reveal href="<?php echo esc_url(tj_url('cong-ty', ['company' => $company->ID])); ?>">
                    <span class="tj-company-mark" aria-hidden="true"><?php echo esc_html(mb_substr(tj_title($company->ID), 0, 1)); ?></span>
                    <strong><?php echo esc_html(tj_title($company->ID)); ?></strong>
                    <span class="tj-company-job-count"><?php echo esc_html(count($company_jobs)); ?> <?php tj_e('việc làm đang tuyển', 'open jobs'); ?></span>
                    <span class="tj-company-card-arrow" aria-hidden="true">↗</span>
                </a>
            <?php } ?>
        </div>
    </section>
<?php } ?>

<section class="tj-container tj-practice-promo<?php echo $route === '' ? '' : ' tj-practice-promo-compact'; ?>">
    <div class="tj-practice-copy">
        <h2 data-tj-reveal><?php tj_e('Cơ hội tốt bắt đầu từ sự chuẩn bị.', 'Your next opportunity starts with preparation.'); ?></h2>
        <p data-tj-reveal><?php tj_e('Luyện trả lời 5 câu hỏi theo kỹ năng và cấp độ. Chế độ demo có nhãn rõ ràng khi chưa cấu hình Gemini.', 'Practice five questions by skill and level. Demo mode is clearly labeled when Gemini is not configured.'); ?></p>
        <button class="tj-button" type="button" data-tj-practice data-tj-reveal><?php tj_e('Bắt đầu luyện phỏng vấn', 'Start interview practice'); ?></button>
    </div>
    <?php if ($route === '') { ?>
    <div class="tj-practice-gallery" role="list" aria-label="<?php tj_e('Ảnh về môi trường làm việc IT', 'Images of IT work environments'); ?>">
        <figure role="listitem" data-tj-reveal>
            <img src="<?php echo esc_url(TJ_URL . 'assets/images/tasteskill-world-20261103/coding-programming.jpg'); ?>" width="960" height="640" alt="<?php echo esc_attr(tj_t('Mã nguồn hiển thị trên laptop', 'Source code displayed on a laptop')); ?>" loading="lazy" decoding="async">
            <figcaption><span><?php tj_e('Mã nguồn trên laptop', 'Source code on a laptop'); ?></span></figcaption>
        </figure>
        <figure role="listitem" data-tj-reveal>
            <img src="<?php echo esc_url(TJ_URL . 'assets/images/tasteskill-world-20261103/programmer-at-work-unsplash.jpg'); ?>" width="1920" height="1391" alt="<?php echo esc_attr(tj_t('Lập trình viên làm việc với laptop', 'A programmer working at a laptop')); ?>" loading="lazy" decoding="async">
            <figcaption><span><?php tj_e('Lập trình viên tại nơi làm việc', 'A programmer at work'); ?></span></figcaption>
        </figure>
        <figure role="listitem" data-tj-reveal>
            <img src="<?php echo esc_url(TJ_URL . 'assets/images/tasteskill-world-20261103/software-developer-at-work-03.jpg'); ?>" width="1920" height="1440" alt="<?php echo esc_attr(tj_t('Lập trình viên làm việc tại văn phòng', 'A software developer working in an office')); ?>" loading="lazy" decoding="async">
            <figcaption><span><?php tj_e('Làm việc với mã nguồn', 'Working with source code'); ?></span></figcaption>
        </figure>
        <figure role="listitem" data-tj-reveal>
            <img src="<?php echo esc_url(TJ_URL . 'assets/images/tasteskill-world-20261103/programmers.jpg'); ?>" width="1920" height="1280" alt="<?php echo esc_attr(tj_t('Các lập trình viên cùng xem mã nguồn', 'Programmers reviewing source code together')); ?>" loading="lazy" decoding="async">
            <figcaption><span><?php tj_e('Trao đổi trên cùng màn hình', 'Reviewing code together'); ?></span></figcaption>
        </figure>
    </div>
    <?php } ?>
</section>
