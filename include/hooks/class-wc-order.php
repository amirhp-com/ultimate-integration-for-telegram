<?php
/*
* @Author: Amirhossein Hosseinpour <https://amirhp.com>
* @Last modified by: amirhp-com <its@amirhp.com>
* @Last modified time: 2025/01/19 22:52:03
*/

use BlackSwan\Telegram\Notifier;

class class_wc_order extends Notifier {
  public $notif_id = [];
  public $wc_emails = [];
  public $wc_statuses = [];
  public function __construct() {
    parent::__construct();
    $this->wc_emails = wp_list_pluck(WC()->mailer()->get_emails(), "title", "id");
    $this->wc_statuses = wc_get_order_statuses();

    $this->notif_id = [
      "wc_new_order",
      "wc_order_saved",
      "wc_trash_order",
      "wc_delete_order",
      "wc_order_refunded",
      "wc_payment_complete",
      "wc_checkout_processed",
      "wc_checkout_api_processed",
      "wc_order_status_changed",
      "wc_mail_sent",
    ];

    foreach ($this->wc_statuses as $slug => $name) {
      $slug = $this->remove_status_prefix($slug);
      $this->notif_id[] = "wc_order_status_to_{$slug}";
    }

    foreach ($this->wc_emails as $slug => $name) {
      $this->notif_id[] = "wc_mail_{$slug}";
    }

    $this->notif_id = apply_filters("blackswan-telegram/helper/woocommerce-orders/macro-available-notif-list", $this->notif_id);

    if (!empty($this->get_notifications_by_type("wc_new_order"))) {
      add_action("woocommerce_new_order", array($this, "wc_new_order"), 10, 1);
    }
    if (!empty($this->get_notifications_by_type("wc_payment_complete"))) {
      add_action("woocommerce_payment_complete", array($this, "wc_payment_complete"), 10, 1);
    }
    if (!empty($this->get_notifications_by_type("wc_order_refunded"))) {
      add_action("woocommerce_order_refunded", array($this, "wc_order_refunded"), 10, 1);
    }
    if (!empty($this->get_notifications_by_type("wc_checkout_processed"))) {
      add_action("woocommerce_checkout_order_processed", array($this, "wc_checkout_processed"), 10, 3);
    }
    if (!empty($this->get_notifications_by_type("wc_checkout_api_processed"))) {
      add_action("woocommerce_store_api_checkout_order_processed", array($this, "wc_checkout_api_processed"), 10, 1);
    }
    if (!empty($this->get_notifications_by_type("wc_order_status_changed"))) {
      add_action("woocommerce_order_status_changed", array($this, "wc_order_status_changed"), 10, 4);
    }
    foreach ($this->wc_statuses as $slug => $name) {
      $slug = $this->remove_status_prefix($slug);
      $list_notif = $this->get_notifications_by_type("wc_order_status_to_{$slug}");
      if ($list_notif && !empty($list_notif)) {
        add_action("woocommerce_order_status_{$slug}", function($order_id, $order, $status_transition) use ($list_notif){
          $status_from = isset($status_transition['from']) ? $status_transition['from'] : "";
          $status_to = isset($status_transition['to']) ? $status_transition['to'] : "";
          foreach ((array) $list_notif as $notif) @$this->send_telegram_msg($notif->config->message, $notif->config->btn_row1, __CLASS__, [
            "current_hook" => current_action(),
            "order_status_prev" => $status_from,
            "order_status_new" => $status_to,
            "order_id" => $order_id,
          ], $notif->config->html_parser, false);
        }, 10, 3);
      }
    }

    if (!empty($this->get_notifications_by_type("wc_trash_order"))) {
      add_action("woocommerce_trash_order", array($this, "wc_trash_order"), 10, 1);
    }
    if (!empty($this->get_notifications_by_type("wc_delete_order"))) {
      add_action("woocommerce_delete_order", array($this, "wc_delete_order"), 10, 1);
    }
    if (!empty($this->get_notifications_by_type("wc_order_saved"))) {
      add_action("woocommerce_update_order", array($this, "wc_order_saved"), 10, 1);
    }
    add_action("woocommerce_email_sent", array($this, "send_notification_on_email_send"), 10, 3);

    add_filter("blackswan-telegram/notif-panel/notif-macro-list", array($this, "add_custom_macros"), 31, 2);
    add_filter("blackswan-telegram/helper/translate-pairs", array($this, "translate_params_custom"), 10, 5);
  }
  public function send_notification_on_email_send($wp_mail_return, $email_id, $email_obj) {
    if (!is_object($email_obj)) { return; }  // Ensure $email_obj is an object before accessing it.
    $list_notif = [];
    foreach ($this->wc_emails as $slug => $name) {
      if ($email_id == $slug) {
        $list_notif = $this->get_notifications_by_type("wc_mail_{$slug}");
      }
    }

    $list_notif = array_merge($list_notif, $this->get_notifications_by_type("wc_mail_sent"));

    // Get the associated order if available
    $order_id = false;
    if (isset($email_obj->object) && $email_obj->object instanceof \WC_Order) {
      $order_id = $email_obj->object->get_id();
    }
    if (!$order_id) return;
    foreach ((array) $list_notif as $notif) {
      @$this->send_telegram_msg(
        $notif->config->message,
        $notif->config->btn_row1,
        __CLASS__,
        array(
          "current_hook" => current_action(),
          "order_id"     => $order_id,
          "WC_EMAIL"     => array(
            "email_id"           => $email_id,
            "email_from_name"    => $email_obj->get_from_name(),
            "email_from_address" => $email_obj->get_from_address(),
            "email_attachments"  => wp_json_encode($email_obj->get_attachments()),
            "email_title"        => $email_obj->get_title(),
            "email_recipient"    => $email_obj->get_recipient(),
            "email_body_html"    => $email_obj->get_content_html(),
            "email_body_text"    => $email_obj->get_content_plain(),
            "email_subject"      => $email_obj->get_subject(),
          ),
        ),
        $notif->config->html_parser,
        false
      );
    }
  }
  #region macro declaration >>>>>>>>>>>>>
  public function translate_params_custom($pairs, $msg, $ref, $ex, $def) {
    // if we are sending request from this class, then handle macro
    if (in_array($ref, [__CLASS__, "SANITIZE_BTN", "SANITIZE_URL"]) && !empty($ex["order_id"])) {
      $order_id = $ex["order_id"];
      $order = wc_get_order($order_id);
      $new_macros = array(
        // Order Details
        "order_id"                   => $order->get_id(), // Order ID
        "order_number"               => $order->get_order_number(), // Order Number
        "order_key"                  => $order->get_order_key(), // Order Key
        "order_status_raw"           => $order->get_status(), // Order Status
        "order_status_prev"          => isset($ex["order_status_prev"]) ? wc_get_order_status_name( $ex["order_status_prev"] ) : "",
        "order_status_new"           => isset($ex["order_status_new"]) ? wc_get_order_status_name( $ex["order_status_new"] ) : "",
        "order_status"               => wc_get_order_status_name($order->get_status()), // Order Status
        "order_total"                => $order->get_total(), // Order Total Amount
        "order_subtotal"             => $order->get_subtotal(), // Order Subtotal Amount
        "order_discount_total"       => $order->get_total_discount(), // Order Discount Amount
        "order_tax_total"            => $order->get_total_tax(), // Order Tax Amount
        "order_shipping_total"       => $order->get_shipping_total(), // Order Shipping Amount
        "order_fees_total"           => $order->get_total_fees(), // Order Fees Total (if applicable, custom method needed)
        "order_currency"             => $order->get_currency(), // Order Currency
        "order_payment_method"       => $order->get_payment_method(), // Order Payment Method
        "order_payment_method_title" => $order->get_payment_method_title(), // Payment Method Title
        "transaction_id"             => $order->get_transaction_id(), // Transaction ID
        "order_date"                 => $order->get_date_created() ? $order->get_date_created()->date('Y-m-d H:i:s') : '', // Order Creation Date
        "order_jalali_date"          => pu_jdate('Y/m/d H:i:s', strtotime($order->get_date_created()), "", "local", "en"), // Order Creation Date (Jalali)
        "order_modified_date"        => $order->get_date_modified() ? $order->get_date_modified()->date('Y-m-d H:i:s') : '', // Order Last Modified Date
        "order_jalali_modified"      => pu_jdate('Y/m/d H:i:s', strtotime($order->get_date_modified()), "", "local", "en"), // Order Last Modified Date (Jalali)
        "order_date_completed"       => $order->get_date_completed() ? $order->get_date_completed()->date('Y-m-d H:i:s') : '', // Order Completed Date
        "order_jalali_completed"     => pu_jdate('Y/m/d H:i:s', strtotime($order->get_date_completed()), "", "local", "en"), // Order Completed Date (Jalali)
        "order_date_paid"            => $order->get_date_paid() ? $order->get_date_paid()->date('Y-m-d H:i:s') : '', // Order Paid Date
        "order_jalali_paid"          => pu_jdate('Y/m/d H:i:s', strtotime($order->get_date_paid()), "", "local", "en"), // Order Paid Date (Jalali)
        "order_customer_ip"          => $order->get_customer_ip_address(), // Customer IP Address
        "order_user_agent"           => $order->get_customer_user_agent(), // Customer User Agent
        "order_meta_data"            => wp_json_encode($order->get_meta_data()), // Order Meta Data
        // URLs
        "edit_url"      => admin_url("post.php?post={$order->get_id()}&action=edit"), // Order Edit URL (Admin)
        "view_url"      => $order->get_view_order_url(), // Order View URL (Customer)
        "pay_url"       => $order->get_checkout_payment_url(), // Order Payment URL
        "cancel_url"    => $order->get_cancel_order_url(), // Order Cancel URL
        "thank_you_url" => $order->get_checkout_order_received_url(), // Order Thank You Page URL
        // Customer Details
        "customer_id"             => $order->get_customer_id(), // Customer ID
        "customer_username"       => $order->get_user_id() ? get_userdata($order->get_user_id())->user_login : '', // Customer Username
        "customer_email"          => $order->get_billing_email(), // Customer Email
        "customer_phone"          => $order->get_billing_phone(), // Customer Phone Number
        "customer_first_name"     => $order->get_billing_first_name(), // Customer First Name
        "customer_last_name"      => $order->get_billing_last_name(), // Customer Last Name
        "customer_display_name"   => $order->get_user_id() ? get_userdata($order->get_user_id())->display_name : '', // Customer Display Name
        "customer_note"           => $order->get_customer_note(), // Customer Note
        // Billing Details
        "billing_first_name"      => $order->get_billing_first_name(), // Billing First Name
        "billing_last_name"       => $order->get_billing_last_name(), // Billing Last Name
        "billing_company"         => $order->get_billing_company(), // Billing Company
        "billing_address"         => $order->get_formatted_billing_address(), // Billing Full Address
        "billing_city"            => $order->get_billing_city(), // Billing City
        "billing_state"           => $order->get_billing_state(), // Billing State
        "billing_postcode"        => $order->get_billing_postcode(), // Billing Postcode
        "billing_country"         => $order->get_billing_country(), // Billing Country
        "billing_phone"           => $order->get_billing_phone(), // Billing Phone Number
        "billing_email"           => $order->get_billing_email(), // Billing Email Address
        // Shipping Details
        "shipping_first_name"     => $order->get_shipping_first_name(), // Shipping First Name
        "shipping_last_name"      => $order->get_shipping_last_name(), // Shipping Last Name
        "shipping_company"        => $order->get_shipping_company(), // Shipping Company
        "shipping_address"        => $order->get_formatted_shipping_address(), // Shipping Full Address
        "shipping_city"           => $order->get_shipping_city(), // Shipping City
        "shipping_state"          => $order->get_shipping_state(), // Shipping State
        "shipping_postcode"       => $order->get_shipping_postcode(), // Shipping Postcode
        "shipping_country"        => $order->get_shipping_country(), // Shipping Country
        "shipping_method"         => implode(', ', wp_list_pluck($order->get_shipping_methods(), 'name')), // Shipping Method
        // Order Items
        "order_items_count"                   => $order->get_item_count(), // Number of Items in Order
        "order_items_list"                    => implode("\n", array_map(function ($item) { return $item->get_name(); }, $order->get_items())), // List of Items in Order
        "order_items_sku_list"                => implode("\n", array_map(function ($item) { return $item->get_name() . ($item->get_product() ? " (".$item->get_product()->get_sku().")" : ""); }, $order->get_items())), // List of Items SKU
        "order_items_price_list"              => implode("\n", array_map(function ($item) { return $item->get_name() . " - " . number_format($item->get_subtotal()); }, $order->get_items())), // List of Items with Prices
        "order_items_quantity_list"           => implode("\n", array_map(function ($item) { return $item->get_quantity(); }, $order->get_items())), // List of Items with Quantity
        "order_items_price_quantity_list"     => implode("\n", array_map(function ($item) { return $item->get_name() . " - " . number_format($item->get_subtotal()) . ' x ' . $item->get_quantity(); }, $order->get_items())), // List of Items with Prices & Quantity
        "order_items_sku_price_list"          => implode("\n", array_map(function ($item) { return $item->get_name() . ($item->get_product() ? " (".$item->get_product()->get_sku().")" : "") . ' - ' .number_format($item->get_subtotal()); }, $order->get_items())), // List of Items with SKU & Price
        "order_items_sku_quantity_list"       => implode("\n", array_map(function ($item) { return $item->get_name() . ($item->get_product() ? " (".$item->get_product()->get_sku().")" : "") . ' x ' . $item->get_quantity(); }, $order->get_items())), // List of Items with SKU & Quantity
        "order_items_sku_price_quantity_list" => implode("\n", array_map(function ($item) { return $item->get_name() . ($item->get_product() ? " (".$item->get_product()->get_sku().")" : "") . ' x ' . $item->get_quantity() . ' = ' . number_format($item->get_subtotal()); }, $order->get_items())), // List of Items with SKU, Price & Quantity
        "order_items_total_qty"               => array_sum(wp_list_pluck($order->get_items(), 'quantity')), // Total Quantity of Items
        // Taxes, Discounts, Fees, and Refunds
        "tax_lines"               => implode(', ', array_map(function ($tax) {
          return $tax->get_label() . ': ' . $tax->get_tax_total();
        }, $order->get_tax_totals())), // Tax Lines Breakdown
        "tax_total"               => $order->get_total_tax(), // Total Tax Amount
        "order_discount_total"    => $order->get_total_discount(), // Order Discount Amount
        "order_fees_total"        => implode(', ', array_map(function ($fee) {
          return $fee->get_name() . ': ' . wc_price($fee->get_total());
        }, $order->get_items('fee'))), // Order Fees Total
        "refund_total"            => $order->get_total_refunded(), // Total Refund Amount
        "refund_reason"           => implode(', ', array_map(function ($refund) {
          return $refund->get_reason();
        }, $order->get_refunds())), // Reason for Refund
        // Coupons
        "coupons_applied"         => count($order->get_items('coupon')), // Coupons Applied
        "coupon_codes"            => implode(', ', wp_list_pluck($order->get_items('coupon'), 'code')), // List of Coupon Codes
        "coupon_discounts"        => implode(', ', array_map(function ($coupon) {
          return $coupon->get_code() . ': ' . wc_price($coupon->get_discount());
        }, $order->get_items('coupon'))), // List of Coupon Discounts
      );
      $pairs = array_merge($pairs, $new_macros);

      if (isset($ex["WC_EMAIL"]) && !empty($ex["WC_EMAIL"])) {
        $new_macros = array(
          "email_id"                 => $ex["WC_EMAIL"]["email_id"],
          "email_from_name"          => $ex["WC_EMAIL"]["email_from_name"],
          "email_from_address"       => $ex["WC_EMAIL"]["email_from_address"],
          "email_attachments"        => $ex["WC_EMAIL"]["email_attachments"],
          "email_title"              => $ex["WC_EMAIL"]["email_title"],
          "email_recipient"          => $ex["WC_EMAIL"]["email_recipient"],
          "email_body_html"          => trim(html_entity_decode($ex["WC_EMAIL"]["email_body_html"])),
          "email_body_html_stripped" => trim(html_entity_decode(wp_strip_all_tags($ex["WC_EMAIL"]["email_body_html"]))),
          "email_body_text"          => trim(html_entity_decode(wp_strip_all_tags($ex["WC_EMAIL"]["email_body_text"]))),
          "email_subject"            => $ex["WC_EMAIL"]["email_subject"],
        );
        $pairs = array_merge($pairs, $new_macros);
      }

      // Add-on: WooCommerce Subscription
      if (class_exists('WC_Subscriptions') && wcs_order_contains_subscription($order)) {
        $new_macros = array();
        $subscriptions = wcs_get_subscriptions_for_order($order);
        foreach ($subscriptions as $subscription) {
          $new_macros = array(
            "subscription_id"         => $subscription->get_id(), // Subscription ID
            "subscription_status"     => $subscription->get_status(), // Subscription Status
            "subscription_total"      => $subscription->get_total(), // Subscription Total Amount
            "subscription_start_date" => $subscription->get_date_created() ? $subscription->get_date_created()->date('Y-m-d H:i:s') : '', // Subscription Start Date
            "subscription_end_date"   => $subscription->get_date('end') ? $subscription->get_date('end')->date('Y-m-d H:i:s') : '', // Subscription End Date
            "subscription_next_payment" => $subscription->get_date('next_payment') ? $subscription->get_date('next_payment')->date('Y-m-d H:i:s') : '', // Next Payment Date
          );
        }
        $pairs = array_merge($pairs, $new_macros);
      }

      // Add-on: WooCommerce Membership
      if (class_exists('WC_Memberships') && wc_memberships_is_user_active_member($order->get_customer_id())) {
        $new_macros = array();
        $memberships = wc_memberships_get_user_active_memberships($order->get_customer_id());
        foreach ($memberships as $membership) {
          $new_macros = array(
            "membership_plan"         => $membership->get_plan()->get_name(), // Membership Plan Name
            "membership_status"       => $membership->get_status(), // Membership Status
            "membership_start_date"   => $membership->get_start_date('Y-m-d H:i:s'), // Membership Start Date
            "membership_end_date"     => $membership->get_end_date('Y-m-d H:i:s') ?: '', // Membership Expiration Date
          );
        }
        $pairs = array_merge($pairs, $new_macros);
      }

      // Add-on: WooCommerce Booking
      if (class_exists('WC_Bookings')) {
        $new_macros = array();
        $bookings = \WC_Booking_Data_Store::get_booking_ids_from_order_id($order->get_id());
        if (!empty($bookings)) {
          foreach ($bookings as $booking_id) {
            $booking = new \WC_Booking($booking_id);
            $new_macros[] = array(
              "booking_id"              => $booking->get_id(), // Booking ID
              "booking_status"          => $booking->get_status(), // Booking Status
              "booking_date"            => $booking->get_date_created() ? $booking->get_date_created()->date('Y-m-d H:i:s') : '', // Booking Date
              "booking_start_time"      => $booking->get_start_date('Y-m-d H:i:s'), // Booking Start Time
              "booking_end_time"        => $booking->get_end_date('Y-m-d H:i:s'), // Booking End Time
            );
          }
          $pairs = array_merge($pairs, $new_macros);
        }
      }

      // Add-on: WooCommerce Points and Rewards
      if (class_exists('WC_Points_Rewards_Manager')) {
        $new_macros = array();
        $customer_id = $order->get_customer_id();
        $points_earned = \WC_Points_Rewards_Manager::get_points_earned_for_order($order);
        $points_balance = \WC_Points_Rewards_Manager::get_users_points($customer_id);
        $points_redeemed = \WC_Points_Rewards_Manager::get_points_redeemed_for_order($order);
        $new_macros = array(
          "points_earned"           => $points_earned, // Points Earned
          "points_balance"          => $points_balance, // Customer Points Balance
          "points_redeemed"         => $points_redeemed, // Points Redeemed
        );
        $pairs = array_merge($pairs, $new_macros);
      }

      // Add-on: WooCommerce Multi-Currency
      if (class_exists('WC_Aelia_CurrencySwitcher')) {
        $new_macros = array();
        $conversion_rate = get_post_meta($order->get_id(), '_currency_conversion_rate', true);
        $converted_total = get_post_meta($order->get_id(), '_order_total_converted', true);
        $new_macros = array(
          "currency_conversion_rate" => $conversion_rate ?: '1.00', // Currency Conversion Rate
          "order_total_converted"    => $converted_total ?: $order->get_total(), // Order Total (Converted)
        );
        $pairs = array_merge($pairs, $new_macros);
      }
    }
    // always return array
    return (array) $pairs;
  }
  public function add_custom_macros($macros, $notif_id) {

    if (in_array($notif_id, $this->notif_id) && is_array($macros)) {

      $wc_email_notif = array(
        "wc_email_notif" => array(
          "title" => __("WooCommerce Emails", "blackswan-telegram"),
          "macros" => array(
            "email_id"                 => _x("Email ID", "macro", "blackswan-telegram"),
            "email_title"              => _x("Email Title", "macro", "blackswan-telegram"),
            "email_recipient"          => _x("Email Recipient", "macro", "blackswan-telegram"),
            "email_subject"            => _x("Email Subject", "macro", "blackswan-telegram"),
            "email_attachments"        => _x("Email Attachments", "macro", "blackswan-telegram"),
            "email_from_name"          => _x("Email From name", "macro", "blackswan-telegram"),
            "email_from_address"       => _x("Email From address", "macro", "blackswan-telegram"),
            /* translators: 1: notice */
            "email_body_text"          => sprintf(_x("Email Body TEXT %s", "macro", "blackswan-telegram"), "<span style='color:white; background: red; display: block;'>&nbsp;".__("Only use when HTML Formatting is set to 'ENABLED' or within preformatted blocks (`code`, ```code```, &amp;lt;pre&amp;gt;code&amp;lt;/pre&amp;gt;) for proper rendering.", "blackswan-telegram")."&nbsp;</span>"),
            /* translators: 1: notice */
            "email_body_html"          => sprintf(_x("Email Body HTML %s", "macro", "blackswan-telegram"), "<span style='color:white; background: red; display: block;'>&nbsp;".__("Only use when HTML Formatting is set to 'ENABLED' or within preformatted blocks (`code`, ```code```, &amp;lt;pre&amp;gt;code&amp;lt;/pre&amp;gt;) for proper rendering.", "blackswan-telegram")."&nbsp;</span>"),
            /* translators: 1: notice */
            "email_body_html_stripped" => sprintf(_x("Email Body HTML (tags stripped) %s", "macro", "blackswan-telegram"), "<span style='color:white; background: red; display: block;'>&nbsp;".__("Only use when HTML Formatting is set to 'ENABLED' or within preformatted blocks (`code`, ```code```, &amp;lt;pre&amp;gt;code&amp;lt;/pre&amp;gt;) for proper rendering.", "blackswan-telegram")."&nbsp;</span>"),
          ),
        ),
      );
      foreach ($this->wc_emails as $slug => $name) {
        if ($notif_id == "wc_mail_{$slug}") {
          $macros = array_merge($macros, $wc_email_notif);
        }
      }
      if ($notif_id == "wc_mail_sent") { $macros = array_merge($macros, $wc_email_notif); }

      $new_macros = array(
        "wc_order_general_details" => array(
          "title" => __("Order General Details", "blackswan-telegram"),
          "macros" => array(
            // General Order Details
            "order_id"                   => _x("Order ID", "macro", "blackswan-telegram"),
            "order_number"               => _x("Order Number", "macro", "blackswan-telegram"),
            "order_key"                  => _x("Order Key", "macro", "blackswan-telegram"),
            "order_status"               => _x("Order Status", "macro", "blackswan-telegram"),
            "order_status_raw"           => _x("Order Status RAW", "macro", "blackswan-telegram"),
            "order_status_prev"          => _x("Prev. Order Status", "macro", "blackswan-telegram"),
            "order_status_new"           => _x("New Order Status", "macro", "blackswan-telegram"),
            "order_total"                => _x("Order Total Amount", "macro", "blackswan-telegram"),
            "order_subtotal"             => _x("Order Subtotal Amount", "macro", "blackswan-telegram"),
            "order_discount_total"       => _x("Order Discount Amount", "macro", "blackswan-telegram"),
            "order_tax_total"            => _x("Order Tax Amount", "macro", "blackswan-telegram"),
            "order_shipping_total"       => _x("Order Shipping Amount", "macro", "blackswan-telegram"),
            "order_fees_total"           => _x("Order Fees Total", "macro", "blackswan-telegram"),
            "order_currency"             => _x("Order Currency", "macro", "blackswan-telegram"),
            "order_payment_method"       => _x("Order Payment Method", "macro", "blackswan-telegram"),
            "order_payment_method_title" => _x("Payment Method Title", "macro", "blackswan-telegram"),
            "transaction_id"             => _x("Transaction ID", "macro", "blackswan-telegram"),
            "order_date"                 => _x("Order Creation Date", "macro", "blackswan-telegram"),
            "order_jalali_date"          => _x("Order Creation Date (Jalali)", "macro", "blackswan-telegram"),
            "order_modified_date"        => _x("Order Last Modified Date", "macro", "blackswan-telegram"),
            "order_jalali_modified"      => _x("Order Last Modified Date (Jalali)", "macro", "blackswan-telegram"),
            "order_date_completed"       => _x("Order Completed Date", "macro", "blackswan-telegram"),
            "order_jalali_completed"     => _x("Order Completed Date (Jalali)", "macro", "blackswan-telegram"),
            "order_date_paid"            => _x("Order Paid Date", "macro", "blackswan-telegram"),
            "order_jalali_paid"          => _x("Order Paid Date (Jalali)", "macro", "blackswan-telegram"),
            "order_customer_ip"          => _x("Customer IP Address", "macro", "blackswan-telegram"),
            "order_user_agent"           => _x("Customer User Agent", "macro", "blackswan-telegram"),
            "order_meta_data"            => _x("Order Meta Data", "macro", "blackswan-telegram"),
          )
        ),
        "wc_order_urls" => array(
          "title" => __("Order URLs", "blackswan-telegram"),
          "macros" => array(
            // URLs
            "edit_url"      => _x("Order Edit URL (Admin)", "macro", "blackswan-telegram"),
            "view_url"      => _x("Order View URL (Customer)", "macro", "blackswan-telegram"),
            "pay_url"       => _x("Order Payment URL", "macro", "blackswan-telegram"),
            "cancel_url"    => _x("Order Cancel URL", "macro", "blackswan-telegram"),
            "thank_you_url" => _x("Order Thank You Page URL", "macro", "blackswan-telegram"),
          )
        ),
        "wc_order_customer_details" => array(
          "title" => __("Order Customer Details", "blackswan-telegram"),
          "macros" => array(
            // Customer Details
            "customer_id"             => _x("Customer ID", "macro", "blackswan-telegram"),
            "customer_username"       => _x("Customer Username", "macro", "blackswan-telegram"),
            "customer_email"          => _x("Customer Email", "macro", "blackswan-telegram"),
            "customer_phone"          => _x("Customer Phone Number", "macro", "blackswan-telegram"),
            "customer_first_name"     => _x("Customer First Name", "macro", "blackswan-telegram"),
            "customer_last_name"      => _x("Customer Last Name", "macro", "blackswan-telegram"),
            "customer_display_name"   => _x("Customer Display Name", "macro", "blackswan-telegram"),
            "customer_note"           => _x("Customer Note", "macro", "blackswan-telegram"),
          )
        ),
        "wc_order_billing_details" => array(
          "title" => __("Order Billing Details", "blackswan-telegram"),
          "macros" => array(
            // Billing Details
            "billing_first_name"      => _x("Billing First Name", "macro", "blackswan-telegram"),
            "billing_last_name"       => _x("Billing Last Name", "macro", "blackswan-telegram"),
            "billing_company"         => _x("Billing Company", "macro", "blackswan-telegram"),
            "billing_address"         => _x("Billing Full Address", "macro", "blackswan-telegram"),
            "billing_city"            => _x("Billing City", "macro", "blackswan-telegram"),
            "billing_state"           => _x("Billing State", "macro", "blackswan-telegram"),
            "billing_postcode"        => _x("Billing Postcode", "macro", "blackswan-telegram"),
            "billing_country"         => _x("Billing Country", "macro", "blackswan-telegram"),
            "billing_phone"           => _x("Billing Phone Number", "macro", "blackswan-telegram"),
            "billing_email"           => _x("Billing Email Address", "macro", "blackswan-telegram"),
          )
        ),
        "wc_order_shipping_details" => array(
          "title" => __("Order Shipping Details", "blackswan-telegram"),
          "macros" => array(
            // Shipping Details
            "shipping_first_name"     => _x("Shipping First Name", "macro", "blackswan-telegram"),
            "shipping_last_name"      => _x("Shipping Last Name", "macro", "blackswan-telegram"),
            "shipping_company"        => _x("Shipping Company", "macro", "blackswan-telegram"),
            "shipping_address"        => _x("Shipping Full Address", "macro", "blackswan-telegram"),
            "shipping_city"           => _x("Shipping City", "macro", "blackswan-telegram"),
            "shipping_state"          => _x("Shipping State", "macro", "blackswan-telegram"),
            "shipping_postcode"       => _x("Shipping Postcode", "macro", "blackswan-telegram"),
            "shipping_country"        => _x("Shipping Country", "macro", "blackswan-telegram"),
            "shipping_method"         => _x("Shipping Method", "macro", "blackswan-telegram"),
          )
        ),
        "wc_order_items" => array(
          "title" => __("Order Items", "blackswan-telegram"),
          "macros" => array(
            // Order Items
            "order_items_count"                   => _x("Number of Items in Order", "macro", "blackswan-telegram"),
            "order_items_list"                    => _x("List of Items in Order", "macro", "blackswan-telegram"),
            "order_items_sku_list"                => _x("List of Items SKU", "macro", "blackswan-telegram"),
            "order_items_price_list"              => _x("List of Items with Prices", "macro", "blackswan-telegram"),
            "order_items_quantity_list"           => _x("List of Items with Quantity", "macro", "blackswan-telegram"),
            "order_items_price_quantity_list"     => _x("List of Items with Prices & Quantity", "macro", "blackswan-telegram"),
            "order_items_sku_price_list"          => _x("List of Items with SKU & Price", "macro", "blackswan-telegram"),
            "order_items_sku_quantity_list"       => _x("List of Items with SKU & Quantity", "macro", "blackswan-telegram"),
            "order_items_sku_price_quantity_list" => _x("List of Items with SKU, Price & Quantity", "macro", "blackswan-telegram"),
            "order_items_total_qty"               => _x("Total Quantity of Items", "macro", "blackswan-telegram"),
          )
        ),
        "wc_order_taxes_discounts" => array(
          "title" => __("Order Taxes, Discounts, Fees, and Refunds", "blackswan-telegram"),
          "macros" => array(
            // Taxes, Discounts, Fees, and Refunds
            "tax_lines"               => _x("Tax Lines Breakdown", "macro", "blackswan-telegram"),
            "tax_total"               => _x("Total Tax Amount", "macro", "blackswan-telegram"),
            "order_discount_total"    => _x("Order Discount Amount", "macro", "blackswan-telegram"),
            "order_fees_total"        => _x("Order Fees Total", "macro", "blackswan-telegram"),
            "refund_total"            => _x("Total Refund Amount", "macro", "blackswan-telegram"),
            "refund_reason"           => _x("Reason for Refund", "macro", "blackswan-telegram"),
          )
        ),
        "wc_order_coupons" => array(
          "title" => __("Order Coupons", "blackswan-telegram"),
          "macros" => array(
            // Coupons
            "coupons_applied"         => _x("Coupons Applied", "macro", "blackswan-telegram"),
            "coupon_codes"            => _x("List of Coupon Codes", "macro", "blackswan-telegram"),
            "coupon_discounts"        => _x("List of Coupon Discounts", "macro", "blackswan-telegram"),
          )
        ),
      );
      $macros = array_merge($macros, $new_macros);

      $wc_subscriptions_info = array(
        "wc_subscriptions_info" => array(
          "title" => __("Add-on: WooCommerce Subscription", "blackswan-telegram"),
          "macros" => array(
            "subscription_id"         => _x("Subscription ID", "macro", "blackswan-telegram"),
            "subscription_status"     => _x("Subscription Status", "macro", "blackswan-telegram"),
            "subscription_total"      => _x("Subscription Total Amount", "macro", "blackswan-telegram"),
            "subscription_start_date" => _x("Subscription Start Date", "macro", "blackswan-telegram"),
            "subscription_end_date"   => _x("Subscription End Date", "macro", "blackswan-telegram"),
            "subscription_next_payment" => _x("Next Payment Date", "macro", "blackswan-telegram"),
          ),
        ),
      );
      $wc_memberships_info = array(
        "wc_memberships_info" => array(
          "title" => __("Add-on: WooCommerce Membership", "blackswan-telegram"),
          "macros" => array(
            "membership_plan"         => _x("Membership Plan Name", "macro", "blackswan-telegram"),
            "membership_status"       => _x("Membership Status", "macro", "blackswan-telegram"),
            "membership_start_date"   => _x("Membership Start Date", "macro", "blackswan-telegram"),
            "membership_end_date"     => _x("Membership Expiration Date", "macro", "blackswan-telegram"),
          ),
        ),
      );
      $wc_bookings_info = array(
        "wc_bookings_info" => array(
          "title" => __("Add-on: WooCommerce Booking", "blackswan-telegram"),
          "macros" => array(
            "booking_id"              => _x("Booking ID", "macro", "blackswan-telegram"),
            "booking_status"          => _x("Booking Status", "macro", "blackswan-telegram"),
            "booking_date"            => _x("Booking Date", "macro", "blackswan-telegram"),
            "booking_start_time"      => _x("Booking Start Time", "macro", "blackswan-telegram"),
            "booking_end_time"        => _x("Booking End Time", "macro", "blackswan-telegram"),
          ),
        ),
      );
      $wc_points_rewards_info = array(
        "wc_points_rewards_info" => array(
          "title" => __("Add-on: WooCommerce Points and Rewards", "blackswan-telegram"),
          "macros" => array(
            "points_earned"           => _x("Points Earned", "macro", "blackswan-telegram"),
            "points_balance"          => _x("Customer Points Balance", "macro", "blackswan-telegram"),
            "points_redeemed"         => _x("Points Redeemed", "macro", "blackswan-telegram"),
          ),
        ),
      );
      $wc_multi_currency_info = array(
        "wc_multi_currency_info" => array(
          "title" => __("Add-on: WooCommerce Multi-Currency", "blackswan-telegram"),
          "macros" => array(
            "currency_conversion_rate" => _x("Currency Conversion Rate", "macro", "blackswan-telegram"),
            "order_total_converted"   => _x("Order Total (Converted)", "macro", "blackswan-telegram"),
          ),
        ),
      );
      if (class_exists('WC_Subscriptions')) {
        $macros = array_merge($macros, $wc_subscriptions_info);
      }
      if (class_exists('WC_Memberships')) {
        $macros = array_merge($macros, $wc_memberships_info);
      }
      if (class_exists('WC_Bookings')) {
        $macros = array_merge($macros, $wc_bookings_info);
      }
      if (class_exists('WC_Points_Rewards')) {
        $macros = array_merge($macros, $wc_points_rewards_info);
      }
      if (class_exists('WC_MultiCurrency')) {
        $macros = array_merge($macros, $wc_multi_currency_info);
      }
    }
    return (array) $macros;
  }
  #endregion
  #region hooked functions >>>>>>>>>>>>>
  public function wc_new_order($order_id) {
    $list_notif = $this->get_notifications_by_type("wc_new_order");
    foreach ((array) $list_notif as $notif) @$this->send_telegram_msg($notif->config->message, $notif->config->btn_row1, __CLASS__, ["current_hook" => current_action(), "order_id" => $order_id], $notif->config->html_parser, false);
  }
  public function wc_order_saved($order_id) {
    remove_action("woocommerce_update_order", array($this, "wc_order_saved"), 10, 1);
    $list_notif = $this->get_notifications_by_type("wc_order_saved");
    foreach ((array) $list_notif as $notif) @$this->send_telegram_msg($notif->config->message, $notif->config->btn_row1, __CLASS__, ["current_hook" => current_action(), "order_id" => $order_id], $notif->config->html_parser, false);
  }
  public function wc_payment_complete($order_id) {
    $list_notif = $this->get_notifications_by_type("wc_payment_complete");
    foreach ((array) $list_notif as $notif) @$this->send_telegram_msg($notif->config->message, $notif->config->btn_row1, __CLASS__, ["current_hook" => current_action(), "order_id" => $order_id], $notif->config->html_parser, false);
  }
  public function wc_checkout_processed($order_id, $posted_data, $order) {
    $list_notif = $this->get_notifications_by_type("wc_checkout_processed");
    foreach ((array) $list_notif as $notif) @$this->send_telegram_msg($notif->config->message, $notif->config->btn_row1, __CLASS__, ["current_hook" => current_action(), "order_id" => $order_id], $notif->config->html_parser, false);
  }
  public function wc_checkout_api_processed($order) {
    $order = is_a($order, 'WC_Order') ? $order : wc_get_order($order);
    $list_notif = $this->get_notifications_by_type("wc_checkout_api_processed");
    foreach ((array) $list_notif as $notif) @$this->send_telegram_msg($notif->config->message, $notif->config->btn_row1, __CLASS__, ["current_hook" => current_action(), "order_id" => $order->get_id()], $notif->config->html_parser, false);
  }
  public function wc_order_status_changed($order_id, $status_from, $status_to, $order) {
    $list_notif = $this->get_notifications_by_type("wc_order_status_changed");
    foreach ((array) $list_notif as $notif) @$this->send_telegram_msg($notif->config->message, $notif->config->btn_row1, __CLASS__, [
      "current_hook" => current_action(),
      "order_id" => $order_id,
      "order_status_prev" => $status_from,
      "order_status_new" => $status_to,
    ], $notif->config->html_parser, false);
  }
  public function wc_order_refunded($order_id) {
    $list_notif = $this->get_notifications_by_type("wc_delete_order");
    foreach ((array) $list_notif as $notif) @$this->send_telegram_msg($notif->config->message, $notif->config->btn_row1, __CLASS__, ["current_hook" => current_action(), "order_id" => $order_id], $notif->config->html_parser, false);
  }
  public function wc_trash_order($order_id) {
    $list_notif = $this->get_notifications_by_type("wc_trash_order");
    foreach ((array) $list_notif as $notif) @$this->send_telegram_msg($notif->config->message, $notif->config->btn_row1, __CLASS__, ["current_hook" => current_action(), "order_id" => $order_id], $notif->config->html_parser, false);
  }
  public function wc_delete_order($order_id) {
    $list_notif = $this->get_notifications_by_type("wc_delete_order");
    foreach ((array) $list_notif as $notif) @$this->send_telegram_msg($notif->config->message, $notif->config->btn_row1, __CLASS__, ["current_hook" => current_action(), "order_id" => $order_id], $notif->config->html_parser, false);
  }
  #endregion
}
new class_wc_order;
