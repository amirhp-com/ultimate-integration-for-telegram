<?php
/*
 * Plugin Name: BlackSwan - Telegram Notification
 * Description: Send Customized WooCommerce Emails/Notifications to Telegram Channel/Group/Bot/PrivateChat with Built-in Gettext Replace, Translation Manager and String Replace across site
 * Version: 1.0.0
 * Stable tag: 1.0.0
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
 * @Last modified time: 2025/01/08 17:44:54
*/

namespace BlackSwan\Telegram;
defined("ABSPATH") or die("<h2>Unauthorized Access!</h2><hr><small>BlackSwan - Telegram Notification :: Developed by <a href='https://amirhp.com/'>Amirhp-com</a></small>");

if (!class_exists("Notifier")) {
  class Notifier {
    protected $td = "blackswan-telegram";
    protected $db_slug = "blackswan-telegram";
    protected $version = "1.0.0";
    protected $title = "BlackSwan - Telegram Notification";
    protected $title_small = "Telegram Notification";
    protected $assets_url;
    protected $str_replace;
    protected $gettext_replace;
    protected $include_dir;
    protected $debug = false;
    public function __construct() {
      $this->setup_variables();
      #region hooks >>>>>>>>>>>>>
      add_action("init", array($this, "init_plugin"));
      #endregion
      #region string-replace and translate-replace >>>>>>>>>>>>>
      add_filter("gettext", array($this, "gettext_translate"), 999999, 3);
      add_filter("the_content", array($this, "str_replace_translate"), 999999);
      add_action("template_redirect", array($this, "buffer_start_replace_translate"));
      add_action("shutdown", array($this, "buffer_finish_replace_translate"));
      #endregion
    }
    private function setup_variables(){
      $this->assets_url = plugins_url("/assets/", __FILE__);
      $this->include_dir = plugin_dir_path(__FILE__) . "include";
      $this->debug = "yes" == $this->read("debug", "no");
      $this->str_replace = $this->read("str_replace");
      $this->gettext_replace = $this->read("gettext_replace");
      add_action("before_woocommerce_init", [$this, "add_hpos_support"]);
    }
    public function add_hpos_support() {
      if (class_exists(\Automattic\WooCommerce\Utilities\FeaturesUtil::class)) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
      }
    }
    #region hooked-functions >>>>>>>>>>>>>
    public function init_plugin() {
      $this->title = __("BlackSwan - Telegram Notification", $this->td);
      $this->title_small = __("Telegram Notification", $this->td);

      add_action("admin_init", array($this, "register_settings"), 1);
      add_action("admin_menu", array($this, "admin_menu"));
    }
    public function admin_menu() {
      add_options_page($this->title, $this->title_small, "manage_options", $this->db_slug, array($this, "setting_page_container"));
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
              }else{
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
      } catch (\Throwable $th) { }
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
      } catch (\Throwable $th) { }
      return $content;
    }
    public function buffer_start_replace_translate(){
      ob_start(function($content){
        try {
          $debug = json_decode($this->str_replace);
          if (isset($debug->gettext)) {
            foreach ($debug->gettext as $obj) {
              if ("yes" != $obj->active) continue;
              $original = trim($obj->original);
              $translate = trim($obj->translate);
              $buffer = $obj->buffer;
              if ($buffer == "yes") { $content = str_replace($original, $translate, $content); }
            }
          }
        } catch (\Throwable $th) { }
        return $content;
      });
    }
    public function buffer_finish_replace_translate(){
      while (@ob_end_flush());
    }
    #endregion
    #region settings-page and functions >>>>>>>>>>>>>
    private function read($slug = "debug", $default = "") {
      return (string) get_option("{$this->db_slug}__{$slug}", $default);
    }
    private function enabled($slug = "debug", $default = "no", $compare = "yes") {
      return $compare === (string) $this->read($slug, $default);
    }
    private function get_setting_options_list() {
      return array( array(
          "name" => "general",
          "data" => array(
            "debug" => "no",
            "gettext_replace" => "",
            "str_replace" => "",
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
    private function update_footer_info() {
      add_filter("update_footer", function () {
        return sprintf(_x("%s — Version %s", "footer-copyright", $this->td), $this->title, $this->version);
      }, 999999999);
    }
    public function setting_page_container() {
      ob_start();
      $this->update_footer_info();
      wp_enqueue_script('wp-color-picker');
      wp_enqueue_style('wp-color-picker');
      wp_enqueue_style($this->db_slug . "-fas", "//cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css", array(), current_time("timestamp"));
      wp_enqueue_style($this->db_slug . "-setting", "{$this->assets_url}css/backend.css");
      wp_enqueue_script($this->db_slug . "-setting", "{$this->assets_url}js/jquery.repeater.min.js", ["jquery"], "1.2.1");
      is_rtl() and wp_add_inline_style($this->db_slug, "#wpbody-content { font-family: bodyfont, roboto, Tahoma; }");
      ?>
      <div class="wrap">
        <!-- region head and success message -->
          <h1 class="page-title-heading">
            <span class='fas fa-cogs'></span>&nbsp;
            <strong style="font-weight: 700;"><?= $this->title; ?></strong> <sup>v.<?= $this->version; ?></sup>
          </h1>
          <?php
          if (isset($_REQUEST["settings-updated"]) && $_REQUEST["settings-updated"] == "true") {
            echo '<div id="message" class="updated notice is-dismissible"><p>' . _x("Settings saved successfully.", "setting-general", $this->td) . "</p></div>";
          }
          ?>
        <form method="post" action="options.php">
          <!-- region tab items -->
            <nav class="nav-tab-wrapper woo-nav-tab-wrapper">
              <a href="#" data-tab="tab_general" class="nav-tab nav-tab-active"><?= __("General", $this->td); ?></a>
              <a href="#" data-tab="tab_customization" class="nav-tab"><?= __("Customization", $this->td); ?></a>
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
                  <h2 style='display: inline-block;'>" . __("General Configuration", $this->td) . "</h2></th></tr>";
                  ?>
                </thead>
                <tbody>
                  <?php
                  $this->print_setting_checkbox(["slug" => "debug", "caption" => __("Active Debug Mode", $this->td), "desc" => "<a href='" . admin_url("?send_test_sms=ORDER_ID") . "'>Send Test SMS</a>"]);
                  $this->print_setting_checkbox(["slug" => "classic_editor", "caption" => __("Disable Gutenberg", $this->td),]);
                  $this->print_setting_input(["extra_class" => "wpColorPicker", "slug" => "telegram_fab_hover_color", "caption" => __("FAB Hover Color", $this->td),]);
                  $this->print_setting_input(["slug" => "fontawesome", "caption" => esc_html__("Add FontAwesome", $this->td), "desc" => __("Add FontAwesome to All frontend pages, leave empty to disable.", $this->td) . "<br><small>//cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css</small>",]);
                  ?>

                </tbody>
              </table>
            </div>
            <div class="tab-content" data-tab="tab_customization">
              <br>
              <table class="form-table wp-list-table widefat striped table-view-list posts">
                <thead>
                  <?php
                  echo "<tr class='sync_site_tr border-top'><th colspan=2>
                  <h2 style='display: inline-block;'>" . __("Customization", $this->td) . "</h2></th></tr>";
                  ?>
                </thead>
                <tbody>
                  <?php
                  $this->print_setting_checkbox(["slug" => "show_welcome", "caption" => __("Show Welcome CurrencySelect", $this->td),]);
                  $this->print_setting_textarea(["slug" => "welcome_cc", "caption" => __("Welcome CurrencySelect Message", $this->td), "style" => "width: 100%; direction: ltr; min-height: 300px; font-family: monospace; font-size: 0.8rem;",]);
                  ?>
                </tbody>
              </table>
            </div>
            <div class="tab-content" data-tab="tab_documentation">
              <br>
              <div class="desc">
                <table class="wp-list-table widefat striped table-view-list posts">
                  <thead>
                    <tr>
                      <th><strong>Endpoint/Shortcode/Entry</strong></th>
                      <td><strong>Description</strong></td>
                    </tr>
                  </thead>
                  <tr>
                    <td style="direction: ltr;">[secure_video file="video.mp4" height="360" width="640" poster=""<br>loop="" muted="" return="" autoplay="" preload="metadata" class="wp-video-shortcode"]</td>
                    <td>Display video inside secure folder using nginX SecureDownload method.<br>file is relative path to mp4 video file name inside secure folder.<br>If poster is empty, video file name .jpg is used.<br>If return is "url" the video url would be returned.</td>
                  </tr>
                  <tr>
                    <td style="direction: ltr;">[secure_audio file="video.mp3" <br>loop="" muted="" return="" autoplay="" preload="metadata" class="wp-audio-shortcode"]</td>
                    <td>Display audio inside secure folder using nginX SecureDownload method.<br>file is relative path to mp3 audio file name inside secure folder.<br>If return is "url" the audio url would be returned.</td>
                  </tr>
                  <tr>
                    <td style="direction: ltr;">[total_sales id="3435" <br>before="Total Sold: " after=" Units"]</td>
                    <td>Display total_sales of given product. if id is empty current product is selected.</td>
                  </tr>
                  <tr>
                    <td style="direction: ltr;">.hide-for-loggedin / .hide-for-loggedout</td>
                    <td>Optional Classes are added on frontend by default.</td>
                  </tr>
                  <tr>
                    <td style="direction: ltr;">.read_more</td>
                    <td>If ReadMore feature is Activated, add this class to any element to append ReadMore toggle</td>
                  </tr>
                  <tr>
                    <td style="direction: ltr;">wp-admin/?enrol_pid_to_ld=1&course_id=1010&product_id=2020</td>
                    <td>Enroll Users who bought product_id 2020 in an-completed order, to learndash course_id 1010</td>
                  </tr>
                  <tr>
                    <td style="direction: ltr;">wp-admin/?enrol_pid_to_ld=1&course_id=1010&product_id=2020&remove=yes</td>
                    <td>Remove learndash course_id 1010 form Users who bought product_id 2020 in an-completed order</td>
                  </tr>
                  <tr>
                    <td style="direction: ltr;">anypage/?currency_switch | anypage/?cs</td>
                    <td>Switch Currency to another one</td>
                  </tr>
                  <tr>
                    <td style="direction: ltr;">[current_currency] / [other_currency]</td>
                    <td>return currency name</td>
                  </tr>
                  <tr>
                    <td style="direction: ltr;">[if_irr]Content[/if_irr]</td>
                    <td>Show Content if current currency is: IRR</td>
                  </tr>
                  <tr>
                    <td style="direction: ltr;">[if_usd]Content[/if_usd]</td>
                    <td>Show Content if current currency is: USD</td>
                  </tr>
                  <tr>
                    <td style="direction: ltr;">[user_ip]</td>
                    <td>Show current user's detected IP Address</td>
                  </tr>
                  <tr>
                    <td style="direction: ltr;"><?= home_url("/?continue_learning"); ?></td>
                    <td>Continue LearnDash Last Course</td>
                  </tr>
                  <tr>
                    <td style="direction: ltr;"><?= home_url("/checkout/?add-to-cart=1912&coupon=dev100<"); ?></td>
                    <td>Add Product 1912 to cart, apply dev100 coupon, and redirect to checkbox</td>
                  </tr>
                  <tr>
                    <td style="direction: ltr;">[custom-thankyou order_id=6464]</td>
                    <td>Remove 'order_id' to automatically get from current order</td>
                  </tr>
                  <tr>
                    <td style="direction: ltr;">Host Cron Job, Required for Scheduled Notification</td>
                    <td style="direction: ltr;">define('DISABLE_WP_CRON', true);<br>* * * * * wget --delete-after <?= home_url("/wp-cron.php?doing_wp_cron"); ?> >/dev/null 2>&1<br>/usr/local/bin/php /home/public_html/wp-cron.php?doing_wp_cron >/dev/null 2>&1</td>
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
                    <td>Readmore.js</td>
                    <td><a href="https://github.com/jedfoster/Readmore.js" target="_blank">https://github.com/jedfoster/Readmore.js</a></td>
                  </tr>
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
              <button id="submit" class="button button-primary button-hero">
                <span style="margin: 12px 8px;" class="dashicons dashicons-yes-alt"></span>&nbsp;
                <?= __("Save setting", $this->td); ?>
              </button>
            </div>
        </form>
      </div>
      <!-- region tab switch script and js-repeater -->
        <script>
          (function($) {
            $(document).ready(function() {

              var _pepro_ajax_request = null;
              var $success_color = "rgba(21, 139, 2, 0.8)";
              var $error_color = "rgba(139, 2, 2, 0.8)";
              var $info_color = "rgba(2, 133, 139, 0.8)";
              if (!$("toast").length) {
                $(document.body).append($("<toast>"));
              }

              $("input.wpColorPicker").wpColorPicker();

              // initiate repeater
              var $repeater = $(".repeater.translation-panel").repeater({
                hide: function(deleteElement) {
                  $(this).remove();
                  build_translation_data(".repeater.translation-panel", "#gettext_replace");
                },
              });
              var $str_replace = $(".repeater.str_replace-panel").repeater({
                hide: function(deleteElement) {
                  $(this).remove();
                  build_translation_data(".repeater.str_replace-panel", "#str_replace");
                },
              });

              // load repeater prev-data
              var json = $("#gettext_replace").val();
              try {
                var obj = JSON.parse(json);
                var list = new Array();
                if (obj.gettext) {
                  $.each(obj.gettext, function(i, x) {
                    list.push(x);
                  });
                  $repeater.setList(list);
                }
              } catch (e) {
                console.info("could not load translations repeater data");
              }

              // load repeater prev-data
              var json = $("#str_replace").val();
              try {
                var obj = JSON.parse(json);
                var list = new Array();
                if (obj.gettext) {
                  $.each(obj.gettext, function(i, x) {
                    list.push(x);
                  });
                  $str_replace.setList(list);
                }
              } catch (e) {
                console.info("could not load str_replace repeater data");
              }

              // update repeater data
              $(document).on("change keyup", ".repeater.translation-panel input", function(e) {
                e.preventDefault();
                build_translation_data(".repeater.translation-panel", "#gettext_replace");
              });
              $(document).on("change keyup", ".repeater.str_replace-panel input", function(e) {
                e.preventDefault();
                build_translation_data(".repeater.str_replace-panel", "#str_replace");
              });

              // Sortable ui for translation panel
              if ($('.repeater.translation-panel table.wp-list-table tbody tr').length > 2) {
                $('.repeater.translation-panel table.wp-list-table').sortable({
                  items: 'tr',
                  cursor: 'move',
                  axis: 'y',
                  scrollSensitivity: 40,
                  update: function(event, ui) {
                    build_translation_data(".repeater.translation-panel", "#gettext_replace");
                  },
                  /* handle: 'td.wc-shipping-zone-method-sort', */
                });
              }
              if ($('.repeater.str_replace-panel table.wp-list-table tbody tr').length > 2) {
                $('.repeater.str_replace-panel table.wp-list-table').sortable({
                  items: 'tr',
                  cursor: 'move',
                  axis: 'y',
                  scrollSensitivity: 40,
                  update: function(event, ui) {
                    build_translation_data(".repeater.str_replace-panel", "#str_replace");
                  },
                  /* handle: 'td.wc-shipping-zone-method-sort', */
                });
              }

              // make json data
              function build_translation_data(container = ".repeater.translation-panel", data_inp = "#gettext_replace") {
                console.log(`build_translation_data ${container} ~> ${data_inp}`);
                try {
                  var gettext = {
                    "gettext": []
                  };
                  $(`${container} table tr[data-repeater-item]`).each(function(i, x) {
                    var item = {};
                    $(this).find("[data-slug]").each(function(indexInArray, valueOfElement) {
                      let el = $(valueOfElement);
                      slug = el.attr("data-slug");
                      switch (el.attr("type")) {
                        case "checkbox":
                          val = el.prop("checked") ? "yes" : false;
                          break;
                        case "checkbox":
                          val = el.prop("checked") ? "yes" : false;
                          break;
                        default:
                          val = el.val();
                      }
                      item[slug] = val;
                    });
                    gettext["gettext"][i] = item;
                  });
                  var jsonData = JSON.stringify(gettext);
                  $(data_inp).val(jsonData).trigger("change");
                } catch (e) {}
              }

              $(document).on("click tap", "a.nav-tab", function(e) {
                e.preventDefault();
                var me = $(this);
                $(".nav-tab.nav-tab-active").removeClass("nav-tab-active");
                me.addClass("nav-tab-active");
                $(".tab-content.tab-active").removeClass("tab-active");
                $(`.tab-content[data-tab=${me.data("tab")}]`).addClass("tab-active");
                window.location.hash = me.data("tab");
                localStorage.setItem("pigdev-lms", me.data("tab"));
              });

              function reload_last_active_tab() {
                if (window.location.hash && "" !== window.location.hash) {
                  $(".nav-tab[data-tab=" + window.location.hash.replace("#", "") + "]").trigger("click");
                } else {
                  last = localStorage.getItem("pigdev-lms");
                  if (last && "" != last) {
                    $(".nav-tab[data-tab=" + last.replace("#", "") + "]").trigger("click");
                  }
                }
              }

              reload_last_active_tab();
              setTimeout(reload_last_active_tab, 100);
              setTimeout(reload_last_active_tab, 500);
              setTimeout(reload_last_active_tab, 1000);

              function show_toast(data = "Sample Toast!", bg = "", delay = 3000) {
                if (!$("toast").length) {
                  $(document.body).append($("<toast>"));
                } else {
                  $("toast").removeClass("active");
                }
                setTimeout(function() {
                  $("toast").css("--toast-bg", bg).html(data).stop().addClass("active").delay(delay).queue(function() {
                    $(this).removeClass("active").dequeue().off("click tap");
                  }).on("click tap", function(e) {
                    e.preventDefault();
                    $(this).stop().removeClass("active");
                  });
                }, 200);
              }
            });
          })(jQuery);
        </script>
      <?php
      $html = ob_get_contents();
      ob_end_clean();
      print $html;
    }
    #endregion
    #region settings-option functions >>>>>>>>>>>>>
    private function print_setting_input($data) {
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
    private function print_setting_checkbox($data) {
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
    private function print_setting_select($data) {
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
    private function print_setting_textarea($data) {
      extract(wp_parse_args($data, array(
        "slug"        => "",
        "caption"     => "",
        "style"     => "",
        "desc"        => "",
        "rows"        => "5",
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
    private function print_setting_editor($data) {
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
    #region display-user, sanitize-number, get user ip >>>>>>>>>>>>>
    public function display_user($uid = 0, $link = false, $id = true) {
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
          return "$user_info->first_name $user_info->last_name";
        }
      } else {
        return sprintf(__("ID #%s [deleted-user]", $this->td), $uid);
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
    public static function str_starts_with(string $haystack, string $needle): bool {
      return 0 === strncmp($haystack, $needle, \strlen($needle));
    }
    public static function str_ends_with(string $haystack, string $needle): bool {
      return '' === $needle || ('' !== $haystack && 0 === substr_compare($haystack, $needle, -\strlen($needle)));
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