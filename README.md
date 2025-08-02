# Product Recommendation Advisor

A powerful WordPress plugin for WooCommerce that helps customers find the perfect products through an interactive questionnaire system. Create custom questions and match them with products to provide personalized recommendations.

## Features

* 🎯 Interactive questionnaire system
* 🔄 Dynamic product recommendations
* 🛍️ WooCommerce integration
* 📱 Responsive design
* 🌐 Multi-language support
* ⚙️ Easy to customize
* 📊 Product attribute matching
* 💰 Price range filtering
* 🎨 Beautiful UI/UX
* 🔒 Secure and reliable

## Requirements

* WordPress 5.0 or higher
* PHP 7.2 or higher
* WooCommerce 3.0 or higher
* MySQL 5.6 or higher

## Translation

The plugin comes with full translation support:

- English (default)
- Persian (Farsi)

You can add more translations by adding .po/.mo files to the `languages` directory.

## Installation

1. Download the plugin zip file
2. Go to WordPress admin panel > Plugins > Add New
3. Click "Upload Plugin" and select the downloaded zip file
4. Click "Install Now"
5. After installation, click "Activate"

## Configuration

1. Go to WordPress admin panel > Product Recommendation Advisor
2. Click on "Settings" to configure the plugin
3. Set up your questions and product matches
4. Customize the appearance and behavior
5. Save your settings

## Usage

### Adding Questions

1. Go to Product Recommendation Advisor > Questions
2. Click "Add New Question"
3. Enter your question text
4. Select the question type (Single Choice, Multiple Choice, etc.)
5. Add possible answers
6. Match answers with products
7. Save the question

### Displaying the Form

Use one of these methods to display the recommendation form:

1. **Shortcode Method**
   ```
   [product_advisor_form]
   ```

2. **Template Method**
   Create a new page and select "Product Recommendation Form Template" as the template.

### Customizing the Form

You can customize the form's appearance by:

1. Modifying the CSS in `assets/css/product-recommendation-advisor.css`
2. Using the built-in customization options in the admin panel
3. Adding custom classes to the form elements

## Development

### File Structure

```
product-recommendation-advisor/
├── admin/
│   ├── css/
│   │   └── product-recommendation-advisor-admin.css
│   └── js/
│       └── product-recommendation-advisor-admin.js
├── assets/
│   ├── css/
│   │   └── product-recommendation-advisor.css
│   └── js/
│       └── product-recommendation-advisor.js
├── includes/
│   ├── form-handler.php
│   └── product-recommender.php
├── languages/
├── templates/
│   └── product-advisor-template.php
├── product-recommendation-advisor.php
└── README.md
```

### Hooks and Filters

The plugin provides several hooks and filters for customization:

```php
// Filter to modify product recommendations
add_filter('product_recommendation_advisor_recommendations', 'custom_recommendations', 10, 2);

// Action before form submission
add_action('product_recommendation_advisor_before_submit', 'custom_before_submit');

// Action after form submission
add_action('product_recommendation_advisor_after_submit', 'custom_after_submit');
```

## Contributing

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add some amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## Support

For support, please:

1. Check the documentation
2. Search existing issues
3. Create a new issue if needed

## 📜 License

This project is licensed under a **Custom NonCommercial Attribution License**.

- ✅ Free to use, modify, and share for **non-commercial** and **educational** purposes
- ❌ **Commercial use, resale, or monetization** is strictly prohibited without prior written consent
- 📛 Attribution required: Developed by Mohammad Nasser Haji Hashemabad (https://mohammadnasser.com)

📬 For commercial licensing or inquiries: [info@mohammadnasser.com](mailto:info@mohammadnasser.com)


## Credits
Developed by [Mohammad Nasser Haji Hashemabad](https://mohammadnasser.com) 
* Author: محمد ناصر حاجی هاشم آباد Mohammad Nasser Haji Hashemabad
* Email: info@mohammadnasser.com
* Website: mohammadnasser.com


## Changelog

### 1.0.0

* Initial release
* Basic question and answer system
* Product matching functionality
* WooCommerce integration
* Responsive design
* Multi-language support

## Links

- [LinkedIn](https://ir.linkedin.com/in/naserhha)
- [GitHub](https://github.com/naserhha)
- [Website](https://mohammadnasser.com/)
- [Product Quiz Repository](https://github.com/naserhha/product-recommendation-advisor)
