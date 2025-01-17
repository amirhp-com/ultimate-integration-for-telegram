<?php
use BlackSwan\Telegram\Notifier;
class class_wp_hook extends Notifier{
  public $notif = [];
  public function __construct() {
    $this->notif = $this->get_notifications_by_type("wp_user_registered");
    add_action("blackswan-telegram/init", array($this, "init"));
  }
  public function init(){
    if (!empty($this->notif)) {

      add_action("user_register", array($this, "send_new_user_msg"), 10, 2);

      if (current_user_can("manage_options") && is_admin() && isset($_GET["test"]) && !empty($_GET["test"])) {

        foreach ($this->notif as $notif) {
          $isSent = $this->send_telegram_msg(
            $notif->config->message,
            $notif->config->btn_row1,
            __CLASS__,
            $notif->config->html_parser
          );
          echo "<pre style='text-align: left; direction: ltr; border:1px solid #1e45cf; padding: 1rem; color: #1e45cf;'>". var_export($isSent,1) ."</pre>";
        }

        exit;
      }
    }
  }
  public function send_new_user_msg(int $user_id, array $userdata){
    //code goes here
  }
}
new class_wp_hook;