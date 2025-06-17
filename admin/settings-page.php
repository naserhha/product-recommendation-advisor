<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Include common functions
require_once plugin_dir_path(__FILE__) . '../includes/common-functions.php';

// Add admin menu
function perfume_advisor_admin_menu() {
    add_menu_page(
        __('مشاور عطر', 'perfume-advisor'),
        __('مشاور عطر', 'perfume-advisor'),
        'manage_options',
        'perfume-advisor',
        'perfume_advisor_main_page',
        'dashicons-smiley',
        30
    );
    
    add_submenu_page(
        'perfume-advisor',
        __('سوالات', 'perfume-advisor'),
        __('سوالات', 'perfume-advisor'),
        'manage_options',
        'perfume-advisor-questions',
        'perfume_advisor_questions_page'
    );
    
    add_submenu_page(
        'perfume-advisor',
        __('مچ کردن محصولات با پاسخ‌ها', 'perfume-advisor'),
        __('مچینگ', 'perfume-advisor'),
        'manage_options',
        'perfume-advisor-matching',
        'perfume_advisor_matching_page'
    );
    
    add_submenu_page(
        'perfume-advisor',
        __('تنظیمات', 'perfume-advisor'),
        __('تنظیمات', 'perfume-advisor'),
        'manage_options',
        'perfume-advisor-settings',
        'perfume_advisor_settings_page'
    );
    
    add_submenu_page(
        'perfume-advisor',
        __('راهنما و مستندات', 'perfume-advisor'),
        __('راهنما', 'perfume-advisor'),
        'manage_options',
        'perfume-advisor-help',
        'perfume_advisor_help_page'
    );
}
add_action('admin_menu', 'perfume_advisor_admin_menu');

// Register settings
function perfume_advisor_register_settings() {
    register_setting('perfume_advisor_settings', 'perfume_advisor_currency');
    register_setting('perfume_advisor_settings', 'perfume_advisor_number_format');
    register_setting('perfume_advisor_settings', 'perfume_advisor_save_responses');
}
add_action('admin_init', 'perfume_advisor_register_settings');

// AJAX handler for getting attribute terms
function perfume_advisor_ajax_get_attribute_terms() {
    check_ajax_referer('perfume_advisor_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => __('شما دسترسی لازم برای این عملیات را ندارید.', 'perfume-advisor')));
    }
    
    $attribute_name = isset($_POST['attribute']) ? sanitize_text_field($_POST['attribute']) : '';
    
    if (empty($attribute_name)) {
        wp_send_json_error(array('message' => __('نام ویژگی مورد نیاز است.', 'perfume-advisor')));
    }
    
    $terms = get_terms(array(
        'taxonomy' => 'pa_' . $attribute_name,
        'hide_empty' => false
    ));
    
    if (is_wp_error($terms)) {
        wp_send_json_error(array('message' => __('خطا در دریافت زیرویژگی‌ها.', 'perfume-advisor')));
    }
    
    $terms_data = array();
    foreach ($terms as $term) {
        $terms_data[] = array(
            'id' => $term->term_id,
            'text' => $term->name
        );
    }
    
    wp_send_json_success($terms_data);
}
add_action('wp_ajax_perfume_advisor_get_attribute_terms', 'perfume_advisor_ajax_get_attribute_terms');

// Main admin page
function perfume_advisor_main_page() {
    ?>
    <div class="wrap" dir="rtl">
        <h1><?php _e('مشاور عطر', 'perfume-advisor'); ?></h1>
        
        <div class="perfume-advisor-dashboard">
            <div class="dashboard-section">
                <h2><?php _e('به مشاور عطر خوش آمدید', 'perfume-advisor'); ?></h2>
                <p><?php _e('با استفاده از این افزونه می‌توانید یک سیستم پیشنهاد عطر شخصی‌سازی شده برای فروشگاه ووکامرس خود ایجاد کنید.', 'perfume-advisor'); ?></p>
                
                <div class="quick-links">
                    <a href="<?php echo admin_url('admin.php?page=perfume-advisor-questions'); ?>" class="button button-primary">
                        <?php _e('مدیریت سوالات', 'perfume-advisor'); ?>
                    </a>
                    <a href="<?php echo admin_url('admin.php?page=perfume-advisor-questions&tab=add_new'); ?>" class="button button-secondary">
                        <?php _e('افزودن سوال جدید', 'perfume-advisor'); ?>
                    </a>
                    <a href="<?php echo admin_url('admin.php?page=perfume-advisor-settings'); ?>" class="button">
                        <?php _e('تنظیمات', 'perfume-advisor'); ?>
                    </a>
                    <a href="<?php echo admin_url('admin.php?page=perfume-advisor-help'); ?>" class="button">
                        <?php _e('راهنما و مستندات', 'perfume-advisor'); ?>
                    </a>
                </div>
            </div>
            
            <div class="dashboard-section">
                <h2><?php _e('آمار کلی', 'perfume-advisor'); ?></h2>
                <?php
                $questions = get_option('perfume_advisor_questions', array());
                $total_questions = count($questions);
                $total_products = 0;
                
                foreach ($questions as $question) {
                    if (isset($question['products'])) {
                        $total_products += count($question['products']);
                    }
                }
                ?>
                <div class="stats-grid">
                    <div class="stat-box">
                        <span class="stat-number"><?php echo $total_questions; ?></span>
                        <span class="stat-label"><?php _e('تعداد کل سوالات', 'perfume-advisor'); ?></span>
                    </div>
                    <div class="stat-box">
                        <span class="stat-number"><?php echo $total_products; ?></span>
                        <span class="stat-label"><?php _e('محصولات مرتبط', 'perfume-advisor'); ?></span>
                    </div>
                </div>
            </div>
            
            <div class="dashboard-section">
                <h2><?php _e('شروع کار', 'perfume-advisor'); ?></h2>
                <div class="getting-started-steps">
                    <div class="step">
                        <span class="step-number">1</span>
                        <h3><?php _e('ایجاد سوالات', 'perfume-advisor'); ?></h3>
                        <p><?php _e('سوالاتی اضافه کنید تا به کاربران در یافتن عطر مناسب کمک کنید.', 'perfume-advisor'); ?></p>
                    </div>
                    <div class="step">
                        <span class="step-number">2</span>
                        <h3><?php _e('تنظیمات', 'perfume-advisor'); ?></h3>
                        <p><?php _e('واحد پول، فرمت اعداد و سایر تنظیمات را پیکربندی کنید.', 'perfume-advisor'); ?></p>
                    </div>
                    <div class="step">
                        <span class="step-number">3</span>
                        <h3><?php _e('افزودن به سایت', 'perfume-advisor'); ?></h3>
                        <p><?php _e('از شورت‌کد [perfume_advisor_form] برای نمایش فرم استفاده کنید.', 'perfume-advisor'); ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php
}

