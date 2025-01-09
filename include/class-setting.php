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
                      __('Message %1$s on Telegram to register your bot and get the token.%3$sEnter the token, click “%2$s” and save if successful.', $this->td),
                      "<a href='https://t.me/botfather' target='_blank'>@BotFather</a>",
                      "<a href='#' class='button button-link validate_token'>" . __("Validate", $this->td) . "</a>",
                      "<br>",
                    ),
                  ]);
                  /* translators: 1: validate token link, 2: line break <br> */
                  $this->print_setting_input([
                    "slug" => "username",
                    "extra_html" => "readonly",
                    "caption" => esc_html__("Your bot username", $this->td),
                    "desc" => sprintf(
                      __('Click %1$s to fetch bot details using the provided token;%2$sthe bot username will be auto-filled.', $this->td),
                      "<a href='#' class='button button-link validate_token'>" . __("Validate Token", $this->td) . "</a>",
                      "<br>",
                    ),
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
      $this->add_js_inline();
      $html = ob_get_contents();
      ob_end_clean();
      print $html;
    }
    private function add_js_inline(){
      ?>
      <script>
        (function($) {
          $(document).ready(function() {

            var _request = null;
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
              // console.log(`build_translation_data ${container} ~> ${data_inp}`);
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

            $(document).on("click tap", ".validate_token", function(e) {
              e.preventDefault();
              if (_request != null) { _request.abort(); }
              show_toast("<?=__("Please wait ...",$this->td);?>", $info_color);
              _request = $.ajax({
                type: "POST",
                dataType: "json",
                url: "<?= admin_url("admin-ajax.php"); ?>",
                data: {
                  action: "<?= $this->td; ?>",
                  nonce: "<?= wp_create_nonce($this->td); ?>",
                  wparam: "connect",
                  lparam: $("#token").val(),
                },
                success: function(e) {
                  if (e.success === true) {
                    show_toast(e.data.msg, $success_color);
                  } else {
                    show_toast(e.data.msg, $error_color);
                  }
                },
                error: function(e) {
                  show_toast("<?=__("Unknown Error Occured!",$this->td);?>", $error_color);
                  console.error(e);
                },
                complete: function(e) { },
              });
            });
            $(document).on("click tap", ".send_test", function(e) {
              e.preventDefault();
              if (_request != null) { _request.abort(); }
              show_toast("<?=__("Please wait ...",$this->td);?>", $info_color);
              _request = $.ajax({
                type: "POST",
                dataType: "json",
                url: "<?= admin_url("admin-ajax.php"); ?>",
                data: { action: "<?= $this->td; ?>", nonce: "<?= wp_create_nonce($this->td); ?>", wparam: "send_test", },
                success: function(e) { if (e.success === true) { show_toast(e.data.msg, $success_color); } else { show_toast(e.data.msg, $error_color); } },
                error: function(e) { show_toast("<?=__("Unknown Error Occured!",$this->td);?>", $error_color); console.error(e); },
                complete: function(e) { },
              });
            });
            $(document).on("click tap", ".connect", function(e) {
              e.preventDefault();
              if (_request != null) { _request.abort(); }
              show_toast("<?=__("Please wait ...",$this->td);?>", $info_color);
              _request = $.ajax({
                type: "POST",
                dataType: "json",
                url: "<?= admin_url("admin-ajax.php"); ?>",
                data: { action: "<?= $this->td; ?>", nonce: "<?= wp_create_nonce($this->td); ?>", wparam: "connect", },
                success: function(e) { if (e.success === true) { show_toast(e.data.msg, $success_color); } else { show_toast(e.data.msg, $error_color); } },
                error: function(e) { show_toast("<?=__("Unknown Error Occured!",$this->td);?>", $error_color); console.error(e); },
                complete: function(e) { },
              });
            });
            $(document).on("click tap", ".disconnect", function(e) {
              e.preventDefault();
              if (_request != null) { _request.abort(); }
              show_toast("<?=__("Please wait ...",$this->td);?>", $info_color);
              _request = $.ajax({
                type: "POST",
                dataType: "json",
                url: "<?= admin_url("admin-ajax.php"); ?>",
                data: { action: "<?= $this->td; ?>", nonce: "<?= wp_create_nonce($this->td); ?>", wparam: "disconnect", },
                success: function(e) { if (e.success === true) { show_toast(e.data.msg, $success_color); } else { show_toast(e.data.msg, $error_color); } },
                error: function(e) { show_toast("<?=__("Unknown Error Occured!",$this->td);?>", $error_color); console.error(e); },
                complete: function(e) { },
              });
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