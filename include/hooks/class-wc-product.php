<?php
/*
 * @Last modified by: amirhp-com <its@amirhp.com>
 * @Last modified time: 2025/09/04 00:47:19
 */

use PigmentDev\Ultimate_Integration_Telegram\Notifier;

defined("ABSPATH") or die("<h2>Unauthorized Access!</h2><hr><small>Ultimate Integration for Telegram :: Developed by <a href='https://pigment.dev/'>Pigment.Dev</a></small>");
if (!class_exists("Ultimate_Integration_Telegram_Products")) {
  class Ultimate_Integration_Telegram_Products extends Notifier {
    public function __construct() {
      parent::__construct(false);
      add_filter("ultimate-integration-for-telegram/notif-panel/notif-macro-list", array($this, "add_custom_macros"), 30, 2);
      add_filter("ultimate-integration-for-telegram/helper/translate-pairs", array($this, "translate_params_custom"), 10, 5);

      // wc_product_updated
      if (!empty($this->get_notifications_by_type("wc_product_updated"))) {
        add_action("save_post_product", array($this, "send_msg_on_update"), 30, 1);
      }
      // wc_product_stock_increased
      if (!empty($this->get_notifications_by_type("wc_product_stock_increased"))) {
        add_filter("woocommerce_update_product_stock_query", array($this, "send_msg_on_stock_update"), 99, 4);
      }
      // wc_product_stock_decreased
      if (!empty($this->get_notifications_by_type("wc_product_stock_decreased"))) {
        add_filter("woocommerce_update_product_stock_query", array($this, "send_msg_on_stock_update"), 99, 4);
      }
      // wc_product_stock_changed
      if (!empty($this->get_notifications_by_type("wc_product_stock_changed"))) {
        add_filter("woocommerce_update_product_stock_query", array($this, "send_msg_on_stock_update"), 99, 4);
      }
      // wc_product_stock_outofstock
      if (!empty($this->get_notifications_by_type("wc_product_stock_outofstock"))) {
        add_action('woocommerce_no_stock_notification', function($product) {
          if (!$product instanceof WC_Product) return;
          $low_stock_amount = $product->get_low_stock_amount();
          $stock_quantity = $product->get_stock_quantity();
          if ($low_stock_amount && $stock_quantity !== null && $stock_quantity < $low_stock_amount) {
            $this->send_msg_on_outofstock($product->get_id(), 'outofstock');
          }
        }, 30, 1);
        add_action("woocommerce_product_set_stock_status", array($this, "send_msg_on_outofstock"), 30, 2);
        add_action("woocommerce_variation_set_stock_status", array($this, "send_msg_on_outofstock"), 30, 2);
      }
      // wc_product_stock_low_stock
      if (!empty($this->get_notifications_by_type("wc_product_stock_low_stock"))) {
        add_action('woocommerce_low_stock_notification', function($product) {
          if (!$product instanceof WC_Product) return;
          $low_stock_amount = $product->get_low_stock_amount();
          $stock_quantity = $product->get_stock_quantity();
          if ($low_stock_amount && $stock_quantity !== null && $stock_quantity < $low_stock_amount) {
            $this->send_msg_on_outofstock($product->get_id(), 'low_stock');
          }
        }, 30, 1);
        add_action("woocommerce_product_set_stock_status", array($this, "send_msg_on_outofstock"), 30, 2);
        add_action("woocommerce_variation_set_stock_status", array($this, "send_msg_on_outofstock"), 30, 2);
      }
    }
    public function translate_params_custom($pairs=[], $msg="", $ref="", $ex=[], $def=[]) {
      // if we are sending request from this class, then handle macro
      if (in_array($ref, [__CLASS__, "SANITIZE_BTN", "SANITIZE_URL"]) && !empty($ex["product_id"])) {
        $product_id = $ex["product_id"];
        $product = wc_get_product($product_id);
        if (!$product) return (array) $pairs;
        $get_image_id = $product->get_image_id() ? $product->get_image_id() : get_option('woocommerce_placeholder_image', 0);
        $src_thumb = wp_get_attachment_image_src($get_image_id, 'full', false);
        $thumbnail = $get_image_id && isset($src_thumb[0]) ? $src_thumb[0] : wc_placeholder_img_src();
        $new_macros = array(
          "product_id"         => $product_id,
          "stock_quantity_new" => $ex["stock_quantity_new"]??"",
          "name"               => $product->get_name(),
          "url"                => $product->get_permalink(),
          "formatted_name"     => $product->get_formatted_name(),
          "sku"                => $product->get_sku(),
          "stock_status"       => wc_format_stock_for_display($product),
          "stock_quantity"     => $product->get_stock_quantity(),
          "price"              => $this->number_format($product->get_price()),
          "sale_price"         => $this->number_format($product->get_sale_price()),
          "regular_price"      => $this->number_format($product->get_regular_price()),
          "type"               => $product->get_type(),
          "thumbnail"          => $thumbnail,
          "description"        => $product->get_short_description(),
          "date_published"     => wp_date('Y-m-d H:i:s', strtotime($product->get_date_created())),
          "date_modified"      => wp_date('Y-m-d H:i:s', strtotime($product->get_date_modified())),
          "date_jpublished"    => Ultimate_Integration_Telegram__jdate('Y/m/d H:i:s', strtotime($product->get_date_created()), "", "local", "en"),
          "date_jmodified"     => Ultimate_Integration_Telegram__jdate('Y/m/d H:i:s', strtotime($product->get_date_modified()), "", "local", "en"),
          // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r
          "product_meta"    => print_r(get_post_meta($product_id, "", true), 1),
        );
        $pairs = array_merge($pairs, $new_macros);
      }
      // always return array
      return (array) $pairs;
    }
    public function add_custom_macros($macros, $notif_id) {
      if (
        in_array($notif_id, [
          "wc_product_updated",
          "wc_product_stock_changed",
          "wc_product_stock_increased",
          "wc_product_stock_decreased",
          "wc_product_stock_outofstock",
          "wc_product_stock_low_stock",
        ]) && is_array($macros)
      ) {
        $product_macros_default = array(
          "product_id"         => _x("Product ID", "macro", "ultimate-integration-for-telegram"),
          "name"               => _x("Product name", "macro", "ultimate-integration-for-telegram"),
          "url"                => _x("Product permalink URL", "macro", "ultimate-integration-for-telegram"),
          "formatted_name"     => _x("Product name with SKU or ID", "macro", "ultimate-integration-for-telegram"),
          "thumbnail"          => _x("Product Thumbnail URL", "macro", "ultimate-integration-for-telegram"),
          "sku"                => _x("Product SKU", "macro", "ultimate-integration-for-telegram"),
          "price"              => _x("Product Price", "macro", "ultimate-integration-for-telegram"),
          "sale_price"         => _x("Product Sale Price", "macro", "ultimate-integration-for-telegram"),
          "regular_price"      => _x("Product Regular Price", "macro", "ultimate-integration-for-telegram"),
          "type"               => _x("Product Type", "macro", "ultimate-integration-for-telegram"),
          "description"        => _x("Product short description", "macro", "ultimate-integration-for-telegram"),
          "date_published"     => _x("Product published date", "macro", "ultimate-integration-for-telegram"),
          "date_modified"      => _x("Product modified date", "macro", "ultimate-integration-for-telegram"),
          "date_jpublished"    => _x("Product published date in Jalali", "macro", "ultimate-integration-for-telegram"),
          "date_jmodified"     => _x("Product modified date in Jalali", "macro", "ultimate-integration-for-telegram"),
          "stock_status"       => _x("Product stock status", "macro", "ultimate-integration-for-telegram"),
          "stock_quantity"     => _x("Product stock quantity", "macro", "ultimate-integration-for-telegram"),
          "stock_quantity_new" => _x("Product stock quantity (new)", "macro", "ultimate-integration-for-telegram"),
          "product_meta"       => _x("Product meta Array", "macro", "ultimate-integration-for-telegram"),
          "view_url"           => _x("Product View URL", "macro", "ultimate-integration-for-telegram"),
          "edit_url"           => _x("Product Edit URL", "macro", "ultimate-integration-for-telegram"),
        );
        $new_macros = array(
          "wc_product_updated" => array(
            "title" => __("Product Information", "ultimate-integration-for-telegram"),
            "macros" => $product_macros_default,
          ),
        );
        $macros = array_merge($macros, $new_macros);
      }
      return (array) $macros;
    }
    public function send_msg_on_update($product_id=0) {
      $notif_list = $this->get_notifications_by_type("wc_product_updated");
      foreach ((array) $notif_list as $notif) {
        $res = @$this->send_telegram_msg(
          $notif->config->message,
          $notif->config->buttons,
          __CLASS__,
          ["product_id" => $product_id],
          $notif->config->html_parser,
          false,
          $notif->config->recipients
        );
      }
    }
    public function send_msg_on_outofstock($product_id=0, $stock_status="") {
      if ($stock_status === 'outofstock') {
        $notif_list = $this->get_notifications_by_type("wc_product_stock_outofstock");
        foreach ((array) $notif_list as $notif) {
          $res = @$this->send_telegram_msg(
            $notif->config->message,
            $notif->config->buttons,
            __CLASS__,
            ["product_id" => $product_id],
            $notif->config->html_parser,
            false,
            $notif->config->recipients
          );
        }
      }
      if ($stock_status === 'low_stock') {
        $notif_list = $this->get_notifications_by_type("wc_product_stock_low_stock");
        foreach ((array) $notif_list as $notif) {
          @$this->send_telegram_msg(
            $notif->config->message,
            $notif->config->buttons,
            __CLASS__,
            ["product_id" => $product_id],
            $notif->config->html_parser,
            false,
            $notif->config->recipients
          );
        }
      }
    }
    public function send_msg_on_stock_update($sql="", $product_id=0, $new_stock=0, $operation="") {
      switch ($operation) {
        case 'increase':
          $notif_list = $this->get_notifications_by_type("wc_product_stock_increased");
          break;

        case 'decrease':
          $notif_list = $this->get_notifications_by_type("wc_product_stock_decreased");
          break;

        case 'set':
          $notif_list = $this->get_notifications_by_type("wc_product_stock_changed");
          break;

        default:
          $notif_list = [];
          break;
      }
      foreach ((array) $notif_list as $notif) {
        @$this->send_telegram_msg(
          $notif->config->message,
          $notif->config->buttons,
          __CLASS__,
          ["product_id" => $product_id, "stock_quantity_new" => $new_stock,],
          $notif->config->html_parser,
          false,
          $notif->config->recipients
        );
      }
      return $sql;
    }
  }
}
new Ultimate_Integration_Telegram_Products;
