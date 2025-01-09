<?php
/*
 * Plugin Name: BlackSwan - Telegram Notification
 * Description: Send Customized WooCommerce Emails/Notifications to Telegram Channel/Group/Bot/PrivateChat with Built-in Gettext Replace, Translation Manager and String Replace across site
 * Version: 1.0.0
 * Stable tag: 1.0.0
 * Author: BlackSwan
 * Author URI: https://amirhp.com/landing
 * Plugin URI: https://wordpress.org/plugins/blackswan-telegram/
 * Contributors: amirhpcom, blackswanlab, pigmentdev
 * Tags: woocommerce, telegram, notification
 * Requires PHP: 7.0
 * Requires at least: 5.0
 * Tested up to: 6.5.3
 * WC requires at least: 5.0
 * WC tested up to: 9.5.1
 * Text Domain: blackswan-telegram
 * Domain Path: /languages
 * Copyright: (c) BlackSwanDev, All rights reserved.
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * @Last modified by: amirhp-com <its@amirhp.com>
 * @Last modified time: 2025/01/09 12:31:02
*/

namespace BlackSwan\Telegram;

defined("ABSPATH") or die("<h2>Unauthorized Access!</h2><hr><small>BlackSwan - Telegram Notification :: Developed by <a href='https://amirhp.com/'>Amirhp-com</a></small>");

