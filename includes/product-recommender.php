<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Include common functions
require_once PERFUME_ADVISOR_PLUGIN_DIR . 'includes/common-functions.php';

// Get product recommendations based on user answers
function perfume_advisor_get_recommendations($answers) {
    // Get all products
    $args = array(
        'post_type' => 'product',
        'posts_per_page' => -1,
        'post_status' => 'publish'
    );
    
    $products = wc_get_products($args);
    $recommendations = array();
    $attribute_info = get_option('perfume_advisor_attributes', array());
    
    foreach ($products as $product) {
        $score = 0;
        $reasons = array();
        
        // Check each answer against product attributes
        foreach ($answers as $question_id => $answer) {
            $question = perfume_advisor_get_question($question_id);
            if (!$question) continue;
            
            // Get the attribute name from the question
            $attribute_name = $question['attribute'];
            if (!isset($attribute_info[$attribute_name])) continue;
            
            // Get product attribute value
            $product_attribute = $product->get_attribute($attribute_name);
            if (empty($product_attribute)) continue;
            
            // Check if the answer matches the product attribute
            if (is_array($answer)) {
                // For multiple choice questions
                foreach ($answer as $selected_option) {
                    if (strpos($product_attribute, $selected_option) !== false) {
                        $score += 1;
                        $reasons[] = sprintf(
                            __('این محصول دارای ویژگی %s است که شما انتخاب کرده‌اید.', 'perfume-advisor'),
                            $selected_option
                        );
                    }
                }
            } else {
                // For single choice questions
                if (strpos($product_attribute, $answer) !== false) {
                    $score += 1;
                    $reasons[] = sprintf(
                        __('این محصول دارای ویژگی %s است که شما انتخاب کرده‌اید.', 'perfume-advisor'),
                        $answer
                    );
                }
            }
        }
        
        // Add product to recommendations if it has a score
        if ($score > 0) {
            $recommendations[] = array(
                'id' => $product->get_id(),
                'title' => $product->get_name(),
                'price' => $product->get_price_html(),
                'image' => wp_get_attachment_image_url($product->get_image_id(), 'medium'),
                'url' => get_permalink($product->get_id()),
                'add_to_cart_url' => $product->add_to_cart_url(),
                'score' => $score,
                'reasons' => $reasons
            );
        }
    }
    
    // Sort recommendations by score
    usort($recommendations, function($a, $b) {
        return $b['score'] - $a['score'];
    });
    
    // Return top 5 recommendations
    return array_slice($recommendations, 0, 5);
}

/**
 * AJAX handler to get products filtered by selected attribute terms.
 */
function perfume_advisor_ajax_get_products_by_terms() {
    check_ajax_referer('perfume_advisor_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => __('شما دسترسی لازم برای این عملیات را ندارید.', 'perfume-advisor')));
    }

    $attribute = isset($_POST['attribute']) ? sanitize_text_field($_POST['attribute']) : '';
    $terms = isset($_POST['terms']) ? array_map('sanitize_text_field', (array)$_POST['terms']) : array();

    if (empty($attribute) || empty($terms)) {
        wp_send_json_error(array('message' => __('نام ویژگی و زیرویژگی‌ها مورد نیاز هستند.', 'perfume-advisor')));
    }

    $products_data = array();

    // Get products associated with the selected attribute terms
    $args = array(
        'post_type'      => 'product',
        'posts_per_page' => -1,
        'post_status'    => 'publish',
        'tax_query'      => array(
            array(
                'taxonomy' => 'pa_' . $attribute, // WooCommerce attribute taxonomy prefix
                'field'    => 'slug',
                'terms'    => $terms,
                'operator' => 'IN',
            ),
        ),
        'fields' => 'ids', // Only get product IDs initially for efficiency
    );

    $product_ids = get_posts($args);

    if (!empty($product_ids)) {
        foreach ($product_ids as $product_id) {
            $product = wc_get_product($product_id);
            if ($product) {
                $products_data[] = array(
                    'id' => $product->get_id(),
                    'name' => $product->get_name(),
                );
            }
        }
    }

    wp_send_json_success($products_data);
}
add_action('wp_ajax_perfume_advisor_get_products_by_terms', 'perfume_advisor_ajax_get_products_by_terms'); 