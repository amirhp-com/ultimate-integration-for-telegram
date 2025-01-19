<?php
/*
 * @Author: Amirhossein Hosseinpour <https://amirhp.com>
 * @Last modified by: amirhp-com <its@amirhp.com>
 * @Last modified time: 2025/01/19 11:37:24
 */

use BlackSwan\Telegram\Notifier;
class class_wc_product extends Notifier {
  public $notif_id = [];
  public function __construct() {

    if (!empty($this->get_notifications_by_type("wc_new_order"))){
      add_action("woocommerce_new_order", array($this, "wc_new_order"), 10, 1);
    }
    if (!empty($this->get_notifications_by_type("wc_payment_complete"))){
      add_action("woocommerce_payment_complete", array($this, "wc_payment_complete"), 10, 1);
    }
    if (!empty($this->get_notifications_by_type("wc_order_refunded"))){
      add_action("woocommerce_order_refunded", array($this, "wc_order_refunded"), 10, 1);
    }
    if (!empty($this->get_notifications_by_type("wc_checkout_processed"))){
      add_action("woocommerce_checkout_order_processed", array($this, "wc_checkout_processed"), 10, 1);
    }
    if (!empty($this->get_notifications_by_type("wc_checkout_api_processed"))){
      add_action("woocommerce_store_api_checkout_order_processed", array($this, "wc_checkout_api_processed"), 10, 1);
    }
    if (!empty($this->get_notifications_by_type("wc_order_status_changed"))){
      add_action("woocommerce_order_status_changed", array($this, "wc_order_status_changed"), 10, 1);
    }
    if (!empty($this->get_notifications_by_type("wc_trash_order"))){
      add_action("woocommerce_order_status_changed", array($this, "wc_trash_order"), 10, 1);
    }
    if (!empty($this->get_notifications_by_type("wc_delete_order"))){
      add_action("woocommerce_order_status_changed", array($this, "wc_delete_order"), 10, 1);
    }
    $statuses = wc_get_order_statuses();
    $emails = wp_list_pluck(WC()->mailer()->get_emails(), "title", "id");
    foreach ($statuses as $slug => $name) {
      $slug = $this->remove_status_prefix($slug);
      $event = "wc_order_status_to_{$slug}";
      if (!empty($this->get_notifications_by_type($event))){
        add_action("woocommerce_order_status_changed", array($this, "order_status_changed"), 10, 1);
      }
    }

    add_action("blackswan-telegram/notif-panel/notif-macro-list", array($this, "add_custom_macros"));
    add_filter("blackswan-telegram/helper/translate-pairs", array($this, "translate_params_custom"), 10, 5);
  }
  #region macro declaration >>>>>>>>>>>>>
  public function translate_params_custom($pairs, $msg, $ref, $ex, $def) {
    // if we are sending request from this class, then handle macro
    if (in_array($ref, [__CLASS__, "SANITIZE_BTN", "SANITIZE_URL"]) && !empty($ex["product_id"])) {

      $product_id = $ex["product_id"];
      $product = wc_get_product($product_id);
      $get_image_id = $product->get_image_id() ? $product->get_image_id() : get_option( 'woocommerce_placeholder_image', 0 );
      $src_thumb = wp_get_attachment_image_src($get_image_id, 'full', false);
      $thumbnail = $get_image_id && isset($src_thumb[0]) ? $src_thumb[0] : wc_placeholder_img_src();

      $new_macros = array(
        "product_id"      => $product_id,
        "name"            => $product->get_name(),
        "url"             => $product->get_permalink(),
        "formatted_name"  => $product->get_formatted_name(),
        "sku"             => $product->get_sku(),
        "stock_status"    => wc_format_stock_for_display($product),
        "stock_quantity"  => $product->get_stock_quantity(),
        "price"           => number_format($product->get_price()),
        "sale_price"      => number_format($product->get_sale_price()),
        "regular_price"   => number_format($product->get_regular_price()),
        "type"            => $product->get_type(),
        "thumbnail"       => $thumbnail,
        "description"     => $product->get_short_description(),
        "date_published"  => wp_date('Y-m-d H:i:s', strtotime($product->get_date_created())),
        "date_modified"   => wp_date('Y-m-d H:i:s', strtotime($product->get_date_modified())),
        "date_jpublished" => pu_jdate('Y/m/d H:i:s', strtotime($product->get_date_created()), "", "local", "en"),
        "date_jmodified"  => pu_jdate('Y/m/d H:i:s', strtotime($product->get_date_modified()), "", "local", "en"),
        "product_meta"    => print_r(get_post_meta($product_id, "", true), 1),
      );
      $pairs = array_merge($pairs, $new_macros);
    }
    // always return array
    return (array) $pairs;
  }
  public function add_custom_macros($notif_id) {
    if (in_array($notif_id, $this->notif_id)) {
      ?>
      <strong><?= __("Order Information", $this->td); ?></strong>
      <copy data-tippy-content="<?= esc_attr(_x("Product ID", "macro", $this->td)); ?>">{product_id}</copy>
      <copy data-tippy-content="<?= esc_attr(_x("Product name", "macro", $this->td)); ?>">{name}</copy>
      <copy data-tippy-content="<?= esc_attr(_x("Product permalink URL", "macro", $this->td)); ?>">{url}</copy>
      <copy data-tippy-content="<?= esc_attr(_x("Product name with SKU or ID", "macro", $this->td)); ?>">{formatted_name}</copy>
      <copy data-tippy-content="<?= esc_attr(_x("Product Thumbnail URL", "macro", $this->td)); ?>">{thumbnail}</copy>
      <copy data-tippy-content="<?= esc_attr(_x("Product SKU", "macro", $this->td)); ?>">{sku}</copy>
      <copy data-tippy-content="<?= esc_attr(_x("Product Price", "macro", $this->td)); ?>">{price}</copy>
      <copy data-tippy-content="<?= esc_attr(_x("Product Sale Price", "macro", $this->td)); ?>">{sale_price}</copy>
      <copy data-tippy-content="<?= esc_attr(_x("Product Regular Price", "macro", $this->td)); ?>">{regular_price}</copy>
      <copy data-tippy-content="<?= esc_attr(_x("Product Type", "macro", $this->td)); ?>">{type}</copy>
      <copy data-tippy-content="<?= esc_attr(_x("Product short description", "macro", $this->td)); ?>">{description}</copy>
      <copy data-tippy-content="<?= esc_attr(_x("Product published date", "macro", $this->td)); ?>">{date_published}</copy>
      <copy data-tippy-content="<?= esc_attr(_x("Product modified date", "macro", $this->td)); ?>">{date_modified}</copy>
      <copy data-tippy-content="<?= esc_attr(_x("Product published date in Jalali", "macro", $this->td)); ?>">{date_jpublished}</copy>
      <copy data-tippy-content="<?= esc_attr(_x("Product modified date in Jalali", "macro", $this->td)); ?>">{date_jmodified}</copy>
      <copy data-tippy-content="<?= esc_attr(_x("Product stock status", "macro", $this->td)); ?>">{stock_status}</copy>
      <copy data-tippy-content="<?= esc_attr(_x("Product stock quantity", "macro", $this->td)); ?>">{stock_quantity}</copy>
      <copy data-tippy-content="<?= esc_attr(_x("Product meta Array", "macro", $this->td)); ?>">{product_meta}</copy>
      <?php
    }
  }
  #endregion
  #region hooked functions >>>>>>>>>>>>>
  public function wc_new_order($order_id = 0) {
    $list_notif = $this->get_notifications_by_type("wc_new_order");
    foreach ($list_notif as $notif) @$this->send_telegram_msg($notif->config->message, $notif->config->btn_row1, __CLASS__, ["current_hook" => current_action(), "order_id" => $order_id], $notif->config->html_parser, false);
  }
  public function wc_payment_complete($order_id = 0) {
    $list_notif = $this->get_notifications_by_type("wc_payment_complete");
    foreach ($list_notif as $notif) @$this->send_telegram_msg($notif->config->message, $notif->config->btn_row1, __CLASS__, ["current_hook" => current_action(), "order_id" => $order_id], $notif->config->html_parser, false);
  }
  public function wc_checkout_processed($order_id = 0) {
    $list_notif = $this->get_notifications_by_type("wc_checkout_processed");
    foreach ($list_notif as $notif) @$this->send_telegram_msg($notif->config->message, $notif->config->btn_row1, __CLASS__, ["current_hook" => current_action(), "order_id" => $order_id], $notif->config->html_parser, false);
  }
  public function wc_checkout_api_processed($order_id = 0) {
    $list_notif = $this->get_notifications_by_type("wc_checkout_api_processed");
    foreach ($list_notif as $notif) @$this->send_telegram_msg($notif->config->message, $notif->config->btn_row1, __CLASS__, ["current_hook" => current_action(), "order_id" => $order_id], $notif->config->html_parser, false);
  }
  public function wc_order_status_changed($order_id = 0) {
    $list_notif = $this->get_notifications_by_type("wc_order_status_changed");
    foreach ($list_notif as $notif) @$this->send_telegram_msg($notif->config->message, $notif->config->btn_row1, __CLASS__, ["current_hook" => current_action(), "order_id" => $order_id], $notif->config->html_parser, false);
  }
  public function wc_trash_order($order_id = 0) {
    $list_notif = $this->get_notifications_by_type("wc_trash_order");

    foreach ($list_notif as $notif) @$this->send_telegram_msg($notif->config->message, $notif->config->btn_row1, __CLASS__, ["current_hook" => current_action(), "order_id" => $order_id], $notif->config->html_parser, false);
  }
  public function wc_delete_order($order_id = 0) {
    $list_notif = $this->get_notifications_by_type("wc_delete_order");
    foreach ($list_notif as $notif) @$this->send_telegram_msg($notif->config->message, $notif->config->btn_row1, __CLASS__, ["current_hook" => current_action(), "order_id" => $order_id], $notif->config->html_parser, false);
  }
  public function wc_order_refunded($order_id = 0) {
    $list_notif = $this->get_notifications_by_type("wc_delete_order");
    foreach ($list_notif as $notif) @$this->send_telegram_msg($notif->config->message, $notif->config->btn_row1, __CLASS__, ["current_hook" => current_action(), "order_id" => $order_id], $notif->config->html_parser, false);
  }
  #endregion
}
new class_wc_product;