if (!class_exists("Notifier")) {
  class Notifier {
    protected $td = "blackswan-telegram";
    protected $db_slug = "blackswan-telegram";
    protected $version = "1.0.0";
    protected $title = "BlackSwan - Telegram Notification";
    protected $title_small = "Telegram Notification";
    protected $assets_url;
    protected $hook_url;
    protected $config;
    protected $str_replace;
    protected $gettext_replace;
    protected $include_dir;
    protected $debug = false;
    public function __construct() {
      $this->setup_variables();
      #region hooks >>>>>>>>>>>>>
      add_action("init", array($this, "init_plugin"));
      add_action("template_redirect", array($this, "handle_bot_webhook"));
      add_action("wp_ajax_{$this->td}", array($this, "handel_ajax_req"));
      #endregion
      #region string-replace and translate-replace >>>>>>>>>>>>>
      add_filter("gettext", array($this, "gettext_translate"), 999999, 3);
      add_filter("the_content", array($this, "str_replace_translate"), 999999);
      add_action("template_redirect", array($this, "buffer_start_replace_translate"));
      add_action("shutdown", array($this, "buffer_finish_replace_translate"));
      #endregion
    }
    private function handle_bot_webhook(){
      if (isset($_REQUEST[$this->td]) && !empty($_REQUEST[$this->td]) && "webhook" == $_REQUEST[$this->td]) {
        try {
          $telegram = new \Longman\TelegramBot\Telegram($this->config["api_key"], $this->config["bot_username"]);
          $telegram->addCommandsPaths($this->config["commands"]["paths"]);
          $telegram->setDownloadPath($this->config["paths"]["download"]);
          $telegram->setUploadPath($this->config["paths"]["upload"]);
          foreach ($this->config['commands']['configs'] as $command_name => $command_config) {
            $telegram->setCommandConfig($command_name, $command_config);
          }
          $telegram->enableLimiter($this->config['limiter']);
          $telegram->handle();
        } catch (\Longman\TelegramBot\Exception\TelegramException $e) {
          \Longman\TelegramBot\TelegramLog::error($e);
          if ($this->debug) { error_log("BlackSwan - Telegram Notification :: debugging ~> " . PHP_EOL . print_r($e, 1)); }
        } catch (\Longman\TelegramBot\Exception\TelegramLogException $e) {
          if ($this->debug) { error_log("BlackSwan - Telegram Notification :: debugging ~> " . PHP_EOL . print_r($e, 1)); }
        }
        die("<!-- BlackSwan - Telegram Notification :: Webhook done ./ -->");
      }
    }
    private function setup_variables() {
      $this->assets_url = plugins_url("/assets/", __FILE__);
      $this->include_dir = plugin_dir_path(__FILE__) . "include";
      $this->debug = $this->enabled("debug");
      $this->str_replace = $this->read("str_replace");
      $this->gettext_replace = $this->read("gettext_replace");
      $this->hook_url = add_query_arg([$this->td=>"webhook"], home_url());
      $this->config = array(
        "api_key" => $this->read("token"),
        "bot_username" => $this->read("username"),
        "secret" => "super_secret",
        "webhook" => ["url" => $this->hook_url,],
        "commands" => ["paths" => ["{$this->include_dir}/CustomCommands"], "configs" => ["setup" => []]],
        "admins" => [ ],
        "limiter" => [],
        "paths" => [
          "download" => "{$this->include_dir}/temp/download",
          "upload"   => "{$this->include_dir}/temp/upload",
        ],
      );
      add_action("before_woocommerce_init", [$this, "add_hpos_support"]);
    }
    #region hooked-functions >>>>>>>>>>>>>
    public function init_plugin() {
      $this->title = __("BlackSwan - Telegram Notification", $this->td);
      $this->title_small = __("Telegram Notification", $this->td);
      $this->include_class();
      if ($this->enabled("jdate")) {
        require "{$this->include_dir}/class-jdate.php";
        add_filter("date_i18n", array($this, "jdate"), 10, 4);
        add_action("woocommerce_email_before_order_table", function($order, $sent_to_admin, $plain_text, $email){ add_filter("date_i18n", array($this, "jdate"), 10, 4); }, 10, 4);
        add_action("woocommerce_admin_order_data_after_payment_info", function($order){ remove_filter("date_i18n", array($this, "jdate"), 10); });
        add_action("woocommerce_admin_order_data_after_order_details", function($order){ add_filter("date_i18n", array($this, "jdate"), 10, 4); });
      }
    }
    public function jdate($date, $format, $timestamp, $gmt){
      return pu_jdate($format, $timestamp, "", "local");
    }
    public function include_class(){
      require "{$this->include_dir}/vendor/autoload.php";
      require "{$this->include_dir}/class-setting.php";
    }
    public function add_hpos_support() {
      if (class_exists(\Automattic\WooCommerce\Utilities\FeaturesUtil::class)) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
      }
    }
    public function handel_ajax_req() {
      if (wp_doing_ajax() && $_POST["action"] == $this->td) {
        if (!isset($_POST["nonce"])) {
          wp_send_json_error(array("msg" => __("Unauthorized Access!", $this->td)));
        }
        if (!wp_verify_nonce($_POST["nonce"], $this->td)) {
          wp_send_json_error(array("msg" => __("Unauthorized Access!", $this->td)));
        }
        switch ($_POST["wparam"]) {

          case "connect":
            try {
              $telegram = new \Longman\TelegramBot\Telegram($this->read("token"), $this->read("username"));
              $result = $telegram->setWebhook($this->hook_url);
              if ($result->isOk()) wp_send_json_success(array("msg" => $result->getDescription()));
            } catch (\Longman\TelegramBot\Exception\TelegramException $e) {
              wp_send_json_error(array("msg" => $e->getMessage()));
            }
          break;

          case "disconnect":
            try {
              $telegram = new \Longman\TelegramBot\Telegram($this->read("token"), $this->read("username"));
              $result = $telegram->deleteWebhook();
              if ($result->isOk()) wp_send_json_success(array("msg" => $result->getDescription()));
            } catch (\Longman\TelegramBot\Exception\TelegramException $e) {
              wp_send_json_error(array("msg" => $e->getMessage()));
            }
          break;

          case 'test_token':
            wp_send_json_success(["msg" => "all done successfully"]);
          break;

          case 'send_test':
            $message = "✅ **BlackSwan - Telegram Notification v.{$this->version}**";
            $message .= "\nTest Message Sent from " . home_url();
            $message .= "\nServer Date: " . date_i18n("Y/m/d H:i:s", current_time("timestamp"));
            $chat_ids  = $this->read("chat_ids");
            if (empty(trim($chat_ids))) wp_send_json_error(["msg"=>__("No Chat ID found. Please add a Chat ID to send a test message.",$this->td)]);
            $chat_ids  = explode("\n", $chat_ids);
            $chat_ids  = array_map("trim", $chat_ids);
            $res_array = []; $failed = false;
            if (empty($chat_ids)) wp_send_json_error(["msg"=>__("No Chat ID found. Please add a Chat ID to send a test message.",$this->td)]);
            $markup = ["inline_keyboard" => [[
              ["text" => "🏠", "url"  => home_url()],
              ["text" => "⚙️","url"  => admin_url("options-general.php?page={$this->td}")],
              ["text" => "😍","url"  => "https://amirhp.com/landing"],
            ]]];
            try {
              $telegram  = new \Longman\TelegramBot\Telegram($this->read("token"), $this->read("username"));
              /* sample send document
                $result = (new \Longman\TelegramBot\Request)::sendDocument(array(
                  "chat_id" => $chat,
                  "caption" => $message,
                  "document" => (new \Longman\TelegramBot\Request)::encodeFile($pdf_temp),
                  "reply_markup" => $markup,
                  "parse_mode" => "markdown",
                ));
               */
              foreach ($chat_ids as $chat) {
                $result = (new \Longman\TelegramBot\Request)::sendMessage(["chat_id" => $chat, "text" => $message, "reply_markup" => $markup, "parse_mode" => "markdown"]);
                if ($result->isOk()) {
                  $res_array[] = sprintf(__("Test Message sent successfully to ChatID: %s", $this->td), $chat);
                }
                else {
                  if ($this->debug) { error_log("BlackSwan - Telegram Notification :: debugging send test msg ~> " . PHP_EOL . var_export($result, 1)); }
                  $res_array[] = sprintf(__("Error sending test message to ChatID: %s", $this->td), $chat);
                  $failed = true;
                }
              }
            } catch (\Longman\TelegramBot\Exception\TelegramException $e) {
              $failed = true;
              $res_array[] = sprintf(__("Error Occured: %s", $this->td), $e->getMessage());
              if ($this->debug) { error_log("BlackSwan - Telegram Notification :: debugging send test msg ~> " . PHP_EOL . var_export($e, 1)); }
            }
            if ($failed) {
              wp_send_json_error(["msg"=> implode(PHP_EOL, $res_array)]);
            }else{
              wp_send_json_success(["msg"=> implode(PHP_EOL, $res_array)]);
            }
          break;
          default:
            wp_send_json_error(["msg" => __("An unknown error occured.", $this->td), "err" => "loop-default",]);
            break;
        }
        wp_send_json_error(["msg" => __("An unknown error occured.", $this->td), "err" => "out-of-loop",]);
      }
    }
    #endregion
    #region string-replace and translate-replace >>>>>>>>>>>>>
    public function gettext_translate($translated_text, $text_to_translate, $domain) {
      try {
        $debug = json_decode($this->gettext_replace);
        if (isset($debug->gettext)) {
          foreach ($debug->gettext as $obj) {
            $original    = trim($obj->original);
            $translate   = trim($obj->translate);
            $text_domain = trim($obj->text_domain);
            if (!empty($text_domain) && $text_domain != $domain) continue;
            $use_replace = empty($obj->use_replace) ? false : true;
            // $use_regex_replace = empty($obj->use_regex) ? false : true;
            $use_origin_as_translated = empty($obj->two_sided) ? false : true;
            if ($use_replace) {
              if ($use_origin_as_translated) {
                $translated_text = str_replace($original, $translate, $translated_text);
              } else {
                if (stripos($translated_text, $original) != false) {
                  $translated_text = str_replace($original, $translate, $text_to_translate);
                }
              }
            }
            // if ($use_regex_replace) {
            //   if ($use_origin_as_translated) {
            //     // $translated_text = preg_replace($original, $translate, $translated_text);
            //   }else{
            //     // $translated_text = preg_replace($original, $translate, $text_to_translate);
            //   }
            // }
            if ($original == $text_to_translate) {
              $translated_text = $translate;
            }
            if ($use_origin_as_translated && $original == $translated_text) {
              $translated_text = $translate;
            }
          }
        }
      } catch (\Throwable $th) {
      }
      return $translated_text;
    }
    public function str_replace_translate($content) {
      try {
        $debug = json_decode($this->str_replace);
        if (isset($debug->gettext)) {
          foreach ($debug->gettext as $obj) {
            if ("yes" != $obj->active) continue;
            $original = trim($obj->original);
            $translate = trim($obj->translate);
            $buffer = $obj->buffer;
            if ($buffer != "yes") {
              $content = str_replace($original, $translate, $content);
            }
          }
        }
      } catch (\Throwable $th) {
      }
      return $content;
    }
    public function buffer_start_replace_translate() {
      ob_start(function ($content) {
        try {
          $debug = json_decode($this->str_replace);
          if (isset($debug->gettext)) {
            foreach ($debug->gettext as $obj) {
              if ("yes" != $obj->active) continue;
              $original = trim($obj->original);
              $translate = trim($obj->translate);
              $buffer = $obj->buffer;
              if ($buffer == "yes") {
                $content = str_replace($original, $translate, $content);
              }
            }
          }
        } catch (\Throwable $th) {
        }
        return $content;
      });
    }
    public function buffer_finish_replace_translate() {
      while (@ob_end_flush());
    }
    #endregion
    #region setting-related functions >>>>>>>>>>>>>
    protected function read($slug = "debug", $default = "") {
      return (string) get_option("{$this->db_slug}__{$slug}", $default);
    }
    protected function enabled($slug = "debug", $default = "no", $compare = "yes") {
      return $compare === (string) $this->read($slug, $default);
    }
    #endregion
    #region display-user, sanitize-number, get user ip >>>>>>>>>>>>>
    public function display_user($uid = 0, $link = false, $id = true) {
      $user_info = get_userdata($uid);
      if ($user_info) {
        if ($link) {
          return sprintf(
            "<a target='_blank' href='%s' title='%s'>%s</a>%s",
            admin_url("user-edit.php?user_id=$uid"),
            "Email: $user_info->user_email" . PHP_EOL . "Mobile: " . ((string) get_user_meta($uid, "user_mobile", true)) . PHP_EOL . "Username: $user_info->user_login" . PHP_EOL . "Registeration: $user_info->user_registered" . PHP_EOL,
            (trim("$user_info->first_name $user_info->last_name") == "" ? $user_info->user_login : "$user_info->first_name $user_info->last_name"),
            ($id ? "&nbsp;&nbsp;<sup title='ID $uid'>ID: $uid</sup>" : "")
          );
        } else {
          return "$user_info->first_name $user_info->last_name";
        }
      } else {
        return sprintf(__("ID #%s [deleted-user]", $this->td), $uid);
      }
    }
    public function sanitize_number($num) {
      $num_pairs = array("۰" => "0", "۱" => "1", "۲" => "2", "۳" => "3", "۴" => "4", "۵" => "5", "۶" => "6", "۷" => "7", "۸" => "8", "۹" => "9");
      return strtr($num, $num_pairs);
    }
    public function get_real_IP_address() {
      if (!empty($_SERVER["GEOIP_ADDR"])) {
        $ip = wp_unslash($_SERVER["GEOIP_ADDR"]);
      } elseif (!empty($_SERVER["HTTP_X_REAL_IP"])) {
        $ip = wp_unslash($_SERVER["HTTP_X_REAL_IP"]);
      } elseif (!empty($_SERVER["HTTP_CLIENT_IP"])) {
        $ip = wp_unslash($_SERVER["HTTP_CLIENT_IP"]);
      } elseif (!empty($_SERVER["HTTP_X_FORWARDED_FOR"])) {
        $ip = wp_unslash($_SERVER["HTTP_X_FORWARDED_FOR"]);
      } else {
        $ip = wp_unslash($_SERVER["REMOTE_ADDR"]);
      }
      return sanitize_text_field($ip);
    }
    #endregion
    #region php8 string-related functions >>>>>>>>>>>>>
    public static function str_starts_with(string $haystack, string $needle): bool {
      return 0 === strncmp($haystack, $needle, \strlen($needle));
    }
    public static function str_ends_with(string $haystack, string $needle): bool {
      return '' === $needle || ('' !== $haystack && 0 === substr_compare($haystack, $needle, -\strlen($needle)));
    }
    #endregion
  }
  add_action("plugins_loaded", function () {
    load_plugin_textdomain("blackswan-telegram", false, dirname(plugin_basename(__FILE__)) . "/languages/");
    global $BlackSwan_Telegram_Notifier;
    $BlackSwan_Telegram_Notifier = new Notifier;
  }, 2);
}
/*##################################################
Lead Developer: [amirhp-com](https://amirhp.com/)
##################################################*/