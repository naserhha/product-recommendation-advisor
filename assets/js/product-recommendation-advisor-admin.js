jQuery(document).ready(function($) {
    // Function to load attribute terms
    function loadAttributeTerms(attributeName, selectedTerms = []) {
        var $termsSelect = $('#question_terms');
        $termsSelect.empty().append('<option value="">' + perfumeAdvisor.loadingTerms + '</option>');
        
        if (attributeName) {
            $.ajax({
                url: perfumeAdvisor.ajaxurl,
                type: 'POST',
                data: {
                    action: 'perfume_advisor_get_attribute_terms',
                    nonce: perfumeAdvisor.nonce,
                    attribute: attributeName
                },
                success: function(response) {
                    if (response.success) {
                        $termsSelect.empty();
                        if (response.data.length === 0) {
                            $termsSelect.append('<option value="">' + perfumeAdvisor.noTermsFound + '</option>');
                        } else {
                            $.each(response.data, function(index, term) {
                                $termsSelect.append($('<option>', {
                                    value: term.slug,
                                    text: term.name
                                }));
                            });
                            if (selectedTerms.length > 0) {
                                $termsSelect.val(selectedTerms).trigger('change');
                            }
                        }
                    } else {
                        console.error('Error loading terms:', response.data.message);
                        $termsSelect.empty().append('<option value="">' + perfumeAdvisor.errorLoadingTerms + '</option>');
                    }
                },
                error: function() {
                    console.error('AJAX error loading terms.');
                    $termsSelect.empty().append('<option value="">' + perfumeAdvisor.errorLoadingTerms + '</option>');
                }
            });
        } else {
            $termsSelect.empty().append('<option value="">' + perfumeAdvisor.selectAnAttributeFirst + '</option>');
        }
    }

    // Function to load products based on selected terms
    function loadProducts(attributeName, terms, selectedProducts = []) {
        var $productsSelect = $('#question_products');
        $productsSelect.empty().append('<option value="">' + perfumeAdvisor.loadingProducts + '</option>');

        if (attributeName && terms && terms.length > 0) {
            $.ajax({
                url: perfumeAdvisor.ajaxurl,
                type: 'POST',
                data: {
                    action: 'perfume_advisor_get_products_by_terms',
                    nonce: perfumeAdvisor.nonce,
                    attribute: attributeName,
                    terms: terms
                },
                success: function(response) {
                    if (response.success) {
                        $productsSelect.empty();
                        if (response.data.length === 0) {
                            $productsSelect.append('<option value="">' + perfumeAdvisor.noProductsFound + '</option>');
                        } else {
                            $.each(response.data, function(index, product) {
                                $productsSelect.append($('<option>', {
                                    value: product.id,
                                    text: product.name + ' (' + product.price + ')'
                                }));
                            });
                            if (selectedProducts.length > 0) {
                                $productsSelect.val(selectedProducts).trigger('change');
                            }
                        }
                    } else {
                        console.error('Error loading products:', response.data.message);
                        $productsSelect.empty().append('<option value="">' + perfumeAdvisor.errorLoadingProducts + '</option>');
                    }
                },
                error: function() {
                    console.error('AJAX error loading products.');
                    $productsSelect.empty().append('<option value="">' + perfumeAdvisor.errorLoadingProducts + '</option>');
                }
            });
        } else {
            $productsSelect.empty().append('<option value="">' + perfumeAdvisor.selectTermsFirst + '</option>');
        }
    }

    // Initial state on page load
    // toggleAttributeFields(); // No longer needed as sections are always visible

    // Initialize Select2 for attribute, terms, and products selects
    $('#question_attribute').select2({
        placeholder: perfumeAdvisor.selectAttribute,
        allowClear: true,
        language: {
            noResults: function() {
                return perfumeAdvisor.noResultsFound;
            }
        }
    });

    $('#question_terms').select2({
        placeholder: perfumeAdvisor.selectTerms,
        allowClear: true,
        multiple: true,
        language: {
            noResults: function() {
                return perfumeAdvisor.noResultsFound;
            }
        }
    });

    $('#question_products').select2({
        placeholder: perfumeAdvisor.selectProducts,
        allowClear: true,
        multiple: true,
        language: {
            noResults: function() {
                return perfumeAdvisor.noResultsFound;
            }
        }
    });

    // Load initial terms if attribute is selected
    var initialAttribute = $('#question_attribute').val();
    if (initialAttribute) {
        loadAttributeTerms(initialAttribute, $('#question_terms').val());
    }

    // Handle attribute selection change
    $('#question_attribute').on('change', function() {
        var attributeName = $(this).val();
        loadAttributeTerms(attributeName);
        // Clear products when attribute changes
        $('#question_products').empty().val('');
        $('#question_products').trigger('change.select2');
    });

    // Handle terms selection change
    $('#question_terms').on('change', function() {
        var attributeName = $('#question_attribute').val();
        var selectedTerms = $(this).val();
        loadProducts(attributeName, selectedTerms);
    });

    // Handle question reordering
    $('.questions-list').sortable({
        handle: '.drag-handle',
        update: function(event, ui) {
            var questions = [];
            $('.questions-list .question-card').each(function(index) {
                questions.push($(this).data('question-id'));
            });
            
            // Save new order via AJAX
            $.ajax({
                url: perfumeAdvisor.ajaxurl,
                type: 'POST',
                data: {
                    action: 'perfume_advisor_reorder_questions',
                    nonce: perfumeAdvisor.nonce,
                    questions: questions
                },
                success: function(response) {
                    if (response.success) {
                        // Show success message
                        $('.notice-success').remove();
                        $('.wrap h1').after('<div class="notice notice-success is-dismissible"><p>' + 
                            'ترتیب سوالات با موفقیت به‌روزرسانی شد.' + '</p></div>');
                    }
                },
                error: function() {
                    // Error message to be handled later
                }
            });
        }
    });
    
    // Handle question deletion
    $(document).on('click', '.delete-question', function(e) {
        e.preventDefault();
        
        if (!confirm(perfumeAdvisor.confirmDelete)) {
            return;
        }
        
        var $card = $(this).closest('.question-card');
        var questionId = $card.data('question-id');
        
        $.ajax({
            url: perfumeAdvisor.ajaxurl,
            type: 'POST',
            data: {
                action: 'perfume_advisor_delete_question',
                nonce: perfumeAdvisor.nonce,
                question_id: questionId
            },
            success: function(response) {
                if (response.success) {
                    $card.fadeOut(300, function() {
                        $card.remove();
                        // If no questions left, show the "no questions" message
                        if ($('.questions-list tr').length === 0) {
                            $('.questions-list').html('<p class="no-questions">' + perfumeAdvisor.noQuestions + '</p>');
                        }
                    });
                    
                    // Show success message
                    $('.notice-success').remove();
                    $('.wrap h1').after('<div class="notice notice-success is-dismissible"><p>' + 
                        response.data.message + '</p></div>');
                } else {
                    // Show error message
                    $('.notice-error').remove();
                    $('.wrap h1').after('<div class="notice notice-error is-dismissible"><p>' + 
                        response.data.message + '</p></div>');
                }
            },
            error: function() {
                // Show error message
                $('.notice-error').remove();
                $('.wrap h1').after('<div class="notice notice-error is-dismissible"><p>' + 
                    perfumeAdvisor.errorMessage + '</p></div>');
            }
        });
    });
    
    // Handle question editing - redirect to add_new tab with edit_id
    $(document).on('click', '.edit-question', function(e) {
        e.preventDefault();
        var questionId = $(this).data('question-id');
        
        var currentUrl = new URL(window.location.href);
        currentUrl.searchParams.set('tab', 'add_new');
        currentUrl.searchParams.set('edit_id', questionId);
        window.location.href = currentUrl.toString();
    });
    
    // Handle form submission for both add and update
    $('.add-question-form form').on('submit', function(e) {
        e.preventDefault();
        
        var $form = $(this);
        var $submitButton = $form.find('button[type="submit"]');
        var originalButtonText = $submitButton.text();
        var isUpdate = $form.find('input[name="action"]').val() === 'update_question';
        
        // Show loading state
        $submitButton.prop('disabled', true).text(perfumeAdvisor.saving);
        
        // Submit form via AJAX
        $.ajax({
            url: perfumeAdvisor.ajaxurl,
            type: 'POST',
            data: {
                action: isUpdate ? 'perfume_advisor_update_question' : 'perfume_advisor_add_question',
                nonce: perfumeAdvisor.nonce,
                question_id: $('#question_id').val(),
                question_text: $('#question_text').val(),
                question_type: $('#question_type').val(),
                question_required: $('#question_required').is(':checked'),
                question_attribute: $('#question_attribute').val(),
                question_terms: $('#question_terms').val(),
                question_products: $('#question_products').val()
            },
            success: function(response) {
                if (response.success) {
                    // Show success message
                    $('.notice-success').remove();
                    $('.wrap h1').after('<div class="notice notice-success is-dismissible"><p>' + 
                        response.data.message + '</p></div>');
                    
                    if (isUpdate) {
                        // Update the question in the table
                        var $row = $('tr[data-question-id="' + response.data.question.id + '"]');
                        $row.find('td:first-child strong').text(response.data.question.text);
                        $row.find('td:nth-child(2)').text(response.data.question.type);
                        $row.find('td:nth-child(3)').text(response.data.question.required ? perfumeAdvisor.yes : perfumeAdvisor.no);
                        
                        // Switch back to list tab
                        $('.nav-tab[href*="tab=list"]').click();
                    } else {
                        // Add new question to the table
                        var $tbody = $('.questions-list table tbody');
                        var $noQuestions = $('.questions-list .no-questions');
                        
                        if ($noQuestions.length) {
                            $noQuestions.remove();
                            $tbody = $('<tbody></tbody>').appendTo('.questions-list table');
                        }
                        
                        var $row = $('<tr data-question-id="' + response.data.question.id + '"></tr>');
                        $row.append('<td><strong>' + response.data.question.text + '</strong></td>');
                        $row.append('<td>' + response.data.question.type + '</td>');
                        $row.append('<td>' + (response.data.question.required ? perfumeAdvisor.yes : perfumeAdvisor.no) + '</td>');
                        $row.append('<td>' +
                            '<a href="#" class="edit-question" data-question-id="' + response.data.question.id + '">' +
                            '<span class="dashicons dashicons-edit"></span></a> ' +
                            '<a href="#" class="delete-question" data-question-id="' + response.data.question.id + '">' +
                            '<span class="dashicons dashicons-trash"></span></a>' +
                            '</td>'
                        );
                        
                        $tbody.append($row);
                        
                        // Switch back to list tab
                        $('.nav-tab[href*="tab=list"]').click();
                    }
                    
                    // Reset form
                    $form[0].reset();
                    $('#question_id').remove();
                    $form.find('input[name="action"]').val('add_question');
                    $submitButton.text(perfumeAdvisor.addQuestion);
                    
                    // Hide attribute and terms fields
                    $('.attribute-row, .terms-row, .products-row').hide();
                } else {
                    // Show error message
                    $('.notice-error').remove();
                    $('.wrap h1').after('<div class="notice notice-error is-dismissible"><p>' + 
                        response.data.message + '</p></div>');
                }
            },
            error: function() {
                // Show error message
                $('.notice-error').remove();
                $('.wrap h1').after('<div class="notice notice-error is-dismissible"><p>' + 
                    perfumeAdvisor.errorMessage + '</p></div>');
            },
            complete: function() {
                // Reset button state
                $submitButton.prop('disabled', false).text(originalButtonText);
            }
        });
    });
    
    // Handle settings form submission
    $('#perfume-advisor-settings-form').on('submit', function(e) {
        e.preventDefault();
        
        var $form = $(this);
        var $submitButton = $form.find('button[type="submit"]');
        var originalButtonText = $submitButton.text();
        
        // Show loading state
        $submitButton.prop('disabled', true).text('در حال ذخیره...');
        
        // Submit form via AJAX
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'perfume_advisor_save_settings',
                nonce: perfumeAdvisor.nonce,
                settings: $form.serialize()
            },
            success: function(response) {
                if (response.success) {
                    // Show success message
                    $('.notice-success').remove();
                    $('.wrap h1').after('<div class="notice notice-success is-dismissible"><p>' + 
                        'تنظیمات با موفقیت ذخیره شد.' + '</p></div>');
                } else {
                    // Show error message
                    $('.notice-error').remove();
                    $('.wrap h1').after('<div class="notice notice-error is-dismissible"><p>' + 
                        (response.data.message || 'خطا در ذخیره تنظیمات') + '</p></div>');
                }
                $submitButton.prop('disabled', false).text(originalButtonText);
            },
            error: function() {
                // Show error message
                $('.notice-error').remove();
                $('.wrap h1').after('<div class="notice notice-error is-dismissible"><p>' + 
                    'خطا در ارتباط با سرور' + '</p></div>');
                $submitButton.prop('disabled', false).text(originalButtonText);
            }
        });
    });
    
    // Tab functionality for questions page
    $('.nav-tab-wrapper a').on('click', function(e) {
        e.preventDefault();
        var $this = $(this);
        var tab_id = $this.attr('href').split('tab=')[1];
        
        // Remove active class from all tabs and tab panes, then hide all tab panes
        $('.nav-tab').removeClass('nav-tab-active');
        $('.tab-pane').removeClass('active').hide();
        
        // Add active class to the clicked tab and show its corresponding tab pane
        $this.addClass('nav-tab-active');
        $('#' + tab_id + '_questions_tab').addClass('active').show().removeAttr('style');

        // Clear any existing notices
        $('.notice').remove();

        // Update URL hash to reflect tab change without full page reload
        if (history.pushState) {
            var newurl = window.location.protocol + "//" + window.location.host + window.location.pathname + '?page=perfume-advisor-questions&tab=' + tab_id;
            window.history.pushState({path:newurl}, '', newurl);
        }
    });

    // Activate tab on page load based on URL parameter
    var urlParams = new URLSearchParams(window.location.search);
    var initialTab = urlParams.get('tab');
    
    // Hide all tab panes initially
    $('.tab-pane').hide();

    // Default to 'list' tab if no tab parameter is present or if the parameter is not a valid tab
    var tabToActivate = 'list';
    if (initialTab && $('.nav-tab[href*="tab=' + initialTab + '"]').length) {
        tabToActivate = initialTab;
    }

    $('.nav-tab[href*="tab=' + tabToActivate + '"]').addClass('nav-tab-active');
    $('#' + tabToActivate + '_questions_tab').addClass('active').show().removeAttr('style');

    // Initialize tooltips
    $('[data-tooltip]').tooltipster({
        theme: 'tooltipster-light',
        side: 'left',
        maxWidth: 300
    });
    
    // Make terms and products selectable
    $('#terms, #products').select2({
        width: '100%',
        placeholder: function() {
            return $(this).data('placeholder');
        }
    });

    // Initialize Select2 for product selection in matching form
    if ($('#product_ids').length) {
        $('#product_ids').select2({
            placeholder: perfumeAdvisor.selectProducts,
            allowClear: true,
            multiple: true,
            language: {
                noResults: function() {
                    return perfumeAdvisor.noResultsFound;
                }
            }
        });
    }

    // Initialize Select2 for answer selection in matching form
    if ($('#answer_value').length) {
        $('#answer_value').select2({
            placeholder: perfumeAdvisor.selectAnswer,
            allowClear: true,
            language: {
                noResults: function() {
                    return perfumeAdvisor.noResultsFound;
                }
            }
        });
    }

    // Initialize Select2 for product selection
    if ($('.perfume-advisor-product-select').length) {
        $('.perfume-advisor-product-select').select2({
            ajax: {
                url: perfume_advisor_params.ajax_url,
                dataType: 'json',
                delay: 250,
                data: function(params) {
                    return {
                        q: params.term,
                        action: 'perfume_advisor_search_products',
                        nonce: perfume_advisor_params.nonce
                    };
                },
                processResults: function(data) {
                    return {
                        results: data.data
                    };
                },
                cache: true
            },
            minimumInputLength: 2,
            placeholder: perfume_advisor_params.i18n.search_products,
            language: {
                noResults: function() {
                    return perfume_advisor_params.i18n.no_products_found;
                }
            }
        });
    }

    // Initialize Select2 for answer selection
    if ($('.perfume-advisor-answer-select').length) {
        $('.perfume-advisor-answer-select').select2({
            placeholder: perfume_advisor_params.i18n.select_answer,
            allowClear: true
        });
    }

    // Handle attribute terms selection
    if ($('#question_attribute').length) {
        $('#question_attribute').on('change', function() {
            var attribute = $(this).val();
            var $termsSelect = $('#question_terms');
            
            if (!attribute) {
                $termsSelect.html('<option value="">' + perfume_advisor_params.i18n.select_attribute_first + '</option>').prop('disabled', true);
                return;
            }
            
            $.ajax({
                url: perfume_advisor_params.ajax_url,
                type: 'POST',
                data: {
                    action: 'perfume_advisor_get_attribute_terms',
                    attribute: attribute,
                    nonce: perfume_advisor_params.nonce
                },
                beforeSend: function() {
                    $termsSelect.prop('disabled', true);
                },
                success: function(response) {
                    if (response.success) {
                        var options = '<option value="">' + perfume_advisor_params.i18n.select_terms + '</option>';
                        response.data.forEach(function(term) {
                            options += '<option value="' + term.id + '">' + term.text + '</option>';
                        });
                        $termsSelect.html(options).prop('disabled', false);
                    } else {
                        alert(response.data.message);
                    }
                },
                error: function() {
                    alert(perfume_advisor_params.i18n.error_loading_terms);
                }
            });
        });

        // Initialize Select2 for terms selection
        $('#question_terms').select2({
            placeholder: perfume_advisor_params.i18n.select_terms,
            allowClear: true,
            multiple: true
        });
    }
}); 