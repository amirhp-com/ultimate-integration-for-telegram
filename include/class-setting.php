<?php
use BlackSwan\Telegram\Notifier;
if (!class_exists("class_setting")) {
  class class_setting extends Notifier {
    public function __construct() {
      parent::__construct();
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
            "debug" => "no",
            "jdate" => "no",
            "gettext_replace" => "",
            "str_replace" => "",
            "token" => "",
            "username" => "",
            "chat_ids" => "",
            "notifications" => "",
          ),
        ),
      );
    }
    public function register_settings() {
      foreach ($this->get_setting_options_list() as $sections) {
        foreach ($sections["data"] as $id => $def) {
          add_option("{$this->db_slug}__{$id}", $def, "", 'no');
          register_setting("{$this->db_slug}__{$sections["name"]}", "{$this->db_slug}__{$id}");
        }
      }
    }
    public function setting_page(){
      ob_start();
      $this->update_footer_info();
      wp_enqueue_script('wp-color-picker');
      wp_enqueue_style('wp-color-picker');
      // Enqueue jQuery UI
      wp_enqueue_script('jquery-ui');
      wp_enqueue_script('jquery-ui-core');
      wp_enqueue_script('jquery-ui-datepicker');
      wp_enqueue_style($this->db_slug . "-fas", "//cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css", array(), current_time("timestamp"));
      wp_enqueue_style($this->db_slug . "-setting", "{$this->assets_url}css/backend.css");
      wp_enqueue_script($this->db_slug . "-setting", "{$this->assets_url}js/jquery.repeater.min.js", ["jquery"], "1.2.1");
      wp_enqueue_script($this->db_slug . "-panel", "{$this->assets_url}js/panel.js", ["jquery"], time());
      $data = $this->read("notifications");
      $localized = apply_filters("blackswan-telegram/notif-panel/localize-front-script", array(
        "ajax" => admin_url("admin-ajax.php"),
        "action" => $this->td,
        "notif_json" => htmlentities($data),
        "nonce" => wp_create_nonce($this->td),
        "wait" => _x("Please wait ...", "front-js", $this->td),
        "check_toggle" => _x("Check to Enable this Notif", "front-js", $this->td),
        "delete_confirm" => _x("Are you sure you want to delete this notifications? If you do and Save this page, YOU CANNOT UNDO WHAT YOU DID.", "front-js", $this->td),
        "delete_all" => _x("Are you sure you want to delete all notifications? If you do and Save this page, YOU CANNOT UNDO WHAT YOU DID.", "front-js", $this->td),
        "copied" => _x("Copied %s to clipboard.", "front-js", $this->td),
        "code_copied" => _x("JSON Data Copied to clipboard.", "front-js", $this->td),
        "error_slug_empty" => _x("first select a notif type", "front-js", $this->td),
        "error_option_empty" => _x("no option found for selected notif", "front-js", $this->td),
        "unknown" => _x("An Unknown Error Occured. Check console for more information.", "front-js", $this->td),
        "md" => array(
          "asterisk" => _x('Unmatched asterisk (*) found.', "front-js", $this->td),
          "underscore" => _x('Unmatched underscore (_) found.', "front-js", $this->td),
          "backtick" => _x('Unmatched backtick (`) found.', "front-js", $this->td),
          "triple_backticks" => _x('Unmatched triple backticks (```) found.', "front-js", $this->td),
          "invalid" => _x('Invalid {entity} formatting. Example: {example}', "front-js", $this->td),
          "valid" => _x('Success, Markdown is valid!', "front-js", $this->td),
        ),
      ));
      wp_localize_script($this->db_slug . "-panel", "_panel", $localized);
      is_rtl() and wp_add_inline_style($this->db_slug, "#wpbody-content { font-family: bodyfont, roboto, Tahoma; }");
      ?>
      <div class="wrap">
        <!-- region head and success message -->
        <h1 class="page-title-heading">
          <span class='fas fa-cogs'></span>&nbsp;
          <strong style="font-weight: 700;"><?= $this->title; ?></strong> <sup>v.<?= $this->version; ?></sup>
        </h1>
        <form method="post" action="options.php">
          <!-- region tab items -->
            <nav class="nav-tab-wrapper woo-nav-tab-wrapper">
              <a href="#" data-tab="tab_general" class="nav-tab nav-tab-active"><?= __("General", $this->td); ?></a>
              <a href="#" data-tab="tab_workspace" class="nav-tab"><?= __("Notifications", $this->td); ?></a>
              <a href="#" data-tab="tab_translate" class="nav-tab"><?= __("Translate", $this->td); ?></a>
              <a href="#" data-tab="tab_str_replace" class="nav-tab"><?= __("String Replace", $this->td); ?></a>
              <a href="#" data-tab="tab_documentation" class="nav-tab"><?= __("Documentation", $this->td); ?></a>
            </nav>
          <!-- region tab content -->
            <?php settings_fields("{$this->db_slug}__general"); ?>
            <div class="tab-content tab-active" data-tab="tab_general">
              <br>
              <table class="form-table wp-list-table widefat striped table-view-list posts">
                <thead>
                  <?php
                  echo "<tr class='sync_site_tr border-top'><th colspan=2>
                    <h2 style='display: inline-block; margin:0;'>" . __("General Configuration", $this->td) . "</h2></th></tr>";
                  ?>
                </thead>
                <tbody>
                  <?php
                  $this->print_setting_checkbox([
                    "slug" => "debug",
                    "caption" => __("Enable Debug Mode", $this->td),
                    "desc" => __("Enable to log detailed information for troubleshooting. Recommended for development only.",$this->td),
                  ]);
                  $this->print_setting_checkbox([
                    "slug" => "jdate",
                    "caption" => __("Enable Built-in Jalali Date", $this->td),
                    "desc" => __("Enable to use the Jalali (Shamsi) calendar format. Disable other Jalali plugins to avoid conflicts.",$this->td),
                  ]);
                  /* translators: 1: @BotFather hyperlinked, 2: validate token link, 3: line break <br> */
                  $this->print_setting_input([
                    "slug" => "token",
                    "caption" => esc_html__("Your bot token", $this->td),
                    "desc" => sprintf(
                      __('Message %1$s on Telegram to register your bot and get the token.%3$sEnter the token, save settings and click “%2$s”.', $this->td),
                      "<a href='https://t.me/botfather' target='_blank'>@BotFather</a>",
                      "<a href='#' class='button button-link connect'>" . __("Connect Webhook", $this->td) . "</a>",
                      "<br>",
                    ),
                  ]);
                  /* translators: 1: validate token link, 2: line break <br> */
                  $this->print_setting_input([
                    "slug" => "username",
                    // "extra_html" => "readonly",
                    "caption" => esc_html__("Your bot username", $this->td),
                    "desc" => __('Enter the username provided when you created the bot (without the "@" symbol).', $this->td),
                  ]);
                  $this->print_setting_textarea([
                    "slug" => "chat_ids",
                    "caption" => esc_html__("Default Chat IDs", $this->td),
                    "desc" => sprintf(
                      __("After saving the bot token, message your bot or add it to a group/channel to get the Chat ID.%sEnter each Chat ID, one per line, in the field above.", $this->td),
                      "<br>",
                    ),
                  ]);
                  ?>
                </tbody>
              </table>
            </div>
            <div class="tab-content" data-tab="tab_workspace">
              <br>
              <table class="form-table wp-list-table widefat striped table-view-list posts workspace-wrapper-parent">
                <thead>
                  <?php
                  echo "<tr class='sync_site_tr border-top'><th colspan=2>
                    <h2 style='display: inline-block; margin: 0;'>" . __("Notifications Panel", $this->td) . "</h2></th></tr>";
                  ?>
                  <tr>
                    <th colspan="2"><?=$this->render_workspace_tools();?></th>
                  </tr>
                </thead>
                <tbody class="workplace" data-empty="<?=esc_attr(__("No notif added to panel, you toolbar above to add your first notif.",$this->td));?>"></tbody>
                <tfoot>
                  <tr class="type-textarea notifications toggle-export-import hide">
                    <th scope="row" colspan="2">
                      <h3 style="margin-top: 0;"><label for="notifications"><?=__("Import/Export as JSON Data", $this->td);?></label></h3><a href="#" class="button button-secondary copy-code"><?=__("Copy to Clipboard", $this->td);?></a><br>
                      <p class="data-textarea"><textarea name="blackswan-telegram__notifications" id="notifications" rows="4" style="width: 100%; direction: ltr; font-family: monospace; font-size: smaller;" class="regular-text"><?=$this->read("notifications");?></textarea></p>
                      <p class="description"><?=__("you can use the json data to migrate settings across multiple sites. Enter JSON Data and Save page to reload Workspace.", $this->td);?></p>
                    </th>
                  </tr>
                </tfoot>
              </table>
            </div>
            <div class="tab-content" data-tab="tab_documentation">
              <br>
              <div class="desc">
                <table class="fixed wp-list-table widefat striped table-view-list posts hooks-docs">
                  <thead>
                    <tr>
                      <th><strong><?=__("Hook Entry", $this->td);?></strong></th>
                      <td><strong><?=__("Description", $this->td);?></strong></td>
                    </tr>
                  </thead>


                  <tr>
                    <td><?=$this->highlight('apply_filters("blackswan-telegram/notif-panel/notif-types-array", $options)');?></td>
                    <td>Add new Notification type, e.g. Support for Custom Plugin</td>
                  </tr>

                  <tr>
                    <td><?=$this->highlight('do_action("blackswan-telegram/notif-panel/after-notif-setting", $slug)');?></td>
                    <td>Add Custom Setting a Notification type</td>
                  </tr>

                  <tr>
                    <td><?=$this->highlight('do_action("blackswan-telegram/notif-panel/notif-macro-list", $slug)');?></td>
                    <td>Add Custom Macro for a Notification type</td>
                  </tr>

                  <tr>
                    <td><?=$this->highlight('apply_filters("blackswan-telegram/notif-panel/notif-default-message", "", $slug)');?></td>
                    <td>Change Default Message for a Notification type</td>
                  </tr>

                  <tr>
                    <td><?=$this->highlight('apply_filters("blackswan-telegram/notif-panel/notif-default-parser",false, $slug)');?></td>
                    <td>Change Default Parser as HTML Checkbox state for a Notification type</td>
                  </tr>

                  <tr>
                    <td><?=$this->highlight('apply_filters("blackswan-telegram/notif-panel/notif-default-buttons", "", $slug)');?></td>
                    <td>Change Default Buttons list for a Notification type</td>
                  </tr>

                  <tr>
                    <td>Host Cron Job</td>
                    <td>wp-config: <?=$this->highlight("define('DISABLE_WP_CRON', true);");?>
                    <br>cronjob: <?=$this->highlight("* * * * * wget --delete-after ".home_url("/wp-cron.php?doing_wp_cron")." >/dev/null 2>&1");?>
                    <br>cronjob: <?=$this->highlight('/usr/local/bin/php /home/public_html/wp-cron.php?doing_wp_cron >/dev/null 2>&1');?></td>
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
                      <th class="th-original"><?= __("Original", $this->td); ?></th>
                      <th class="th-translate"><?= __("Translate", $this->td); ?></th>
                      <th class="th-text-domain"><?= __("TextDomain", $this->td); ?></th>
                      <th class="th-options"><?= __("Options", $this->td); ?></th>
                      <th class="th-action" style="width: 100px;"><?= __("Action", $this->td); ?></th>
                    </tr>
                  </thead>
                  <tbody data-repeater-list="gettext">
                    <tr data-repeater-item style="display:none;">
                      <td class="th-original"><span class="dashicons dashicons-menu-alt move-handle"></span>&nbsp;<input type="text" data-slug="original" name="original" value="" placeholder="<?= __("Original text ...", $this->td); ?>" /></td>
                      <td class="th-translate"><input type="text" data-slug="translate" name="translate" placeholder="<?= __("Translate to ...", $this->td); ?>" /></td>
                      <td class="th-text-domain"><input type="text" data-slug="text_domain" name="text_domain" placeholder="<?= __("Text Domain (Optional)", $this->td); ?>" /></td>
                      <td class="th-options">
                        <label><input type="checkbox" value="yes" data-slug="use_replace" name="use_replace" />&nbsp;<?= __("Partial Replace?", $this->td); ?></label>&nbsp;&nbsp;
                        <!-- <label><input type="checkbox" value="yes" data-slug="use_regex" name="use_regex"/>&nbsp;<?= __("Use RegEx?", $this->td); ?></label>&nbsp;&nbsp; -->
                        <label><input type="checkbox" value="yes" data-slug="two_sided" name="two_sided" />&nbsp;<?= __("Translated Origin?", $this->td); ?></label>
                      </td>
                      <td class="th-action">
                        <a class="button button-secondary" data-repeater-delete><span style="margin: 4px 0;" class="dashicons dashicons-trash"></span><?= __("Delete Row", $this->td); ?></a>
                      </td>
                    </tr>
                  </tbody>
                </table>
                <br>
                <a data-repeater-create class="button button-secondary button-hero"><span style="margin: 12px 8px;" class="dashicons dashicons-insert"></span><?= __("Add New Row", $this->td); ?></a>&nbsp;&nbsp;
              </div>
              <br><br>
              <table class="form-table wp-list-table widefat striped table-view-list posts">
                <thead>
                  <?php
                  echo "<tr class='sync_site_tr border-top'><th colspan=2><strong style='display: inline-block;'>" . __("Migrate Translation", $this->td) . "</strong></th></tr>";
                  ?>
                </thead>
                <tbody>
                  <?php
                  $this->print_setting_textarea(["slug" => "gettext_replace", "caption" => __("Translation Data", $this->td), "style" => "width: 100%; direction: ltr; min-height: 300px; font-family: monospace; font-size: 0.8rem;",]);
                  ?>
                </tbody>
              </table>
            </div>
            <div class="tab-content" data-tab="tab_str_replace">
              <br>
              <div class="desc repeater str_replace-panel">
                <table class="wp-list-table widefat striped table-view-list posts">
                  <thead>
                    <tr>
                      <th class="th-original"><?= __("Original", $this->td); ?></th>
                      <th class="th-translate"><?= __("Replace", $this->td); ?></th>
                      <th class="th-options"><?= __("Options", $this->td); ?></th>
                      <th class="th-action" style="width: 100px;"><?= __("Action", $this->td); ?></th>
                    </tr>
                  </thead>
                  <tbody data-repeater-list="gettext">
                    <tr data-repeater-item style="display:none;">
                      <td class="th-original"><span class="dashicons dashicons-menu-alt move-handle"></span>&nbsp;<input type="text" data-slug="original" name="original" value="" placeholder="<?= __("Original text ...", $this->td); ?>" /></td>
                      <td class="th-translate"><input type="text" data-slug="translate" name="translate" placeholder="<?= __("Translate to ...", $this->td); ?>" /></td>
                      <td class="th-options">
                        <label><input type="checkbox" value="yes" data-slug="buffer" name="buffer" />&nbsp;<?= __("Green: Replace in Output Buffer | Red: Replace in Content Only", $this->td); ?></label>&nbsp;&nbsp;
                        <label><input type="checkbox" value="yes" data-slug="active" name="active" />&nbsp;<?= __("Active", $this->td); ?></label>&nbsp;&nbsp;
                      </td>
                      <td class="th-action">
                        <a class="button button-secondary" data-repeater-delete><span style="margin: 4px 0;" class="dashicons dashicons-trash"></span><?= __("Delete Row", $this->td); ?></a>
                      </td>
                    </tr>
                  </tbody>
                </table>
                <br>
                <a data-repeater-create class="button button-secondary button-hero"><span style="margin: 12px 8px;" class="dashicons dashicons-insert"></span><?= __("Add New Row", $this->td); ?></a>&nbsp;&nbsp;
              </div>
              <br><br>
              <table class="form-table wp-list-table widefat striped table-view-list posts">
                <thead>
                  <?php
                  echo "<tr class='sync_site_tr border-top'><th colspan=2><strong style='display: inline-block;'>" . __("Migrate String Replace", $this->td) . "</strong></th></tr>";
                  ?>
                </thead>
                <tbody>
                  <?php
                  $this->print_setting_textarea(["slug" => "str_replace", "caption" => __("String Replace Data", $this->td), "style" => "width: 100%; direction: ltr; min-height: 300px; font-family: monospace; font-size: 0.8rem;",]);
                  ?>
                </tbody>
              </table>
            </div>
          <!-- region submit wrapper -->
            <div class="submit_wrapper">
              <button class="button button-primary button-hero"><?= __("Save setting", $this->td); ?></button>
              <a href='#' class="button button-secondary button-hero send_test"><?=__("Send Test Message", $this->td);?></a>
              <a href='#' class="button button-secondary button-hero connect"><?=__("Connect Webhook", $this->td);?></a>
              <a href='#' class="button button-secondary button-hero disconnect"><?=__("Disconnect Webhook", $this->td);?></a>
            </div>
        </form>
      </div>
      <!-- region tab switch script and js-repeater -->
      <?php
      $html = ob_get_contents();
      ob_end_clean();
      print $html;
    }
    public function highlight($php){
      return str_replace("&lt;?php", "", highlight_string('<?php'.$php, 1));
    }
    public static function remove_status_prefix( string $status ): string {
      if ( strpos( $status, 'wc-' ) === 0 ) $status = substr( $status, 3 );
      return $status;
    }
    public function sample_setting_row_wrapper(){
      ob_start();
      ?>
      <tr class="setting-row newly-added" data-type="{slug}">
        <th colspan="2">
          <h3 class="entry-name">{category} / {title}</h3>&nbsp;
          <div class="fa-pull-right">
            <input type="checkbox" class="enable--entry" data-slug="_enabled" title="<?=_x("Check to Enable this Notif", "front-js", $this->td);?>" />
            <a href="#" class="edit--entry"><span class="fa-stack"><i class="fa-solid fa-circle fa-stack-2x"></i><i class="fas fa-stack-1x fa-inverse fa-cog"></i></span></a>
            <a href="#" class="delete--entry"><span class="fa-stack"><i class="fa-solid fa-circle fa-stack-2x"></i><i class="fas fa-stack-1x fa-inverse fa-trash-alt"></i></span></a>
          </div>
          <table class="sub-setting form-table wp-list-table widefat striped table-view-list fixed hide"><tbody><tr><th colspan="2">{row_details}</th></tr></tbody></table>
        </th>
      </tr>
      <?php
      $htmloutput = ob_get_contents();
      ob_end_clean();
      return apply_filters("blackswan-telegram/notif-panel/sample_setting_row_wrapper", $htmloutput);
    }
    public function render_workspace_tools(){
      ?>
      <div class="workspace-notifications-list">
        <div class="workspace-tools">
          <select id="notif_types">
            <option value="" ><?=__("-- None --", $this->td);?></option>
            <?php
            do_action("blackswan-telegram/notif-panel/after-general-notif-dropdown");
            $this->print_notif_types();
            do_action("blackswan-telegram/notif-panel/after-notif-types-dropdown");
            ?>
          </select>
          <a href="#" class="button button-secondary add-new-notif"><?=__("Add New Notif", $this->td);?></a>
          <a href="#" class="button button-secondary clear-all-notif"><?=__("Clear All Notif", $this->td);?></a>
          <a href="#" class="button button-secondary export-import-notif"><?=__("Export/Import Notif", $this->td);?></a>
        </div>
        <div class="template-wrapper">
          <?php
          foreach ($this->print_notif_types(true) as $category => $items) {
            foreach ($items["options"] as $key => $value) {
              echo "<!-- Notif Setting / ".esc_attr($items["title"])." / ".esc_attr($value)." -->";
              echo "<template id='".esc_attr($key)."'>";
              echo $this->print_notif_setting($key);
              echo "</template>";
            }
          }
          ?>
          <template id="sample_setting_row_wrapper"><?=$this->sample_setting_row_wrapper();?></template>
          <?php do_action("blackswan-telegram/notif-panel/notif-types-setting"); ?>
        </div>
      </div>
      <?php
    }
    public function print_notif_types($return=false){
      $options = array();
      $options["wp_general"] = array(
        "title" => __("WordPress General", $this->td),
        "options" => array(
          "wp_user_registered" => __("New User Registered", $this->td),
          "wp_comment_submitted" => __("New Comment Submitted", $this->td),
        ),
      );
      if (class_exists("WooCommerce")) {
        $options["woocommerce_order"] = array(
          "title" => __("WooCommerce / Order", $this->td),
          "options" => array(
            "wc_new_order_at_checkout_processed" => __("New Order at Checkout Processed", $this->td),
            "wc_new_order_on_thank_you" => __("New Order on Thank You", $this->td),
            "wc_new_order_payment_complete" => __("New Order Payment Complete", $this->td),
          ),
        );
        $statuses = wc_get_order_statuses();
        $emails = wp_list_pluck(WC()->mailer()->get_emails(), "title", "id");
        $statuses_options = $mail_options = array();
        foreach ($statuses as $slug => $name) {
          $slug = $this->remove_status_prefix($slug);
          $statuses_options["wc_order_status_to_{$slug}"] = sprintf(__("Changed to %s",$this->td), $name);
        }
        $options["woocommerce_order_status"] = array(
          "title" => __("WooCommerce / Order / Status", $this->td),
          "options" => $statuses_options,
        );
        $options["woocommerce_product"] = array(
          "title" => __("WooCommerce / Product", $this->td),
          "options" => array(
            "wc_product_updated" => __("Product Status/Meta Updated",$this->td),
            "wc_product_purchased" => __("Product Purchased on New Order",$this->td),
            "wc_product_stock_increased" => __("Product Stock Quantity Increased",$this->td),
            "wc_product_stock_decreased" => __("Product Stock Quantity Decreased",$this->td),
          ),
        );
        foreach ($emails as $slug => $name) {
          $slug = $this->remove_status_prefix($slug);
          $mail_options["wc_mail_{$slug}"] = sprintf(__("Mail Sent: %s",$this->td), $name);
        }
        $options["woocommerce_mail"] = array(
          "title" => __("WooCommerce / E-Mail", $this->td),
          "options" => $mail_options,
        );
      }
      $options = apply_filters("blackswan-telegram/notif-panel/notif-types-array", $options);
      if ($return) return $options;
      foreach ($options as $category => $items) {
        echo "<optgroup data-id='".esc_attr($category)."' label='".esc_attr($items["title"])."'></optgroup>";
        foreach ($items["options"] as $key => $value) {
          echo "<option value='".esc_attr($key)."'>".esc_attr($value)."</option>";
        }
      }
    }
    public function print_notif_setting($slug){
      ob_start();
      $btn_placeholder = __("Button 1 label | Button 1 URL\nButton 2 label | Button 2 URL\nButton 3 label | Button 3 URL",$this->td);
      do_action("blackswan-telegram/notif-panel/before-notif-setting", $slug);
      $default_message = apply_filters("blackswan-telegram/notif-panel/notif-default-message", "", $slug);
      $default_parser = apply_filters("blackswan-telegram/notif-panel/notif-default-parser", false, $slug);
      $default_buttons = apply_filters("blackswan-telegram/notif-panel/notif-default-buttons", "", $slug);
      ?>
      <tr>
        <th><?=__("Message", $this->td);?></th>
        <td>
          <textarea rows="4" data-slug="message"><?=$default_message;?></textarea>
          <p class="description"><?=sprintf(
            __('You can use <b>HTML</b>, <b>Markdown</b>, and <b>Macros</b> in your message (See the %1$s). Ensure your Markdown is valid with the "%2$s".',$this->td),
            '<a href="https://core.telegram.org/api/entities#allowed-entities" target="_blank">'.__("Telegram Formatting Guide",$this->td).'</a>',
            "<a href='#' class='button button-link validate_markdown'>".__("Markdown Validator",$this->td)."</a>");?></p>
        </td>
      </tr>
      <tr>
        <th><?=__("Message Formatting Method", $this->td);?></th>
        <td>
          <label><input type="checkbox" <?=checked($default_parser, true, false);?> data-slug="html_parser" value="html">&nbsp;
          <?=__("Enable (green) to use HTML, or disable (red) to use Markdown", $this->td);?></label>
        </td>
      </tr>
      <tr>
        <th><?=__("Buttons", $this->td);?></th>
        <td>
          <textarea rows="4" data-slug="btn_row1" placeholder="<?=$btn_placeholder;?>"><?=$default_buttons;?></textarea>
          <p class="description">
            <?=__("You can add up to four buttons. Both labels and URLs can use plain text or macros, and labels can also include emojis.", $this->td);?>
            <?=sprintf(
              __("Sample Button: %s", $this->td),
              '<copy title="'.esc_attr__("Sample Button", $this->td).'">🌏 Visit {site_name}|{site_url}</copy>');?>
          </p>
        </td>
      </tr>
      <tr class="description">
        <th><?=__("Available Macros for this Notif", $this->td);?></th>
        <td class="macro-list">
            <strong><?=__("General", $this->td);?></strong>
            <copy title="<?=esc_attr(_x("Current Time","macro",$this->td));?>">{current_time}</copy>
            <copy title="<?=esc_attr(_x("Current Date","macro",$this->td));?>">{current_date}</copy>
            <copy title="<?=esc_attr(_x("Current Date-time","macro",$this->td));?>">{current_date_time}</copy>
            <copy title="<?=esc_attr(_x("Current Jalali Date","macro",$this->td));?>">{current_jdate}</copy>
            <copy title="<?=esc_attr(_x("Current Jalali Date-time","macro",$this->td));?>">{current_jdate_time}</copy>
            <copy title="<?=esc_attr(_x("Current User id","macro",$this->td));?>">{current_user_id}</copy>
            <copy title="<?=esc_attr(_x("Current User name","macro",$this->td));?>">{current_user_name}</copy>
            <copy title="<?=esc_attr(_x("Site name","macro",$this->td));?>">{site_name}</copy>
            <copy title="<?=esc_attr(_x("Site URL","macro",$this->td));?>">{site_url}</copy>
            <copy title="<?=esc_attr(_x("Admin URL","macro",$this->td));?>">{admin_url}</copy>
            <?php do_action("blackswan-telegram/notif-panel/notif-macro-list", $slug); ?>
        </td>
      </tr>
      <?php
      do_action("blackswan-telegram/notif-panel/after-notif-setting", $slug);
      $htmloutput = ob_get_contents();
      ob_end_clean();
      return apply_filters("blackswan-telegram/notif-panel/notif-setting-html", $htmloutput, $slug);
    }
    protected function update_footer_info() {
      add_filter("update_footer", function () {
        return sprintf(_x("%s — Version %s", "footer-copyright", $this->td), $this->title, $this->version);
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
      echo "<tr class='type-" . esc_attr($type) . " $extra_class " . sanitize_title($slug) . "'>
              <th scope='row'><label for='$slug'>$caption</label></th>
              <td><input
                      name='" . esc_attr("{$this->db_slug}__{$slug}") . "'
                      id='" . esc_attr($slug) . "'
                      type='" . esc_attr($type) . "'
                      placeholder='" . esc_attr($caption) . "'
                      title='" . esc_attr(sprintf(_x("Enter %s", "setting-page", $this->td), $caption)) . "'
                      value='" . esc_attr((get_option("{$this->db_slug}__{$slug}", "") ?: "")) . "'
                      class='regular-text " . esc_attr($extra_class) . "' " . esc_attr($extra_html) . " />
              <p class='description'>" . ($desc) . "</p></td></tr>";
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
      echo "<tr class='type-checkbox $extra_class " . sanitize_title($slug) . "'>
              <th scope='row'><label for='$slug'>$caption</label></th>
              <td><input
                      name='" . esc_attr("{$this->db_slug}__{$slug}") . "'
                      id='" . esc_attr($slug) . "'
                      type='checkbox'
                      title='" . esc_attr(sprintf(_x("Enter %s", "setting-page", $this->td), $caption)) . "'
                      value='" . esc_attr($value) . "'
                      " . checked($value, get_option("{$this->db_slug}__{$slug}", ""), false) . "
                      class='regular-text " . esc_attr($extra_class) . "' " . esc_attr($extra_html) . " />
              <p class='description'>" . ($desc) . "</p></td></tr>";
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
      echo "<tr class='type-select $extra_class " . sanitize_title($slug) . "'>
              <th scope='row'><label for='$slug'>$caption</label></th>
              <td><select
                      name='" . esc_attr("{$this->db_slug}__{$slug}") . "'
                      id='" . esc_attr($slug) . "'
                      title='" . esc_attr(sprintf(_x("Choose %s", "setting-page", $this->td), $caption)) . "'
                      class='regular-text " . esc_attr($extra_class) . "' " . esc_attr($extra_html) . ">";
      foreach ($options as $key => $value) {
        if ($key == "EMPTY") {
          $key = "";
        }
        echo "<option value='" . esc_attr($key) . "' " . selected(get_option("{$this->db_slug}__{$slug}", ""), $key, false) . ">" . esc_html($value) . "</option>";
      }
      echo "</select><p class='description'>" . ($desc) . "</p></td></tr>";
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
      echo "<tr class='type-textarea $extra_class" . sanitize_title($slug) . "'>
              <th scope='row'><label for='$slug'>$caption</label></th>
              <td><textarea
                      name='" . esc_attr("{$this->db_slug}__{$slug}") . "'
                      id='" . esc_attr($slug) . "'
                      placeholder='" . esc_attr($caption) . "'
                      title='" . esc_attr(sprintf(_x("Enter %s", "setting-page", $this->td), $caption)) . "'
                      rows='" . esc_attr($rows) . "'
                      style='" . esc_attr($style) . "'
                      class='regular-text " . esc_attr($extra_class) . "' " . esc_attr($extra_html) . " >" . (get_option("{$this->db_slug}__{$slug}", "") ?: "") . "</textarea>
              <p class='description'>" . ($desc) . "</p></td></tr>";
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
      echo "<tr class='type-editor $extra_class " . sanitize_title($slug) . "'>
      <th scope='row'><label for='$slug'>$caption</label></th><td>";
      wp_editor((get_option("{$this->db_slug}__{$slug}", "") ?: ""), $editor_id, $editor_settings);

      echo "<p class='description'>" . ($desc) . "</p></td></tr>";
    }
    #endregion
  }
}
new class_setting;