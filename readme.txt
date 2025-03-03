=== Ultimate Integration for Telegram ===
Version: 1.0.7
Stable tag: 1.0.7
Author: BlackSwan
Donate link: https://amirhp.com/contact/
Author URI: https://amirhp.com/landing
Plugin URI: https://wordpress.org/plugins/ultimate-integration-for-telegram/
Contributors: amirhpcom, blackswanlab, pigmentdev
Tags: woocommerce, telegram, notification, automation
Tested up to: 6.7
WC requires at least: 5.0
WC tested up to: 9.7
Text Domain: ultimate-integration-for-telegram
Domain Path: /languages
Copyright: (c) BlackSwanDev, All rights reserved.
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Integrate Telegram with WordPress & WooCommerce. Send notifications, manage translations, and automate with Jalali date support.

== Description ==

Integrate Telegram with WordPress, WooCommerce, and a wide range of plugins. Send customized notifications to channels, groups, bots, or private chats with built-in advanced translation and string replacement tools.

Ultimate Integration for Telegram is the most versatile plugin for sending notifications from WordPress and WooCommerce to Telegram. With features like translation management, string replacement, macros, and markdown validation, this plugin is the ultimate solution for Telegram automation.

== Features ==
- Send notifications to Telegram channels, groups, bots, or private chats.
- Full support for WooCommerce and popular add-ons (Subscriptions, Memberships, etc.).
- Built-in Jalali (Shamsi) date conversion.
- Import/export notification settings with ease.
- Advanced macros for dynamic content in messages and buttons.
- Test and validate messages with the Markdown validator.
- Developer-friendly with extensive hooks and filters.

== Jalali Date Converter & Timezone Handling ==
This plugin includes an optional **Jalali Date Converter** feature, which allows users to display dates in the Jalali (Persian) calendar format. If you enable this feature on the plugin's settings page, the following changes will occur:

= Timezone Adjustment =
- To ensure accurate Jalali date conversion, the plugin temporarily sets the default timezone to `Asia/Tehran` (or the timezone specified in your WordPress settings) when converting dates.
- This adjustment is **only active when the Jalali Date Converter is enabled** and does not affect other parts of your site.
- If the Jalali Date Converter is **disabled**, no timezone changes are made, and WordPress will continue to use its default UTC timezone.

= Why This is Necessary =
- The Jalali calendar relies on specific timezone settings for accurate date conversion. Without this adjustment, the converted dates may not align correctly with the Persian calendar.

= Important Notes =
- If you use other plugins or custom code that rely on the default UTC timezone, ensure compatibility before enabling the Jalali Date Converter.
- The timezone adjustment is **temporary** and only applies during date conversion processes. It does not permanently alter your WordPress timezone settings.

== Third-Party & External Resources Used ==
This plugin utilizes the following third-party libraries to enhance functionality:

1. Tippy.js v6.3.7
  - **Description**: Tippy.js is a lightweight, customizable tooltip library. It is used in this plugin to provide user-friendly tooltips for better UI/UX.
  - **License**: MIT License
  - **Source**: [https://atomiks.github.io/tippyjs/](https://atomiks.github.io/tippyjs/)

2. jQuery Repeater v1.2.2
  - **Description**: jQuery Repeater is a library that allows dynamic addition and removal of form fields. It is used in this plugin to manage repeatable input fields.
  - **License**: MIT License
  - **Source**: [https://github.com/DubFriend/jquery.repeater](https://github.com/DubFriend/jquery.repeater)

3. Font Awesome (Free Version)
  - **Description**: Font Awesome is used to provide icons ONLY on the plugin's settings page for a better user experience.
  - **Source**: [https://github.com/FortAwesome/Font-Awesome](https://github.com/FortAwesome/Font-Awesome)
  - **License**: [Font Awesome Free License](https://fontawesome.com/license/free).
  - **No Account Required**: No additional setup or account is needed.

== Screenshots ==

1. Setting > General
2. Setting > Notifications
3. Setting > Translation Panel

== Installation ==
1. Upload the plugin files to the `/wp-content/plugins/` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Go to **Settings > Telegram** to configure the plugin.

== Frequently Asked Questions ==
= What is required to use this plugin? =
You need a Telegram bot. Follow the instructions in the plugin to set it up via BotFather.

= How can I contribute to this plugin? =
You can help us improve our works by committing your changes to [blackswandevcom/ultimate-integration-for-telegram](https://github.com/blackswandevcom/ultimate-integration-for-telegram)

= Does the plugin support WooCommerce add-ons? =
Yes, it supports major WooCommerce add-ons like WooCommerce Subscription, WooCommerce Membership, and WooCommerce Booking.

== Upgrade Notice ==
= v1.0.0 | 2025-01-20 | 1403-11-01 =
Upgrade to enjoy the latest features and stability improvements.

== Credits ==
Developed at [BlackSwanDev](https://blackswandev.com/).
Lead Developer: [AmirhpCom](https://amirhp.com/).

== Changelog ==
For full changelog please view [Github Repository (github.com/pigment-dev/ultimate-integration-for-telegram)](https://github.com/pigment-dev/ultimate-integration-for-telegram)

= v1.0.0 | 2025-01-20 | 1403-11-01 =
* Initial release of Ultimate Integration for Telegram.
* Telegram bot integration with webhook support.
* Notification macros and string replacement features.
* Full WooCommerce integration.
