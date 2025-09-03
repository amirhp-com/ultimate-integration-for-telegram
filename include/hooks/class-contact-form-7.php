<?php
/*
 * @Last modified by: amirhp-com <its@amirhp.com>
 * @Last modified time: 2025/09/04 02:20:16
 */

use PigmentDev\Ultimate_Integration_Telegram\Notifier;

defined("ABSPATH") or die("<h2>Unauthorized Access!</h2><hr><small>Ultimate Integration for Telegram :: Developed by <a href='https://pigment.dev/'>Pigment.Dev</a></small>");
if (!class_exists("Ultimate_Integration_Telegram_CF7_Hook")) {
  class Ultimate_Integration_Telegram_CF7_Hook extends Notifier {
    public $supported_events = array( "wpcf7_mail_sent", );
    public function __construct() {
      parent::__construct(false);
      // register event type
      add_filter("ultimate-integration-for-telegram/notif-panel/notif-types-array", array($this, "register_new_provider"), 10, 1);
      // register event custom macros
      add_filter("ultimate-integration-for-telegram/notif-panel/notif-macro-list", array($this, "add_custom_params"), 10, 2);
      // replace custom macros with actual values
      add_filter("ultimate-integration-for-telegram/helper/translate-pairs", array($this, "pair_custom_params"), 10, 5);
      // set default message
      add_filter("ultimate-integration-for-telegram/notif-panel/notif-default-message", array($this, "set_default_message"), 10, 4);
      // set default buttons
      add_filter("ultimate-integration-for-telegram/notif-panel/notif-default-buttons", array($this, "set_default_buttons"), 10, 4);
      // add forms dropdown
      add_action("ultimate-integration-for-telegram/notif-panel/after-notif-setting", array($this, "add_forms_dropdown"), 10, 1);
      // hook to send notification
      if (!empty($this->get_notifications_by_type("wpcf7_mail_sent"))) {
        add_action("wpcf7_before_send_mail", array($this, "send_custom_message"), 10, 3);
      }
    }
    public function register_new_provider($options) {
      $options["wpcf7"] = array(
        "title"   => __("Contact Form 7", "ultimate-integration-for-telegram"),
        "icon"    => "fa fa-paper-plane",
        "desc"    => __("Send Telegram notifications based on Contact Form 7 events.", "ultimate-integration-for-telegram"),
        "options" => array(
          "wpcf7_mail_sent" => __("Mail Sent", "ultimate-integration-for-telegram"),
        ),
      );
      return $options;
    }
    public function add_custom_params($macros = [], $notif_id = "") {
      if (in_array($notif_id, $this->supported_events) && is_array($macros)) {
        $new_macros = array(
          "wpcf7_mail_sent_info" => array(
            "title" => __("Contact Form 7 - Entry Information", "ultimate-integration-for-telegram"),
            "macros" => array(
              "form_id"        => _x("Form ID", "macro", "ultimate-integration-for-telegram"),
              "form_title"     => _x("Form Title", "macro", "ultimate-integration-for-telegram"),
              "your-name"      => _x("Your Name", "macro", "ultimate-integration-for-telegram"),
              "your-email"     => _x("Your Email", "macro", "ultimate-integration-for-telegram"),
              "your-subject"   => _x("Your Subject", "macro", "ultimate-integration-for-telegram"),
              "your-message"   => _x("Your Message", "macro", "ultimate-integration-for-telegram"),
              "cf7-field-slug" => _x("Other CF7 Field (replace 'cf7-field-slug' with actual field name)", "macro", "ultimate-integration-for-telegram"),
              "raw_data"       => _x("Raw Data", "macro", "ultimate-integration-for-telegram"),
            ),
          ),
        );
        $macros = array_merge($macros, $new_macros);
      }
      return (array) $macros;
    }
    public function pair_custom_params($pairs = [], $message = "", $reference = "", $extra_data = [], $defaults = []) {

      // if we are sending request from this class, and we have user_id, then handle macro
      if (in_array($reference, [__CLASS__, "SANITIZE_BTN", "SANITIZE_URL"]) && !empty($extra_data["cf7_data"])) {

        // handle cf7 entry
        $cf7_macro = array(
          "form_id"      => $extra_data["form_id"] ?? 0,
          "form_title"   => get_the_title($extra_data["form_id"] ?? 0),
          "raw_data"     => print_r($extra_data["cf7_data"]??"", 1),
          "your-name"    => sanitize_text_field(wp_unslash($extra_data["cf7_data"]["your-name"]??"")),
          "your-email"   => sanitize_text_field(wp_unslash($extra_data["cf7_data"]["your-email"]??"")),
          "your-subject" => sanitize_text_field(wp_unslash($extra_data["cf7_data"]["your-subject"]??"")),
          "your-message" => sanitize_text_field(wp_unslash($extra_data["cf7_data"]["your-message"]??"")),
        );
        $pairs = array_merge($pairs, $cf7_macro);
        if (!empty($extra_data["cf7_data"]) && is_array($extra_data["cf7_data"])) {
          foreach ($extra_data["cf7_data"] as $key => $value){
            $pairs[$key] = sanitize_text_field(wp_unslash($value));
          }
        }
      }

      // always return array
      return (array) $pairs;
    }
    public function set_default_message($message = "", $notif_id = "", $category = "", $items = []) {
      if ($notif_id === "wpcf7_mail_sent") {
        $message = __("New entry submitted in your form *\"{form_title}\"* 🎉.\n\nDate Created: {current_date_time}\nName: {your-name}\nEmail: {your-email}\nSubject: {your-subject}\n```message\n{your-message}\n```", "ultimate-integration-for-telegram");
      }
      return $message;
    }
    public function set_default_buttons($buttons = [], $notif_id = "", $category = "", $items = []) {
      // if ($notif_id === "wpcf7_mail_sent") { $buttons = "View Entry | {entry_edit_url}"; }
      return $buttons;
    }
    public function add_forms_dropdown($slug=""){
      if ("wpcf7_mail_sent" === $slug) {
        ?>
        <tr class="toggle-advanced-no tg-gf-form-ids">
            <th>
              <?php esc_html_e("Select Form", "ultimate-integration-for-telegram"); ?>
              <i class="fas fa-question-circle" data-tippy-content="<?php esc_html_e("Select the forms you want to use for this notification.", "ultimate-integration-for-telegram"); ?>"></i>
            </th>
            <td>
              <select class="chosen" multiple data-slug="form_ids">
                <option value="all"><?php esc_html_e('All Forms', 'ultimate-integration-for-telegram'); ?></option>
                <?php
                if (class_exists('WPCF7_ContactForm')) {
                  $forms = \WPCF7_ContactForm::find();
                  foreach ($forms as $form) {
                    printf( '<option value="%d">%s</option>', intval($form->id), esc_html($form->title) );
                  }
                }
                ?>
              </select>
            </td>
          </tr>
        <?php
      }
    }
    public function send_custom_message($contact_form, $abort, $that) {
      $submission = WPCF7_Submission::get_instance();
      $list_notif = $this->get_notifications_by_type("wpcf7_mail_sent");
      $data = array(
        "form_id"  => $submission->get_contact_form()->id ?? 0,
        "cf7_data" => $submission->get_posted_data(),
        "form_obj" => $submission->get_contact_form(),
      );
      foreach ((array) $list_notif as $notif) {
        $form_ids = $notif->config->form_ids ?? [];
        if (!empty($form_ids) && !in_array('all', $form_ids) && !in_array($data["form_id"], $form_ids)) { continue; }
        @$this->send_telegram_msg($notif->config->message, $notif->config->buttons, __CLASS__, $data, $notif->config->html_parser, false, $notif->config->recipients);
      }
    }
  }
}
new Ultimate_Integration_Telegram_CF7_Hook;
