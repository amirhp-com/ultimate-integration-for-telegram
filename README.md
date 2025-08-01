# Ultimate Integration for Telegram
**Ultimate Integration for Telegram** is the ultimate solution for connecting WordPress and WooCommerce to Telegram. With this plugin, you can send customized notifications to Telegram channels, groups, bots, or private chats. Packed with features like a Markdown Validator, extensive macros, and a notifications manager, this plugin ensures your Telegram automation is seamless and efficient.

## About the Plugin
**Ultimate Integration for Telegram** is the most versatile plugin for connecting WordPress and WooCommerce to Telegram. Easily send personalized notifications to Telegram channels, groups, bots, or private chats. The plugin offers powerful features like dynamic translation management, gettext replacement, and string customization to tailor messages for your audience. Designed for flexibility, it fully supports WooCommerce core and popular add-ons such as WooCommerce Subscription, WooCommerce Membership, WooCommerce Booking, WooCommerce Points and Reward, and WooCommerce Multi-Currency. With extensive developer hooks and filters, it’s open for seamless customization and new integrations. Whether for business, e-commerce, or automation, this plugin is the ultimate solution for managing Telegram notifications directly from your WordPress site.


---

## Features
- **Comprehensive Notifications Panel**: Manage notifications for WordPress, WooCommerce, and supported add-ons with ease.
- **Send Notifications Anywhere**: Send alerts to Telegram channels, groups, bots, or private chats.
- **WooCommerce & Add-on Support**: Fully supports WooCommerce and popular extensions, including:
  - WooCommerce Subscriptions
  - WooCommerce Memberships
  - WooCommerce Bookings
  - WooCommerce Points and Rewards
  - WooCommerce Multi-Currency
- **Built-in Jalali (Shamsi) Date Support**: Automatically convert Gregorian dates to Jalali for Persian-speaking audiences.
- **Import/Export Notifications**: Easily migrate or duplicate notification settings across websites with JSON import/export.
- **Macros for Dynamic Content**: Customize notifications and buttons with an extensive list of predefined macros.
- **Built-in Translation Manager**: Dynamically translate and replace strings using the built-in gettext manager.
- **String Replacement Tool**: Replace placeholders or dynamic text in notifications effortlessly.
- **HTML and Markdown Support**: Create visually appealing messages with flexible formatting options.
- **Markdown Validator**: Validate messages for correct Markdown formatting to prevent rendering issues in Telegram.
- **Custom Buttons**: Add interactive buttons to your Telegram messages for better user engagement.
- **Developer-Friendly**: Extend functionality using extensive hooks and filters for full customization.

---


## Available Macros
Macros are placeholders that get dynamically replaced with actual values in your Telegram messages and buttons. Below is a sample list of macros available in the plugin.

You can find the complete list of macros in the plugin under the Notifications > Available Macros section.

### General Macros
- `{current_date}`: Current date.
- `{current_time}`: Current time.
- `{current_date_time}`: Current date and time.
- `{current_user_id}`: Current user ID.
- `{current_user_name}`: Current user name.
- `{site_name}`: Website name.
- `{site_url}`: Website URL.

### WooCommerce Order Details
- `{order_id}`: Order ID.
- `{order_number}`: Order number.
- `{order_status}`: Order status.
- `{order_total}`: Total order amount.
- `{order_subtotal}`: Order subtotal amount.
- `{order_tax_total}`: Total tax amount.
- `{order_discount_total}`: Total discount amount.
- `{customer_name}`: Customer name.
- `{customer_email}`: Customer email.
- `{customer_phone}`: Customer phone number.

### WooCommerce Order URLs
- `{edit_url}`: Order edit URL (Admin).
- `{view_url}`: Order view URL (Customer).
- `{pay_url}`: Order payment URL.
- `{cancel_url}`: Order cancel URL.
- `{thank_you_url}`: Order thank-you page URL.
- `{tracking_url}`: Order tracking URL.

### WooCommerce Add-ons
- **WooCommerce Subscription**:
  - `{subscription_id}`: Subscription ID.
  - `{subscription_status}`: Subscription status.
  - `{subscription_total}`: Subscription total amount.
  - `{subscription_start_date}`: Subscription start date.
  - `{subscription_end_date}`: Subscription end date.
- **WooCommerce Membership**:
  - `{membership_plan}`: Membership plan name.
  - `{membership_status}`: Membership status.
  - `{membership_start_date}`: Membership start date.
  - `{membership_end_date}`: Membership expiration date.
- **WooCommerce Booking**:
  - `{booking_id}`: Booking ID.
  - `{booking_status}`: Booking status.
  - `{booking_date}`: Booking date.
  - `{booking_start_time}`: Booking start time.
  - `{booking_end_time}`: Booking end time.