// Questions management page
function perfume_advisor_questions_page() {
    // Get all questions
    $questions = get_option('perfume_advisor_questions', array());
    
    // Get all WooCommerce attributes
    $attributes = wc_get_attribute_taxonomies();
    
    ?>
    <div class="wrap" dir="rtl">
        <h1><?php _e('مدیریت سوالات', 'perfume-advisor'); ?></h1>
        
        <div class="perfume-advisor-questions-form-wrap">
            <h2><?php _e('افزودن سوال جدید', 'perfume-advisor'); ?></h2>
            <form method="post" action="" class="perfume-advisor-questions-form">
                <?php wp_nonce_field('perfume_advisor_add_question'); ?>
                <input type="hidden" name="action" value="add_question">
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="question_text"><?php _e('متن سوال:', 'perfume-advisor'); ?></label>
                        </th>
                        <td>
                            <input type="text" name="question_text" id="question_text" class="regular-text" required>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="question_attribute"><?php _e('ویژگی:', 'perfume-advisor'); ?></label>
                        </th>
                        <td>
                            <select name="question_attribute" id="question_attribute" class="regular-text" required>
                                <option value=""><?php _e('انتخاب ویژگی...', 'perfume-advisor'); ?></option>
                                <?php foreach ($attributes as $attribute) : ?>
                                    <option value="<?php echo esc_attr($attribute->attribute_name); ?>"><?php echo esc_html($attribute->attribute_label); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="question_terms"><?php _e('زیرویژگی‌ها:', 'perfume-advisor'); ?></label>
                        </th>
                        <td>
                            <select name="question_terms[]" id="question_terms" class="regular-text" multiple="multiple" style="width: 100%;">
                                <option value=""><?php _e('ابتدا یک ویژگی انتخاب کنید...', 'perfume-advisor'); ?></option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="question_required"><?php _e('اجباری:', 'perfume-advisor'); ?></label>
                        </th>
                        <td>
                            <label>
                                <input type="checkbox" name="question_required" id="question_required" value="1">
                                <?php _e('این سوال اجباری است', 'perfume-advisor'); ?>
                            </label>
                        </td>
                    </tr>
                </table>
                
                <p class="submit">
                    <input type="submit" name="submit" id="submit" class="button button-primary" value="<?php _e('افزودن سوال', 'perfume-advisor'); ?>">
                </p>
            </form>
        </div>
        
        <h2 class="title"><?php _e('سوالات موجود', 'perfume-advisor'); ?></h2>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php _e('متن سوال', 'perfume-advisor'); ?></th>
                    <th><?php _e('ویژگی', 'perfume-advisor'); ?></th>
                    <th><?php _e('زیرویژگی‌ها', 'perfume-advisor'); ?></th>
                    <th><?php _e('اجباری', 'perfume-advisor'); ?></th>
                    <th><?php _e('عملیات', 'perfume-advisor'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($questions)) : ?>
                    <tr>
                        <td colspan="5"><?php _e('هیچ سوالی تعریف نشده است.', 'perfume-advisor'); ?></td>
                    </tr>
                <?php else : ?>
                    <?php foreach ($questions as $question) : ?>
                        <tr>
                            <td><?php echo esc_html($question['text']); ?></td>
                            <td>
                                <?php
                                foreach ($attributes as $attribute) {
                                    if ($attribute->attribute_name === $question['attribute']) {
                                        echo esc_html($attribute->attribute_label);
                                        break;
                                    }
                                }
                                ?>
                            </td>
                            <td>
                                <?php
                                if (isset($question['terms']) && !empty($question['terms'])) {
                                    echo esc_html(implode(', ', $question['terms']));
                                } else {
                                    _e('بدون زیرویژگی', 'perfume-advisor');
                                }
                                ?>
                            </td>
                            <td>
                                <?php echo isset($question['required']) && $question['required'] ? __('بله', 'perfume-advisor') : __('خیر', 'perfume-advisor'); ?>
                            </td>
                            <td>
                                <a href="#" class="edit-question" data-question-id="<?php echo esc_attr($question['id']); ?>"><?php _e('ویرایش', 'perfume-advisor'); ?></a> |
                                <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=perfume-advisor-questions&action=delete&id=' . $question['id']), 'delete_question_' . $question['id']); ?>" class="delete-question" onclick="return confirm('<?php _e('آیا از حذف این سوال اطمینان دارید؟', 'perfume-advisor'); ?>');"><?php _e('حذف', 'perfume-advisor'); ?></a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php
}

