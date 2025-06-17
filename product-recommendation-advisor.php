/**
 * Plugin Name: Product Recommendation Advisor
 * Plugin URI: https://github.com/nasserhaji
 * Description: A powerful WordPress plugin for WooCommerce that helps customers find the perfect products through an interactive questionnaire system. Create custom questions and match them with products to provide personalized recommendations.
 * Version: 1.0.0
 * Author: Mohammad Nasser Haji Hashemabad
 * Author URI: https://github.com/nasserhaji
 * Text Domain: product-recommendation-advisor
 * Domain Path: /languages
 * Requires at least: 5.0
 * Requires PHP: 7.2
 * WC requires at least: 3.0
 * WC tested up to: 8.0
 * Author Email: info@mohammadnasser.com
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('PRODUCT_ADVISOR_VERSION', '1.0.0');
define('PRODUCT_ADVISOR_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('PRODUCT_ADVISOR_PLUGIN_URL', plugin_dir_url(__FILE__));

// Check if WooCommerce is active
function product_advisor_check_woocommerce() {
    if (!class_exists('WooCommerce')) {
        add_action('admin_notices', 'product_advisor_woocommerce_notice');
        return false;
    }
    return true;
}

// WooCommerce not active notice
function product_advisor_woocommerce_notice() {
    ?>
    <div class="error">
        <p><?php _e('Product Recommendation Advisor requires WooCommerce to be installed and active.', 'product-recommendation-advisor'); ?></p>
    </div>
    <?php
}

// Initialize plugin
function product_advisor_init() {
    if (!product_advisor_check_woocommerce()) {
        return;
    }
    
    // Include necessary files
    require_once PRODUCT_ADVISOR_PLUGIN_DIR . 'includes/common-functions.php';
    require_once PRODUCT_ADVISOR_PLUGIN_DIR . 'includes/form-handler.php';
    require_once PRODUCT_ADVISOR_PLUGIN_DIR . 'includes/product-recommender.php';
    require_once PRODUCT_ADVISOR_PLUGIN_DIR . 'admin/settings-page.php';
    
    if (is_admin()) {
        require_once PRODUCT_ADVISOR_PLUGIN_DIR . 'admin/settings-page.php';
    }
    
    // Register activation hook
    register_activation_hook(__FILE__, 'product_advisor_activate');
    
    // Register deactivation hook
    register_deactivation_hook(__FILE__, 'product_advisor_deactivate');
    
    // Enqueue scripts and styles
    add_action('wp_enqueue_scripts', 'product_advisor_enqueue_scripts');
    add_action('admin_enqueue_scripts', 'product_advisor_admin_enqueue_scripts');
    
    // Add shortcodes
    add_shortcode('product_advisor_form', 'product_advisor_form_shortcode');
    add_shortcode('product_advisor_recommendations', 'product_advisor_recommendations_shortcode');
    
    // Add WooCommerce product attributes
    add_action('init', 'product_advisor_add_product_attributes');
    
    // Add custom page template
    add_filter('theme_page_templates', 'product_advisor_register_template');
    add_filter('template_include', 'product_advisor_template_include');
}

// Load plugin text domain
function product_advisor_load_textdomain() {
    load_plugin_textdomain('product-recommendation-advisor', false, dirname(plugin_basename(__FILE__)) . '/languages');
}
add_action('init', 'product_advisor_load_textdomain');

add_action('plugins_loaded', 'product_advisor_init');

// Function to ensure the matching table exists
function product_advisor_ensure_matching_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'product_matching';
    
    // Check if table exists
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
    
    if (!$table_exists) {
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $table_name (
            id BIGINT(20) NOT NULL AUTO_INCREMENT,
            question_key VARCHAR(100) NOT NULL,
            answer_value VARCHAR(255) NOT NULL,
            product_ids TEXT NOT NULL,
            PRIMARY KEY (id),
            KEY question_key (question_key),
            KEY answer_value (answer_value)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
}

// Plugin activation
function product_advisor_activate() {
    // Create the matching table
    product_advisor_ensure_matching_table();
    
    // Set default options if they don't exist
    if (get_option('product_advisor_questions') === false) {
        update_option('product_advisor_questions', array());
    }
    if (get_option('product_advisor_currency') === false) {
        update_option('product_advisor_currency', 'toman');
    }
    if (get_option('product_advisor_number_format') === false) {
        update_option('product_advisor_number_format', 'persian');
    }
    if (get_option('product_advisor_save_responses') === false) {
        update_option('product_advisor_save_responses', 'yes');
    }
    
    // Create template directory if it doesn't exist
    if (!file_exists(PRODUCT_ADVISOR_PLUGIN_DIR . 'templates')) {
        mkdir(PRODUCT_ADVISOR_PLUGIN_DIR . 'templates', 0755, true);
    }
    
    // Create template file if it doesn't exist
    $template_file = PRODUCT_ADVISOR_PLUGIN_DIR . 'templates/product-advisor-template.php';
    if (!file_exists($template_file)) {
        $template_content = '<?php
/**
 * Template Name: Product Recommendation Form
 * Description: A custom template for the Product Recommendation Advisor form
 */

