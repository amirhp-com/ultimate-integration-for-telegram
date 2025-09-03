<?php
/*
 * @Last modified by: amirhp-com <its@amirhp.com>
 * @Last modified time: 2025/09/04 00:47:19
 */

use PigmentDev\Ultimate_Integration_Telegram\Notifier;

defined("ABSPATH") or die("<h2>Unauthorized Access!</h2><hr><small>Ultimate Integration for Telegram :: Developed by <a href='https://pigment.dev/'>Pigment.Dev</a></small>");
if (!class_exists("Ultimate_Integration_Telegram_Hooks")) {
  class Ultimate_Integration_Telegram_Hooks extends Notifier {
    public function __construct() {
      parent::__construct(false);

      add_filter("ultimate-integration-for-telegram/notif-panel/notif-macro-list", array($this, "add_user_macros"), 2, 2);
      add_filter("ultimate-integration-for-telegram/notif-panel/notif-macro-list", array($this, "add_comment_macros"), 2, 2);
      add_filter("ultimate-integration-for-telegram/helper/translate-pairs", array($this, "translate_params_custom"), 10, 5);

      if (!empty($this->get_notifications_by_type("user_registered"))) {
        add_action("user_register", array($this, "send_new_user_msg"), 10, 2);
      }
      if (!empty($this->get_notifications_by_type("wp_user_edited"))) {
        add_action("wp_update_user", array($this, "send_edit_user_msg"), 9999, 3);
      }

      if (!empty($this->get_notifications_by_type("wp_comment_created"))) {
        add_action("wp_insert_comment", array($this, "send_new_comment_msg"), 9999, 2);
      }
      if (!empty($this->get_notifications_by_type("wp_comment_updated"))) {
        add_action("edit_comment", array($this, "send_edit_comment_msg"), 9999, 2);
      }
      if (!empty($this->get_notifications_by_type("wp_comment_trashed"))) {
        add_action("wp_set_comment_status", array($this, "send_comment_status_msg"), 9999, 2);
      }
      if (!empty($this->get_notifications_by_type("wp_comment_approved"))) {
        add_filter("wp_set_comment_status", array($this, "send_comment_status_msg"), 9999, 2);
      }
      if (!empty($this->get_notifications_by_type("wp_comment_spammed"))) {
        add_filter("wp_set_comment_status", array($this, "send_comment_status_msg"), 9999, 2);
      }

    }
    public function add_user_macros($macros, $notif_id) {
      if (in_array($notif_id, [
        "wp_user_registered",
        "wp_user_edited",
      ]) && is_array($macros)) {
        $new_macros = array(
          "wp_user_info" => array(
            "title" => __("User Information", "ultimate-integration-for-telegram"),
            "macros" => array(
              "user_id"          => _x("User ID", "macro", "ultimate-integration-for-telegram"),
              "user_login"       => _x("User Login (username)", "macro", "ultimate-integration-for-telegram"),
              "user_role"        => _x("User Role", "macro", "ultimate-integration-for-telegram"),
              "user_email"       => _x("User Email", "macro", "ultimate-integration-for-telegram"),
              "user_mobile"      => _x("User Mobile", "macro", "ultimate-integration-for-telegram"),
              "user_first_name"  => _x("User First name", "macro", "ultimate-integration-for-telegram"),
              "user_last_name"   => _x("User Last name", "macro", "ultimate-integration-for-telegram"),
              "user_nickname"    => _x("User Nickname", "macro", "ultimate-integration-for-telegram"),
              "user_archive"     => _x("Author posts Archive", "macro", "ultimate-integration-for-telegram"),
              "user_site"        => _x("User Website", "macro", "ultimate-integration-for-telegram"),
              "user_registered"  => _x("User Registered Date", "macro", "ultimate-integration-for-telegram"),
              "user_jregistered" => _x("User Registered Jalali Date", "macro", "ultimate-integration-for-telegram"),
              "user_meta"        => _x("User Meta Array", "macro", "ultimate-integration-for-telegram"),
              "user_edit_url"    => _x("User Edit Profile URL", "macro", "ultimate-integration-for-telegram"),
            ),
          ),
        );
        $macros = array_merge($macros, $new_macros);
      }
      return (array) $macros;
    }
    public function add_comment_macros($macros, $notif_id) {
      if (in_array($notif_id, [
        "wp_comment_created",
        "wp_comment_updated",
        "wp_comment_approved",
        "wp_comment_spammed",
        "wp_comment_trashed",
      ]) && is_array($macros)) {
        $new_macros = array(
          "wp_comment_info" => array(
            "title" => __("Comment Information", "ultimate-integration-for-telegram"),
            "macros" => array(
              "comment_id"            => _x("Comment ID", "macro", "ultimate-integration-for-telegram"),
              "comment_post_ID"       => _x("Comment Post ID", "macro", "ultimate-integration-for-telegram"),
              "comment_post_title"    => _x("Comment Post Title", "macro", "ultimate-integration-for-telegram"),
              "comment_post_url"      => _x("Comment Post URL", "macro", "ultimate-integration-for-telegram"),
              "comment_author"        => _x("Comment Author", "macro", "ultimate-integration-for-telegram"),
              "comment_author_email"  => _x("Comment Author Email", "macro", "ultimate-integration-for-telegram"),
              "comment_author_url"    => _x("Comment Author URL", "macro", "ultimate-integration-for-telegram"),
              "comment_author_IP"     => _x("Comment Author IP", "macro", "ultimate-integration-for-telegram"),
              "comment_user_agent"    => _x("Comment User Agent", "macro", "ultimate-integration-for-telegram"),
              "comment_date"          => _x("Comment Date", "macro", "ultimate-integration-for-telegram"),
              "comment_jdate"         => _x("Comment Jalali Date", "macro", "ultimate-integration-for-telegram"),
              "comment_content"       => _x("Comment Content", "macro", "ultimate-integration-for-telegram"),
              "comment_type"          => _x("Comment Type", "macro", "ultimate-integration-for-telegram"),
              "comment_meta"          => _x("Comment Meta Array", "macro", "ultimate-integration-for-telegram"),
              "comment_edit_url"      => _x("Comment Edit URL", "macro", "ultimate-integration-for-telegram"),
              "comment_approve_url"   => _x("Comment 'Make Approve' URL", "macro", "ultimate-integration-for-telegram"),
              "comment_unapprove_url" => _x("Comment 'Make Unapprove' URL", "macro", "ultimate-integration-for-telegram"),
              "comment_spam_url"      => _x("Comment 'Make Spam' URL", "macro", "ultimate-integration-for-telegram"),
            ),
          ),
          "wp_user_info" => array(
            "title" => __("Commentor User Information", "ultimate-integration-for-telegram"),
            "macros" => array(
              "user_id"          => _x("User ID", "macro", "ultimate-integration-for-telegram"),
              "user_login"       => _x("User Login (username)", "macro", "ultimate-integration-for-telegram"),
              "user_role"        => _x("User Role", "macro", "ultimate-integration-for-telegram"),
              "user_email"       => _x("User Email", "macro", "ultimate-integration-for-telegram"),
              "user_mobile"      => _x("User Mobile", "macro", "ultimate-integration-for-telegram"),
              "user_first_name"  => _x("User First name", "macro", "ultimate-integration-for-telegram"),
              "user_last_name"   => _x("User Last name", "macro", "ultimate-integration-for-telegram"),
              "user_nickname"    => _x("User Nickname", "macro", "ultimate-integration-for-telegram"),
              "user_archive"     => _x("Author posts Archive", "macro", "ultimate-integration-for-telegram"),
              "user_site"        => _x("User Website", "macro", "ultimate-integration-for-telegram"),
              "user_registered"  => _x("User Registered Date", "macro", "ultimate-integration-for-telegram"),
              "user_jregistered" => _x("User Registered Jalali Date", "macro", "ultimate-integration-for-telegram"),
              "user_meta"        => _x("User Meta Array", "macro", "ultimate-integration-for-telegram"),
              "user_edit_url"    => _x("User Edit Profile URL", "macro", "ultimate-integration-for-telegram"),
            ),
          ),
        );
        $macros = array_merge($macros, $new_macros);
      }
      return (array) $macros;
    }
    public function translate_params_custom($pairs, $message, $reference, $extra_data, $defaults) {

      // if we are sending request from this class, and we have user_id, then handle macro
      if (in_array($reference, [__CLASS__, "SANITIZE_BTN", "SANITIZE_URL"]) && !empty($extra_data["user_id"])) {
        $user_id = $extra_data["user_id"] ?? 0;
        $user = get_user_by("ID", $user_id);
        if ($user) {
          $new_macros = array(
            "user_id"          => $user_id,
            "user_login"       => $user->user_login,
            "user_role"        => wp_roles()->get_names()[$user->roles[0]],
            "user_email"       => $user->user_email,
            "user_mobile"      => get_user_meta($user_id, "user_mobile", true),
            "first_name"       => $user->first_name,
            "user_first_name"  => $user->first_name,
            "last_name"        => $user->last_name,
            "user_last_name"   => $user->last_name,
            "nickname"         => $user->nickname,
            "user_nickname"    => $user->nickname,
            "user_archive"     => get_author_posts_url($user_id, $user->nickname),
            "user_site"        => $user->user_url,
            "user_registered"  => $user->user_registered,
            "user_jregistered" => Ultimate_Integration_Telegram__jdate('Y/m/d H:i:s', strtotime($user->user_registered), "", "local", "en"),
            // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r
            "user_meta"       => print_r(get_user_meta($user_id), 1),
            "user_edit_url"   => admin_url("user-edit.php?user_id=" . $user_id),
          );
          $pairs = array_merge($pairs, $new_macros);
        }
      }

      // if we are sending request from this class, and we have comment_id, then handle macro
      if (in_array($reference, [__CLASS__, "SANITIZE_BTN", "SANITIZE_URL"]) && !empty($extra_data["comment_id"])) {

        $comment_id = $extra_data["comment_id"] ?? 0;
        $comment = get_comment($comment_id);

        if ($comment) {
          $new_macros = array(
              "comment_id"            => $comment_id,
              "comment_post_ID"       => $comment->comment_post_ID,
              "comment_post_title"    => get_the_title($comment->comment_post_ID),
              "comment_post_url"      => get_permalink($comment->comment_post_ID),
              "comment_author"        => $comment->comment_author,
              "comment_author_email"  => $comment->comment_author_email,
              "comment_author_url"    => $comment->comment_author_url,
              "comment_author_IP"     => $comment->comment_author_IP,
              "comment_user_agent"    => $comment->comment_user_agent,
              "comment_date"          => $comment->comment_date,
              "comment_jdate"         => Ultimate_Integration_Telegram__jdate('Y/m/d H:i:s', strtotime($comment->comment_date), "", "local", "en"),
              "comment_content"       => $comment->comment_content,
              "comment_type"          => $comment->comment_type,
              "comment_meta"          => print_r(get_comment_meta($comment_id), 1),
              "comment_edit_url"      => admin_url("comment.php?action=editcomment&c=" . $comment_id),
              "comment_approve_url"   => admin_url("comment.php?action=approvecomment&c=" . $comment_id),
              "comment_unapprove_url" => admin_url("comment.php?action=unapprovecomment&c=" . $comment_id),
              "comment_spam_url"      => admin_url("comment.php?action=spamcomment&c=" . $comment_id),
          );
          $pairs = array_merge($pairs, $new_macros);
        }

      }
      // always return array
      return (array) $pairs;
    }
    #region User Notifications
    public function send_new_user_msg($user_id = 0, $userdata = []) {
      $list_notif = $this->get_notifications_by_type("user_registered");
      foreach ($list_notif as $notif) {
        $message = $this->translate_param(
          $notif->config->message,
          __CLASS__,
          ["user_id" => $user_id], // to identify class in translate_param
          [
            // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r
            "user_data" => print_r($userdata, 1),
          ]
        );
        @$this->send_telegram_msg(
          $message,
          $notif->config->buttons,
          __CLASS__,
          ["user_id" => $user_id], // to replace values for tg-button
          $notif->config->html_parser,
          false,
          $notif->config->recipients
        );
      }
    }
    public function send_edit_user_msg($user_id = 0, $userdata = [], $userdata_raw = []) {
      $list_notif = $this->get_notifications_by_type("wp_user_edited");
      foreach ($list_notif as $notif) {
        $message = $this->translate_param($notif->config->message, __CLASS__, ["user_id" => $user_id], [
          // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r
          "user_data" => print_r($userdata, 1),
          // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r
          "user_data_raw" => print_r($userdata_raw, 1),
        ]);
        $this->send_telegram_msg(
          $message,
          $notif->config->buttons,
          __CLASS__,
          ["user_id" => $user_id],
          $notif->config->html_parser,
          false,
          $notif->config->recipients
        );
      }
    }
    #endregion
    #region Comment Notifications
    public function send_new_comment_msg($comment_id = 0, $comment_data = []) {
      $list_notif = $this->get_notifications_by_type("wp_comment_created");
      foreach ((array) $list_notif as $notif) {
        $message = $this->translate_param(
          $notif->config->message,
          __CLASS__,
          ["comment_id" => $comment_id], // to identify class in translate_param
          [
            // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r
            "comment_data" => print_r($comment_data, 1),
          ]
        );
        @$this->send_telegram_msg(
          $message,
          $notif->config->buttons,
          __CLASS__,
          ["comment_id" => $comment_id], // to replace values for tg-button
          $notif->config->html_parser,
          false,
          $notif->config->recipients
        );
      }
    }
    public function send_edit_comment_msg($comment_id = 0, $comment_data = []) {
      $list_notif = $this->get_notifications_by_type("send_edit_comment_msg");
      foreach ((array) $list_notif as $notif) {
        $message = $this->translate_param(
          $notif->config->message,
          __CLASS__,
          ["comment_id" => $comment_id], // to identify class in translate_param
          [
            // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r
            "comment_data" => print_r($comment_data, 1),
          ]
        );
        @$this->send_telegram_msg(
          $message,
          $notif->config->buttons,
          __CLASS__,
          ["comment_id" => $comment_id], // to replace values for tg-button
          $notif->config->html_parser,
          false,
          $notif->config->recipients
        );
      }
    }
    public function send_comment_status_msg($comment_id = 0, $comment_status = 0) {
      if ("approve" === $comment_status) {
        $list_notif = $this->get_notifications_by_type("wp_comment_approved");
      }elseif ("spam" === $comment_status) {
        $list_notif = $this->get_notifications_by_type("wp_comment_spammed");
      }elseif ("trash" === $comment_status) {
        $list_notif = $this->get_notifications_by_type("wp_comment_trashed");
      }else{
        $list_notif = [];
      }
      foreach ((array) $list_notif as $notif) {
        $message = $this->translate_param(
          $notif->config->message,
          __CLASS__,
          ["comment_id" => $comment_id], // to identify class in translate_param
          [
            // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r
            "comment_data" => print_r($comment_status, 1),
          ]
        );
        @$this->send_telegram_msg(
          $message,
          $notif->config->buttons,
          __CLASS__,
          ["comment_id" => $comment_id], // to replace values for tg-button
          $notif->config->html_parser,
          false,
          $notif->config->recipients
        );
      }
    }
    #endregion
  }
}
new Ultimate_Integration_Telegram_Hooks;
