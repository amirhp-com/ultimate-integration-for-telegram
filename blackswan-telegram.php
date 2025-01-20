<?php
/*
 * Plugin Name: Ultimate Telegram Integration
 * Description: Integrate Telegram with WordPress, WooCommerce, and a wide range of plugins. Send customized notifications to channels, groups, bots, or private chats with built-in advanced translation and string replacement tools.
 * Version: 1.0.2
 * Stable tag: 1.0.2
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
 * @Last modified time: 2025/01/20 11:47:10
*/

namespace BlackSwan\Telegram;
defined("ABSPATH") or die("<h2>Unauthorized Access!</h2><hr><small>Ultimate Telegram Integration :: Developed by <a href='https://amirhp.com/'>Amirhp-com</a></small>");

if (!class_exists("Notifier")) {
  class Notifier {
    protected $td = "blackswan-telegram";
    protected $db_slug = "blackswan-telegram";
    protected $version = "1.0.0";
    protected $title = "Ultimate Telegram Integration";
    protected $title_small = "Telegram";
    protected $assets_url;
    protected $hook_url;
    protected $cog_url;
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
      add_filter("plugin_action_links_" . plugin_basename(__FILE__), array($this, "add_plugin_settings_link"));
      #endregion
      #region string-replace and translate-replace >>>>>>>>>>>>>
      add_filter("gettext", array($this, "gettext_translate"), 999999, 3);
      add_filter("the_content", array($this, "str_replace_translate"), 999999);
      add_action("template_redirect", array($this, "buffer_start_replace_translate"));
      add_action("shutdown", array($this, "buffer_finish_replace_translate"));
      #endregion
    }
    private function setup_variables() {
      $this->cog_url = admin_url("options-general.php?page={$this->td}#tab_general");
      $this->assets_url = plugins_url("/assets/", __FILE__);
      $this->include_dir = plugin_dir_path(__FILE__) . "include";

      require "{$this->include_dir}/vendor/autoload.php";

      $this->debug = $this->enabled("debug");
      $this->str_replace = $this->read("str_replace");
      $this->gettext_replace = $this->read("gettext_replace");
      $this->hook_url = home_url("?{$this->td}=webhook");
      $this->config = array(
        "api_key" => $this->read("token"),
        "bot_username" => $this->read("username"),
        "secret" => "super_secret",
        "webhook" => ["url" => $this->hook_url,],
        "commands" => ["paths" => ["{$this->include_dir}/CustomCommands"], "configs" => ["setup" => []]],
        "admins" => [],
        "limiter" => [],
        "paths" => [
          "download" => "{$this->include_dir}/temp/download",
          "upload"   => "{$this->include_dir}/temp/upload",
        ],
      );
      add_action("before_woocommerce_init", [$this, "add_hpos_support"]);
    }
    public function handle_bot_webhook(){
      if (isset($_REQUEST["blackswan-telegram"]) && !empty($_REQUEST["blackswan-telegram"]) && "webhook" == $_REQUEST["blackswan-telegram"]) {
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
          if ($this->debug) { error_log("Ultimate Telegram Integration :: debugging ~> " . PHP_EOL . print_r($e, 1)); }
        } catch (\Longman\TelegramBot\Exception\TelegramLogException $e) {
          if ($this->debug) { error_log("Ultimate Telegram Integration :: debugging ~> " . PHP_EOL . print_r($e, 1)); }
        }
        die("<!-- Ultimate Telegram Integration :: Webhook done ./ -->");
      }
    }
    public function get_notifications_by_type($type=[]){
      $all_notif = $this->read("notifications");
      if (empty($all_notif)) return [];
      $all_notif = json_decode($all_notif);
      if (!is_array($all_notif) || empty($all_notif)) return [];
      if (!empty($type)) {
        $all_notif = array_filter($all_notif, function($arg) use ($type){return in_array($arg->type, (array) $type) && $arg->config->_enabled == "yes";});
      }
      return $all_notif;
    }
    #region hooked-functions >>>>>>>>>>>>>
    public function init_plugin() {
      $this->title = __("Ultimate Telegram Integration", "blackswan-telegram");
      $this->title_small = __("Telegram", "blackswan-telegram");
      $this->include_class();
      if ($this->enabled("jdate")) {
        add_filter("date_i18n", array($this, "jdate"), 10, 4);
        add_action("woocommerce_email_before_order_table", function($order, $sent_to_admin, $plain_text, $email){ add_filter("date_i18n", array($this, "jdate"), 10, 4); }, 10, 4);
        add_action("woocommerce_admin_order_data_after_payment_info", function($order){ remove_filter("date_i18n", array($this, "jdate"), 10); });
        add_action("woocommerce_admin_order_data_after_order_details", function($order){ add_filter("date_i18n", array($this, "jdate"), 10, 4); });
      }
      add_action("admin_bar_menu", array($this, "add_link_to_admin_bar"), 100);
      do_action("blackswan-telegram/init");
    }
    public function add_link_to_admin_bar($wp_admin_bar) {
      if ($this->enabled("admin_bar_link")) {
        $wp_admin_bar->add_node(array( "id" => $this->td, "title" => $this->title_small, "href" => $this->cog_url, ));
      }
    }
    public function add_plugin_settings_link($links) {
      $links[$this->td] = '<a href="' . esc_attr(admin_url("options-general.php?page={$this->td}#tab_general")) . '">' . _x("Settings", "action-row", "blackswan-telegram") . '</a>';
      return $links;
    }
    public function jdate($date, $format, $timestamp, $gmt){
      return pu_jdate($format, $timestamp, "", "local", "en");
    }
    public function include_class(){
      require "{$this->include_dir}/class-jdate.php";
      require "{$this->include_dir}/class-setting.php";
      require "{$this->include_dir}/hooks/class-wp-hook.php";
      require "{$this->include_dir}/hooks/class-wc-product.php";
      require "{$this->include_dir}/hooks/class-wc-order.php";
      do_action("blackswan-telegram/load-library");
    }
    public function add_hpos_support() {
      if (class_exists(\Automattic\WooCommerce\Utilities\FeaturesUtil::class)) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
      }
    }
    public function handel_ajax_req() {
      if (wp_doing_ajax() && $_POST["action"] == $this->td) {
        if (!isset($_POST["nonce"])) {
          wp_send_json_error(array("msg" => __("Unauthorized Access!", "blackswan-telegram")));
        }
        if (!wp_verify_nonce(wp_unslash($_POST["nonce"]), "blackswan-telegram")) {
          wp_send_json_error(array("msg" => __("Unauthorized Access!", "blackswan-telegram")));
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


          case 'send_test':
            global $wp_version;
            $wc_version = defined('WC_VERSION') ? WC_VERSION : "Not Found";
            $site_host = wp_parse_url(home_url(), PHP_URL_HOST);

            $message = "Hi there! 👋\n\n*Welcome to Ultimate Telegram Integrations*\n" .
            "Seamlessly connect your WordPress site & WooCommerce store to Telegram.\n\n" .
            "With our Plugin, you’ll receive instant, real-time notifications for important events like new WooCommerce orders, user registrations, and WordPress emails. Replace traditional email notifications with fast and customizable Telegram messages tailored to your needs.\n\n" .
            "Receive notifications wherever you want:\n" .
            "- In *groups* or *channels* by adding this bot as an Administrator.\n" .
            "- Directly in this *private chat* with the bot for instant updates.\n\n" .
            ">*Your Site Information:*\n" .
            ">Host URL: {$site_host}\n".
            ">Server Date: " . wp_date("Y/m/d H:i:s", current_time("timestamp")) . "\n" .
            ">Server jDate: " . pu_jdate("Y/m/d H:i:s", current_time("timestamp"), "", "local", "en") . "\n" .
            ">Plugin Version: {$this->version}\n".
            ">WordPress: {$wp_version}\n".
            ">WooCommerce: {$wc_version}\n".
            ">PHP Version: " . PHP_VERSION."".
            "\n
            "."\nTo get started:\n" .
            "1️⃣ Add me as an *Administrator* to your group or channel.\n" .
            "2️⃣ Send the command /setup to get the *Chat ID*.\n" .
            "3️⃣ Go to the *Settings* on your site & add it to the list.\n\n" .
            "🆔 *Your Chat ID:* `{chat_id}` _(specific to this chat)_\n\n" .
            "👨‍🔧 For support or questions, contact: @amirhp\\_com";
            $message = str_replace(
              [
                "(",
                ")",
                "[",
                "]",
                ".",
                "!",
                "-",
              ], [
                "\\(",
                "\\)",
                "\\[",
                "\\]",
                "\\.",
                "\\!",
                "\\-",
              ],
              $message
            );

            $markup = array(
              array(
                ["text" => "🏠 Home", "url"  => home_url()],
                ["text" => "⚙️ Config","url"  => admin_url("options-general.php?page={$this->td}")],
                ["text" => "😍 Developer", "url"  => "https://amirhp.com/landing"],
              ),
              array(
                ['text' => "💻 Contribute (Github)", "url" => "https://github.com/blackswandevcom/blackswan-telegram"],
                ['text' => "🍺 Buy me a Beer (Donate)", "url" => "https://amirhp.com/contact#payment"],
              ),
              array(
                ['text' => "🌏 Ultimate Telegram Integration", "url" => "https://wordpress.org/plugins/blackswan-telegram/"],
              ),
            );

            $chat_ids  = $this->read("chat_ids");
            if (empty(trim($chat_ids))) wp_send_json_error(["msg"=>__("No Chat ID found. Please add a Chat ID to send a test message.", "blackswan-telegram")]);
            $chat_ids  = explode("\n", $chat_ids);
            $chat_ids  = array_map("trim", $chat_ids);
            $res_array = []; $failed = false; $errors = []; $errors2 = []; $errors3 = [];
            if (empty($chat_ids)) wp_send_json_error(["msg"=>__("No Chat ID found. Please add a Chat ID to send a test message.", "blackswan-telegram")]);
            try {
              $telegram  = new \Longman\TelegramBot\Telegram($this->read("token"), $this->read("username"));
              /* sample send document
                $result = (new \Longman\TelegramBot\Request)::sendDocument(array(
                  "chat_id" => $chat,
                  "caption" => $message,
                  "document" => (new \Longman\TelegramBot\Request)::encodeFile($pdf_temp),
                  "reply_markup" => ["inline_keyboard"=>$markup],
                  "parse_mode" => "MarkdownV2",
                ));
               */
              foreach ($chat_ids as $chat) {
                $msg = str_replace("{chat_id}", $chat, $message);
                $result = (new \Longman\TelegramBot\Request)::sendMessage([
                  "chat_id"                  => $chat,
                  "text"                     => $msg,
                  "protect_content"          => true,
                  "quote"                    => "Host URL",
                  "disable_web_page_preview" => true,
                  "reply_markup"             => ["inline_keyboard" => $markup],
                  "parse_mode"               => "MarkdownV2",
                ]);
                if ($result->isOk()) {
                  /* translators: 1: Chat ID */
                  $res_array[] = sprintf(__("Test Message sent successfully to ChatID: %s", "blackswan-telegram"), $chat);
                }
                else {
                  $errors[] = var_export($result, 1);
                  $errors2[] = print_r($result, 1);
                  $errors3[] = $result->getDescription();
                  if ($this->debug) { error_log("Ultimate Telegram Integration :: debugging send test msg ~> " . PHP_EOL . var_export($result, 1)); }
                  /* translators: 1: Chat ID */
                  $res_array[] = sprintf(__("Error sending test message to ChatID: %s", "blackswan-telegram") . "<br>" . $result->getDescription(), $chat);
                  $failed = true;
                }
              }
            } catch (\Longman\TelegramBot\Exception\TelegramException $e) {
              $failed = true;
              $errors2[] = print_r([$e->getMessage(), $e], 1);
              $errors[] = var_export($e->getMessage(), 1);
              /* translators: 1: Error message */
              $res_array[] = sprintf(__("Error Occured: %s", "blackswan-telegram"), $e->getMessage());
              if ($this->debug) { error_log("Ultimate Telegram Integration :: debugging send test msg ~> " . PHP_EOL . var_export($e, 1)); }
            }
            if ($failed) {
              wp_send_json_error(["msg"=> implode(PHP_EOL, $res_array), "err_msg" => $errors3, "err" => $errors, "err2" => $errors2,]);
            }else{
              wp_send_json_success(["msg"=> implode(PHP_EOL, $res_array)]);
            }
          break;

          default:
            wp_send_json_error(["msg" => __("An unknown error occured.", "blackswan-telegram"), "err" => "loop-default",]);
            break;
        }
        wp_send_json_error(["msg" => __("An unknown error occured.", "blackswan-telegram"), "err" => "out-of-loop",]);
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
    public static function display_user($uid = 0, $link = false, $id = true) {
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
        }
        else {
          return !empty(trim("{$user_info->first_name} {$user_info->last_name}")) ? "{$user_info->first_name} {$user_info->last_name}" : (!empty(trim($user_info->display_name)) ? $user_info->display_name : $user_info->user_login);
        }
      } else {
        /* translators: 1: User ID */
        return sprintf(__("ID #%s [deleted-user]", "blackswan-telegram"), $uid);
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
    public static function str_starts_with($haystack, $needle): bool {
      return 0 === strncmp($haystack, $needle, \strlen($needle));
    }
    public static function str_ends_with($haystack, $needle): bool {
      return '' === $needle || ('' !== $haystack && 0 === substr_compare($haystack, $needle, -\strlen($needle)));
    }
    public static function remove_status_prefix( string $status ): string {
      if ( strpos( $status, 'wc-' ) === 0 ) $status = substr( $status, 3 );
      return $status;
    }
    #endregion
    #region send telegram notif >>>>>>>>>>>>>
    public function send_telegram_msg($message="", $buttons=[], $reference="", $extra_data=[], $html_parser=false, $json=false){
      // $sample = $this->tg_send_msg("Sample Test Sent!", [ ["text" => "btn1", "url" => home_url("/pg-1"),], ["text" => "btn2", "url" => home_url("/pg-2"),], ]);
      $chat_ids = $this->read("chat_ids");
      if (empty(trim($chat_ids))){
        $debug = ["msg"=>__("No Chat ID found. Please add a Chat ID to send a test message.", "blackswan-telegram")];
        if ($this->debug) { error_log("Ultimate Telegram Integration :: debugging ".current_action().": ".__METHOD__.PHP_EOL.var_export($debug,1)); }
        if ($json) { wp_send_json_error($debug); }
        return new \WP_Error("chat_id", __("No Chat ID found. Please add a Chat ID to send a test message.", "blackswan-telegram"));
      }

      $chat_ids  = explode("\n", $chat_ids);
      $chat_ids  = array_map("trim", $chat_ids);
      $chat_ids  = array_unique($chat_ids);
      $res_array = []; $failed = false; $errors = []; $errors2 = [];
      if (empty($chat_ids)) {
        $debug = ["msg"=>__("No Chat ID found. Please add a Chat ID to send a test message.", "blackswan-telegram")];
        if ($this->debug) { error_log("Ultimate Telegram Integration :: debugging ".current_action().": ".__METHOD__.PHP_EOL.var_export($debug,1)); }
        if ($json) { wp_send_json_error($debug); }
        return new \WP_Error("chat_id", __("No Chat ID found. Please add a Chat ID to send a test message.", "blackswan-telegram"));
      }

      if (is_string($buttons) && !empty($buttons)) {
        $markup = array();
        $button = explode("\n", $buttons);
        foreach ($button as $btn) {
          list($text, $url) = explode("|", $btn);
          $markup[] = [
            "text" => $this->translate_param($text, "SANITIZE_BTN", $extra_data, []),
            "url" => $this->sanitize_url($url, $extra_data),
          ];
        }
        $buttons = $markup;
      }

      if (!is_bool($html_parser)) $html_parser = "yes" == $html_parser;
      $message = $this->translate_param($message, $reference, $extra_data, []);

      if ($buttons && !empty($buttons)) {
        $buttons = apply_filters("blackswan-telegram/helper/send-message-buttons", array_slice($buttons, 0, 4), $buttons, $reference, func_get_args());
        $markup = array($buttons, [['text' => "🌏 Ultimate Telegram Integration", "url" => "https://wordpress.org/plugins/blackswan-telegram/"],] );
      }else{
        $markup = [[['text' => "🌏 Ultimate Telegram Integration", "url" => "https://wordpress.org/plugins/blackswan-telegram/"],]];
      }
      try {
        $telegram  = new \Longman\TelegramBot\Telegram($this->read("token"), $this->read("username"));
        foreach ($chat_ids as $chat) {
          $tg = (new \Longman\TelegramBot\Request);
          $method = apply_filters("blackswan-telegram/helper/send-message-method", "sendMessage", func_get_args());
          $site_host = wp_parse_url(home_url(), PHP_URL_HOST);
          $host_ip = $_SERVER['SERVER_ADDR'];
          if ($html_parser) {
            $message .= "\n\n<blockquote>Disclaimer: sent via Ultimate Telegram Integration Plugin for WordPress from Host: $site_host ($host_ip)</blockquote>";
          }else{
            $message .= "\n\n-----------\n_Disclaimer: sent via Ultimate Telegram Integration Plugin for WordPress from Host: $site_host ($host_ip)_";
          }
          $tg_arguments = apply_filters("blackswan-telegram/helper/send-message-arguments", array(
            "chat_id"                  => $chat,
            "text"                     => $message,
            "protect_content"          => false,
            "disable_web_page_preview" => false,
            "reply_markup"             => ["inline_keyboard"=>$markup],
            "parse_mode"               => $html_parser ? "html" : "markdown",
          ), $tg, func_get_args() );
          $result = @forward_static_call_array([$tg, $method], [$tg_arguments]);
          $result = apply_filters("blackswan-telegram/helper/sent-message-result", $result, $tg, func_get_args());
          if ($result->isOk()) {
            /* translators: 1: Chat ID */
            $res_array[] = sprintf(__("Test Message sent successfully to ChatID: %s", "blackswan-telegram"), $chat);
            if ($this->debug) {
              /* translators: 1: Chat ID */
              error_log("Ultimate Telegram Integration :: debugging send msg ~> REF:" . $reference . " - " . sprintf(__("Test Message sent successfully to ChatID: %s", "blackswan-telegram"), $chat) . PHP_EOL . var_export($result, 1));
            }
          }
          else {
            $errors[] = var_export($result, 1);
            $errors2[] = print_r($result, 1);
            if ($this->debug) { error_log("Ultimate Telegram Integration :: debugging send msg ~> " . PHP_EOL . var_export($result, 1)); }
            /* translators: 1: Chat ID */
            $res_array[] = sprintf(__("Error sending test message to ChatID: %s", "blackswan-telegram"), $chat);
            $failed = true;
          }
        }
      }
      catch (\Longman\TelegramBot\Exception\TelegramException $e) {
        $failed = true;
        $errors2[] = print_r([$e->getMessage(), $e], 1);
        $errors[] = var_export($e->getMessage(), 1);
        /* translators: 1: Error message */
        $res_array[] = sprintf(__("Error Occured: %s", "blackswan-telegram"), $e->getMessage());
        if ($this->debug) { error_log("Ultimate Telegram Integration :: debugging send msg ~> " . PHP_EOL . var_export($e, 1)); }
      }
      if ($failed) {
        $debug = ["msg"=> implode(PHP_EOL, $res_array), "err" => $errors, "err2" => $errors2, "message" => $message,];
        do_action("blackswan-telegram/helper/send-message-result/failed", $debug, func_get_args());
        if ($this->debug) { error_log("Ultimate Telegram Integration :: debugging ".current_action().": ".__METHOD__.PHP_EOL.var_export($debug,1)); }
        if ($json) { wp_send_json_error($debug); }
        return new \WP_Error("err_send", implode(PHP_EOL, $res_array), $debug);
      }
      else{
        do_action("blackswan-telegram/helper/send-message-result/success", $res_array, func_get_args());
        if ($this->debug) { error_log("Ultimate Telegram Integration :: SUCCESS ".current_action().": ".__METHOD__.PHP_EOL.var_export(implode(PHP_EOL, $res_array),1)); }
        if ($json) {wp_send_json_success(["msg"=> implode(PHP_EOL, $res_array)]);}
        return $res_array;
      }
    }
    public function sanitize_url($url="", $extra_data=[]) {
      if (empty($url)) return home_url();
      $url = $this->translate_param($url, "SANITIZE_URL", $extra_data, []);
      #page_id / @page_slug / {special_pages} / Full URL
      if ($this->str_starts_with($url, "#")) {
        $url = get_permalink(ltrim($url, "#"));
        return $url ? sanitize_url($url) : sanitize_url(home_url($url));
      }
      if ($this->str_starts_with($url, "@")) {
        $slug = ltrim(sanitize_text_field($url), "@");
        return sanitize_url(home_url("/{$slug}"));
      }
      $url = $this->special_pages_to_url($url);
      return sanitize_url($url);
    }
    public function special_pages_to_url($url = "") {
      global $PeproDevUPS_Profile;
      if ($PeproDevUPS_Profile) {
        $profile = $PeproDevUPS_Profile->get_profile_page(true);
        $profileEdit = $PeproDevUPS_Profile->get_profile_page(["section" => "edit"]);
      }else{
        $profile = home_url("/profile");
        $profileEdit = add_query_arg(["section" => "edit"], home_url("/profile"));
      }
      $url = str_replace(
        ["{home}", "{admin}", "{profile}", "{profile_edit}",],
        [ home_url(), admin_url(), $profile, $profileEdit,],
      $url);
      return apply_filters("pepro_reglogin_special_pages", $url);
    }
    public function translate_param($message="", $reference="", $extra_data=[], $defaults=[]){
      $return = $message;
      $pairs = array(
        "ref_hook"           => $reference,
        "current_time"       => wp_date(get_option("time_format"), current_time("timestamp")),
        "current_date"       => wp_date(get_option("date_format"), current_time("timestamp")),
        "current_date_time"  => wp_date(get_option("date_format") . " " . get_option("time_format"), current_time("timestamp")),
        "current_jdate"      => pu_jdate("Y/m/d", current_time("timestamp"), "", "local", "en"),
        "current_jdate_time" => pu_jdate("Y/m/d H:i:s", current_time("timestamp"), "", "local", "en"),
        "current_user_id"    => get_current_user_id(),
        "current_user_name"  => $this->display_user(get_current_user_id(), false, false),
        "site_name"          => get_bloginfo("name"),
        "site_url"           => home_url(),
        "admin_url"          => admin_url(),
      );
      $pairs = wp_parse_args($pairs, $defaults);
      $pairs = apply_filters("blackswan-telegram/helper/translate-pairs", $pairs, $message, $reference, $extra_data, $defaults);
      foreach ($pairs as $macro => $value) {
        $return = str_replace("{".$macro."}", $value, $return);
      }
      return apply_filters("blackswan-telegram/helper/translated-message", $return, $message, $pairs, $reference, $extra_data, $defaults);
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