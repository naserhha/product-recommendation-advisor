<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Handle form submission
function perfume_advisor_handle_form_submission() {
    // Verify nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'perfume_advisor_nonce')) {
        wp_send_json_error(array('message' => __('خطای امنیتی. لطفا صفحه را رفرش کنید.', 'perfume-advisor')));
    }

    // Get questions
    $questions = get_option('perfume_advisor_questions', array());
    if (empty($questions)) {
        wp_send_json_error(array('message' => __('هیچ سوالی تعریف نشده است.', 'perfume-advisor')));
    }

    // Process answers
    $answers = array();
    foreach ($questions as $question) {
        if (isset($question['id'])) {
            $field_id = 'question_' . $question['id'];
            $field_name = 'answers[' . $question['id'] . ']';
        } else {
            // مدیریت خطا یا مقدار پیش‌فرض
            $field_id = 'question_default';
            $field_name = 'answers[default]';
        }
        if (isset($_POST[$field_id])) {
            $answers[$question['attribute_name']] = sanitize_text_field($_POST[$field_id]);
        }
    }

    // Get recommendations
    $recommendations = perfume_advisor_get_recommendations($answers);
    
    if (empty($recommendations)) {
        wp_send_json_error(array('message' => __('متاسفانه محصولی با این مشخصات یافت نشد.', 'perfume-advisor')));
    }

    // Save user answers if enabled
    if (get_option('perfume_advisor_save_user_answers', 'yes') === 'yes') {
        perfume_advisor_save_user_answers($answers);
    }

    // Prepare response
    $response = array(
        'success' => true,
        'recommendations' => $recommendations,
        'message' => __('پیشنهادات با موفقیت دریافت شد.', 'perfume-advisor')
    );

    wp_send_json_success($response);
}
add_action('wp_ajax_perfume_advisor_submit', 'perfume_advisor_handle_form_submission');
add_action('wp_ajax_nopriv_perfume_advisor_submit', 'perfume_advisor_handle_form_submission');

// Display recommendations
function perfume_advisor_show_recommendations() {
    if (!isset($_GET['perfume_advisor']) || $_GET['perfume_advisor'] !== 'recommendations') {
        return;
    }
    
    $recommendations = WC()->session->get('perfume_advisor_recommendations');
    $answers = WC()->session->get('perfume_advisor_answers');
    
    if (empty($recommendations)) {
        echo '<div class="perfume-advisor-message">';
        echo '<p>' . __('متاسفانه هیچ محصولی با مشخصات مورد نظر شما یافت نشد.', 'perfume-advisor') . '</p>';
        echo '<a href="' . esc_url(add_query_arg('perfume_advisor', 'form', wc_get_page_permalink('shop'))) . '" class="button">';
        echo __('بازگشت به فرم', 'perfume-advisor');
        echo '</a>';
        echo '</div>';
        return;
    }
    
    echo '<div class="perfume-advisor-recommendations">';
    echo '<h2>' . __('پیشنهادات ما برای شما', 'perfume-advisor') . '</h2>';
    
    echo '<div class="recommendations-grid">';
    foreach ($recommendations as $product) {
        $reasons = $recommender->get_recommendation_reason($product);
        
        echo '<div class="recommendation-item">';
        
        // Product image
        echo '<div class="product-image">';
        echo $product->get_image('woocommerce_thumbnail');
        echo '</div>';
        
        // Product title
        echo '<h3 class="product-title">';
        echo '<a href="' . esc_url($product->get_permalink()) . '">';
        echo esc_html($product->get_name());
        echo '</a>';
        echo '</h3>';
        
        // Product price
        echo '<div class="product-price">';
        echo $product->get_price_html();
        echo '</div>';
        
        // Recommendation reasons
        if (!empty($reasons)) {
            echo '<div class="recommendation-reasons">';
            echo '<h4>' . __('چرا این عطر را پیشنهاد می‌کنیم؟', 'perfume-advisor') . '</h4>';
            echo '<ul>';
            foreach ($reasons as $reason) {
                echo '<li>' . esc_html($reason) . '</li>';
            }
            echo '</ul>';
            echo '</div>';
        }
        
        // Add to cart button
        echo '<div class="product-actions">';
        echo apply_filters(
            'woocommerce_loop_add_to_cart_link',
            sprintf(
                '<a href="%s" data-quantity="%s" class="%s" %s>%s</a>',
                esc_url($product->add_to_cart_url()),
                esc_attr(isset($args['quantity']) ? $args['quantity'] : 1),
                esc_attr(isset($args['class']) ? $args['class'] : 'button'),
                isset($args['attributes']) ? wc_implode_html_attributes($args['attributes']) : '',
                esc_html($product->add_to_cart_text())
            ),
            $product,
            $args
        );
        echo '</div>';
        
        echo '</div>'; // .recommendation-item
    }
    echo '</div>'; // .recommendations-grid
    
    // Back to form button
    echo '<div class="perfume-advisor-actions">';
    echo '<a href="' . esc_url(add_query_arg('perfume_advisor', 'form', wc_get_page_permalink('shop'))) . '" class="button">';
    echo __('بازگشت به فرم', 'perfume-advisor');
    echo '</a>';
    echo '</div>';
    
    echo '</div>'; // .perfume-advisor-recommendations
}
add_action('woocommerce_before_shop_loop', 'perfume_advisor_show_recommendations', 20);