---

## Sample Messages
Here are some examples of how you can use macros in your notifications:

### Example 1: New Order Notification
**Message:**
```
🎉 New Order Received! 🎉

Order ID: {order_id}
Customer: {customer_name}
Total: {order_total}

View Order: {edit_url}
```

### Example 2: Subscription Renewal Reminder
**Message:**
```
🔔 Subscription Renewal Alert 🔔

Subscription ID: {subscription_id}
Plan: {membership_plan}
Next Payment Date: {subscription_end_date}
```

### Example 3: Booking Confirmation
**Message:**
```
✅ Booking Confirmed ✅

Booking ID: {booking_id}
Date: {booking_date}
Start Time: {booking_start_time}
End Time: {booking_end_time}
```

---

## Supported Add-ons and Plugins
In addition to WordPress and WooCommerce core, the plugin supports:
- WooCommerce Subscription
- WooCommerce Membership
- WooCommerce Booking
- WooCommerce Points and Reward
- WooCommerce Multi-Currency
- Contact Form 7 (coming soon)
- Gravity Form (coming soon)

The plugin is also open to integration with other plugins via hooks and filters.

---

## How to Setup the Plugin
1. **Install the Plugin**: Upload and activate it via the WordPress admin dashboard.
2. **Configure General Settings**:
   - Go to **Settings > Telegram**.
   - Enter your Telegram Bot Token and username.
   - Enable or disable options like Jalali date conversion and admin bar link.
3. **Connect the Telegram Bot**:
   - Click "Connect Webhook" to enable Telegram integration.
   - Use "Send Test Message" to verify the setup.
4. **Create Notifications**:
   - Navigate to the **Notifications** tab to manage or add new notifications.
   - Customize the message, macros, and formatting for each notification type.

---

## How to Setup a Telegram Bot
1. Open Telegram and search for **@BotFather**.
2. Start a chat and use the `/newbot` command to create a bot.
3. Follow the instructions to name your bot and get the **Bot Token**.
4. Paste the token into the plugin’s settings under **Your Bot Token**.
5. Use `/setprivacy` in BotFather to allow the bot to receive messages.

---

## How to Add Custom Notifications
1. Go to the **Notifications** tab in the plugin settings.
2. Select an existing notification or create a new one.
3. Customize the message and macros for each event (e.g., WooCommerce order created, status changed, email sent).
4. Save the notification to enable it.

---

## Import/Export Notifications
- Use the **Import/Export** button in the Notifications tab to transfer settings.
- Export configurations as JSON to duplicate them on another website.
- Import JSON files to quickly set up predefined notifications.

---

## Disclaimer and Warranty
This plugin is provided "as is" without any warranties, express or implied. While every effort has been made to ensure reliability and security, the developers are not responsible for any issues arising from its use. Always test in a staging environment before deploying to production.

---

## Contribution and Support
We welcome contributions to improve the plugin! If you have feature requests, bug reports, or suggestions, please create a GitHub issue or pull request.

For support, contact us at **[support (@) pigment (.) dev](mailto:support@pigment.dev)**.

---

## License
This plugin is licensed under the **GPL v2.0 or later**. You are free to use, modify, and distribute the plugin under these terms.

---

## Changelog
```md
= v1.5.0 | 2025-08-02 | 1404-05-11 =
* New UI and improved user experience throughout the plugin.
* Added import/export feature for notifications and settings.
* Added default message content and a list of default messages.
* Changed the method for saving settings in the database for better reliability.
* Added support for custom Telegram bot servers.
* Added channel agent feature for advanced channel management.
* Introduced a step-by-step setup wizard on the plugin settings page.
* Added support for notification and notification categories to have descriptions.
* Added ability to customize recipients per message.
* Enhanced Markdown validator for more robust message formatting.
* Merged translations and string replacement panels into a single translation tool.
* Switched to offloaded Font Awesome v7 for improved performance and up-to-date icon support.
* Improved stability and minor bug fixes.
* Enhanced security checks for safer operation.

= v1.2.0 | 2025-06-07 | 1404-03-17 =
* Added New Integration: WordPress Comments
* Added New Integration: WooCommerce Comments
* Added Pre-defined Notification Text
* Added Pre-defined Notifications List on first setup
* Added Setup Wizard & Enhanced Setup UIX

= v1.1.0 | 2025-03-17 | 1403-12-27 =
* Set minimum PHP required to 7.4
* Fixed Markdown Validator

= v1.0.0 | 2025-01-20 | 1403-11-01 =
* Initial release of Ultimate Integration for Telegram.
* Telegram bot integration with webhook support.
* Notification macros and string replacement features.
* Full WooCommerce integration.
```