// Handle question form submission
function perfume_advisor_handle_question_form() {
    if (!isset($_POST['action']) || $_POST['action'] !== 'add_question') {
        return;
    }

    check_admin_referer('perfume_advisor_add_question');
    
    $question_text = sanitize_text_field($_POST['question_text']);
    $question_attribute = sanitize_text_field($_POST['question_attribute']);
    $question_terms = isset($_POST['question_terms']) ? array_map('sanitize_text_field', (array)$_POST['question_terms']) : array();
    $question_required = isset($_POST['question_required']) ? true : false;
    
    // Get existing questions
    $questions = get_option('perfume_advisor_questions', array());
    
    // Generate unique ID for the question
    $question_id = uniqid('q_');
    
    // Add new question
    $questions[] = array(
        'id' => $question_id,
        'text' => $question_text,
        'attribute' => $question_attribute,
        'terms' => $question_terms,
        'required' => $question_required
    );
    
    // Save questions
    update_option('perfume_advisor_questions', $questions);
    
    add_settings_error(
        'perfume_advisor_messages',
        'perfume_advisor_message',
        __('سوال با موفقیت اضافه شد.', 'perfume-advisor'),
        'updated'
    );
}
add_action('admin_init', 'perfume_advisor_handle_question_form');

// Settings page
function perfume_advisor_settings_page() {
    ?>
    <div class="wrap" dir="rtl">
        <h1><?php _e('تنظیمات مشاور عطر', 'perfume-advisor'); ?></h1>
        
        <form method="post" action="options.php">
            <?php
            settings_fields('perfume_advisor_settings');
            do_settings_sections('perfume_advisor_settings');
            ?>
            
            <table class="form-table">
                <tr>
                    <th scope="row"><?php _e('واحد پول', 'perfume-advisor'); ?></th>
                    <td>
                        <select name="perfume_advisor_currency">
                            <option value="toman" <?php selected(get_option('perfume_advisor_currency'), 'toman'); ?>><?php _e('تومان', 'perfume-advisor'); ?></option>
                            <option value="rial" <?php selected(get_option('perfume_advisor_currency'), 'rial'); ?>><?php _e('ریال', 'perfume-advisor'); ?></option>
                        </select>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><?php _e('فرمت اعداد', 'perfume-advisor'); ?></th>
                    <td>
                        <select name="perfume_advisor_number_format">
                            <option value="persian" <?php selected(get_option('perfume_advisor_number_format'), 'persian'); ?>><?php _e('فارسی', 'perfume-advisor'); ?></option>
                            <option value="english" <?php selected(get_option('perfume_advisor_number_format'), 'english'); ?>><?php _e('انگلیسی', 'perfume-advisor'); ?></option>
                        </select>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><?php _e('ذخیره پاسخ‌های کاربر', 'perfume-advisor'); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="perfume_advisor_save_responses" value="1" <?php checked(get_option('perfume_advisor_save_responses'), '1'); ?>>
                            <?php _e('ذخیره پاسخ‌های کاربران در دیتابیس', 'perfume-advisor'); ?>
                        </label>
                    </td>
                </tr>
            </table>
            
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}