// Display the survey form
function perfume_advisor_display_form() {
    // Get questions from options
    $questions = get_option('perfume_advisor_questions', array());
    
    if (empty($questions)) {
        return '<div class="perfume-advisor-notice">' . 
               __('هیچ سوالی تعریف نشده است. لطفا ابتدا سوالات را در بخش مدیریت تنظیم کنید.', 'perfume-advisor') . 
               '</div>';
    }
    
    ob_start();
    ?>
    <div class="perfume-advisor-form-container">
        <form id="perfume-advisor-form" class="perfume-advisor-form" method="post" action="">
            <?php wp_nonce_field('perfume_advisor_form_submit', 'perfume_advisor_nonce'); ?>
            
            <?php foreach ($questions as $question) : ?>
                <div class="form-group">
                    <label for="question_<?php echo esc_attr($question['id']); ?>">
                        <?php echo esc_html($question['text']); ?>
                        <?php if ($question['required']) : ?>
                            <span class="required">*</span>
                        <?php endif; ?>
                    </label>
                    
                    <?php
                    $field_id = 'question_' . $question['id'];
                    $field_name = 'answers[' . $question['id'] . ']';
                    $required = $question['required'] ? 'required' : '';
                    
                    // All questions are now attribute-based, so render options using attribute_terms
                    if (isset($question['attribute_name']) && !empty($question['attribute_terms'])) {
                        echo '<div class="perfume-advisor-options">';
                        foreach ($question['attribute_terms'] as $term_slug) {
                            // Get the term object to display the name
                            $term = get_term_by('slug', $term_slug, $question['attribute_name']);
                            if ($term && !is_wp_error($term)) {
                                echo '<div class="perfume-advisor-option">';
                                echo '<input type="radio" 
                                               id="' . esc_attr($field_id . '_' . esc_attr($term->slug)) . '" 
                                               name="' . esc_attr($field_name) . '" 
                                               value="' . esc_attr($term->slug) . '"';
                                echo $required;
                                echo '>';
                                echo '<label for="' . esc_attr($field_id . '_' . esc_attr($term->slug)) . '">' . esc_html($term->name) . '</label>';
                                echo '</div>';
                            }
                        }
                        echo '</div>';
                    } else {
                        // Fallback for old questions or if terms are not set, display a simple text input.
                        // This should ideally not be reached if questions are created via the new admin form.
                        echo '<input type="text" id="' . esc_attr($field_id) . '" name="' . esc_attr($field_name) . '" class="form-control" ' . $required . '>';
                    }
                    ?>
                </div>
            <?php endforeach; ?>
            
            <div class="form-group">
                <button type="submit" class="perfume-advisor-submit">
                    <?php _e('دریافت پیشنهادات', 'perfume-advisor'); ?>
                </button>
            </div>
        </form>
    </div>
    <?php
    return ob_get_clean();
}
add_action('woocommerce_before_shop_loop', 'perfume_advisor_display_form', 20);

