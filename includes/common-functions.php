<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Get attribute terms for a specific attribute
function perfume_advisor_get_attribute_terms($attribute_name) {
    $terms = get_terms(array(
        'taxonomy' => $attribute_name,
        'hide_empty' => true
    ));
    
    if (is_wp_error($terms)) {
        return array();
    }
    
    $term_options = array();
    foreach ($terms as $term) {
        $term_options[] = array(
            'slug' => $term->slug,
            'name' => $term->name
        );
    }
    
    return $term_options;
}

// Get products by attribute terms
function perfume_advisor_get_products_by_terms($attribute_name, $terms) {
    $args = array(
        'post_type' => 'product',
        'posts_per_page' => -1,
        'tax_query' => array(
            array(
                'taxonomy' => $attribute_name,
                'field' => 'slug',
                'terms' => $terms
            )
        )
    );
    
    $products = array();
    $query = new WP_Query($args);
    
    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $product = wc_get_product(get_the_ID());
            if ($product) {
                $products[] = array(
                    'id' => $product->get_id(),
                    'name' => $product->get_name(),
                    'price' => $product->get_price_html()
                );
            }
        }
    }
    wp_reset_postdata();
    
    return $products;
}

// Get question by ID
function perfume_advisor_get_question($question_id) {
    $questions = get_option('perfume_advisor_questions', array());
    foreach ($questions as $question) {
        if ($question['id'] == $question_id) {
            return $question;
        }
    }
    return false;
}

function perfume_advisor_get_woocommerce_attributes() {
    $attribute_taxonomies = wc_get_attribute_taxonomies();
    $attributes = array();
    if ($attribute_taxonomies) {
        foreach ($attribute_taxonomies as $tax) {
            $attributes[] = $tax;
        }
    }
    return $attributes;
} 