// Help page
function perfume_advisor_help_page() {
    ?>
    <div class="wrap perfume-advisor-help" dir="rtl">
        <h1><?php _e('راهنما و مستندات مشاور عطر', 'perfume-advisor'); ?></h1>
        
        <div class="help-section">
            <h2><?php _e('شروع کار', 'perfume-advisor'); ?></h2>
            <div class="help-content">
                <p><?php _e('به مشاور عطر خوش آمدید! این افزونه به شما کمک می‌کند تا یک سیستم پیشنهاد عطر شخصی‌سازی شده برای فروشگاه ووکامرس خود ایجاد کنید.', 'perfume-advisor'); ?></p>
                
                <h3><?php _e('راهنمای سریع', 'perfume-advisor'); ?></h3>
                <ol>
                    <li><?php _e('به صفحه سوالات بروید و اولین سوال خود را ایجاد کنید', 'perfume-advisor'); ?></li>
                    <li><?php _e('تنظیمات خود را در صفحه تنظیمات پیکربندی کنید', 'perfume-advisor'); ?></li>
                    <li><?php _e('شورت‌کد [perfume_advisor_form] را در هر صفحه یا نوشته اضافه کنید', 'perfume-advisor'); ?></li>
                </ol>
            </div>
        </div>
        
        <div class="help-section">
            <h2><?php _e('مدیریت سوالات', 'perfume-advisor'); ?></h2>
            <div class="help-content">
                <h3><?php _e('ایجاد سوالات', 'perfume-advisor'); ?></h3>
                <p><?php _e('برای ایجاد یک سوال جدید:', 'perfume-advisor'); ?></p>
                <ol>
                    <li><?php _e('روی "افزودن جدید" در صفحه سوالات کلیک کنید', 'perfume-advisor'); ?></li>
                    <li><?php _e('متن سوال خود را وارد کنید', 'perfume-advisor'); ?></li>
                    <li><?php _e('یک ویژگی ووکامرس انتخاب کنید', 'perfume-advisor'); ?></li>
                    <li><?php _e('زیرویژگی‌های مورد نظر خود را به عنوان گزینه انتخاب کنید', 'perfume-advisor'); ?></li>
                    <li><?php _e('اختیاری: محصولات مرتبط را برای هر زیرویژگی انتخاب کنید', 'perfume-advisor'); ?></li>
                </ol>
                
                <h3><?php _e('انواع سوالات', 'perfume-advisor'); ?></h3>
                <ul>
                    <li><strong><?php _e('متنی:', 'perfume-advisor'); ?></strong> <?php _e('برای پاسخ‌های متنی باز', 'perfume-advisor'); ?></li>
                    <li><strong><?php _e('عددی:', 'perfume-advisor'); ?></strong> <?php _e('برای پاسخ‌های عددی', 'perfume-advisor'); ?></li>
                    <li><strong><?php _e('رادیویی:', 'perfume-advisor'); ?></strong> <?php _e('برای انتخاب تک گزینه‌ای', 'perfume-advisor'); ?></li>
                    <li><strong><?php _e('چک‌باکس:', 'perfume-advisor'); ?></strong> <?php _e('برای انتخاب چند گزینه‌ای', 'perfume-advisor'); ?></li>
                    <li><strong><?php _e('انتخاب:', 'perfume-advisor'); ?></strong> <?php _e('برای انتخاب از منوی کشویی', 'perfume-advisor'); ?></li>
                </ul>
            </div>
        </div>
        
        <div class="help-section">
            <h2><?php _e('تنظیمات', 'perfume-advisor'); ?></h2>
            <div class="help-content">
                <h3><?php _e('تنظیمات موجود', 'perfume-advisor'); ?></h3>
                <ul>
                    <li><strong><?php _e('واحد پول:', 'perfume-advisor'); ?></strong> <?php _e('انتخاب بین تومان یا ریال', 'perfume-advisor'); ?></li>
                    <li><strong><?php _e('فرمت اعداد:', 'perfume-advisor'); ?></strong> <?php _e('انتخاب فرمت اعداد فارسی یا انگلیسی', 'perfume-advisor'); ?></li>
                    <li><strong><?php _e('ذخیره پاسخ‌های کاربر:', 'perfume-advisor'); ?></strong> <?php _e('فعال یا غیرفعال کردن ذخیره پاسخ‌های کاربران', 'perfume-advisor'); ?></li>
                </ul>
            </div>
        </div>
        
        <div class="help-section">
            <h2><?php _e('شورت‌کدها', 'perfume-advisor'); ?></h2>
            <div class="help-content">
                <h3><?php _e('شورت‌کدهای موجود', 'perfume-advisor'); ?></h3>
                <ul>
                    <li><code>[perfume_advisor_form]</code> - <?php _e('نمایش فرم مشاور عطر', 'perfume-advisor'); ?></li>
                    <li><code>[perfume_advisor_recommendations]</code> - <?php _e('نمایش پیشنهادات عطر', 'perfume-advisor'); ?></li>
                </ul>
                
                <h3><?php _e('استفاده از شورت‌کدها', 'perfume-advisor'); ?></h3>
                <p><?php _e('برای افزودن مشاور عطر به سایت خود:', 'perfume-advisor'); ?></p>
                <ol>
                    <li><?php _e('یک صفحه یا نوشته جدید ایجاد کنید', 'perfume-advisor'); ?></li>
                    <li><?php _e('شورت‌کد [perfume_advisor_form] را در محل مورد نظر برای نمایش فرم قرار دهید', 'perfume-advisor'); ?></li>
                    <li><?php _e('اختیاری: شورت‌کد [perfume_advisor_recommendations] را در محل مورد نظر برای نمایش پیشنهادات قرار دهید', 'perfume-advisor'); ?></li>
                </ol>
            </div>
        </div>
        
        <div class="help-section">
            <h2><?php _e('یکپارچه‌سازی با ووکامرس', 'perfume-advisor'); ?></h2>
            <div class="help-content">
                <h3><?php _e('ویژگی‌های محصول', 'perfume-advisor'); ?></h3>
                <p><?php _e('این افزونه از ویژگی‌های محصول ووکامرس برای ایجاد سوالات و پیشنهادات استفاده می‌کند. اطمینان حاصل کنید که محصولات شما دارای ویژگی‌های لازم هستند.', 'perfume-advisor'); ?></p>
                
                <h3><?php _e('محصولات مرتبط', 'perfume-advisor'); ?></h3>
                <p><?php _e('می‌توانید محصولات خاصی را برای نمایش با هر گزینه سوال انتخاب کنید. این به کاربران کمک می‌کند تا هنگام پاسخ به سوالات، محصولات مرتبط را ببینند.', 'perfume-advisor'); ?></p>
            </div>
        </div>
        
        <div class="help-section">
            <h2><?php _e('عیب‌یابی', 'perfume-advisor'); ?></h2>
            <div class="help-content">
                <h3><?php _e('مشکلات رایج', 'perfume-advisor'); ?></h3>
                <ul>
                    <li><strong><?php _e('فرم نمایش داده نمی‌شود:', 'perfume-advisor'); ?></strong> <?php _e('اطمینان حاصل کنید که حداقل یک سوال ایجاد کرده‌اید', 'perfume-advisor'); ?></li>
                    <li><strong><?php _e('بدون پیشنهاد:', 'perfume-advisor'); ?></strong> <?php _e('بررسی کنید که محصولات شما دارای ویژگی‌های صحیح هستند', 'perfume-advisor'); ?></li>
                    <li><strong><?php _e('خطاهای AJAX:', 'perfume-advisor'); ?></strong> <?php _e('اطمینان حاصل کنید که سرور شما از AJAX پشتیبانی می‌کند و خطاهای JavaScript را بررسی کنید', 'perfume-advisor'); ?></li>
                </ul>
                
                <h3><?php _e('نیاز به کمک بیشتر؟', 'perfume-advisor'); ?></h3>
                <p><?php _e('اگر به کمک بیشتری نیاز دارید، لطفاً با تیم پشتیبانی ما تماس بگیرید.', 'perfume-advisor'); ?></p>
            </div>
        </div>
    </div>
    <?php
}

