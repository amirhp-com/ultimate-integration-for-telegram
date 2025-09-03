<?php
/*
 * @Last modified by: amirhp-com <its@amirhp.com>
 * @Last modified time: 2025/09/04 02:05:50
 */
use PigmentDev\Ultimate_Integration_Telegram\Notifier;
defined("ABSPATH") or die("<h2>Unauthorized Access!</h2><hr><small>Ultimate Integration for Telegram :: Developed by <a href='https://pigment.dev/'>Pigment.Dev</a></small>");
if (!class_exists("Ultimate_Integration_Telegram_GF_Hook")) {
  class Ultimate_Integration_Telegram_GF_Hook extends Notifier {
    public $supported_events = array( "gf_form_submission", );
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
      if (!empty($this->get_notifications_by_type("gf_form_submission"))) {
        add_action("gform_after_submission", array($this, "send_custom_message"), 10, 2);
      }
    }
    public function register_new_provider($options) {
      $options["gravity_forms"] = array(
        "title"   => __("Gravity Forms", "ultimate-integration-for-telegram"),
        "icon"    => "fa fa-paper-plane",
        "desc"    => __("Send Telegram notifications based on Gravity Forms events.", "ultimate-integration-for-telegram"),
        "options" => array(
          "gf_form_submission" => __("Form Submission", "ultimate-integration-for-telegram"),
        ),
      );
      return $options;
    }
    public function add_custom_params($macros = [], $notif_id = "") {
      if (in_array($notif_id, $this->supported_events) && is_array($macros)) {
        $new_macros = array(
          "gf_form_submission_info" => array(
            "title" => __("Form Submission - Entry Information", "ultimate-integration-for-telegram"),
            "macros" => array(
              "entry_id"         => _x("Entry ID", "macro", "ultimate-integration-for-telegram"),
              "form_id"          => _x("Form ID", "macro", "ultimate-integration-for-telegram"),
              "form_title"       => _x("Form Title", "macro", "ultimate-integration-for-telegram"),
              "ip"               => _x("IP Address", "macro", "ultimate-integration-for-telegram"),
              "source_id"        => _x("Source ID", "macro", "ultimate-integration-for-telegram"),
              "source_url"       => _x("Source URL", "macro", "ultimate-integration-for-telegram"),
              "date_created"     => _x("Date Created", "macro", "ultimate-integration-for-telegram"),
              "date_updated"     => _x("Date Updated", "macro", "ultimate-integration-for-telegram"),
              "jdate_created"    => _x("Jalali Date Created", "macro", "ultimate-integration-for-telegram"),
              "jdate_updated"    => _x("Jalali Date Updated", "macro", "ultimate-integration-for-telegram"),
              "user_agent"       => _x("User Agent", "macro", "ultimate-integration-for-telegram"),
              "payment_status"   => _x("Payment Status", "macro", "ultimate-integration-for-telegram"),
              "payment_date"     => _x("Payment Date", "macro", "ultimate-integration-for-telegram"),
              "payment_jdate"    => _x("Payment Jalali Date", "macro", "ultimate-integration-for-telegram"),
              "payment_amount"   => _x("Payment Amount", "macro", "ultimate-integration-for-telegram"),
              "payment_method"   => _x("Payment Method", "macro", "ultimate-integration-for-telegram"),
              "transaction_id"   => _x("Transaction ID", "macro", "ultimate-integration-for-telegram"),
              "transaction_type" => _x("Transaction Type", "macro", "ultimate-integration-for-telegram"),
              "user_id"          => _x("User ID", "macro", "ultimate-integration-for-telegram"),
              "field_1"          => _x("Field 1", "macro", "ultimate-integration-for-telegram"),
              "field_1.2"        => _x("Field 1.2", "macro", "ultimate-integration-for-telegram"),
              "field_1.3"        => _x("Field 1.3", "macro", "ultimate-integration-for-telegram"),
              "field_1.4"        => _x("Field 1.4", "macro", "ultimate-integration-for-telegram"),
              "field_2"          => _x("Field 2", "macro", "ultimate-integration-for-telegram"),
              "field_3"          => _x("Field 3", "macro", "ultimate-integration-for-telegram"),
              "field_4"          => _x("Field 4", "macro", "ultimate-integration-for-telegram"),
              "raw_data"         => _x("Raw Data", "macro", "ultimate-integration-for-telegram"),
              "entry_edit_url"   => _x("Entry Edit URL", "macro", "ultimate-integration-for-telegram"),
            ),
          ),
          "gf_form_submission_user" => array(
            "title" => __("Form Submission - User Information", "ultimate-integration-for-telegram"),
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
    public function pair_custom_params($pairs = [], $message = "", $reference = "", $extra_data = [], $defaults = []) {

      // if we are sending request from this class, and we have user_id, then handle macro
      if (in_array($reference, [__CLASS__, "SANITIZE_BTN", "SANITIZE_URL"]) && !empty($extra_data["gf_entry_id"])) {

        // handle user info
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

        // handle gravity forms entry
        $gf_macro = array(
          "entry_id"         => $extra_data["gf_entry_id"] ?? 0,
          "form_id"          => $extra_data["form_id"] ?? 0,
          "form_title"       => $extra_data["form_obj"]["title"] ?? "",
          "ip"               => sanitize_text_field(wp_unslash($extra_data["entry_obj"]["ip"] ?? "")),
          "source_id"        => sanitize_text_field(wp_unslash($extra_data["entry_obj"]["source_id"] ?? "")),
          "source_url"       => sanitize_text_field(wp_unslash($extra_data["entry_obj"]["source_url"] ?? "")),
          "date_created"     => sanitize_text_field(wp_unslash(!empty($extra_data["entry_obj"]["date_created"] ?? "") ? wp_date("Y-m-d H:i:s", strtotime($extra_data["entry_obj"]["date_created"])) : "")),
          "date_updated"     => sanitize_text_field(wp_unslash(!empty($extra_data["entry_obj"]["date_updated"] ?? "") ? wp_date("Y-m-d H:i:s", strtotime($extra_data["entry_obj"]["date_updated"])) : "")),
          "jdate_created"    => sanitize_text_field(wp_unslash(!empty($extra_data["entry_obj"]["date_created"] ?? "") ? Ultimate_Integration_Telegram__jdate('Y/m/d H:i:s', strtotime($extra_data["entry_obj"]["date_created"]), "", "local", "en") : "")),
          "jdate_updated"    => sanitize_text_field(wp_unslash(!empty($extra_data["entry_obj"]["date_updated"] ?? "") ? Ultimate_Integration_Telegram__jdate('Y/m/d H:i:s', strtotime($extra_data["entry_obj"]["date_updated"]), "", "local", "en") : "")),
          "user_agent"       => sanitize_text_field(wp_unslash($extra_data["entry_obj"]["user_agent"] ?? "")),
          "payment_status"   => sanitize_text_field(wp_unslash($extra_data["entry_obj"]["payment_status"] ?? "")),
          "payment_date"     => sanitize_text_field(wp_unslash(!empty($extra_data["entry_obj"]["payment_date"] ?? "") ? wp_date("Y-m-d H:i:s", strtotime($extra_data["entry_obj"]["payment_date"])) : "")),
          "payment_jdate"    => sanitize_text_field(wp_unslash(!empty($extra_data["entry_obj"]["payment_date"] ?? "") ? Ultimate_Integration_Telegram__jdate('Y/m/d H:i:s', strtotime($extra_data["entry_obj"]["payment_date"]), "", "local", "en") : "")),
          "payment_amount"   => sanitize_text_field(wp_unslash(!empty($extra_data["entry_obj"]["payment_amount"] ?? "") ? number_format((float) ($extra_data["entry_obj"]["payment_amount"] ?? 0)) : "")),
          "payment_method"   => sanitize_text_field(wp_unslash($extra_data["entry_obj"]["payment_method"] ?? "")),
          "transaction_id"   => sanitize_text_field(wp_unslash($extra_data["entry_obj"]["transaction_id"] ?? "")),
          "transaction_type" => sanitize_text_field(wp_unslash($extra_data["entry_obj"]["transaction_type"] ?? "")),
          "user_id"          => sanitize_text_field(wp_unslash($extra_data["entry_obj"]["user_id"] ?? "")),
          "raw_data"         => print_r($extra_data["entry_obj"]??"", 1),
          "entry_edit_url"   => admin_url("admin.php?page=gf_entries&view=entry&lid=" . ($extra_data["gf_entry_id"]??0) . "&id=" . ($extra_data["form_id"]??0)),
        );
        $pairs = array_merge($pairs, $gf_macro);
        if (!empty($extra_data["entry_obj"]) && is_array($extra_data["entry_obj"])) {
          foreach ($extra_data["entry_obj"] as $key => $value) {
            if (is_numeric($key) || (is_string($key) && is_numeric(substr($key, 0, 1)))) {
              $new_key = "field_" . str_replace(".", "_", $key);
              if (is_array($value)) {
                $pairs[$new_key] = sanitize_text_field(wp_unslash(implode(", ", $value)));
              } else {
                $pairs[$new_key] = sanitize_text_field(wp_unslash($value));
              }
            }
          }
        }
      }

      // always return array
      return (array) $pairs;
    }
    public function set_default_message($message = "", $notif_id = "", $category = "", $items = []) {
      if ($notif_id === "gf_form_submission") {
        $message = __("New entry submitted in your form *\"{form_title}\"* 🎉.\n\nEntry ID: {entry_id}\nDate Created: {date_created}\n\n```raw-data\n{raw_data}\n```", "ultimate-integration-for-telegram");
      }
      return $message;
    }
    public function set_default_buttons($buttons = [], $notif_id = "", $category = "", $items = []) {
      if ($notif_id === "gf_form_submission") {
        $buttons = "View Entry | {entry_edit_url}";
      }
      return $buttons;
    }
    public function add_forms_dropdown($slug=""){
      if ("gf_form_submission" === $slug) {
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
                if (class_exists('GFFormsModel')) {
                  $forms = \GFFormsModel::get_forms(true);
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
    public function send_custom_message($entry_data = [], $form_data = []) {
      $list_notif = $this->get_notifications_by_type("gf_form_submission");
      $entry_id = $entry_data["id"] ?? 0;
      $data = array(
        "gf_entry_id" => $entry_id,
        "form_id"     => $form_data["id"] ?? 0,
        "entry_obj"   => $entry_data,
        "form_obj"    => $form_data,
      );
      foreach ((array) $list_notif as $notif) {
        $form_ids = $notif->config->form_ids ?? [];
        if (!empty($form_ids) && !in_array('all', $form_ids) && !in_array($form_data["id"], $form_ids)) { continue; }
        @$this->send_telegram_msg($notif->config->message, $notif->config->buttons, __CLASS__, $data, $notif->config->html_parser, false, $notif->config->recipients);
      }
    }
  }
}
new Ultimate_Integration_Telegram_GF_Hook;
