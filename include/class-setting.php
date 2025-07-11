<?php
/*
* @Author: Amirhossein Hosseinpour <https://amirhp.com>
* @Last modified by: amirhp-com <its@amirhp.com>
* @Last modified time: 2025/02/23 21:25:11
*/
defined("ABSPATH") or die("<h2>Unauthorized Access!</h2><hr><small>Ultimate Integration for Telegram :: Developed by <a href='https://amirhp.com/'>Amirhp-com</a></small>");

use BlackSwan\Ultimate_Integration_Telegram\Notifier;

if (!class_exists("Ultimate_Integration_Telegram_Setting")) {
  class Ultimate_Integration_Telegram_Setting extends Notifier {
    public function __construct() {
      parent::__construct(false);
      add_action("admin_init", array($this, "register_settings"), 1);
      add_action("admin_menu", array($this, "admin_menu"));
    }
    public function admin_menu() {
      add_options_page($this->title, $this->title_small, "manage_options", $this->db_slug, array($this, "setting_page"));
    }
    protected function get_setting_options_list() {
      return array(
        array(
          "name" => "general",
          "data" => array(
            "debug"           => "no",
            "jdate"           => "no",
            "admin_bar_link"  => "yes",
            "gettext_replace" => "",
            "str_replace"     => "",
            "token"           => "",
            "username"        => "",
            "chat_ids"        => "",
            "notifications"   => "",
          ),
        ),
      );
    }
    public function register_settings() {
      foreach ($this->get_setting_options_list() as $sections) {
        foreach ($sections["data"] as $id => $def) {
          $slug = $this->db_slug . "__" . $id;
          $section = $this->db_slug . "__" . $sections["name"];
          add_option($slug, $def, "", "no");
          // phpcs:ignore PluginCheck.CodeAnalysis.SettingSanitization.register_settingDynamic
          register_setting($section, $slug, array('type' => 'string', 'sanitize_callback' => 'sanitize_textarea_field'));
        }
      }
    }
    public function setting_page() {
      ob_start();
      $this->update_footer_info();
      wp_enqueue_script('wp-color-picker');
      wp_enqueue_style('wp-color-picker');
      // Enqueue jQuery UI
      wp_enqueue_script('jquery-ui');
      wp_enqueue_script('jquery-ui-core');
      wp_enqueue_script('jquery-ui-sortable');
      wp_enqueue_script('jquery-ui-datepicker');

      // Font Awesome is used to provide icons ONLY on the plugin's settings page for a better user experience.
      wp_enqueue_style($this->db_slug . "-setting", "{$this->assets_url}css/backend.css", [], time());
      wp_enqueue_style($this->db_slug . "-fas", "{$this->assets_url}/fa/css/all.min.css", [], "1.2.1");
      wp_enqueue_script($this->db_slug . "-popper", "{$this->assets_url}js/popper.min.js", ["jquery"], "1.2.1", true);
      wp_enqueue_script($this->db_slug . "-tippy", "{$this->assets_url}js/tippy-bundle.umd.min.js", ["jquery"], "1.2.1", true);
      wp_enqueue_script($this->db_slug . "-setting", "{$this->assets_url}js/jquery.repeater.min.js", ["jquery"], "1.2.1", true);
      wp_enqueue_script($this->db_slug . "-panel", "{$this->assets_url}js/panel.js", ["jquery"], time(), true);

      $data = $this->read("notifications");
      $localized = apply_filters("ultimate-integration-for-telegram/notif-panel/localize-front-script", array(
        "ajax" => admin_url("admin-ajax.php"),
        "action" => $this->td,
        "notif_json" => htmlentities($data),
        "nonce" => wp_create_nonce($this->td),
        "reset_confirm" => esc_html__("Are you sure you want to reset the notifications list to default? This will overwrite your current notifications. You cannot undo this action.", "ultimate-integration-for-telegram"),
        "default_list" => $this->get_default_list(),
        "default_applied" => esc_html__("Default notifications list has been applied (but not saved yet). To apply changes, please save settings.", "ultimate-integration-for-telegram"),
        "wait" => _x("Please wait ...", "front-js", "ultimate-integration-for-telegram"),
        "check_toggle" => _x("Turn On (green) to Enable this Notif", "front-js", "ultimate-integration-for-telegram"),
        "delete_confirm" => _x("Are you sure you want to delete this notifications? If you do and Save this page, YOU CANNOT UNDO WHAT YOU DID.", "front-js", "ultimate-integration-for-telegram"),
        "delete_all" => _x("Are you sure you want to delete all notifications? If you do and Save this page, YOU CANNOT UNDO WHAT YOU DID.", "front-js", "ultimate-integration-for-telegram"),
        /* translators: 1: copied text */
        "copied" => _x("Copied %s to clipboard.", "front-js", "ultimate-integration-for-telegram"),
        "code_copied" => _x("JSON Data Copied to clipboard.", "front-js", "ultimate-integration-for-telegram"),
        "error_slug_empty" => _x("first select a notif type", "front-js", "ultimate-integration-for-telegram"),
        "error_option_empty" => _x("no option found for selected notif", "front-js", "ultimate-integration-for-telegram"),
        "unknown" => _x("An Unknown Error Occured. Check console for more information.", "front-js", "ultimate-integration-for-telegram"),
        "md" => array(
          "asterisk" => _x('Unmatched asterisk (*) found.', "front-js", "ultimate-integration-for-telegram"),
          "underscore" => _x('Unmatched underscore (_) found.', "front-js", "ultimate-integration-for-telegram"),
          "backtick" => _x('Unmatched backtick (`) found.', "front-js", "ultimate-integration-for-telegram"),
          "triple_backticks" => _x('Unmatched triple backticks (```) found.', "front-js", "ultimate-integration-for-telegram"),
          "invalid" => _x('Sorry! Your Markdown formatting is Invalid.', "front-js", "ultimate-integration-for-telegram"),
          "valid" => _x('Success! Your Markdown formatting is valid.', "front-js", "ultimate-integration-for-telegram"),
        ),
      ));
      wp_localize_script($this->db_slug . "-panel", "_panel", $localized);
      is_rtl() and wp_add_inline_style($this->db_slug, "#wpbody-content { font-family: bodyfont, roboto, Tahoma; }");
?>
      <div class="wrap">
        <!-- region head and success message -->
        <h1 class="page-title-heading">
          <img src="<?= $this->assets_url; ?>/pigmentdev.svg" alt="pigment-dev" style="vertical-align: middle;width: 2rem;" />&nbsp;
          <strong style="font-weight:900;"><?php echo esc_html($this->title); ?></strong> <sup style="font-size: small;">v.<?php echo esc_html($this->version); ?></sup>
        </h1>
        <form method="post" action="options.php">
          <!-- region tab items -->
          <nav class="nav-tab-wrapper woo-nav-tab-wrapper">
            <a href="#" data-tab="tab_general" class="nav-tab nav-tab-active"><?php esc_html_e("General", "ultimate-integration-for-telegram"); ?></a>
            <a href="#" data-tab="tab_workspace" class="nav-tab"><?php esc_html_e("Notifications", "ultimate-integration-for-telegram"); ?></a>
            <a href="#" data-tab="tab_translate" class="nav-tab"><?php esc_html_e("Translate", "ultimate-integration-for-telegram"); ?></a>
            <a href="#" data-tab="tab_str_replace" class="nav-tab"><?php esc_html_e("String Replace", "ultimate-integration-for-telegram"); ?></a>
            <a href="#" data-tab="tab_export" class="nav-tab"><?php esc_html_e("Migrate", "ultimate-integration-for-telegram"); ?></a>
            <a href="#" data-tab="tab_documentation" class="nav-tab"><?php esc_html_e("Documentation", "ultimate-integration-for-telegram"); ?></a>
          </nav>
          <!-- region tab content -->
          <?php settings_fields("{$this->db_slug}__general"); ?>
          <div class="tab-content tab-active" data-tab="tab_general">
            <br>
            <table class="form-table wp-list-table widefat striped table-view-list posts">
              <thead>
                <?php
                echo "<tr class='sync_site_tr border-top'><th colspan=2>
                    <h2 style='display: inline-block; margin:0;'><i class='fas fa-cog'></i>&nbsp;" . esc_html__("General Configuration", "ultimate-integration-for-telegram") . "</h2></th></tr>";
                ?>
              </thead>
              <tbody>
                <?php
                $this->print_setting_checkbox([
                  "slug" => "debug",
                  "caption" => __("Enable Debug Mode", "ultimate-integration-for-telegram"),
                  "desc" => __("Enable to log detailed information for troubleshooting. Recommended for development only.", "ultimate-integration-for-telegram"),
                ]);
                $this->print_setting_checkbox([
                  "slug" => "admin_bar_link",
                  "caption" => __("Show Plugin Link in Admin Bar", "ultimate-integration-for-telegram"),
                  "desc" => __("Enable this option to display a link to the plugin in the WordPress admin bar for administrators.", "ultimate-integration-for-telegram"),
                ]);
                $this->print_setting_checkbox([
                  "slug" => "jdate",
                  "caption" => __("Enable Built-in Jalali Date", "ultimate-integration-for-telegram"),
                  "desc" => __("Enable to use the Jalali (Shamsi) calendar format. Disable other Jalali plugins to avoid conflicts.", "ultimate-integration-for-telegram"),
                ]);
                ?>
              </tbody>
            </table>
            <br>
            <table class="form-table wp-list-table widefat striped table-view-list posts">
              <thead>
                <tr class='sync_site_tr border-top'>
                  <th colspan=2>
                    <h2 style='display: inline-block; margin:0;'>
                      <i class="fab fa-telegram"></i>
                      <?php echo esc_html__("Telegram Bot Connection", "ultimate-integration-for-telegram"); ?>
                    </h2>
                  </th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <th><?php echo esc_html__("Step 1: Create a Bot", $this->td); ?></th>
                  <td><?php printf(
                        /* translators: 1: bot father link, 2: connect btn, 3: break */
                        __('Open %1$s in Telegram and send the command /newbot. Follow the instructions to name your bot and choose a username (e.g., my_notifier_bot).', "ultimate-integration-for-telegram"),
                        "<a href='https://t.me/botfather' target='_blank'>@BotFather</a>",
                      ); ?></td>
                </tr>
                <tr>
                  <th><?php echo esc_html__("Step 2: Get the Token", $this->td); ?></th>
                  <td><?php echo sprintf(__("Once your bot is created, BotFather will send you a Token — a long string like this: %s. Tap to copy that token, You’ll need it in the next step.", $this->td), "<code>123456789:ABCDefghIJKlmNoPQRstuVWXyz12345678</code>"); ?></td>
                </tr>
                <?php
                /* translators: 1: @BotFather hyperlinked, 2: validate token link, 3: line break <br> */
                $this->print_setting_input([
                  "slug" => "token",
                  "caption" => esc_html__("Step 3: Paste the Token", "ultimate-integration-for-telegram"),
                  "desc" => esc_html__('Paste the token you got from BotFather into the Token field above.', "ultimate-integration-for-telegram"),
                ]);
                /* translators: 1: validate token link, 2: line break <br> */
                $this->print_setting_input([
                  "slug" => "username",
                  // "extra_html" => "readonly",
                  "caption" => esc_html__("Step 4: Enter the Bot Username", "ultimate-integration-for-telegram"),
                  "desc" => __('Type the username of your bot (with @ at the start), exactly as you created it — for example: @my_notifier_bot.', "ultimate-integration-for-telegram"),
                ]);
                ?>
                <tr>
                  <th><?php echo esc_html__("Step 5: Connect Your Bot", "ultimate-integration-for-telegram"); ?></th>
                  <td>
                    <?php
                    echo "<strong>💾 " . esc_html__("Please make sure to save the settings on this page before connecting your bot.", "ultimate-integration-for-telegram") . "</strong>";
                    echo "<br><div class='notice notice-alt notice-warning inline' style='display: inline-block;margin: 0.5rem 0rem -0.5rem 0 !important;'>" . esc_html__("Changes won't take effect unless saved.", "ultimate-integration-for-telegram") . "</div>";
                    echo "<ul><li>🔗 " . esc_html__("Click “Connect WordPress & Telegram” to link your bot to this site.", "ultimate-integration-for-telegram") . "</li>";
                    echo "<li>🚀 " . esc_html__("Start your bot, then click “Send Test Message to Telegram” to make sure everything is working!", "ultimate-integration-for-telegram") . "</li>";
                    echo "<li>✅ " . esc_html__("If everything’s set up correctly, you’ll receive a test message in Telegram!", "ultimate-integration-for-telegram") . "</li></ul>";
                    ?>
                    <p class="button-wrapper">
                      <a href='#' class="button button-secondary connect"><?php esc_html_e("1. Connect WordPress & Telegram", "ultimate-integration-for-telegram"); ?></a>
                      <a href='#' class="button button-secondary send_test"><?php esc_html_e("2. Send Test Message to Telegram", "ultimate-integration-for-telegram"); ?></a>
                      <a href='#' class="button button-secondary disconnect" style="border-color: #d63638; color: #d63638;"><?php esc_html_e("Disconnect the Bot form WordPress", "ultimate-integration-for-telegram"); ?></a>
                    </p>
                  </td>
                </tr>
                <?php
                $this->print_setting_textarea([
                  "slug" => "chat_ids",
                  "caption" => esc_html__("Step 6: Add Default Recipients", "ultimate-integration-for-telegram"),
                  "desc" => __("Paste the Chat ID or, if it's a public channel/group, the username with @ — e.g., @mychannel.<br>➡️ One Chat ID or username per line.", "ultimate-integration-for-telegram"),
                ]);
                ?>
              </tbody>
            </table>
          </div>
          <div class="tab-content" data-tab="tab_workspace">
            <br>
            <table class="form-table -wp-list-table -widefat -striped -table-view-list -posts workspace-wrapper-parent">
              <thead>
                <tr>
                  <th colspan="2" style="padding: 0;">
                    <?php echo $this->render_workspace_tools(); ?>
                  </th>
                </tr>
              </thead>
            </table>
          </div>
          <div class="tab-content" data-tab="tab_documentation">
            <br>
            <div class="desc">
              <table class="fixed wp-list-table widefat striped table-view-list posts hooks-docs">
                <thead>
                  <tr>
                    <th><strong><?php esc_html_e("Hook Entry", "ultimate-integration-for-telegram"); ?></strong></th>
                    <td><strong><?php esc_html_e("Description", "ultimate-integration-for-telegram"); ?></strong></td>
                  </tr>
                </thead>
                <tr>
                  <td><?php
                      // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                      echo $this->highlight('apply_filters("ultimate-integration-for-telegram/notif-panel/notif-types-array", $options)');
                      ?></td>
                  <td>Add new Notification type, e.g. Support for Custom Plugin</td>
                </tr>

                <tr>
                  <td><?php
                      // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                      echo $this->highlight('do_action("ultimate-integration-for-telegram/notif-panel/after-notif-setting", $slug)'); ?></td>
                  <td>Add Custom Setting a Notification type</td>
                </tr>

                <tr>
                  <td><?php
                      // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                      echo $this->highlight('apply_filters("ultimate-integration-for-telegram/notif-panel/notif-macro-list", $array, $slug)'); ?></td>
                  <td>Add Custom Macro for a Notification type</td>
                </tr>

                <tr>
                  <td><?php
                      // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                      echo $this->highlight('do_action("ultimate-integration-for-telegram/notif-panel/after-macro-list", $macros_filtered, $default_macro, $slug)'); ?></td>
                  <td>Print Custom content after Macro section for a Notification type</td>
                </tr>

                <tr>
                  <td><?php
                      // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                      echo $this->highlight('apply_filters("ultimate-integration-for-telegram/notif-panel/notif-default-message", "", $slug)'); ?></td>
                  <td>Change Default Message for a Notification type</td>
                </tr>

                <tr>
                  <td><?php
                      // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                      echo $this->highlight('apply_filters("ultimate-integration-for-telegram/notif-panel/notif-default-parser",false, $slug)'); ?></td>
                  <td>Change Default Parser as HTML Checkbox state for a Notification type</td>
                </tr>

                <tr>
                  <td><?php
                      // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                      echo $this->highlight('apply_filters("ultimate-integration-for-telegram/notif-panel/notif-default-buttons", "", $slug)'); ?></td>
                  <td>Change Default Buttons list for a Notification type</td>
                </tr>

                <tr>
                  <td>Host Cron Job</td>
                  <td>wp-config: <?php
                                  // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                                  echo $this->highlight("define('DISABLE_WP_CRON', true);"); ?>
                    <br>cronjob: <?php
                                  // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                                  echo $this->highlight("* * * * * wget --delete-after " . home_url("/wp-cron.php?doing_wp_cron") . " >/dev/null 2>&1"); ?>
                    <br>cronjob: <?php
                                  // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                                  echo $this->highlight('/usr/local/bin/php /home/public_html/wp-cron.php?doing_wp_cron >/dev/null 2>&1'); ?>
                  </td>
                </tr>

              </table>
              <br>
              <table class="wp-list-table widefat striped table-view-list posts">
                <thead>
                  <tr>
                    <th><strong>Credits</strong></th>
                    <td><strong>Library</strong></td>
                  </tr>
                </thead>
                <tr>
                  <td>jquery.repeater</td>
                  <td><a href="https://github.com/DubFriend/jquery.repeater" target="_blank">https://github.com/DubFriend/jquery.repeater</a></td>
                </tr>
              </table>
            </div>
          </div>
          <div class="tab-content" data-tab="tab_translate">
            <br>
            <div class="desc repeater translation-panel">
              <table class="wp-list-table widefat striped table-view-list posts">
                <thead>
                  <tr>
                    <th class="th-original"><?php esc_html_e("Original", "ultimate-integration-for-telegram"); ?></th>
                    <th class="th-translate"><?php esc_html_e("Translate", "ultimate-integration-for-telegram"); ?></th>
                    <th class="th-text-domain"><?php esc_html_e("TextDomain", "ultimate-integration-for-telegram"); ?></th>
                    <th class="th-options"><?php esc_html_e("Options", "ultimate-integration-for-telegram"); ?></th>
                    <th class="th-action" style="width: 100px;"><?php esc_html_e("Action", "ultimate-integration-for-telegram"); ?></th>
                  </tr>
                </thead>
                <tbody data-repeater-list="gettext">
                  <tr data-repeater-item style="display:none;">
                    <td class="th-original"><span class="dashicons dashicons-menu-alt move-handle"></span>&nbsp;<input type="text" data-slug="original" name="original" value="" placeholder="<?php esc_html_e("Original text ...", "ultimate-integration-for-telegram"); ?>" /></td>
                    <td class="th-translate"><input type="text" data-slug="translate" name="translate" placeholder="<?php esc_html_e("Translate to ...", "ultimate-integration-for-telegram"); ?>" /></td>
                    <td class="th-text-domain"><input type="text" data-slug="text_domain" name="text_domain" placeholder="<?php esc_html_e("Text Domain (Optional)", "ultimate-integration-for-telegram"); ?>" /></td>
                    <td class="th-options">
                      <label><input type="checkbox" value="yes" data-slug="use_replace" name="use_replace" />&nbsp;<?php esc_html_e("Partial Replace?", "ultimate-integration-for-telegram"); ?></label>&nbsp;&nbsp;
                      <!-- <label><input type="checkbox" value="yes" data-slug="use_regex" name="use_regex"/>&nbsp;<?php esc_html_e("Use RegEx?", "ultimate-integration-for-telegram"); ?></label>&nbsp;&nbsp; -->
                      <label><input type="checkbox" value="yes" data-slug="two_sided" name="two_sided" />&nbsp;<?php esc_html_e("Translated Origin?", "ultimate-integration-for-telegram"); ?></label>
                    </td>
                    <td class="th-action">
                      <a class="button button-secondary" data-repeater-delete><span style="margin: 4px 0;" class="dashicons dashicons-trash"></span><?php esc_html_e("Delete Row", "ultimate-integration-for-telegram"); ?></a>
                    </td>
                  </tr>
                </tbody>
              </table>
              <br>
              <a data-repeater-create class="button button-secondary button-hero"><span style="margin: 12px 8px;" class="dashicons dashicons-insert"></span><?php esc_html_e("Add New Row", "ultimate-integration-for-telegram"); ?></a>&nbsp;&nbsp;
            </div>
            <br><br>
            <table class="form-table wp-list-table widefat striped table-view-list posts">
              <thead>
                <?php
                echo "<tr class='sync_site_tr border-top'><th colspan=2><strong style='display: inline-block;'>" . esc_html__("Migrate Translation", "ultimate-integration-for-telegram") . "</strong></th></tr>";
                ?>
              </thead>
              <tbody>
                <?php
                $this->print_setting_textarea(["slug" => "gettext_replace", "caption" => esc_html__("Translation Data", "ultimate-integration-for-telegram"), "style" => "width: 100%; direction: ltr; min-height: 300px; font-family: monospace; font-size: 0.8rem;",]);
                ?>
              </tbody>
            </table>
          </div>
          <div class="tab-content" data-tab="tab_export">
            <br>
            <table class="form-table wp-list-table widefat striped table-view-list posts">
              <thead>
                <?php
                echo "<tr class='sync_site_tr border-top'><th colspan=2><strong style='display: inline-block;'>" . esc_html__("Export / Import Setting", "ultimate-integration-for-telegram") . "</strong></th></tr>";
                ?>
              </thead>
              <tbody>
                <tr class="type-textarea notifications data-textarea toggle-export-import">
                  <th scope="row" colspan="2">
                    <h3 style="margin-top: 0;">
                      <label for="notifications"><?php esc_html_e("Migrate Notifications List and Settings", "ultimate-integration-for-telegram"); ?></label>
                    </h3>
                    <p>
                      <a href="#" class="button button-secondary copy-code"><?php esc_html_e("Copy to Clipboard", "ultimate-integration-for-telegram"); ?></a>
                      <a href="#" class="button button-secondary reset-default-list"><?php esc_html_e("Reset to Default Notifications List", "ultimate-integration-for-telegram"); ?></a>
                    </p>
                    <textarea rows="20" name="ultimate-integration-for-telegram__notifications" id="notifications" rows="4" style="width: 100%; direction: ltr; font-family: monospace; font-size: smaller;" class="regular-text"><?php echo esc_textarea($this->read("notifications")); ?></textarea>
                    <p class="description"><?php esc_html_e("you can use the json data to migrate settings across multiple sites. Enter JSON Data and Save page to reload Workspace.", "ultimate-integration-for-telegram"); ?></p>
                  </th>
                </tr>
              </tbody>
            </table>
          </div>
          <div class="tab-content" data-tab="tab_str_replace">
            <br>
            <div class="desc repeater str_replace-panel">
              <table class="wp-list-table widefat striped table-view-list posts">
                <thead>
                  <tr>
                    <th class="th-original"><?php esc_html_e("Original", "ultimate-integration-for-telegram"); ?></th>
                    <th class="th-translate"><?php esc_html_e("Replace", "ultimate-integration-for-telegram"); ?></th>
                    <th class="th-options"><?php esc_html_e("Options", "ultimate-integration-for-telegram"); ?></th>
                    <th class="th-action" style="width: 100px;"><?php esc_html_e("Action", "ultimate-integration-for-telegram"); ?></th>
                  </tr>
                </thead>
                <tbody data-repeater-list="gettext">
                  <tr data-repeater-item style="display:none;">
                    <td class="th-original"><span class="dashicons dashicons-menu-alt move-handle"></span>&nbsp;<input type="text" data-slug="original" name="original" value="" placeholder="<?php esc_html_e("Original text ...", "ultimate-integration-for-telegram"); ?>" /></td>
                    <td class="th-translate"><input type="text" data-slug="translate" name="translate" placeholder="<?php esc_html_e("Translate to ...", "ultimate-integration-for-telegram"); ?>" /></td>
                    <td class="th-options">
                      <label><input type="checkbox" value="yes" data-slug="buffer" name="buffer" />&nbsp;<?php esc_html_e("Green: Replace in Output Buffer | Red: Replace in Content Only", "ultimate-integration-for-telegram"); ?></label>&nbsp;&nbsp;
                      <label><input type="checkbox" value="yes" data-slug="active" name="active" />&nbsp;<?php esc_html_e("Active", "ultimate-integration-for-telegram"); ?></label>&nbsp;&nbsp;
                    </td>
                    <td class="th-action">
                      <a class="button button-secondary" data-repeater-delete><span style="margin: 4px 0;" class="dashicons dashicons-trash"></span><?php esc_html_e("Delete Row", "ultimate-integration-for-telegram"); ?></a>
                    </td>
                  </tr>
                </tbody>
              </table>
              <br>
              <a data-repeater-create class="button button-secondary button-hero"><span style="margin: 12px 8px;" class="dashicons dashicons-insert"></span><?php esc_html_e("Add New Row", "ultimate-integration-for-telegram"); ?></a>&nbsp;&nbsp;
            </div>
            <br><br>
            <table class="form-table wp-list-table widefat striped table-view-list posts">
              <thead>
                <?php
                echo "<tr class='sync_site_tr border-top'><th colspan=2><strong style='display: inline-block;'>" . esc_html__("Migrate String Replace", "ultimate-integration-for-telegram") . "</strong></th></tr>";
                ?>
              </thead>
              <tbody>
                <?php
                $this->print_setting_textarea(["slug" => "str_replace", "caption" => esc_html__("String Replace Data", "ultimate-integration-for-telegram"), "style" => "width: 100%; direction: ltr; min-height: 300px; font-family: monospace; font-size: 0.8rem;",]);
                ?>
              </tbody>
            </table>
          </div>
          <!-- region submit wrapper -->
          <div class="submit_wrapper">
            <button class="button button-primary button-hero"><?php esc_html_e("Save setting", "ultimate-integration-for-telegram"); ?></button>
          </div>
        </form>
      </div>
      <!-- region tab switch script and js-repeater -->
    <?php
      $html = ob_get_contents();
      ob_end_clean();
      // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
      echo $html;
    }
    public function highlight($php) {
      return str_replace("&lt;?php", "", highlight_string('<?php' . $php, 1));
    }
    public function sample_setting_row_wrapper() {
      ob_start();
    ?>
      <div class="setting-row newly-added" data-type="{slug}">
        <div class="heading">
          <h3 class="entry-name">{category} / {title}</h3>
          <div class="entry-actions">
            <input type="checkbox" class="enable--entry" data-slug="_enabled" data-tippy-content="<?php echo esc_attr_x("Toggle On (GREEN) to Enable this Event Notification", "front-js", "ultimate-integration-for-telegram"); ?>" />
            <a href="#" data-tippy-content="<?php echo esc_attr__("Delete this Event Notification", "ultimate-integration-for-telegram"); ?>" class="delete--entry"><i class="fas fa-fw fa-lg fa-trash-alt"></i></a>
            <a href="#" data-tippy-content="<?php echo esc_attr__("Open Event Notification Setting", "ultimate-integration-for-telegram"); ?>" class="edit--entry"><i class="fas fa-fw fa-lg chevron fa-chevron-up fa-chevron-down"></i></a>
          </div>
        </div>
        <div class="event-setting">{row_details}</div>
      </div>
    <?php
      $htmloutput = ob_get_contents();
      ob_end_clean();
      return apply_filters("ultimate-integration-for-telegram/notif-panel/sample_setting_row_wrapper", $htmloutput);
    }
    public function render_workspace_tools() {
    ?>
      <div class="render_workspace_tools">
        <!-- <a href="#" class="button button-secondary add-new-notif"><?php esc_html_e("Add New", "ultimate-integration-for-telegram"); ?></a>
        <a href="#" class="button button-secondary clear-all-notif"><?php esc_html_e("Clear All", "ultimate-integration-for-telegram"); ?></a>
        <a href="#" class="button button-secondary export-import-notif"><?php esc_html_e("Import / Export", "ultimate-integration-for-telegram"); ?></a> -->
        <div class="template-wrapper">
          <?php
          foreach ($this->print_notif_types(true) as $category => $items) {
            foreach ($items["options"] as $key => $value) {
              $label = $value;
              if (is_array($value)) $label = $value["label"] ?? "";
              echo "<!-- Notif Setting / " . esc_attr($items["title"]) . " / " . esc_attr($label) . " -->";
              echo "<template id='" . esc_attr($key) . "' data-cat-slug='" . esc_attr($category) . "' data-category='" . esc_attr($items["title"]) . "' data-title='" . esc_attr($label) . "'>";
              // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
              echo $this->print_notif_setting($key);
              echo "</template>";
            }
          }
          ?>
          <template id="sample_setting_row_wrapper">
            <?php echo
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            $this->sample_setting_row_wrapper();
            ?>
          </template>
          <?php do_action("ultimate-integration-for-telegram/notif-panel/notif-types-setting"); ?>
        </div>
        <div class="workspace-notifications-list">
          <div class="notif-panel-side side-left">
            <div class="panel-heading"><?= esc_html__("Available Events", $this->td); ?></div>
            <div class="list-notif-types">
              <?php
              do_action("ultimate-integration-for-telegram/notif-panel/after-general-notif-dropdown");
              $this->print_notif_types(false, "items");
              do_action("ultimate-integration-for-telegram/notif-panel/after-notif-types-dropdown");
              ?>
            </div>
          </div>
          <div class="notif-panel-side side-right">
            <div class="panel-heading"><?= esc_html__("Notifications List", $this->td); ?></div>
            <div class="workplace" data-empty="<?php echo esc_attr(__("No notif added to panel, you toolbar above to add your first notif.", "ultimate-integration-for-telegram")); ?>"></div>
          </div>
        </div>
      </div>
    <?php
    }
    public function print_notif_types($return_array = false, $stdout_type = "category") {
      $options = array();
      $options["wp_users"] = array(
        "title" => __("WordPress Users", "ultimate-integration-for-telegram"),
        "desc" => __("This notif will be triggered when a WordPress User is registered or updated.", "ultimate-integration-for-telegram"),
        "options" => array(
          "wp_user_registered" => [
            "label" => __("New User Registered", "ultimate-integration-for-telegram"),
            "desc" => __("WHen a new user is registered either via admin panel or wordpress front-end", $this->td),
          ],
          "wp_user_edited" => __("User Profile Updated", "ultimate-integration-for-telegram"),
        ),
      );
      $options["wp_comment"] = array(
        "title"   => __("WordPress / Comment", "ultimate-integration-for-telegram"),
        "desc"    => __("This notif will be triggered when a WordPress Comment is created, updated, or deleted.", "ultimate-integration-for-telegram"),
        "options" => array(
          "wp_comment_created"    => __("New Comment Created", "ultimate-integration-for-telegram"),
          "wp_comment_updated"    => __("Comment Updated", "ultimate-integration-for-telegram"),
          "wp_comment_deleted"    => __("Comment Deleted", "ultimate-integration-for-telegram"),
          "wp_comment_approved"   => __("Comment Approved", "ultimate-integration-for-telegram"),
          "wp_comment_unapproved" => __("Comment Unapproved", "ultimate-integration-for-telegram"),
        ),
      );
      if (class_exists("WooCommerce")) {
        $options["woocommerce_order"] = array(
          "title"   => __("WooCommerce / Order", "ultimate-integration-for-telegram"),
          "desc"    => __("This notif will be triggered when a WooCommerce Order is created, updated, deleted, or refunded.", "ultimate-integration-for-telegram"),
          "options" => array(
            "wc_new_order"              => __("New Order Created", "ultimate-integration-for-telegram"),
            "wc_order_saved"            => __("Order Updated / Saved", "ultimate-integration-for-telegram"),
            "wc_trash_order"            => __("Trash Order", "ultimate-integration-for-telegram"),
            "wc_delete_order"           => __("Delete Order", "ultimate-integration-for-telegram"),
            "wc_order_refunded"         => __("Refunded Order", "ultimate-integration-for-telegram"),
            "wc_payment_complete"       => __("Order Payment Complete", "ultimate-integration-for-telegram"),
            "wc_checkout_processed"     => __("Checkout Order Processed", "ultimate-integration-for-telegram"),
            "wc_checkout_api_processed" => __("Checkout Order Processed via API", "ultimate-integration-for-telegram"),
          ),
        );
        $statuses = wc_get_order_statuses();
        $emails = wp_list_pluck(WC()->mailer()->get_emails(), "title", "id");
        $statuses_options = $mail_options = array();
        $statuses_options["wc_order_status_changed"] = __("Changed to anything", "ultimate-integration-for-telegram");
        foreach ($statuses as $slug => $name) {
          $slug = $this->remove_status_prefix($slug);
          /* translators: 1: status name */
          $statuses_options["wc_order_status_to_{$slug}"] = sprintf(__("Changed to %s", "ultimate-integration-for-telegram"), $name);
        }
        $options["woocommerce_order_status"] = array(
          "title" => __("WooCommerce / Order / Status", "ultimate-integration-for-telegram"),
          "desc" => __("This notif will be triggered when a WooCommerce Order status is changed.", "ultimate-integration-for-telegram"),
          "options" => $statuses_options,
        );
        $options["woocommerce_product"] = array(
          "title" => __("WooCommerce / Product", "ultimate-integration-for-telegram"),
          "desc" => __("This notif will be triggered when a WooCommerce Product is created, updated, or deleted.", "ultimate-integration-for-telegram"),
          "options" => array(
            "wc_product_updated" => __("Product Updated or Saved", "ultimate-integration-for-telegram"),
            "--wc_product_purchased" => __("Product Purchased on New Order", "ultimate-integration-for-telegram"),
            "--wc_product_stock_increased" => __("Product Stock Quantity Increased", "ultimate-integration-for-telegram"),
            "--wc_product_stock_decreased" => __("Product Stock Quantity Decreased", "ultimate-integration-for-telegram"),
          ),
        );
        $mail_options["wc_mail_sent"] = sprintf(__("Mail Sent: All Emails", "ultimate-integration-for-telegram"), $name);
        foreach ($emails as $slug => $name) {
          /* translators: 1: email name */
          $mail_options["wc_mail_{$slug}"] = sprintf(__("Mail Sent: %s", "ultimate-integration-for-telegram"), $name);
        }
        $options["woocommerce_mail"] = array(
          "title" => __("WooCommerce / E-Mail", "ultimate-integration-for-telegram"),
          "desc" => __("This notif will be triggered when a WooCommerce E-Mail is sent.", "ultimate-integration-for-telegram"),
          "options" => $mail_options,
        );
      }
      $options = apply_filters("ultimate-integration-for-telegram/notif-panel/notif-types-array", $options);
      if ($return_array) return $options;
      if ($stdout_type === "category") {
        foreach ($options as $key => $value) {
          $class = apply_filters("ultimate-integration-for-telegram/notif-panel/notif-category/class", "notif-category", $key, $value);
          $attr = apply_filters("ultimate-integration-for-telegram/notif-panel/notif-category/attributes", "", $key, $value);
          if ($this->str_starts_with($key, "--")) {
            $attr .= " disabled ";
            $class .= " disabled ";
          }
          echo "<li class='" . esc_attr($class) . "' " . esc_attr($attr) . " data-key='" . esc_attr($key) . "'>" . esc_attr($value["title"]) . "</li>";
        }
      } elseif ($stdout_type === "items") {

        foreach ($options as $category => $items) {
          $class = apply_filters("ultimate-integration-for-telegram/notif-panel/notif-item-heading/class", "notif-heading", $category, $items);
          $attr = apply_filters("ultimate-integration-for-telegram/notif-panel/notif-item-heading/attributes", "", $category, $items);
          $info = isset($items["desc"]) && !empty($items["desc"]) ? "<span class='fas fa-info-circle help-event-span help-category-span' data-tippy-content='" . esc_attr($items["desc"]) . "'></span>" : "";
          echo "<div class='" . esc_attr($class) . "' data-category='" . esc_attr($category) . "' " . esc_attr($attr) . ">" . esc_attr($items["title"]) . "" . $info . "</div>";
          foreach ($items["options"] as $key => $value) {
            $label = $value;
            if (is_array($value)) $label = $value["label"] ?? $key;
            $class = apply_filters("ultimate-integration-for-telegram/notif-panel/notif-item-entry/class", "notif-entry", $key, $value);
            $attr = apply_filters("ultimate-integration-for-telegram/notif-panel/notif-item-entry/attributes", "", $key, $value);
            $attr = apply_filters("ultimate-integration-for-telegram/notif-panel/notif-item-entry/label", "", $key, $value);
            $attr = apply_filters("ultimate-integration-for-telegram/notif-panel/notif-item-entry/desc", "", $key, $value);
            if ($this->str_starts_with($key, "--")) {
              $attr .= " disabled ";
              $class .= " disabled ";
            }
            $info = isset($value["desc"]) && !empty($value["desc"]) ? "<span class='fas fa-question-circle help-event-span' data-tippy-content='" . esc_attr($value["desc"]) . "'></span>" : "";
            $add = "<span class='fas fa-plus add-current-item' data-tippy-content='" . __("Add Notification for this event", $this->td) . "'></span>";
            echo "<li class='" . esc_attr($class) . "' " . esc_attr($attr) . " data-category='" . esc_attr($category) . "' data-key='" . esc_attr($key) . "'>" . esc_attr($label) . $info . $add . "</li>";
          }
        }
      }
    }
    public function print_notif_setting($slug) {
      ob_start();
      $btn_placeholder = "Button label | Button URL";
      do_action("ultimate-integration-for-telegram/notif-panel/before-notif-setting", $slug);
      $default_message = apply_filters("ultimate-integration-for-telegram/notif-panel/notif-default-message", "", $slug);
      $default_parser = apply_filters("ultimate-integration-for-telegram/notif-panel/notif-default-parser", false, $slug);
      $default_buttons = apply_filters("ultimate-integration-for-telegram/notif-panel/notif-default-buttons", "", $slug);
    ?>
      <table class="sub-setting form-table wp-list-table widefat striped table-view-list fixed hide">
        <tbody>
          <tr class="tg-message">
            <th><?php esc_html_e("Message", "ultimate-integration-for-telegram"); ?></th>
            <td>
              <textarea rows="5"
                placeholder="<?php esc_attr_e("Write your message here ...", "ultimate-integration-for-telegram"); ?>"
                data-slug="message"
                ><?php
                // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                echo $default_message; ?></textarea>
              <p class="description">
                <?php
                // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                echo wp_kses_normalize_entities(sprintf(
                  // translators: 1: help link, 2: markdown validator
                  __('You can use <b>HTML</b>, <b>Markdown</b>, and <b>Macros</b> in your message. %1$s', "ultimate-integration-for-telegram"),
                  '<a href="https://core.telegram.org/bots/api#markdown-style" target="_blank">' . esc_html__("Formatting Guide", "ultimate-integration-for-telegram") . '</a>'
                )); ?>
              </p>
            </td>
          </tr>
          <tr class="tg-formatting">
            <th>
              <?php esc_html_e("HTML Formatting", "ultimate-integration-for-telegram"); ?>
              <i class="fas fa-question-circle" data-tippy-content="<?php esc_html_e("Enable (green) to use HTML formatting, or disable (red) to use Markdown formatting. Choose the appropriate option based on your message requirements.", "ultimate-integration-for-telegram"); ?>"></i>
            </th>
            <td>
              <input type="checkbox" <?php echo checked($default_parser, true, false); ?> data-slug="html_parser" value="html" />
            </td>
          </tr>
          <tr class="tg-validator">
            <th>
              <?php esc_html_e("Markdown Validator", "ultimate-integration-for-telegram"); ?>
              <i class="fas fa-question-circle" data-tippy-content="<?php esc_html_e("Tip: Telegram requires your message to be valid, especially when using Markdown formatting. Invalid syntax can cause your message to fail. Use the validator tool above to quickly check your text before saving.", "ultimate-integration-for-telegram"); ?>"></i>
            </th>
            <td>
              <a href='#' class='button button-primary validate_markdown'><?php echo esc_html__("Validate Markdown", "ultimate-integration-for-telegram"); ?></a>
              <div class="validation-result notice inline notice-error notice-alt"></div>
            </td>
          </tr>
          <tr class="tg-buttons">
            <th><?php esc_html_e("Buttons", "ultimate-integration-for-telegram"); ?>
              <i class="fas fa-question-circle" data-tippy-content="<?php esc_html_e("You can add up to four buttons. Both labels and URLs can use plain text or macros, and labels can also include emojis. You can use @page_slug, #page_id or {special_pages} supported by PeproDev Ultimate Profile Solutions as button URL too.", "ultimate-integration-for-telegram"); ?>"></i>
            </th>
            <td>
              <textarea rows="3" data-slug="buttons" placeholder="<?php echo esc_attr($btn_placeholder); ?>"><?php echo esc_textarea($default_buttons); ?></textarea>
              <p class="description">
                <?php
                echo sprintf(
                  /* translators: 1: example */
                  esc_attr__("e.g. %s", "ultimate-integration-for-telegram"),
                  '<copy>🌏 Visit Home | {site_url}</copy>'
                ); ?>
              </p>
            </td>
          </tr>
          <tr class="tg-buttons">
            <th>
              <?php esc_html_e("Recipients", "ultimate-integration-for-telegram"); ?>
              <i class="fas fa-question-circle" data-tippy-content="<?php esc_html_e("Paste the Chat ID or, if it's a public channel/group, the username with at-sign, e.g. @mychannel. \nEnter One Chat ID or username per line.", "ultimate-integration-for-telegram"); ?>"></i>
            </th>
            <td>
              <textarea rows="3" data-slug="recipients"
                placeholder="<?php esc_attr_e("Leave Empty to use Default Recipients.", "ultimate-integration-for-telegram"); ?>"
              ></textarea>
            </td>
          </tr>
          <tr class="description">
            <td colspan="2" class="macro-list">
              <?php
              echo "<h4 style='color: #1d2327;margin: 0;font-weight: normal;'>" . esc_html__("Available Event Macros for this Notification", "ultimate-integration-for-telegram") . "</h4>";
              ?>
              <div class="macro-list-wrapper">
                <?php
                $default_macros = array(
                  "general" => array(
                    "title" => esc_html__("General", "ultimate-integration-for-telegram"),
                    "macros" => array(
                      "current_time"       => _x("Current Time", "macro", "ultimate-integration-for-telegram"),
                      "current_date"       => _x("Current Date", "macro", "ultimate-integration-for-telegram"),
                      "current_date_time"  => _x("Current Date-time", "macro", "ultimate-integration-for-telegram"),
                      "current_jdate"      => _x("Current Jalali Date", "macro", "ultimate-integration-for-telegram"),
                      "current_jdate_time" => _x("Current Jalali Date-time", "macro", "ultimate-integration-for-telegram"),
                      "current_user_id"    => _x("Current User id", "macro", "ultimate-integration-for-telegram"),
                      "current_user_name"  => _x("Current User name", "macro", "ultimate-integration-for-telegram"),
                      "site_name"          => _x("Site name", "macro", "ultimate-integration-for-telegram"),
                      "site_url"           => _x("Site URL", "macro", "ultimate-integration-for-telegram"),
                      "admin_url"          => _x("Admin URL", "macro", "ultimate-integration-for-telegram"),
                    ),
                  ),
                );
                $filtered_hooks = apply_filters("ultimate-integration-for-telegram/notif-panel/notif-macro-list", (array) $default_macros, $slug);
                foreach ((array) $filtered_hooks as $macro_slug => $macro_category) {
                  echo "<strong>" . esc_html(isset($macro_category['title']) ? $macro_category['title'] : $macro_slug) . "</strong>";
                  if (isset($macro_category["macros"])) {
                    foreach ($macro_category["macros"] as $key => $value) {
                      echo "<copy data-tippy-content='" . esc_attr($value) . "'>{" . esc_attr($key) . "}</copy>";
                    }
                  }
                }
                ?>
                <?php do_action("ultimate-integration-for-telegram/notif-panel/after-macro-list", $filtered_hooks, $default_macros, $slug); ?>
              </div>
            </td>
          </tr>
          <?php
          do_action("ultimate-integration-for-telegram/notif-panel/after-notif-setting", $slug);
          ?>
        </tbody>
      </table>
<?php
      $htmloutput = ob_get_contents();
      ob_end_clean();
      return apply_filters("ultimate-integration-for-telegram/notif-panel/notif-setting-html", $htmloutput, $slug);
    }
    protected function update_footer_info() {
      add_filter("update_footer", function () {
        /* translators: 1: plugin name, 2: version ID */
        return sprintf(_x('%1$s — Version %2$s', "footer-copyright", "ultimate-integration-for-telegram"), $this->title, $this->version);
      }, 999999999);
    }
    #region settings-option functions >>>>>>>>>>>>>
    protected function print_setting_input($data) {
      extract(wp_parse_args($data, array(
        "slug"        => "",
        "caption"     => "",
        "type"        => "text",
        "desc"        => "",
        "extra_html"  => "",
        "extra_class" => "",
      )));
      echo "<tr class='type-" . esc_attr($type) . " " . esc_attr($extra_class) . " " . esc_attr(sanitize_title($slug)) . "'>
              <th scope='row'><label for='" . esc_attr($slug) . "'>" . esc_attr($caption) . "</label></th>
              <td><input name='" . esc_attr("{$this->db_slug}__{$slug}") . "'
                      id='" . esc_attr($slug) . "'
                      type='" . esc_attr($type) . "'
                      placeholder='" . esc_attr($caption) . "'
                      data-tippy-content='" .
        /* translators: 1: field name */
        esc_attr(sprintf(_x("Enter %s", "setting-page", "ultimate-integration-for-telegram"), $caption))
        . "' value='" . esc_attr((get_option("{$this->db_slug}__{$slug}", "") ?: "")) . "'
                      class='regular-text " . esc_attr($extra_class) . "' " . esc_attr($extra_html) . " />
                      <p class='description'>" . wp_kses_post($desc) . "</p>
                </td>
            </tr>";
    }
    protected function print_setting_checkbox($data) {
      extract(wp_parse_args($data, array(
        "slug"        => "",
        "caption"     => "",
        "desc"        => "",
        "value"       => "yes",
        "extra_html"  => "",
        "extra_class" => "",
      )));
      echo "<tr class='type-checkbox " . esc_attr($extra_class) . " " . esc_attr(sanitize_title($slug)) . "'>
              <th scope='row'><label for='" . esc_attr($slug) . "'>" . esc_attr($caption) . "</label></th>
              <td><input name='" . esc_attr("{$this->db_slug}__{$slug}") . "' id='" . esc_attr($slug) . "' type='checkbox' data-tippy-content='" .
        esc_attr(
          sprintf(
            /* translators: 1: field name */
            _x("Toggle: %s", "setting-page", "ultimate-integration-for-telegram"),
            $caption
          )
        ) .
        "' value='" . esc_attr($value) . "' " . checked($value, get_option("{$this->db_slug}__{$slug}", ""), false) .
        " class='regular-text " . esc_attr($extra_class) . "' " . esc_attr($extra_html) . " />
          <p class='description'>" . wp_kses_post($desc) . "</p>
          </td>
        </tr>";
    }
    protected function print_setting_select($data) {
      extract(wp_parse_args($data, array(
        "slug"        => "",
        "caption"     => "",
        "options"     => array(),
        "desc"        => "",
        "extra_html"  => "",
        "extra_class" => "",
      )));
      echo "<tr class='type-select " . esc_attr($extra_class) . " " . esc_attr(sanitize_title($slug)) . "'>
              <th scope='row'><label for='" . esc_attr($slug) . "'>" . esc_attr($caption) . "</label></th>
              <td><select name='" . esc_attr("{$this->db_slug}__{$slug}") . "'
                      id='" . esc_attr($slug) . "' data-tippy-content='" . esc_attr(
        /* translators: 1: field name */
        sprintf(_x("Choose %s", "setting-page", "ultimate-integration-for-telegram"), $caption)
      ) . "' class='regular-text " . esc_attr($extra_class) . "' " . esc_attr($extra_html) . ">";
      foreach ($options as $key => $value) {
        if ($key == "EMPTY") $key = "";
        echo "<option value='" . esc_attr($key) . "' " . selected(get_option("{$this->db_slug}__{$slug}", ""), $key, false) . ">" . esc_html($value) . "</option>";
      }
      echo "</select><p class='description'>" . wp_kses_post($desc) . "</p></td></tr>";
    }
    protected function print_setting_textarea($data) {
      extract(wp_parse_args($data, array(
        "slug" => "",
        "caption" => "",
        "style" => "",
        "desc" => "",
        "rows" => "4",
        "extra_html"  => "",
        "extra_class" => "",
      )));
      echo "<tr class='type-textarea " . esc_attr($extra_class) . "" . esc_attr(sanitize_title($slug)) . "'>
              <th scope='row'><label for='" . esc_attr($slug) . "'>" . esc_attr($caption) . "</label></th>
              <td><textarea
                      name='" . esc_attr("{$this->db_slug}__{$slug}") . "'
                      id='" . esc_attr($slug) . "'
                      placeholder='" . esc_attr($caption) . "'
                      data-tippy-content='" . esc_attr(
        /* translators: 1: field name */
        sprintf(_x("Enter %s", "setting-page", "ultimate-integration-for-telegram"), $caption)
      ) . "' rows='" . esc_attr($rows) . "'
                      style='" . esc_attr($style) . "'
                      class='regular-text " . esc_attr($extra_class) . "' " . esc_attr($extra_html) . " >" . esc_textarea(get_option("{$this->db_slug}__{$slug}", "") ?: "") . "</textarea>
              <p class='description'>" . wp_kses_post($desc) . "</p></td></tr>";
    }
    protected function print_setting_editor($data) {
      extract(wp_parse_args($data, array(
        "slug"        => "",
        "caption"     => "",
        "options"     => array(),
        "desc"        => "",
        "extra_class" => "",
      )));
      $editor_settings = array_merge($options, array(
        'editor_height' => 150,    // (number) Editor height in pixels
        'media_buttons' => false,  // (bool) Whether to show the Add Media/other media buttons.
        'teeny'         => false,  // (bool) Whether to output the minimal editor config. Examples include Press This and the Comment editor. Default false.
        'tinymce'       => true,   // (bool|array) Whether to load TinyMCE. Can be used to pass settings directly to TinyMCE using an array. Default true.
        'quicktags'     => false,  // (bool|array) Whether to load Quicktags. Can be used to pass settings directly to Quicktags using an array. Default true.
        'editor_class'  => "",     // (string) Extra classes to add to the editor textarea element. Default empty.
        'textarea_name' => "{$this->db_slug}__{$slug}",
      ));

      $editor_id = strtolower(str_replace(array('-', '_', ' ', '*'), '', $slug));
      echo "<tr class='type-editor " . esc_attr($extra_class) . " " . esc_attr(sanitize_title($slug)) . "'>
      <th scope='row'><label for='" . esc_attr($slug) . "'>" . esc_attr($caption) . "</label></th><td>";
      wp_editor((get_option("{$this->db_slug}__{$slug}", "") ?: ""), $editor_id, $editor_settings);

      echo "<p class='description'>" . wp_kses_post($desc) . "</p></td></tr>";
    }
    #endregion
  }
}
new Ultimate_Integration_Telegram_Setting;
