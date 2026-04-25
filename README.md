# WordPress Starter Kit for Bricks Builder
---

**Don't clone this repository.**

## What's included
Open the ```/plugins/``` folder to check the included plugins.

## Quick Docs: How to start & develop with this kit

Beside the [official WordPress developer resources](https://developer.wordpress.org/), this is what you should know to begin:

* Download this repository (don't clone it). Download it using the button: Code -> Download Zip. Use this files in your new project.
* If you need to add custom functionality for the site you are building, that cannot be done using the included site builder, then either use the custom plugin included: **wes-custom-functionality** (based on [wppb](https://wppb.me/)). Or use ```/bricks-child/``` theme to include your custom functionality.
* Use your local dev enviroment to develop your new site. If you don't have a local dev enviroment, we recommend [Local WP](https://localwp.com/).
* Configure your local WordPress instance with [WP_DEBUG enabled](https://developer.wordpress.org/themes/getting-started/setting-up-a-development-environment/#wp_debug).
* Update any development plugin included. Then enable the ones you want to use during development.
* Only enable and use the included plugins. Don't add nor use any other plugin. More on that below, at the rules and guidelines.
* Start coding.

## Rules and Guidelines
* Code and comment in english. The site itself (front-end) could be targeted to spanish, portuguese or english speakers, that's fine. But your code and comments must always be written in english, so anyone from any country can understand and work over your code too.
* You must follow [WordPress codings standars](https://make.wordpress.org/core/handbook/best-practices/coding-standards/). That's why we include the .editorconfig file. Use it. There's support for it on many code editors and IDEs. [Check it out](https://editorconfig.org/). Make your code editor or IDE honor the included [EditorConfig](https://editorconfig.org/) rules included in this kit.
* You must use the included site builder plugin.
* You can't add third party plugins **without asking us first**. Any third party plugin not included in this repository must be approved by us first. This is mandatory. Failure to follow this rule will result on your work to be rejected by us.
* If you need to generate a form for the site (any purpose), you must use the included plugin for it. No other contact form plugin is allowed.
* If you need to generate [post metaboxes](https://www.advancedcustomfields.com/resources/adding-fields-posts/), an [Options Page](https://www.advancedcustomfields.com/resources/options-page/) or [Gutenberg Blocks](https://www.advancedcustomfields.com/resources/blocks/), use the included plugin Advanced Custom Fields Pro.
* If you use Advanced Custom Fields Pro, then enable [Local JSON caching](https://www.advancedcustomfields.com/resources/local-json/).
* If you use Advanced Custom Fields Pro, and don't need to query ACF meta data with WP_Query/meta_query, then enable ACF Extended plugin and enable his [Single Meta Save feature](https://www.acf-extended.com/features/modules/single-meta-save). If you are only using ACF data inside a loop or a single post, don't use advanced meta_query queries referencing ACF data, and use ACF repeaters extensibly, then enabling this feature it's mandatory. If you need to use ACF data with WP queries, then enable [Single Meta Save feature](https://www.acf-extended.com/features/modules/single-meta-save) only in those areas (post types, options pages) than aren't needed for meta queries and/or mark those fields needed for queries as "Save as Individual Meta".
* Use the proper WP's functions and methods to work in a theme or plugin. Again, [WordPress codings standars](https://make.wordpress.org/core/handbook/best-practices/coding-standards/) are a must. So you need to know the [Template Hierarchy](https://developer.wordpress.org/themes/basics/template-hierarchy/), use the proper calls to [include styles and scripts](https://developer.wordpress.org/themes/basics/including-css-javascript/) in your theme, know [the loop](https://developer.wordpress.org/themes/basics/the-loop/), use [get_stylesheet_directory_uri()](https://developer.wordpress.org/reference/functions/get_stylesheet_directory_uri/) to call the proper URL to include an image, etc. If WordPress provides a way to work with something, doing that "something" in a non-WordPress way is not ok.
* Test your theme with the recommended [WordPress unit test](https://codex.wordpress.org/Theme_Unit_Test). Download the sample [XML with data](https://raw.githubusercontent.com/WPTT/theme-unit-test/master/themeunittestdata.wordpress.xml), and import it into your WordPress development instance using WordPress default importer inside the wp-admin: Tools -> Import -> WordPress. Check the "Download and import file attachments" before importing. Make sure your theme looks correct with simple pages, posts, etc.
* It's ok to copy snippets and solutions from the internet. But please: keep in mind the license of the code you're copying. When pasting a snippet, make sure to format it to use WP's coding standards. Don't mix code indentention. Using .editorconfig is for that. The use of [PHP_CodeSniffer](https://github.com/squizlabs/PHP_CodeSniffer) with [WordPress Coding Standards](https://github.com/WordPress/WordPress-Coding-Standards) ([2](https://gist.github.com/nunomorgadinho/b2d8e5b8f5fec5b0ed946b24fa288a91)) will certainly help, and better if you can setup your code editor to do it automatically for your. Correctly [prefix every function](https://developer.wordpress.org/plugins/plugin-basics/best-practices/#prefix-everything) you copy with a unique prefix for the project, don't keep the name copied from the internet as is.
* Don't make a mess with your code. This will help others and the future you to maintain the project easily. Use PHP includes and separate segments of code according to their functionality. Don't throw every snippet in a main plugin file an make a one big mess inside. Don't make a gigantic unique CSS or JS file that no-one can understand after. Use separate CSS/JS files depending of the functionality. Use CSS variables. Enqueque your files properly and just when they are needed.
* Don't hard-code URLs in your theme or plugins. Ever. Don't forget that WP provides [wp_add_inline_script](https://developer.wordpress.org/reference/functions/wp_add_inline_script/) to expose global variables for use inside your Javascript.

## To Production
* Delete any unused installed plugin or theme.
* Delete any post or page not in use. Don't forget to delete data (posts, pages, attachments) imported with the WordPress unit test XML.
* Disable and delete any development plugin installed.
* Disable WP_DEBUG in your wp-config.php
* Don't forget to change any email (sender/"from" and recipient/"to" addresses) in your forms and WooCommerce if was used. Recipient should be pointing to the correct client's email address.
* Check that the emails are properly configured. Use [Stop WP Emails Going to Spam](https://wordpress.org/plugins/stop-wp-emails-going-to-spam/) to set the correct email address for the email sent from WP (anything@thefinaldomain.tld), and make sure the correct SPF records exists and are valid for the final domain/server. Send a test email with [Check & Log Email](https://wordpress.org/plugins/check-email/) to verify everything is set up correctly.
* Create a different administrator user for the final client. Don't give the client the same user as the Agency is using.
* Done.

## Questions?
Please, ask at #devs in Slack.
