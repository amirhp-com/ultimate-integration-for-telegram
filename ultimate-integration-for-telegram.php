<?php
/*
 * Plugin Name: Pigment Agent Bot
 * Description: Integrate Telegram with WordPress, WooCommerce, and a wide range of plugins. Send customized notifications to channels, groups, bots, or private chats with built-in advanced translation and string replacement tools.
 * Version: 1.4.0
 * Stable tag: 1.2.0
 * Author: BlackSwan
 * Author URI: https://amirhp.com/landing
 * Plugin URI: https://wordpress.org/plugins/ultimate-integration-for-telegram/
 * Contributors: amirhpcom, blackswanlab, pigmentdev
 * Tags: woocommerce, telegram, notification
 * Requires PHP: 7.4
 * Requires at least: 5.0
 * Tested up to: 6.8
 * WC requires at least: 5.0
 * WC tested up to: 9.9.3
 * Text Domain: ultimate-integration-for-telegram
 * Domain Path: /languages
 * Copyright: (c) BlackSwanDev, All rights reserved.
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * @Last modified by: amirhp-com <its@amirhp.com>
 * @Last modified time: 2025/07/29 19:05:48
 * https://packagist.org/packages/longman/telegram-bot#user-content-using-a-custom-bot-api-server
*/

namespace BlackSwan\Ultimate_Integration_Telegram;

use Longman\TelegramBot\Telegram;
use Longman\TelegramBot\Request;
use Longman\TelegramBot\TelegramLog;
use Longman\TelegramBot\Exception\TelegramLogException;
use Longman\TelegramBot\Exception\TelegramException;
use Longman\TelegramBot\Entities\Entity;

defined("ABSPATH") or die("<h2>Unauthorized Access!</h2><hr><small>Pigment Agent Bot :: Developed by <a href='https://amirhp.com/'>Amirhp-com</a></small>");