// Shortcode for displaying the form
function perfume_advisor_form_shortcode($atts) {
    // Start output buffering
    ob_start();
    
    // Get questions from options
    $questions = get_option('perfume_advisor_questions', array());
    
    // If no questions are defined, show a notice
    if (empty($questions)) {
        echo '<div class="perfume-advisor-notice">';
        echo '<p>' . __('هیچ سوالی تعریف نشده است. لطفاً به بخش تنظیمات افزونه مراجعه کنید.', 'perfume-advisor') . '</p>';
        echo '</div>';
        return ob_get_clean();
    }
    
    // Display the form
    echo '<div class="perfume-advisor-form-container">';
    echo '<div class="perfume-advisor-form-header">';
    echo '<h2>' . __('مشاور هوشمند عطر', 'perfume-advisor') . '</h2>';
    echo '<p>' . __('با پاسخ به چند سوال ساده، عطر مناسب خود را پیدا کنید.', 'perfume-advisor') . '</p>';
    echo '</div>';
    
    echo '<form id="perfume-advisor-form" class="perfume-advisor-form" method="post">';
    wp_nonce_field('perfume_advisor_form_nonce', 'perfume_advisor_nonce');
    
    foreach ($questions as $question) {
        echo '<div class="perfume-advisor-form-group">';
        echo '<label for="question_' . esc_attr($question['id']) . '">';
        echo esc_html($question['text']);
        if (isset($question['required']) && $question['required']) {
            echo ' <span class="required">*</span>';
        }
        echo '</label>';

        // All questions are now attribute-based, so render options using attribute_terms
        if (isset($question['attribute_name']) && !empty($question['attribute_terms'])) {
            echo '<div class="perfume-advisor-options">';
            foreach ($question['attribute_terms'] as $term_slug) {
                // Get the term object to display the name
                $term = get_term_by('slug', $term_slug, $question['attribute_name']);
                if ($term && !is_wp_error($term)) {
                    echo '<div class="perfume-advisor-option">';
                    echo '<input type="radio" 
                                   id="question_' . esc_attr($question['id']) . '_' . esc_attr($term->slug) . '" 
                                   name="answers[' . esc_attr($question['id']) . ']" 
                                   value="' . esc_attr($term->slug) . '"';
                    if (isset($question['required']) && $question['required']) {
                        echo ' required';
                    }
                    echo '>';
                    echo '<label for="question_' . esc_attr($question['id']) . '_' . esc_attr($term->slug) . '">' . esc_html($term->name) . '</label>';
                    echo '</div>';
                }
            }
            echo '</div>';
        } else {
            // Fallback for old questions or if terms are not set, display a simple text input.
            // This should ideally not be reached if questions are created via the new admin form.
            echo '<input type="text" id="question_' . esc_attr($question['id']) . '" name="answers[' . esc_attr($question['id']) . ']" class="perfume-advisor-form-control" ';
            if (isset($question['required']) && $question['required']) {
                echo 'required';
            }
            echo '>';
        }
        
        echo '</div>';
    }
    
    echo '<button type="submit" class="perfume-advisor-submit">' . __('دریافت پیشنهادات', 'perfume-advisor') . '</button>';
    echo '</form>';
    echo '</div>';
    
    // Return the buffered content
    return ob_get_clean();
}

// Add shortcode for recommendations
function perfume_advisor_recommendations_shortcode() {
    ob_start();
    perfume_advisor_show_recommendations();
    return ob_get_clean();
}
add_shortcode('perfume_advisor_recommendations', 'perfume_advisor_recommendations_shortcode');

// اسکریپت اصلاح سوالات قدیمی
function perfume_advisor_update_questions_with_id() {
    $questions = get_option('perfume_advisor_questions', array());
    $updated = false;
    foreach ($questions as $key => $question) {
        if (!isset($question['id'])) {
            $questions[$key]['id'] = uniqid('q_');
            $updated = true;
        }
    }
    if ($updated) {
        update_option('perfume_advisor_questions', $questions);
    }
}
add_action('admin_init', 'perfume_advisor_update_questions_with_id'); 