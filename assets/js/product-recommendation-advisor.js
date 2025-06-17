jQuery(document).ready(function($) {
    // Handle form submission
    $('#product-recommendation-advisor-form').on('submit', function(e) {
        e.preventDefault();
        
        var $submitButton = $(this).find('.product-recommendation-advisor-submit');
        var $form = $(this);
        
        // Disable submit button and show loading state
        $submitButton.prop('disabled', true).text('در حال پردازش...');
        
        // Collect form data
        var formData = new FormData(this);
        formData.append('action', 'product_recommendation_advisor_submit');
        formData.append('nonce', productAdvisorAjax.nonce);
        
        // Send AJAX request
        $.ajax({
            url: productAdvisorAjax.ajaxurl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    var recommendationsHtml = '<div class="product-recommendation-advisor-recommendations">';
                    recommendationsHtml += '<h2>پیشنهادات ما برای شما</h2>';
                    
                    response.data.products.forEach(function(product) {
                        recommendationsHtml += '<div class="product-recommendation-advisor-product">';
                        recommendationsHtml += '<img src="' + product.image + '" alt="' + product.title + '" class="product-recommendation-advisor-product-image">';
                        recommendationsHtml += '<div class="product-recommendation-advisor-product-info">';
                        recommendationsHtml += '<h3 class="product-recommendation-advisor-product-title">' + product.title + '</h3>';
                        recommendationsHtml += '<div class="product-recommendation-advisor-product-price">' + product.price + '</div>';
                        recommendationsHtml += '<div class="product-recommendation-advisor-product-reason">' + product.reason + '</div>';
                        recommendationsHtml += '<div class="product-recommendation-advisor-product-actions">';
                        recommendationsHtml += '<a href="' + product.add_to_cart_url + '" class="product-recommendation-advisor-add-to-cart">افزودن به سبد خرید</a>';
                        recommendationsHtml += '<a href="' + product.url + '" class="product-recommendation-advisor-view-product">مشاهده محصول</a>';
                        recommendationsHtml += '</div></div></div>';
                    });
                    
                    recommendationsHtml += '</div>';
                    $('.product-recommendation-advisor-form-container').html(recommendationsHtml);
                } else {
                    alert(response.data.message);
                }
            },
            error: function() {
                alert('خطا در ارتباط با سرور. لطفا دوباره تلاش کنید.');
            },
            complete: function() {
                $submitButton.prop('disabled', false).text('دریافت پیشنهادات');
            }
        });
    });
    
    // Handle form field changes
    $('.product-recommendation-advisor-form').on('change', 'select, input[type="radio"], input[type="checkbox"]', function() {
        var $field = $(this);
        var $container = $field.closest('.product-recommendation-advisor-field');
        
        // Remove previous error state
        $container.removeClass('has-error');
        $container.find('.error-message').remove();
        
        // Validate required fields
        if ($field.prop('required') && !$field.val()) {
            $container.addClass('has-error');
            $container.append('<div class="error-message">این فیلد الزامی است.</div>');
        }
    });
    
    // Handle number input validation
    $('.product-recommendation-advisor-form').on('input', 'input[type="number"]', function() {
        var $field = $(this);
        var $container = $field.closest('.product-recommendation-advisor-field');
        var min = parseFloat($field.attr('min'));
        var max = parseFloat($field.attr('max'));
        var value = parseFloat($field.val());
        
        // Remove previous error state
        $container.removeClass('has-error');
        $container.find('.error-message').remove();
        
        // Validate range
        if (!isNaN(min) && value < min) {
            $container.addClass('has-error');
            $container.append('<div class="error-message">مقدار باید بیشتر از ' + min + ' باشد.</div>');
        }
        if (!isNaN(max) && value > max) {
            $container.addClass('has-error');
            $container.append('<div class="error-message">مقدار باید کمتر از ' + max + ' باشد.</div>');
        }
    });
    
    // Handle price range slider
    $('.product-recommendation-advisor-price-range').on('change', function() {
        var $slider = $(this);
        var minField = $('#product-recommendation-advisor-price-min');
        var maxField = $('#product-recommendation-advisor-price-max');
        
        // Update min/max fields
        minField.val($slider.slider('values', 0));
        maxField.val($slider.slider('values', 1));
        
        // Trigger change event for validation
        minField.trigger('change');
        maxField.trigger('change');
    });
    
    // Handle price input fields
    $('.product-recommendation-advisor-price-input').on('input', function() {
        var $field = $(this);
        var $slider = $('.product-recommendation-advisor-price-range');
        var values = $slider.slider('values');
        
        // Update slider
        if ($field.attr('id') === 'product-recommendation-advisor-price-min') {
            $slider.slider('values', 0, $field.val());
        } else {
            $slider.slider('values', 1, $field.val());
        }
    });
    
    // Handle add to cart button
    $('.product-recommendation-advisor-recommendations').on('click', '.add-to-cart', function(e) {
        e.preventDefault();
        var $button = $(this);
        var productId = $button.data('product-id');
        
        // Disable button and show loading state
        $button.prop('disabled', true).text('در حال افزودن...');
        
        // Add to cart via AJAX
        $.ajax({
            url: productAdvisorAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'product_recommendation_advisor_add_to_cart',
                product_id: productId,
                nonce: productAdvisorAjax.nonce
            },
            success: function(response) {
                if (response.success) {
                    // Show success message
                    $button.text('افزوده شد به سبد خرید');
                    setTimeout(function() {
                        $button.prop('disabled', false).text('افزودن به سبد خرید');
                    }, 2000);
                } else {
                    alert(response.data.message);
                    $button.prop('disabled', false).text('افزودن به سبد خرید');
                }
            },
            error: function() {
                alert('خطا در افزودن به سبد خرید. لطفا دوباره تلاش کنید.');
                $button.prop('disabled', false).text('افزودن به سبد خرید');
            }
        });
    });
}); 