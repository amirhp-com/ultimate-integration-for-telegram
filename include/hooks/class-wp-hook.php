<?php
/*
 * @Author: Amirhossein Hosseinpour <https://amirhp.com>
 * @Last modified by: amirhp-com <its@amirhp.com>
 * @Last modified time: 2025/03/17 05:19:08
 */
defined("ABSPATH") or die("<h2>Unauthorized Access!</h2><hr><small>Ultimate Integration for Telegram :: Developed by <a href='https://amirhp.com/'>Amirhp-com</a></small>");

use BlackSwan\Ultimate_Integration_Telegram\Notifier;

class Ultimate_Integration_Telegram_Hooks extends Notifier {
  public $notif = [];
  public $notif2 = [];
  public $notif_id = "wp_user_registered";
  public $notif_id2 = "wp_user_edited";
  public function __construct() {
    parent::__construct();
    $this->notif = $this->get_notifications_by_type($this->notif_id);
    $this->notif2 = $this->get_notifications_by_type($this->notif_id2);
    add_filter("ultimate-integration-for-telegram/notif-panel/notif-macro-list", array($this, "add_custom_macros"), 2, 2);
    add_filter("ultimate-integration-for-telegram/helper/translate-pairs", array($this, "translate_params_custom"), 10, 5);
    if (!empty($this->notif) || !empty($this->notif2)) {
      add_action("user_register", array($this, "send_new_user_msg"), 10, 2);
      add_action("wp_update_user", array($this, "send_edit_user_msg"), 9999, 3);
    }
  }
  public function add_custom_macros($macros, $notif_id) {
    if (in_array($notif_id, [$this->notif_id, $this->notif_id2]) && is_array($macros)) {
      $new_macros = array(
        "wp_user_info" => array(
          "title" => __("User Information", "ultimate-integration-for-telegram"),
          "macros" => array(
            "user_id"          => _x("User ID", "macro", "ultimate-integration-for-telegram"),
            "user_login"       => _x("User Login (username)", "macro", "ultimate-integration-for-telegram"),
            "user_role"        => _x("User Role", "macro", "ultimate-integration-for-telegram"),
            "user_email"       => _x("User Email", "macro", "ultimate-integration-for-telegram"),
            "user_mobile"      => _x("User Mobile", "macro", "ultimate-integration-for-telegram"),
            "first_name"       => _x("User First name", "macro", "ultimate-integration-for-telegram"),
            "last_name"        => _x("User Last name", "macro", "ultimate-integration-for-telegram"),
            "nickname"         => _x("User Nickname", "macro", "ultimate-integration-for-telegram"),
            "user_archive"     => _x("Author posts Archive", "macro", "ultimate-integration-for-telegram"),
            "user_site"        => _x("User Website", "macro", "ultimate-integration-for-telegram"),
            "user_registered"  => _x("User Registered Date", "macro", "ultimate-integration-for-telegram"),
            "user_jregistered" => _x("User Registered Jalali Date", "macro", "ultimate-integration-for-telegram"),
            "user_meta"        => _x("User Meta Array", "macro", "ultimate-integration-for-telegram"),
          ),
        ),
      );
      $macros = array_merge($macros, $new_macros);
    }
    return (array) $macros;
  }
  public function translate_params_custom($pairs, $message, $reference, $extra_data, $defaults) {

    // if we are sending request from this class, then handle macro
    if (in_array($reference, [__CLASS__, "SANITIZE_BTN", "SANITIZE_URL"]) && !empty($extra_data["user_id"])) {

      $user_id = $extra_data["user_id"];
      $user = get_user_by("ID", $user_id);

      if ($user) {
        $new_macros = array(
          "user_id"         => $user_id,
          "user_role"       => wp_roles()->get_names()[$user->roles[0]],
          "user_login"      => $user->user_login,
          "user_email"      => $user->user_email,
          "user_mobile"     => get_user_meta($user_id, "user_mobile", true),
          "first_name"      => $user->first_name,
          "last_name"       => $user->last_name,
          "nickname"        => $user->nickname,
          "user_archive"    => get_author_posts_url($user_id, $user->nickname),
          "user_site"       => $user->user_url,
          "user_registered" => $user->user_registered,
          "user_jregistered" => Ultimate_Integration_Telegram__jdate('Y-m-d H:i:s', strtotime($user->user_registered), "", "local", "en"),
          // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r
          "user_meta"       => print_r(get_user_meta($user_id), 1),
        );

        $pairs = array_merge($pairs, $new_macros);
      }
    }

    // always return array
    return (array) $pairs;
  }
  public function send_new_user_msg($user_id = 0, $userdata = []) {
    foreach ($this->notif as $notif) {
      $message = $this->translate_param($notif->config->message, __CLASS__, ["user_id" => $user_id], [
        // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r
        "user_data" => print_r($userdata, 1),
      ]);
      $this->send_telegram_msg($message, $notif->config->btn_row1, __CLASS__, ["user_id" => $user_id], $notif->config->html_parser, false);
    }
  }
  public function send_edit_user_msg($user_id = 0, $userdata = [], $userdata_raw = []) {
    foreach ($this->notif2 as $notif) {
      $message = $this->translate_param($notif->config->message, __CLASS__, ["user_id" => $user_id], [
        // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r
        "user_data" => print_r($userdata, 1),
        // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r
        "user_data_raw" => print_r($userdata_raw, 1),
      ]);
      $this->send_telegram_msg($message, $notif->config->btn_row1, __CLASS__, ["user_id" => $user_id], $notif->config->html_parser, false);
    }
  }
}
new Ultimate_Integration_Telegram_Hooks;