// New: Matching products with answers page
function perfume_advisor_matching_page() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'perfume_matching';

    // Handle form submission for adding/editing matches
    if (isset($_POST['action']) && $_POST['action'] === 'add_matching_rule') {
        check_admin_referer('perfume_advisor_add_matching_rule');
        
        $question_key = sanitize_text_field($_POST['question_key']);
        $answer_value = sanitize_text_field($_POST['answer_value']);
        $product_ids = isset($_POST['product_ids']) ? array_map('intval', (array)$_POST['product_ids']) : array();
        
        // Serialize product IDs to store as text
        $product_ids_text = implode(',', $product_ids);

        // Check if a rule for this question_key and answer_value already exists
        $existing_rule = $wpdb->get_row(
            $wpdb->prepare("SELECT id FROM $table_name WHERE question_key = %s AND answer_value = %s", $question_key, $answer_value)
        );

        if ($existing_rule) {
            // Update existing rule
            $wpdb->update(
                $table_name,
                array('product_ids' => $product_ids_text),
                array('id' => $existing_rule->id)
            );
            add_settings_error(
                'perfume_advisor_messages',
                'perfume_advisor_message',
                __('قانون مچینگ با موفقیت به‌روزرسانی شد.', 'perfume-advisor'),
                'updated'
            );
        } else {
            // Insert new rule
            $wpdb->insert(
                $table_name,
                array(
                    'question_key' => $question_key,
                    'answer_value' => $answer_value,
                    'product_ids' => $product_ids_text
                )
            );
            add_settings_error(
                'perfume_advisor_messages',
                'perfume_advisor_message',
                __('قانون مچینگ با موفقیت اضافه شد.', 'perfume-advisor'),
                'updated'
            );
        }
    }

    // Handle deletion
    if (isset($_GET['action']) && $_GET['action'] === 'delete_matching_rule' && isset($_GET['id'])) {
        check_admin_referer('delete_matching_rule_' . $_GET['id']);
        $rule_id = intval($_GET['id']);
        $wpdb->delete($table_name, array('id' => $rule_id));
        add_settings_error(
            'perfume_advisor_messages',
            'perfume_advisor_message',
            __('قانون مچینگ با موفقیت حذف شد.', 'perfume-advisor'),
            'updated'
        );
        // Redirect to clean URL
        wp_redirect(remove_query_arg(array('action', 'id', '_wpnonce')));
        exit;
    }

    // Fetch existing matching rules
    $matching_rules = $wpdb->get_results("SELECT * FROM $table_name ORDER BY question_key, answer_value ASC");

    // Fetch all WooCommerce products for the select box
    $products = wc_get_products(['limit' => -1, 'status' => 'publish']);

    ?>
    <div class="wrap" dir="rtl">
        <h1><?php _e('مچ کردن محصولات با پاسخ‌ها', 'perfume-advisor'); ?></h1>
        <?php settings_errors('perfume_advisor_messages'); ?>

        <div class="perfume-advisor-matching-form-wrap">
            <h2><?php _e('افزودن/ویرایش قانون مچینگ', 'perfume-advisor'); ?></h2>
            <form method="post" action="" class="perfume-advisor-matching-form">
                <?php wp_nonce_field('perfume_advisor_add_matching_rule'); ?>
                <input type="hidden" name="action" value="add_matching_rule">
                <input type="hidden" name="edit_rule_id" value="">

                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="question_key"><?php _e('سوال:', 'perfume-advisor'); ?></label></th>
                        <td>
                            <select name="question_key" id="question_key" class="regular-text" required>
                                <option value=""><?php _e('انتخاب سوال...', 'perfume-advisor'); ?></option>
                                <!-- Populate with custom questions later -->
                                <option value="gender"><?php _e('جنسیت', 'perfume-advisor'); ?></option>
                                <option value="age_group"><?php _e('گروه سنی', 'perfume-advisor'); ?></option>
                                <option value="smoker"><?php _e('آیا سیگاری هستید؟', 'perfume-advisor'); ?></option>
                                <option value="season"><?php _e('فصل', 'perfume-advisor'); ?></option>
                                <option value="occasion"><?php _e('مناسبت', 'perfume-advisor'); ?></option>
                                <option value="fragrance_family"><?php _e('خانواده بویایی', 'perfume-advisor'); ?></option>
                                <option value="longevity"><?php _e('ماندگاری', 'perfume-advisor'); ?></option>
                                <option value="sillage"><?php _e('پراکندگی بو', 'perfume-advisor'); ?></option>
                                <option value="budget"><?php _e('بودجه', 'perfume-advisor'); ?></option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="answer_value"><?php _e('پاسخ:', 'perfume-advisor'); ?></label></th>
                        <td>
                            <input type="text" name="answer_value" id="answer_value" class="regular-text" placeholder="<?php _e('مثلاً: بله، زنانه، گرم', 'perfume-advisor'); ?>" required>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="product_ids"><?php _e('انتخاب محصولات مرتبط:', 'perfume-advisor'); ?></label></th>
                        <td>
                            <select name="product_ids[]" id="product_ids" class="product-selector" multiple="multiple" style="width: 100%;">
                                <?php foreach ($products as $product) : ?>
                                    <option value="<?php echo esc_attr($product->get_id()); ?>"><?php echo esc_html($product->get_name()); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                </table>
                <p class="submit">
                    <input type="submit" name="submit" id="submit" class="button button-primary" value="<?php _e('ذخیره قانون', 'perfume-advisor'); ?>">
                </p>
            </form>
        </div>

        <h2 class="title"><?php _e('قوانین مچینگ موجود', 'perfume-advisor'); ?></h2>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php _e('سوال', 'perfume-advisor'); ?></th>
                    <th><?php _e('پاسخ', 'perfume-advisor'); ?></th>
                    <th><?php _e('محصولات مرتبط', 'perfume-advisor'); ?></th>
                    <th><?php _e('عملیات', 'perfume-advisor'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($matching_rules)) : ?>
                    <tr>
                        <td colspan="4"><?php _e('هیچ قانون مچینگی تعریف نشده است.', 'perfume-advisor'); ?></td>
                    </tr>
                <?php else : ?>
                    <?php foreach ($matching_rules as $rule) : ?>
                        <tr>
                            <td><?php echo esc_html($rule->question_key); ?></td>
                            <td><?php echo esc_html($rule->answer_value); ?></td>
                            <td>
                                <?php
                                $product_ids = explode(',', $rule->product_ids);
                                $product_names = [];
                                foreach ($product_ids as $p_id) {
                                    $product = wc_get_product($p_id);
                                    if ($product) {
                                        $product_names[] = $product->get_name();
                                    }
                                }
                                echo esc_html(implode(', ', $product_names));
                                ?>
                            </td>
                            <td>
                                <a href="#" class="edit-matching-rule" data-rule-id="<?php echo esc_attr($rule->id); ?>" data-question-key="<?php echo esc_attr($rule->question_key); ?>" data-answer-value="<?php echo esc_attr($rule->answer_value); ?>" data-product-ids="<?php echo esc_attr($rule->product_ids); ?>"><?php _e('ویرایش', 'perfume-advisor'); ?></a> |
                                <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=perfume-advisor-matching&action=delete_matching_rule&id=' . $rule->id), 'delete_matching_rule_' . $rule->id); ?>" class="delete-matching-rule" onclick="return confirm('<?php _e('آیا از حذف این قانون اطمینان دارید؟', 'perfume-advisor'); ?>');"><?php _e('حذف', 'perfume-advisor'); ?></a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php
}