get_header();
?>

<div class="product-advisor-page">
    <div class="product-advisor-container">
        <?php
        // Display the form
        echo product_advisor_display_form();
        
        // Display recommendations if they exist
        if (isset($_GET[\'recommendations\']) && $_GET[\'recommendations\'] === \'true\') {
            echo product_advisor_show_recommendations();
        }
        ?>
    </div>
</div>

<?php
get_footer();
?>';
        file_put_contents($template_file, $template_content);
    }
}

// Plugin deactivation
function product_advisor_deactivate() {
    // Optionally, perform cleanup tasks like deleting the custom table.
    // For now, it's commented out to preserve data during testing.
    // global $wpdb;
    // $table_name = $wpdb->prefix . 'product_matching';
    // $wpdb->query("DROP TABLE IF EXISTS $table_name");
}

// Enqueue frontend scripts and styles
function product_advisor_enqueue_scripts() {
    wp_enqueue_style('product-advisor-style', PRODUCT_ADVISOR_PLUGIN_URL . 'assets/css/style.css', array(), PRODUCT_ADVISOR_VERSION);
    wp_enqueue_script('product-advisor-script', PRODUCT_ADVISOR_PLUGIN_URL . 'assets/js/script.js', array('jquery'), PRODUCT_ADVISOR_VERSION, true);

    wp_localize_script('product-advisor-script', 'productAdvisorAjax', array(
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce'   => wp_create_nonce('product_advisor_form_nonce')
    ));
}

