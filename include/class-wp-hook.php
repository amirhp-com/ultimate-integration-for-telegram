<?php
use BlackSwan\Telegram\Notifier;
class class_wp_hook extends Notifier{
  public $notif = [];
  public $notif_id = "wp_user_registered";
  public $notif2 = [];
  public $notif_id2 = "wp_user_edited";
  public function __construct() {
    $this->notif = $this->get_notifications_by_type($this->notif_id);
    $this->notif2 = $this->get_notifications_by_type($this->notif_id2);
    add_action("blackswan-telegram/init", array($this, "init"));
  }
  public function init(){
    if (!empty($this->notif) || !empty($this->notif2)) {

      // add custom macros
      add_action("blackswan-telegram/notif-panel/notif-macro-list", function($notif_id){
        // check if we are showing setting for this class
        if ($this->notif_id == $notif_id || $this->notif_id2 == $notif_id ) { ?>
          <strong><?=__("User Information", $this->td);?></strong>
            <copy data-tippy-content="<?=esc_attr(_x("User ID","macro",$this->td));?>">{user_id}</copy>
            <copy data-tippy-content="<?=esc_attr(_x("User Login (username)","macro",$this->td));?>">{user_login}</copy>
            <copy data-tippy-content="<?=esc_attr(_x("User Role","macro",$this->td));?>">{user_role}</copy>
            <copy data-tippy-content="<?=esc_attr(_x("User Email","macro",$this->td));?>">{user_email}</copy>
            <copy data-tippy-content="<?=esc_attr(_x("User Mobile","macro",$this->td));?>">{user_mobile}</copy>
            <copy data-tippy-content="<?=esc_attr(_x("User First name","macro",$this->td));?>">{first_name}</copy>
            <copy data-tippy-content="<?=esc_attr(_x("User Last name","macro",$this->td));?>">{last_name}</copy>
            <copy data-tippy-content="<?=esc_attr(_x("User Nickname","macro",$this->td));?>">{nickname}</copy>
            <copy data-tippy-content="<?=esc_attr(_x("Author posts Archive","macro",$this->td));?>">{user_archive}</copy>
            <copy data-tippy-content="<?=esc_attr(_x("User Website","macro",$this->td));?>">{user_site}</copy>
            <copy data-tippy-content="<?=esc_attr(_x("User Registered Date","macro",$this->td));?>">{user_registered}</copy>
            <copy data-tippy-content="<?=esc_attr(_x("User Registered Jalali Date","macro",$this->td));?>">{user_jregistered}</copy>
            <copy data-tippy-content="<?=esc_attr(_x("User Meta Array","macro",$this->td));?>">{user_meta}</copy>
          <?php
        }
      });

      // handle macro replacement
      add_filter("blackswan-telegram/helper/translate-pairs", function($pairs, $message, $reference, $extra_data, $defaults){

        // if we are sending request from this class, then handle macro
        if ( in_array($reference, [__CLASS__, "SANITIZE_BTN", "SANITIZE_URL"]) && !empty($extra_data["user_id"]) ) {

          $user_id = $extra_data["user_id"];
          $user = get_user_by("ID", $user_id);

          if ($user) {

            remove_all_filters("date_i18n");
            add_filter("date_i18n", array($this, "jdate"), 10, 4);

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
              "user_jregistered" => date_i18n('Y-m-d H:i:s', strtotime($user->user_registered)),
              "user_meta"       => print_r(get_user_meta($user_id), 1),
            );

            $pairs = array_merge($pairs, $new_macros);

          }

        }

        echo "<pre style='text-align: left; direction: ltr; border:1px solid gray; padding: 1rem; overflow: auto;'>". print_r([$reference, $pairs],1) ."</pre>";

        // always return array
        return (array) $pairs;

      }, 10, 5);

      // add hooks to send messages
      add_action("user_register", array($this, "send_new_user_msg"), 10, 2);
      add_action("wp_update_user", array($this, "send_edit_user_msg"), 9999, 3);

    }
  }
  public function send_new_user_msg($user_id=0, $userdata=[]){
    foreach ($this->notif as $notif) {
      $message = $this->translate_param($notif->config->message, __CLASS__, ["user_id" => $user_id], [
        "user_data" => print_r($userdata, 1),
      ]);
      $this->send_telegram_msg( $message, $notif->config->btn_row1, __CLASS__, ["user_id" => $user_id], $notif->config->html_parser, false );
    }
  }
  public function send_edit_user_msg($user_id=0, $userdata=[], $userdata_raw=[]){
    foreach ($this->notif2 as $notif) {
      $message = $this->translate_param($notif->config->message, __CLASS__, ["user_id" => $user_id], [
        "user_data" => print_r($userdata, 1),
        "user_data_raw" => print_r($userdata_raw, 1),
      ]);
      $this->send_telegram_msg( $message, $notif->config->btn_row1, __CLASS__, ["user_id" => $user_id], $notif->config->html_parser, false );
    }
  }
}
new class_wp_hook;