// AJAX handler for deleting a question
function perfume_advisor_ajax_delete_question() {
    check_ajax_referer('perfume_advisor_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => __('شما دسترسی لازم برای این عملیات را ندارید.', 'perfume-advisor')));
    }
    
    $question_id = sanitize_text_field($_POST['question_id']);
    $questions = get_option('perfume_advisor_questions', array());
    
    foreach ($questions as $key => $question) {
        if ($question['id'] === $question_id) {
            unset($questions[$key]);
            break;
        }
    }
    
    update_option('perfume_advisor_questions', array_values($questions));
    wp_send_json_success(array('message' => __('سوال با موفقیت حذف شد.', 'perfume-advisor')));
}
add_action('wp_ajax_perfume_advisor_delete_question', 'perfume_advisor_ajax_delete_question');

// AJAX handler for getting question data for editing
function perfume_advisor_ajax_get_question() {
    check_ajax_referer('perfume_advisor_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => __('شما دسترسی لازم برای این عملیات را ندارید.', 'perfume-advisor')));
    }
    
    $question_id = sanitize_text_field($_POST['question_id']);
    $questions = get_option('perfume_advisor_questions', array());
    
    foreach ($questions as $question) {
        if ($question['id'] === $question_id) {
            wp_send_json_success($question);
            return;
        }
    }
    
    wp_send_json_error(array('message' => __('سوال مورد نظر یافت نشد.', 'perfume-advisor')));
}
add_action('wp_ajax_perfume_advisor_get_question', 'perfume_advisor_ajax_get_question');

// AJAX handler for adding a question
function perfume_advisor_ajax_add_question() {
    check_ajax_referer('perfume_advisor_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => __('شما دسترسی لازم برای این عملیات را ندارید.', 'perfume-advisor')));
    }
    
    $questions = get_option('perfume_advisor_questions', array());
    $new_question = array(
        'id' => uniqid('q_'),
        'text' => sanitize_text_field(isset($_POST['question_text']) ? $_POST['question_text'] : ''),
        'required' => isset($_POST['question_required']) ? true : false,
        'attribute' => sanitize_text_field(isset($_POST['question_attribute']) ? $_POST['question_attribute'] : ''),
        'terms' => isset($_POST['question_terms']) ? array_map('sanitize_text_field', (array) $_POST['question_terms']) : array(),
        'products' => isset($_POST['question_products']) ? array_map('intval', (array) $_POST['question_products']) : array()
    );
    
    $questions[] = $new_question;
    update_option('perfume_advisor_questions', $questions);
    
    wp_send_json_success(array(
        'message' => __('سوال با موفقیت اضافه شد.', 'perfume-advisor'),
        'question' => $new_question
    ));
}
add_action('wp_ajax_perfume_advisor_add_question', 'perfume_advisor_ajax_add_question');