// Enqueue admin scripts and styles
function product_advisor_admin_enqueue_scripts($hook) {
    if (strpos($hook, 'product-advisor') === false) {
        return;
    }

    wp_enqueue_style('product-advisor-admin', PRODUCT_ADVISOR_PLUGIN_URL . 'assets/css/admin.css', array(), PRODUCT_ADVISOR_VERSION);
    wp_enqueue_script('product-advisor-admin', PRODUCT_ADVISOR_PLUGIN_URL . 'assets/js/admin.js', array('jquery', 'jquery-ui-sortable', 'select2'), PRODUCT_ADVISOR_VERSION, true);
    
    wp_localize_script('product-advisor-admin', 'productAdvisor', array(
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('product_advisor_nonce'),
        'selectAttribute' => __('انتخاب ویژگی...', 'product-recommendation-advisor'),
        'selectTerms' => __('انتخاب زیرویژگی‌ها...', 'product-recommendation-advisor'),
        'selectProducts' => __('انتخاب محصولات...', 'product-recommendation-advisor'),
        'selectAnAttributeFirst' => __('ابتدا یک ویژگی انتخاب کنید...', 'product-recommendation-advisor'),
        'selectTermsFirst' => __('ابتدا زیرویژگی‌ها را انتخاب کنید...', 'product-recommendation-advisor'),
        'loadingTerms' => __('در حال بارگذاری زیرویژگی‌ها...', 'product-recommendation-advisor'),
        'loadingProducts' => __('در حال بارگذاری محصولات...', 'product-recommendation-advisor'),
        'noTermsFound' => __('هیچ زیرویژگی‌ای یافت نشد.', 'product-recommendation-advisor'),
        'noProductsFound' => __('هیچ محصولی یافت نشد.', 'product-recommendation-advisor'),
        'errorLoadingTerms' => __('خطا در بارگذاری زیرویژگی‌ها.', 'product-recommendation-advisor'),
        'errorLoadingProducts' => __('خطا در بارگذاری محصولات.', 'product-recommendation-advisor'),
        'noResultsFound' => __('نتیجه‌ای یافت نشد.', 'product-recommendation-advisor'),
        'confirmDelete' => __('آیا از حذف این سوال اطمینان دارید؟', 'product-recommendation-advisor'),
        'reorderSuccess' => __('ترتیب سوالات با موفقیت به‌روزرسانی شد.', 'product-recommendation-advisor'),
        'reorderError' => __('خطا در به‌روزرسانی ترتیب سوالات.', 'product-recommendation-advisor'),
        'serverError' => __('خطا در ارتباط با سرور.', 'product-recommendation-advisor'),
        'updateQuestionHeading' => __('ویرایش سوال', 'product-recommendation-advisor'),
        'updateQuestion' => __('به‌روزرسانی سوال', 'product-recommendation-advisor'),
        'addQuestion' => __('افزودن سوال', 'product-recommendation-advisor'),
        'saving' => __('در حال ذخیره...', 'product-recommendation-advisor'),
        'errorLoadingQuestionData' => __('خطا در بارگذاری اطلاعات سوال.', 'product-recommendation-advisor'),
        'noQuestionsDefined' => __('هیچ سوالی تعریف نشده است.', 'product-recommendation-advisor')
    ));
}

// Add WooCommerce product attributes
function product_advisor_add_product_attributes() {
    // Get all existing product attributes
    $attributes = wc_get_attribute_taxonomies();
    
    // Store attribute information in plugin options
    $attribute_info = array();
    foreach ($attributes as $attribute) {
        $attribute_info[$attribute->attribute_name] = array(
            'label' => $attribute->attribute_label,
            'name' => $attribute->attribute_name,
            'type' => $attribute->attribute_type
        );
    }
    
    // Save attribute information
    update_option('product_advisor_attributes', $attribute_info);
}

// Register custom page template for the form
function product_advisor_register_template($templates) {
    $templates['product-advisor-template.php'] = __('Product Recommendation Form Template', 'product-recommendation-advisor');
    return $templates;
}

// Add custom template to the page
function product_advisor_template_include($template) {
    if (is_page_template('product-advisor-template.php')) {
        return PRODUCT_ADVISOR_PLUGIN_DIR . 'templates/product-advisor-template.php';
    }
    return $template;
}

// Add action to check table on plugin load
add_action('plugins_loaded', 'product_advisor_ensure_matching_table');

// Enqueue admin scripts
function product_advisor_enqueue_admin_scripts($hook) {
    if (strpos($hook, 'product-advisor') === false) {
        return;
    }

    wp_enqueue_style('select2', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css');
    wp_enqueue_script('select2', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js', array('jquery'));
    wp_enqueue_script('product-advisor-admin', plugin_dir_url(__FILE__) . 'assets/js/admin.js', array('jquery', 'select2'), '1.0.0', true);
    
    wp_localize_script('product-advisor-admin', 'product_advisor_params', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('product_advisor_nonce'),
        'i18n' => array(
            'search_products' => __('جستجوی محصولات...', 'product-recommendation-advisor'),
            'no_products_found' => __('محصولی یافت نشد.', 'product-recommendation-advisor'),
            'select_answer' => __('انتخاب پاسخ...', 'product-recommendation-advisor'),
            'select_attribute_first' => __('ابتدا یک ویژگی انتخاب کنید...', 'product-recommendation-advisor'),
            'select_terms' => __('انتخاب زیرویژگی‌ها...', 'product-recommendation-advisor'),
            'error_loading_terms' => __('خطا در بارگذاری زیرویژگی‌ها.', 'product-recommendation-advisor')
        )
    ));
}
add_action('admin_enqueue_scripts', 'product_advisor_enqueue_admin_scripts'); 