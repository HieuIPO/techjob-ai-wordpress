<?php defined('ABSPATH') || exit; ?>
<div class="tj-stack tj-catalog">
    <p class="tj-muted"><?php tj_e('Tên tiếng Anh là tùy chọn. Nếu để trống, giao diện English sẽ dùng tên tiếng Việt.', 'English names are optional. Empty translations fall back to the Vietnamese name.'); ?></p>
    <?php foreach (['skill' => tj_t('Kỹ năng công nghệ', 'Technology skills'), 'category' => tj_t('Nhóm nghề', 'Categories'), 'position' => tj_t('Vị trí', 'Positions'), 'location' => tj_t('Địa điểm', 'Locations')] as $tax => $label) { ?>
        <section class="tj-panel">
            <h2><?php echo esc_html($label); ?></h2>
            <?php tj_form_start('save_term', 'tj-catalog-form'); tj_hidden('taxonomy', 'tj_' . $tax);
            tj_input('name', tj_t('Tên (tiếng Việt)', 'Name (Vietnamese)'), '', 'text', true);
            tj_input('name_en', tj_t('Tên (tiếng Anh)', 'Name (English)'));
            tj_button('Thêm mới', 'Add'); ?></form>
            <?php $terms = get_terms(['taxonomy' => 'tj_' . $tax, 'hide_empty' => false]); ?>
            <div class="tj-catalog-list">
                <?php if (!$terms || is_wp_error($terms)) { tj_empty(tj_t('Chưa có danh mục.', 'No entries yet.')); }
                foreach (is_wp_error($terms) ? [] : $terms as $term) { ?>
                    <div class="tj-catalog-entry">
                        <?php tj_form_start('save_term', 'tj-catalog-form'); tj_hidden('taxonomy', 'tj_' . $tax); tj_hidden('id', $term->term_id);
                        tj_input('name', tj_t('Tên (tiếng Việt)', 'Name (Vietnamese)'), $term->name, 'text', true);
                        tj_input('name_en', tj_t('Tên (tiếng Anh)', 'Name (English)'), get_term_meta($term->term_id, 'tj_name_en', true));
                        tj_button('Lưu', 'Save', 'secondary'); ?></form>
                        <div class="tj-row tj-between">
                            <span class="tj-muted"><?php echo (int)$term->count; ?> <?php tj_e('tin tuyển dụng', 'jobs'); ?></span>
                            <?php tj_form_start('delete_term', 'tj-inline tj-confirm'); tj_hidden('id', $term->term_id); tj_hidden('taxonomy', 'tj_' . $tax); tj_button('Xóa', 'Delete', 'danger'); ?></form>
                        </div>
                    </div>
                <?php } ?>
            </div>
        </section>
    <?php } ?>
</div>