if (!class_exists("Notifier")) {
  class Notifier {
    protected $td = "ultimate-integration-for-telegram";
    protected $title = "Pigment Agent Bot";
    protected $db_slug = "ultimate-integration-for-telegram";
    protected $version = "1.4.0";
    protected $title_small = "Telegram";
    protected $assets_url;
    protected $hook_url;
    protected $cog_url;
    protected $config;
    protected $str_replace;
    protected $gettext_replace;
    protected $include_dir;
    protected $debug = false;
    public function __construct($full_load = true) {
      $this->setup_variables();
      if ($full_load) {
        #region hooks >>>>>>>>>>>>>
        add_action("init", array($this, "init_plugin"));
        add_action("template_redirect", array($this, "handle_bot_webhook"));
        add_action("wp_ajax_{$this->td}", array($this, "handel_ajax_req"));
        add_filter("plugin_row_meta", array($this, "add_plugin_row_meta"), 10, 2);
        add_filter("plugin_action_links_" . plugin_basename(__FILE__), array($this, "add_plugin_settings_link"));
        #endregion
        #region string-replace and translate-replace >>>>>>>>>>>>>
        add_filter("gettext", array($this, "gettext_translate"), 999999, 3);
        add_filter("the_content", array($this, "str_replace_translate"), 999999);
        add_action("template_redirect", array($this, "buffer_start_replace_translate"));
        add_action("shutdown", array($this, "buffer_finish_replace_translate"));
        #endregion
      }
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
      // (new Request)::setCustomBotApiUri('https://my-worker.amirhp.workers.dev');
      add_action("before_woocommerce_init", [$this, "add_hpos_support"]);
    }
    public function handle_bot_webhook() {
      // phpcs:ignore  WordPress.Security.NonceVerification.Recommended
      if (isset($_REQUEST["ultimate-integration-for-telegram"]) && !empty($_REQUEST["ultimate-integration-for-telegram"]) && "webhook" == $_REQUEST["ultimate-integration-for-telegram"]) {
        try {
          $telegram = new Telegram($this->config["api_key"], $this->config["bot_username"]);
          $telegram->addCommandsPaths($this->config["commands"]["paths"]);
          $telegram->setDownloadPath($this->config["paths"]["download"]);
          $telegram->setUploadPath($this->config["paths"]["upload"]);
          foreach ($this->config['commands']['configs'] as $command_name => $command_config) {
            $telegram->setCommandConfig($command_name, $command_config);
          }
          $telegram->enableLimiter($this->config['limiter']);
          $telegram->handle();
        } catch (TelegramException $e) {
          TelegramLog::error($e);
          if ($this->debug) {
            // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r, WordPress.PHP.DevelopmentFunctions.error_log_error_log
            error_log("Pigment Agent Bot :: debugging ~> " . PHP_EOL . print_r($e, 1));
          }
        } catch (TelegramLogException $e) {
          if ($this->debug) {
            // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r, WordPress.PHP.DevelopmentFunctions.error_log_error_log
            error_log("Pigment Agent Bot :: debugging ~> " . PHP_EOL . print_r($e, 1));
          }
        }
        die("<!-- Pigment Agent Bot :: Webhook done ./ -->");
      }
    }
    public function get_notifications_by_type($type = []) {
      $all_notif = $this->read("notifications");
      if (empty($all_notif)) return [];
      $all_notif = json_decode($all_notif);
      if (!is_array($all_notif) || empty($all_notif)) return [];
      if (!empty($type)) {
        $all_notif = array_filter($all_notif, function ($arg) use ($type) {
          return in_array($arg->type, (array) $type) && $arg->config->_enabled == "yes";
        });
      }
      return $all_notif;
    }
    #region hooked-functions >>>>>>>>>>>>>
    public function init_plugin() {
      load_plugin_textdomain("ultimate-integration-for-telegram", false, dirname(plugin_basename(__FILE__)) . "/languages/");
      $this->title = __("Pigment Agent Bot", "ultimate-integration-for-telegram");
      $this->title_small = __("Telegram", "ultimate-integration-for-telegram");
      $this->include_class();
      if ($this->enabled("jdate")) {
        add_filter("date_i18n", array($this, "jdate"), 10, 4);
        add_action("woocommerce_email_before_order_table", function ($order, $sent_to_admin, $plain_text, $email) {
          add_filter("date_i18n", array($this, "jdate"), 10, 4);
        }, 10, 4);
        add_action("woocommerce_admin_order_data_after_payment_info", function ($order) {
          remove_filter("date_i18n", array($this, "jdate"), 10);
        });
        add_action("woocommerce_admin_order_data_after_order_details", function ($order) {
          add_filter("date_i18n", array($this, "jdate"), 10, 4);
        });
      }
      if ($this->enabled("admin_bar_link")) {
        add_action("admin_bar_menu", array($this, "add_link_to_admin_bar"), 100);
        add_action("admin_head", function () {
          echo '<style>
          #wp-admin-bar-ultimate-integration-for-telegram {
            background-color: #52c2f04f !important;
          }
          </style>';
        });
      }
      do_action("ultimate-integration-for-telegram/init");
    }
    public function add_link_to_admin_bar($wp_admin_bar) {
      $wp_admin_bar->add_node(array(
        "id" => $this->td,
        "title" => $this->title_small,
        "href" => $this->cog_url,
        "meta" => array(
          "title" => $this->title,
          "class" => "ultimate-integration-for-telegram",
        ),
      ));
    }
    public function add_plugin_settings_link($links) {
      $new_links = ['<a href="' . esc_attr(admin_url("options-general.php?page={$this->td}#tab_general")) . '">' . _x("Settings", "action-row", "ultimate-integration-for-telegram") . '</a>',];
      return array_merge($new_links, $links);
    }
    public function add_plugin_row_meta($links, $file) {
      if ($file === plugin_basename(__FILE__)) {
        $meta_links = [
          '<a href="https://github.com/pigment-dev/ultimate-integration-for-telegram/wiki" target="_blank">' . _x("Docs", "plugin-meta", "ultimate-integration-for-telegram") . '</a>',
          '<a href="https://wordpress.org/support/plugin/ultimate-integration-for-telegram/" target="_blank">' . _x("Community Support", "plugin-meta", "ultimate-integration-for-telegram") . '</a>',
        ];
        return array_merge($links, $meta_links);
      }
      return $links;
    }
    public function jdate($date, $format, $timestamp, $gmt) {
      return Ultimate_Integration_Telegram__jdate($format, $timestamp, "", "local", "en");
    }
    public function include_class() {
      require "{$this->include_dir}/class-jdate.php";
      require "{$this->include_dir}/class-setting.php";
      require "{$this->include_dir}/hooks/class-wp-hook.php";
      if (function_exists("is_woocommerce") || class_exists("WooCommerce")) {
        require "{$this->include_dir}/hooks/class-wc-product.php";
        require "{$this->include_dir}/hooks/class-wc-order.php";
      }
      do_action("ultimate-integration-for-telegram/load-library");
    }
    public function add_hpos_support() {
      if (class_exists(\Automattic\WooCommerce\Utilities\FeaturesUtil::class)) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
      }
    }
    public function handel_ajax_req() {
      // phpcs:ignore WordPress.Security.NonceVerification.Missing
      if (wp_doing_ajax() && isset($_POST["action"]) && !empty($_POST["action"]) && sanitize_text_field(stripslashes_deep($_POST["action"])) == $this->td) {
        // phpcs:ignore WordPress.Security.NonceVerification.Missing
        if (!isset($_POST["nonce"]) || empty(sanitize_text_field(stripslashes_deep($_POST["nonce"])))) {
          wp_send_json_error(array("msg" => __("Unauthorized Access!", "ultimate-integration-for-telegram")));
        }
        // phpcs:ignore WordPress.Security.NonceVerification.Missing
        if (!wp_verify_nonce(sanitize_text_field(stripslashes_deep($_POST["nonce"])), "ultimate-integration-for-telegram")) {
          wp_send_json_error(array("msg" => __("Unauthorized Access!", "ultimate-integration-for-telegram")));
        }
        // phpcs:ignore WordPress.Security.NonceVerification.Missing
        $param = isset($_POST["wparam"]) ? sanitize_text_field(stripslashes_deep($_POST["wparam"])) : "none";
        switch ($param) {

          case "connect":
            try {
              $telegram = new Telegram($this->read("token"), $this->read("username"));
              $result = $telegram->setWebhook($this->hook_url);
              if ($result->isOk()) wp_send_json_success(array("msg" => $result->getDescription()));
            } catch (TelegramException $e) {
              wp_send_json_error(array("msg" => $e->getMessage()));
            }
            break;

          case "disconnect":
            try {
              $telegram = new Telegram($this->read("token"), $this->read("username"));
              $result = $telegram->deleteWebhook();
              if ($result->isOk()) wp_send_json_success(array("msg" => $result->getDescription()));
            } catch (TelegramException $e) {
              wp_send_json_error(array("msg" => $e->getMessage()));
            }
            break;


          case 'send_test':
            global $wp_version;
            $wc_version = defined('WC_VERSION') ? WC_VERSION : "0.0.0";
            $site_host = wp_parse_url(home_url(), PHP_URL_HOST);
            $icon = get_site_icon_url();
            $site_name = Entity::escapeMarkdownV2(get_bloginfo("name"));
            $bot_name = Entity::escapeMarkdownV2($this->read("username"));
            $message = "***Hi from [Pigment Agent Bot](https://wordpress.org/plugins/ultimate-integration-for-telegram/)***👋\n\n" .
              "Great news — your site \"***{$site_name}***\" is now successfully connected to Telegram via {$bot_name} and ready to send notifications! 😍🎉\n\n" .
              ">||→ ***site host:*** " . $site_host . "||\n" .
              ">||→ ***server time:*** " . wp_date("Y-m-d H:i:s", current_time("timestamp")) . "||\n" .
              ">||→ ***wordpress:*** " . $wp_version . "||\n" .
              ">||→ ***woocommerce:*** " . $wc_version . "||\n" .
              ">||→ ***php version:*** " . PHP_VERSION . "||";
            $message = str_replace(
              ["@", ".", "!", "-", "=",],
              ["\\@", "\\.", "\\!", "\\-", "\\=",],
              $message
            );
            $markup = [
              [
                ["text" => "🛟 Need Help? Get Instant Support", "url" => "https://t.me/pigment_dev"],
              ],
              [
                ["text" => "⚙️ Setting", "url" => admin_url("options-general.php?page=" . $this->td)],
                ["text" => "👨‍💻 Docs", "url" => "https://github.com/pigment-dev/ultimate-integration-for-telegram/wiki"],
              ],
            ];

            $chat_ids  = $this->read("chat_ids");
            if (empty(trim($chat_ids))) wp_send_json_error(["msg" => __("No Chat ID found. Please add a Chat ID to send a test message.", "ultimate-integration-for-telegram")]);
            $chat_ids  = explode("\n", $chat_ids);
            $chat_ids  = array_map("trim", $chat_ids);
            $res_array = [];
            $failed = false;
            $errors = [];
            $errors2 = [];
            $errors3 = [];
            if (empty($chat_ids)) wp_send_json_error(["msg" => __("No Chat ID found. Please add a Chat ID to send a test message.", "ultimate-integration-for-telegram")]);
            try {
              $telegram  = new Telegram($this->read("token"), $this->read("username"));
              /* sample send document
                $result = (new Request)::sendDocument(array(
                  "chat_id" => $chat,
                  "caption" => $message,
                  "document" => (new Request)::encodeFile($pdf_temp),
                  "reply_markup" => ["inline_keyboard"=>$markup],
                  "parse_mode" => "MarkdownV2",
                ));
               */
              /* $result = (new Request)::sendPhoto([
                  "chat_id" => $chat,
                  "photo" => $icon,
                  "caption" => $msg,
                  "protect_content" => true,
                  "reply_markup" => ["inline_keyboard" => $markup],
                  "parse_mode" => "markdownv2",
                ]); */
              foreach ($chat_ids as $chat) {
                $msg = str_replace("{chat_id}", $chat, $message);
                $result = (new Request)::sendMessage([
                  "text" => $msg,
                  "chat_id" => $chat,
                  "protect_content" => true,
                  // "disable_web_page_preview" => false,
                  "reply_markup" => ["inline_keyboard" => $markup],
                  "parse_mode" => "markdownv2",
                ]);

                if ($result->isOk()) {
                  /* translators: 1: Chat ID */
                  $res_array[] = sprintf(__("Test Message sent successfully to ChatID: %s", "ultimate-integration-for-telegram"), $chat);
                } else {
                  // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_var_export
                  $errors[] = var_export($result, 1);
                  // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r
                  $errors2[] = print_r($result, 1);
                  $errors3[] = $result->getDescription();
                  if ($this->debug) {
                    // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log,WordPress.PHP.DevelopmentFunctions.error_log_var_export
                    error_log("Pigment Agent Bot :: debugging send test msg ~> " . PHP_EOL . var_export($result, 1));
                  }
                  /* translators: 1: Chat ID */
                  $res_array[] = sprintf(__("Error sending test message to ChatID: %s", "ultimate-integration-for-telegram") . "<br>" . $result->getDescription(), $chat);
                  $failed = true;
                }
              }
            } catch (TelegramException $e) {
              $failed = true;
              // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r,WordPress.PHP.DevelopmentFunctions.error_log_error_log,WordPress.PHP.DevelopmentFunctions.error_log_var_export
              $errors2[] = print_r([$e->getMessage(), $e], 1);
              // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r,WordPress.PHP.DevelopmentFunctions.error_log_error_log,WordPress.PHP.DevelopmentFunctions.error_log_var_export
              $errors[] = var_export($e->getMessage(), 1);
              /* translators: 1: Error message */
              $res_array[] = sprintf(__("Error Occured: %s", "ultimate-integration-for-telegram"), $e->getMessage());
              if ($this->debug) {
                // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log,WordPress.PHP.DevelopmentFunctions.error_log_var_export
                error_log("Pigment Agent Bot :: debugging send test msg ~> " . PHP_EOL . var_export($e, 1));
              }
            }
            if ($failed) {
              wp_send_json_error(["msg" => implode(PHP_EOL, $res_array), "err_msg" => $errors3, "err" => $errors, "err2" => $errors2,]);
            } else {
              wp_send_json_success(["msg" => implode(PHP_EOL, $res_array)]);
            }
            break;

          default:
            wp_send_json_error(["msg" => __("An unknown error occured.", "ultimate-integration-for-telegram"), "err" => "loop-default",]);
            break;
        }
        wp_send_json_error(["msg" => __("An unknown error occured.", "ultimate-integration-for-telegram"), "err" => "out-of-loop",]);
      }
    }
    public function get_default_list() {
      $str = "";
      if (file_exists(plugin_dir_path(__FILE__) . 'assets/json/default-notifications.json')) {
        $str = @file_get_contents(plugin_dir_path(__FILE__) . 'assets/json/default-notifications.json');
      }
      return $str;
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
        } else {
          return !empty(trim("{$user_info->first_name} {$user_info->last_name}")) ? "{$user_info->first_name} {$user_info->last_name}" : (!empty(trim($user_info->display_name)) ? $user_info->display_name : $user_info->user_login);
        }
      } else {
        /* translators: 1: User ID */
        return sprintf(__("ID #%s [deleted-user]", "ultimate-integration-for-telegram"), $uid);
      }
    }
    public function sanitize_number($num) {
      $num_pairs = array("۰" => "0", "۱" => "1", "۲" => "2", "۳" => "3", "۴" => "4", "۵" => "5", "۶" => "6", "۷" => "7", "۸" => "8", "۹" => "9");
      return strtr($num, $num_pairs);
    }
    public function get_real_IP_address() {
      // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotValidated
      if (!empty($_SERVER["GEOIP_ADDR"])) {
        $ip = sanitize_text_field(stripslashes_deep($_SERVER["GEOIP_ADDR"]));
      } elseif (!empty($_SERVER["HTTP_X_REAL_IP"])) {
        $ip = sanitize_text_field(stripslashes_deep($_SERVER["HTTP_X_REAL_IP"]));
      } elseif (!empty($_SERVER["HTTP_CLIENT_IP"])) {
        $ip = sanitize_text_field(stripslashes_deep($_SERVER["HTTP_CLIENT_IP"]));
      } elseif (!empty($_SERVER["HTTP_X_FORWARDED_FOR"])) {
        $ip = sanitize_text_field(stripslashes_deep($_SERVER["HTTP_X_FORWARDED_FOR"]));
      } else {
        $ip = isset($_SERVER["REMOTE_ADDR"]) ? sanitize_text_field(stripslashes_deep($_SERVER["REMOTE_ADDR"])) : "";
      }
      return esc_html($ip);
    }
    #endregion
    #region php8 string-related functions >>>>>>>>>>>>>
    public static function str_starts_with($haystack, $needle): bool {
      return 0 === strncmp($haystack, $needle, \strlen($needle));
    }
    public static function str_ends_with($haystack, $needle): bool {
      return '' === $needle || ('' !== $haystack && 0 === substr_compare($haystack, $needle, -\strlen($needle)));
    }
    public static function remove_status_prefix(string $status): string {
      if (strpos($status, 'wc-') === 0) $status = substr($status, 3);
      return $status;
    }
    #endregion
    #region send telegram notif >>>>>>>>>>>>>
    public function send_telegram_msg($message_text = "", $buttons_array = [], $reference = "", $extra_data = [], $html_parser_bool = false, $json = false, $chat_ids = "") {
      // $sample = $this->tg_send_msg("Sample Test Sent!", [ ["text" => "btn1", "url" => home_url("/pg-1"),], ["text" => "btn2", "url" => home_url("/pg-2"),], ]);
      if (empty($chat_ids)) $chat_ids = $this->read("chat_ids");
      if (empty(trim($chat_ids))) {
        $debug = ["msg" => __("No Chat ID found. Please add a Chat ID to send a test message.", "ultimate-integration-for-telegram")];
        if ($this->debug) {
          // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r,WordPress.PHP.DevelopmentFunctions.error_log_error_log,WordPress.PHP.DevelopmentFunctions.error_log_var_export
          error_log("Pigment Agent Bot :: debugging " . current_action() . ": " . __METHOD__ . PHP_EOL . var_export($debug, 1));
        }
        if ($json) {
          wp_send_json_error($debug);
        }
        return new \WP_Error("chat_id", __("No Chat ID found. Please add a Chat ID to send a test message.", "ultimate-integration-for-telegram"));
      }

      $chat_ids  = explode("\n", $chat_ids);
      $chat_ids  = array_map("trim", $chat_ids);
      $chat_ids  = array_unique($chat_ids);
      $res_array = [];
      $failed = false;
      $errors = [];
      $errors2 = [];
      if (empty($chat_ids)) {
        $debug = ["msg" => __("No Chat ID found. Please add a Chat ID to send a test message.", "ultimate-integration-for-telegram")];
        if ($this->debug) {
          // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r,WordPress.PHP.DevelopmentFunctions.error_log_error_log,WordPress.PHP.DevelopmentFunctions.error_log_var_export
          error_log("Pigment Agent Bot :: debugging " . current_action() . ": " . __METHOD__ . PHP_EOL . var_export($debug, 1));
        }
        if ($json) {
          wp_send_json_error($debug);
        }
        return new \WP_Error("chat_id", __("No Chat ID found. Please add a Chat ID to send a test message.", "ultimate-integration-for-telegram"));
      }

      if (is_string($buttons_array) && !empty($buttons_array)) {
        $markup = array();
        $button = explode("\n", $buttons_array);
        foreach ($button as $btn) {
          list($text, $url) = explode("|", $btn);
          $markup[] = [
            "text" => $this->translate_param($text, "SANITIZE_BTN", $extra_data, []),
            "url" => $this->sanitize_url($url, $extra_data),
          ];
        }
        $buttons = $markup;
      } else {
        $buttons = [];
      }

      if (!is_bool($html_parser_bool)) {
        $html_parser = "yes" == $html_parser_bool;
      } else {
        $html_parser = false;
      }
      $message = $this->translate_param($message_text, $reference, $extra_data, []);

      if ($buttons && !empty($buttons)) {
        $buttons = apply_filters("ultimate-integration-for-telegram/helper/send-message-buttons", array_slice($buttons, 0, 4), $buttons, $reference, func_get_args());
        $markup = array($buttons, [['text' => "🌏 Pigment Agent Bot", "url" => "https://wordpress.org/plugins/ultimate-integration-for-telegram/"],]);
      } else {
        $markup = [[['text' => "🌏 Pigment Agent Bot", "url" => "https://wordpress.org/plugins/ultimate-integration-for-telegram/"],]];
      }
      try {
        $telegram  = new Telegram($this->read("token"), $this->read("username"));
        foreach ($chat_ids as $chat) {
          $tg = (new Request);
          $method = apply_filters("ultimate-integration-for-telegram/helper/send-message-method", "sendMessage", func_get_args());
          $site_host = wp_parse_url(home_url(), PHP_URL_HOST);
          $host_ip = isset($_SERVER['SERVER_ADDR']) ? sanitize_text_field(stripslashes_deep($_SERVER['SERVER_ADDR'])) : $this->get_real_IP_address();
          if ($html_parser) {
            $message .= "\n\n<blockquote>Disclaimer: sent via Pigment Agent Bot Plugin for WordPress from Host: $site_host ($host_ip)</blockquote>";
          } else {
            $message .= "\n\n-----------\n_Disclaimer: sent via Pigment Agent Bot Plugin for WordPress from Host: $site_host ($host_ip)_";
          }
          $tg_arguments = apply_filters("ultimate-integration-for-telegram/helper/send-message-arguments", array(
            "chat_id"                  => $chat,
            "text"                     => $message,
            "protect_content"          => false,
            "disable_web_page_preview" => false,
            "reply_markup"             => ["inline_keyboard" => $markup],
            "parse_mode"               => $html_parser ? "html" : "markdown",
          ), $tg, func_get_args());
          $result = @forward_static_call_array([$tg, $method], [$tg_arguments]);
          $result = apply_filters("ultimate-integration-for-telegram/helper/sent-message-result", $result, $tg, func_get_args());
          if ($result->isOk()) {
            /* translators: 1: Chat ID */
            $res_array[] = sprintf(__("Test Message sent successfully to ChatID: %s", "ultimate-integration-for-telegram"), $chat);
            if ($this->debug) {
              // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r,WordPress.PHP.DevelopmentFunctions.error_log_error_log,WordPress.PHP.DevelopmentFunctions.error_log_var_export
              error_log("Pigment Agent Bot :: debugging send msg ~> REF:" . $reference . " - " . sprintf(
                /* translators: 1: Chat ID */
                __("Test Message sent successfully to ChatID: %s", "ultimate-integration-for-telegram"),
                $chat
              ) . PHP_EOL .
                // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r,WordPress.PHP.DevelopmentFunctions.error_log_error_log,WordPress.PHP.DevelopmentFunctions.error_log_var_export
                var_export($result, 1));
            }
          } else {
            // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r,WordPress.PHP.DevelopmentFunctions.error_log_error_log,WordPress.PHP.DevelopmentFunctions.error_log_var_export
            $errors[] = var_export($result, 1);
            // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r,WordPress.PHP.DevelopmentFunctions.error_log_error_log,WordPress.PHP.DevelopmentFunctions.error_log_var_export
            $errors2[] = print_r($result, 1);
            if ($this->debug) {
              // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r,WordPress.PHP.DevelopmentFunctions.error_log_error_log,WordPress.PHP.DevelopmentFunctions.error_log_var_export
              error_log("Pigment Agent Bot :: debugging send msg ~> " . PHP_EOL . var_export($result, 1));
            }
            /* translators: 1: Chat ID */
            $res_array[] = sprintf(__("Error sending test message to ChatID: %s", "ultimate-integration-for-telegram"), $chat);
            $failed = true;
          }
        }
      } catch (TelegramException $e) {
        $failed = true;
        // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r,WordPress.PHP.DevelopmentFunctions.error_log_error_log,WordPress.PHP.DevelopmentFunctions.error_log_var_export
        $errors2[] = print_r([$e->getMessage(), $e], 1);
        // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r,WordPress.PHP.DevelopmentFunctions.error_log_error_log,WordPress.PHP.DevelopmentFunctions.error_log_var_export
        $errors[] = var_export($e->getMessage(), 1);
        /* translators: 1: Error message */
        $res_array[] = sprintf(__("Error Occured: %s", "ultimate-integration-for-telegram"), $e->getMessage());
        if ($this->debug) {
          // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r,WordPress.PHP.DevelopmentFunctions.error_log_error_log,WordPress.PHP.DevelopmentFunctions.error_log_var_export
          error_log("Pigment Agent Bot :: debugging send msg ~> " . PHP_EOL . var_export($e, 1));
        }
      }
      if ($failed) {
        $debug = ["msg" => implode(PHP_EOL, $res_array), "err" => $errors, "err2" => $errors2, "message" => $message,];
        do_action("ultimate-integration-for-telegram/helper/send-message-result/failed", $debug, func_get_args());
        if ($this->debug) {
          // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r,WordPress.PHP.DevelopmentFunctions.error_log_error_log,WordPress.PHP.DevelopmentFunctions.error_log_var_export
          error_log("Pigment Agent Bot :: debugging " . current_action() . ": " . __METHOD__ . PHP_EOL . var_export($debug, 1));
        }
        if ($json) {
          wp_send_json_error($debug);
        }
        return new \WP_Error("err_send", implode(PHP_EOL, $res_array), $debug);
      } else {
        do_action("ultimate-integration-for-telegram/helper/send-message-result/success", $res_array, func_get_args());
        if ($this->debug) {
          // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r,WordPress.PHP.DevelopmentFunctions.error_log_error_log,WordPress.PHP.DevelopmentFunctions.error_log_var_export
          error_log("Pigment Agent Bot :: SUCCESS " . current_action() . ": " . __METHOD__ . PHP_EOL . var_export(implode(PHP_EOL, $res_array), 1));
        }
        if ($json) {
          wp_send_json_success(["msg" => implode(PHP_EOL, $res_array)]);
        }
        return $res_array;
      }
    }
    public function sanitize_url($url = "", $extra_data = []) {
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
      } else {
        $profile = home_url("/profile");
        $profileEdit = add_query_arg(["section" => "edit"], home_url("/profile"));
      }
      $url = str_replace(
        ["{home}", "{admin}", "{profile}", "{profile_edit}",],
        [home_url(), admin_url(), $profile, $profileEdit,],
        $url
      );
      return apply_filters("pepro_reglogin_special_pages", $url);
    }
    public function translate_param($message = "", $reference = "", $extra_data = [], $defaults = []) {
      $return = $message;
      $pairs = array(
        "ref_hook"           => $reference,
        "current_time"       => wp_date(get_option("time_format"), current_time("timestamp")),
        "current_date"       => wp_date(get_option("date_format"), current_time("timestamp")),
        "current_date_time"  => wp_date(get_option("date_format") . " " . get_option("time_format"), current_time("timestamp")),
        "current_jdate"      => Ultimate_Integration_Telegram__jdate("Y/m/d", current_time("timestamp"), "", "local", "en"),
        "current_jdate_time" => Ultimate_Integration_Telegram__jdate("Y/m/d H:i:s", current_time("timestamp"), "", "local", "en"),
        "current_user_id"    => get_current_user_id(),
        "current_user_name"  => $this->display_user(get_current_user_id(), false, false),
        "site_name"          => get_bloginfo("name"),
        "site_url"           => home_url(),
        "admin_url"          => admin_url(),
      );
      $pairs = wp_parse_args($pairs, $defaults);
      $pairs = apply_filters("ultimate-integration-for-telegram/helper/translate-pairs", $pairs, $message, $reference, $extra_data, $defaults);
      foreach ($pairs as $macro => $value) {
        $return = str_replace("{" . $macro . "}", $value, $return);
      }
      return apply_filters("ultimate-integration-for-telegram/helper/translated-message", $return, $message, $pairs, $reference, $extra_data, $defaults);
    }
    #endregion
  }
  add_action("plugins_loaded", function () {
    global $UltimateTelegramIntegration;
    $UltimateTelegramIntegration = new Notifier;
  }, 2);
}
/*##################################################
Lead Developer: [amirhp-com](https://amirhp.com/)
##################################################*/