// AJAX handler for updating a question
function perfume_advisor_ajax_update_question() {
    check_ajax_referer('perfume_advisor_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => __('شما دسترسی لازم برای این عملیات را ندارید.', 'perfume-advisor')));
    }
    
    $question_id = sanitize_text_field($_POST['question_id']);
    $questions = get_option('perfume_advisor_questions', array());
    $updated = false;
    
    foreach ($questions as $key => $question) {
        if ($question['id'] === $question_id) {
            $questions[$key] = array(
                'id' => $question_id,
                'text' => sanitize_text_field(isset($_POST['question_text']) ? $_POST['question_text'] : ''),
                'required' => isset($_POST['question_required']) ? true : false,
                'attribute' => sanitize_text_field(isset($_POST['question_attribute']) ? $_POST['question_attribute'] : ''),
                'terms' => isset($_POST['question_terms']) ? array_map('sanitize_text_field', (array) $_POST['question_terms']) : array(),
                'products' => isset($_POST['question_products']) ? array_map('intval', (array) $_POST['question_products']) : array()
            );
            $updated = true;
            $updated_question = $questions[$key];
            break;
        }
    }
    
    if ($updated) {
        update_option('perfume_advisor_questions', $questions);
        wp_send_json_success(array(
            'message' => __('سوال با موفقیت به‌روزرسانی شد.', 'perfume-advisor'),
            'question' => $updated_question
        ));
    } else {
        wp_send_json_error(array('message' => __('سوال مورد نظر یافت نشد.', 'perfume-advisor')));
    }
}
add_action('wp_ajax_perfume_advisor_update_question', 'perfume_advisor_ajax_update_question');

// Display questions and their matching products
function perfume_advisor_display_questions_matching() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'perfume_matching';
    
    // Ensure the table exists
    perfume_advisor_ensure_matching_table();
    
    // Get all questions
    $questions = get_option('perfume_advisor_questions', array());
    
    // Get all matching rules
    $matching_rules = array();
    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name) {
        $matching_rules = $wpdb->get_results("SELECT * FROM $table_name ORDER BY question_key, answer_value ASC");
    }
    
    ?>
    <div class="wrap" dir="rtl">
        <h1><?php _e('سوالات و محصولات مرتبط', 'perfume-advisor'); ?></h1>
        
        <?php if (empty($questions)) : ?>
            <div class="notice notice-warning">
                <p><?php _e('هیچ سوالی تعریف نشده است. لطفاً ابتدا سوالات را در بخش تنظیمات تعریف کنید.', 'perfume-advisor'); ?></p>
            </div>
        <?php else : ?>
            <div class="perfume-advisor-questions-matching">
                <?php foreach ($questions as $question) : 
                    // Skip if question doesn't have required fields
                    if (!isset($question['id']) || !isset($question['text'])) {
                        continue;
                    }
                ?>
                    <div class="question-section">
                        <h3><?php echo esc_html($question['text']); ?></h3>
                        
                        <?php
                        // Get matching rules for this question
                        $question_rules = array_filter($matching_rules, function($rule) use ($question) {
                            return $rule->question_key === $question['id'];
                        });
                        
                        if (empty($question_rules)) : ?>
                            <div class="notice notice-info">
                                <p><?php _e('هیچ محصولی برای این سوال تعریف نشده است.', 'perfume-advisor'); ?></p>
                            </div>
                        <?php else : ?>
                            <table class="wp-list-table widefat fixed striped">
                                <thead>
                                    <tr>
                                        <th><?php _e('پاسخ', 'perfume-advisor'); ?></th>
                                        <th><?php _e('محصولات مرتبط', 'perfume-advisor'); ?></th>
                                        <th><?php _e('عملیات', 'perfume-advisor'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($question_rules as $rule) : ?>
                                        <tr>
                                            <td><?php echo esc_html($rule->answer_value); ?></td>
                                            <td>
                                                <?php
                                                $product_ids = explode(',', $rule->product_ids);
                                                $product_names = array();
                                                foreach ($product_ids as $product_id) {
                                                    $product = wc_get_product($product_id);
                                                    if ($product) {
                                                        $product_names[] = $product->get_name();
                                                    }
                                                }
                                                echo esc_html(implode(', ', $product_names));
                                                ?>
                                            </td>
                                            <td>
                                                <a href="<?php echo admin_url('admin.php?page=perfume-advisor-matching&action=edit&id=' . $rule->id); ?>" class="button button-small"><?php _e('ویرایش', 'perfume-advisor'); ?></a>
                                                <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=perfume-advisor-matching&action=delete&id=' . $rule->id), 'delete_matching_rule_' . $rule->id); ?>" class="button button-small button-link-delete" onclick="return confirm('<?php _e('آیا از حذف این قانون اطمینان دارید؟', 'perfume-advisor'); ?>');"><?php _e('حذف', 'perfume-advisor'); ?></a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                        
                        <div class="add-matching-rule">
                            <a href="<?php echo admin_url('admin.php?page=perfume-advisor-matching&question=' . $question['id']); ?>" class="button button-primary"><?php _e('افزودن قانون مچینگ جدید', 'perfume-advisor'); ?></a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    <?php
}

// Add the questions matching page to the admin menu
function perfume_advisor_add_questions_matching_page() {
    add_submenu_page(
        'perfume-advisor',
        __('سوالات و محصولات مرتبط', 'perfume-advisor'),
        __('سوالات و محصولات', 'perfume-advisor'),
        'manage_options',
        'perfume-advisor-questions-matching',
        'perfume_advisor_display_questions_matching'
    );
}
add_action('admin_menu', 'perfume_advisor_add_questions_matching_page');

