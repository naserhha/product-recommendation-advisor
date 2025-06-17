<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Add admin menu
function perfume_advisor_admin_menu() {
    add_menu_page(
        __('تنظیمات مشاور عطر', 'perfume-advisor'),
        __('مشاور عطر', 'perfume-advisor'),
        'manage_options',
        'perfume-advisor-settings',
        'perfume_advisor_settings_page',
        'dashicons-perfume',
        30
    );
}
add_action('admin_menu', 'perfume_advisor_admin_menu');

// Register settings
function perfume_advisor_register_settings() {
    register_setting('perfume_advisor_settings', 'perfume_advisor_currency', array(
        'default' => 'toman',
        'sanitize_callback' => 'sanitize_text_field'
    ));
    
    register_setting('perfume_advisor_settings', 'perfume_advisor_number_format', array(
        'default' => 'persian',
        'sanitize_callback' => 'sanitize_text_field'
    ));
    
    register_setting('perfume_advisor_settings', 'perfume_advisor_smoker_products', array(
        'default' => 'yes',
        'sanitize_callback' => 'sanitize_text_field'
    ));
    
    register_setting('perfume_advisor_settings', 'perfume_advisor_use_attributes', array(
        'default' => 'yes',
        'sanitize_callback' => 'sanitize_text_field'
    ));
}
add_action('admin_init', 'perfume_advisor_register_settings');

// Settings page content
function perfume_advisor_settings_page() {
    if (!current_user_can('manage_options')) {
        return;
    }
    ?>
    <div class="wrap" dir="rtl">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        
        <form action="options.php" method="post">
            <?php
            settings_fields('perfume_advisor_settings');
            do_settings_sections('perfume_advisor_settings');
            ?>
            
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="perfume_advisor_currency"><?php _e('واحد پول', 'perfume-advisor'); ?></label>
                    </th>
                    <td>
                        <select name="perfume_advisor_currency" id="perfume_advisor_currency">
                            <option value="toman" <?php selected(get_option('perfume_advisor_currency'), 'toman'); ?>>
                                <?php _e('تومان', 'perfume-advisor'); ?>
                            </option>
                            <option value="rial" <?php selected(get_option('perfume_advisor_currency'), 'rial'); ?>>
                                <?php _e('ریال', 'perfume-advisor'); ?>
                            </option>
                        </select>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="perfume_advisor_number_format"><?php _e('فرمت اعداد', 'perfume-advisor'); ?></label>
                    </th>
                    <td>
                        <select name="perfume_advisor_number_format" id="perfume_advisor_number_format">
                            <option value="persian" <?php selected(get_option('perfume_advisor_number_format'), 'persian'); ?>>
                                <?php _e('فارسی', 'perfume-advisor'); ?>
                            </option>
                            <option value="english" <?php selected(get_option('perfume_advisor_number_format'), 'english'); ?>>
                                <?php _e('انگلیسی', 'perfume-advisor'); ?>
                            </option>
                        </select>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="perfume_advisor_smoker_products"><?php _e('نمایش عطرهای مخصوص سیگاری‌ها', 'perfume-advisor'); ?></label>
                    </th>
                    <td>
                        <select name="perfume_advisor_smoker_products" id="perfume_advisor_smoker_products">
                            <option value="yes" <?php selected(get_option('perfume_advisor_smoker_products'), 'yes'); ?>>
                                <?php _e('بله', 'perfume-advisor'); ?>
                            </option>
                            <option value="no" <?php selected(get_option('perfume_advisor_smoker_products'), 'no'); ?>>
                                <?php _e('خیر', 'perfume-advisor'); ?>
                            </option>
                        </select>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="perfume_advisor_use_attributes"><?php _e('استفاده از ویژگی‌های ووکامرس', 'perfume-advisor'); ?></label>
                    </th>
                    <td>
                        <select name="perfume_advisor_use_attributes" id="perfume_advisor_use_attributes">
                            <option value="yes" <?php selected(get_option('perfume_advisor_use_attributes'), 'yes'); ?>>
                                <?php _e('بله', 'perfume-advisor'); ?>
                            </option>
                            <option value="no" <?php selected(get_option('perfume_advisor_use_attributes'), 'no'); ?>>
                                <?php _e('خیر', 'perfume-advisor'); ?>
                            </option>
                        </select>
                        <p class="description">
                            <?php _e('اگر فعال باشد، از ویژگی‌های ووکامرس برای دسته‌بندی عطرها استفاده می‌شود.', 'perfume-advisor'); ?>
                        </p>
                    </td>
                </tr>
            </table>
            
            <?php submit_button(__('ذخیره تنظیمات', 'perfume-advisor')); ?>
        </form>
    </div>
    <?php
} 