// Display matching form
function perfume_advisor_display_matching_form() {
    $question_id = isset($_GET['question']) ? sanitize_text_field($_GET['question']) : '';
    $questions = get_option('perfume_advisor_questions', array());
    
    // Find the selected question
    $selected_question = null;
    foreach ($questions as $question) {
        if ($question['id'] === $question_id) {
            $selected_question = $question;
            break;
        }
    }
    
    if (!$selected_question) {
        wp_die(__('سوال مورد نظر یافت نشد.', 'perfume-advisor'));
    }
    
    // Get all products
    $products = wc_get_products(array('limit' => -1));
    
    ?>
    <div class="wrap" dir="rtl">
        <h1><?php echo sprintf(__('افزودن قانون مچینگ برای سوال: %s', 'perfume-advisor'), esc_html($selected_question['text'])); ?></h1>
        
        <form method="post" action="">
            <?php wp_nonce_field('perfume_advisor_add_matching_rule'); ?>
            <input type="hidden" name="action" value="add_matching_rule">
            <input type="hidden" name="question_id" value="<?php echo esc_attr($selected_question['id']); ?>">
            
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="answer_value"><?php _e('پاسخ:', 'perfume-advisor'); ?></label>
                    </th>
                    <td>
                        <?php if (isset($selected_question['terms']) && !empty($selected_question['terms'])) : ?>
                            <select name="answer_value" id="answer_value" class="regular-text" required>
                                <option value=""><?php _e('انتخاب پاسخ...', 'perfume-advisor'); ?></option>
                                <?php foreach ($selected_question['terms'] as $term) : ?>
                                    <option value="<?php echo esc_attr($term); ?>"><?php echo esc_html($term); ?></option>
                                <?php endforeach; ?>
                            </select>
                        <?php else : ?>
                            <input type="text" name="answer_value" id="answer_value" class="regular-text" required>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="product_ids"><?php _e('محصولات مرتبط:', 'perfume-advisor'); ?></label>
                    </th>
                    <td>
                        <select name="product_ids[]" id="product_ids" class="regular-text" multiple="multiple" style="width: 100%;">
                            <?php foreach ($products as $product) : ?>
                                <option value="<?php echo esc_attr($product->get_id()); ?>"><?php echo esc_html($product->get_name()); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
            </table>
            
            <p class="submit">
                <input type="submit" name="submit" id="submit" class="button button-primary" value="<?php _e('ذخیره قانون', 'perfume-advisor'); ?>">
                <a href="<?php echo admin_url('admin.php?page=perfume-advisor-questions-matching'); ?>" class="button"><?php _e('بازگشت', 'perfume-advisor'); ?></a>
            </p>
        </form>
    </div>
    <?php
}

// Handle matching form submission
function perfume_advisor_handle_matching_form() {
    if (!isset($_POST['action']) || $_POST['action'] !== 'add_matching_rule') {
        return;
    }

    check_admin_referer('perfume_advisor_add_matching_rule');
    
    global $wpdb;
    $table_name = $wpdb->prefix . 'perfume_matching';
    
    $question_id = sanitize_text_field($_POST['question_id']);
    $answer_value = sanitize_text_field($_POST['answer_value']);
    $product_ids = isset($_POST['product_ids']) ? array_map('intval', (array)$_POST['product_ids']) : array();
    
    // Validate that the question exists and has the selected term
    $questions = get_option('perfume_advisor_questions', array());
    $question_exists = false;
    $term_valid = false;
    
    foreach ($questions as $question) {
        if ($question['id'] === $question_id) {
            $question_exists = true;
            if (isset($question['terms']) && in_array($answer_value, $question['terms'])) {
                $term_valid = true;
            }
            break;
        }
    }
    
    if (!$question_exists) {
        add_settings_error(
            'perfume_advisor_messages',
            'perfume_advisor_error',
            __('سوال مورد نظر یافت نشد.', 'perfume-advisor'),
            'error'
        );
        return;
    }
    
    if (!$term_valid && isset($question['terms'])) {
        add_settings_error(
            'perfume_advisor_messages',
            'perfume_advisor_error',
            __('پاسخ انتخاب شده معتبر نیست.', 'perfume-advisor'),
            'error'
        );
        return;
    }
    
    // Serialize product IDs
    $product_ids_text = implode(',', $product_ids);
    
    // Check if rule exists
    $existing_rule = $wpdb->get_row($wpdb->prepare(
        "SELECT id FROM $table_name WHERE question_key = %s AND answer_value = %s",
        $question_id,
        $answer_value
    ));
    
    if ($existing_rule) {
        // Update existing rule
        $wpdb->update(
            $table_name,
            array('product_ids' => $product_ids_text),
            array('id' => $existing_rule->id)
        );
        add_settings_error(
            'perfume_advisor_messages',
            'perfume_advisor_message',
            __('قانون مچینگ با موفقیت به‌روزرسانی شد.', 'perfume-advisor'),
            'updated'
        );
    } else {
        // Insert new rule
        $wpdb->insert(
            $table_name,
            array(
                'question_key' => $question_id,
                'answer_value' => $answer_value,
                'product_ids' => $product_ids_text
            )
        );
        add_settings_error(
            'perfume_advisor_messages',
            'perfume_advisor_message',
            __('قانون مچینگ با موفقیت اضافه شد.', 'perfume-advisor'),
            'updated'
        );
    }
}
add_action('admin_init', 'perfume_advisor_handle_matching_form');
