/*
 * @Author: Amirhossein Hosseinpour <https://amirhp.com>
 * @Last modified by: amirhp-com <its@amirhp.com>
 * @Last modified time: 2025/01/17 04:07:11
 */

(function ($) {
  var _request = null;
  const $success_color = "rgba(21, 139, 2, 0.8)", $error_color = "rgba(139, 2, 2, 0.8)", $info_color = "rgba(2, 133, 139, 0.8)";
  $(document).ready(function () {

    if (!$("toast").length) { $(document.body).append($("<toast>")); }
    $("input.wpColorPicker").wpColorPicker();
    reload_last_active_tab();
    setTimeout(reload_last_active_tab, 100);
    setTimeout(reload_last_active_tab, 500);
    setTimeout(reload_last_active_tab, 1000);

    const workplace = $("table.workspace-wrapper-parent>tbody.workplace");

    // load json panel
    if (_panel.notif_json && "" != $.trim(_panel.notif_json)) {
      try {
        var saved_json = JSON.parse(_panel.notif_json);
        $.each(saved_json, function (i, x) {
          var slug = x.type, configuration = x.config;
          if (slug && "" != slug && $(`select#notif_types option[value="${slug}"]`).length) {
            var title = $(`select#notif_types option[value="${slug}"]`);
            var category = $(title).prevAll("optgroup").first().attr("label");
            var new_container = $(".template-wrapper #sample_setting_row_wrapper").html().trim();
            var row_setting = $(`.template-wrapper #${slug}`).length ? $(`.template-wrapper #${slug}`).html().trim() : "";
            new_container = new_container.replace("{slug}", slug);
            new_container = new_container.replace("{category}", category);
            new_container = new_container.replace("{row_details}", row_setting);
            new_container = new_container.replace("{title}", title.text());
            var new_added = $(new_container).appendTo(workplace);
            if (configuration && typeof configuration == "object") {
              $.each(configuration, function (name, value) {
                let el = new_added.find(`[data-slug=${name}]`);
                if (el.length) {
                  switch (el.attr("type")) {
                    case "checkbox":
                      el.prop("checked", value == "yes");
                      break;
                    default:
                      el.val(value).trigger("change");
                  }
                }
              });
            }
          }else{
            console.error(slug, `select#notif_types option[value="${slug}"]`, "not found");
          }
        });
      } catch (e) {
        console.error(e);
        show_toast(_panel.unknown,$error_color);
      }
    }

    // initiate repeater
    var $repeater = $(".repeater.translation-panel").repeater({ hide: function(deleteElement) { $(this).remove(); build_translation_data(".repeater.translation-panel", "#gettext_replace"); }, });
    var $str_replace = $(".repeater.str_replace-panel").repeater({ hide: function(deleteElement) { $(this).remove(); build_translation_data(".repeater.str_replace-panel", "#str_replace"); }, });

    // load repeater prev-data
    var json = $("#gettext_replace").val();
    try {
      var obj = JSON.parse(json);
      var list = new Array();
      if (obj.gettext) {
        $.each(obj.gettext, function(i, x) { list.push(x); });
        $repeater.setList(list);
      }
    } catch (e) { console.info("could not load translations repeater data"); }

    // load repeater prev-data
    var json = $("#str_replace").val();
    try {
      var obj = JSON.parse(json);
      var list = new Array();
      if (obj.gettext) {
        $.each(obj.gettext, function(i, x) { list.push(x); });
        $str_replace.setList(list);
      }
    } catch (e) { console.info("could not load str_replace repeater data"); }

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
        items: 'tr', cursor: 'move', axis: 'y', scrollSensitivity: 40,
        update: function(event, ui) { build_translation_data(".repeater.translation-panel", "#gettext_replace"); },
        /* handle: 'td.wc-shipping-zone-method-sort', */
      });
    }
    if ($('.repeater.str_replace-panel table.wp-list-table tbody tr').length > 2) {
      $('.repeater.str_replace-panel table.wp-list-table').sortable({
        items: 'tr', cursor: 'move', axis: 'y', scrollSensitivity: 40,
        update: function(event, ui) { build_translation_data(".repeater.str_replace-panel", "#str_replace"); },
        /* handle: 'td.wc-shipping-zone-method-sort', */
      });
    }

    $(document).on("click tap", ".edit--entry", function(e){
      e.preventDefault(); var me = $(this);
      me.parents("th").find(".sub-setting").toggleClass("hide");
      me.parents("tr").toggleClass("highlight active-editing");
      me.parents("tr").find(".fa-cog").toggleClass("fa-spin");
    });
    $(document).on("click tap", "a.nav-tab", function(e) {
      e.preventDefault();
      var me = $(this);
      $(".nav-tab.nav-tab-active").removeClass("nav-tab-active");
      me.addClass("nav-tab-active");
      $(".tab-content.tab-active").removeClass("tab-active");
      $(`.tab-content[data-tab=${me.data("tab")}]`).addClass("tab-active");
      window.location.hash = me.data("tab");
      localStorage.setItem("bsdev-tg", me.data("tab"));
    });
    $(document).on("click tap", ".validate_token", function(e) {
      e.preventDefault();
      if (_request != null) { _request.abort(); }
      show_toast(_panel.wait, $info_color);
      _request = $.ajax({
        type: "POST",
        dataType: "json",
        url: _panel.ajax,
        data: {
          action: _panel.action,
          nonce: _panel.nonce,
          wparam: "test_token",
          lparam: $("#token").val(),
        },
        success: function(e) {
          if (e.success === true) { show_toast(e.data.msg, $success_color); }
          else { show_toast(e.data.msg, $error_color); }
        },
        error: function(e) { show_toast(_panel.unknown, $error_color); console.error(e); },
        complete: function(e) { },
      });
    });
    $(document).on("click tap", ".send_test", function(e) {
      e.preventDefault();
      if (_request != null) { _request.abort(); }
      show_toast(_panel.wait, $info_color);
      _request = $.ajax({
        type: "POST",
        dataType: "json",
        url: _panel.ajax,
        data: {
          action: _panel.action,
          nonce: _panel.nonce,
          wparam: "send_test",
        },
        success: function(e) {
          if (e.success === true) { show_toast(e.data.msg, $success_color); }
          else { show_toast(e.data.msg, $error_color); }
        },
        error: function(e) { show_toast(_panel.unknown, $error_color); console.error(e); },
        complete: function(e) { },
      });
    });
    $(document).on("click tap", ".connect", function(e) {
      e.preventDefault();
      if (_request != null) { _request.abort(); }
      show_toast(_panel.wait, $info_color);
      _request = $.ajax({
        type: "POST",
        dataType: "json",
        url: _panel.ajax,
        data: {
          action: _panel.action,
          nonce: _panel.nonce,
          wparam: "connect",
        },
        success: function(e) {
          if (e.success === true) { show_toast(e.data.msg, $success_color); }
          else { show_toast(e.data.msg, $error_color); }
        },
        error: function(e) { show_toast(_panel.unknown, $error_color); console.error(e); },
        complete: function(e) { },
      });
    });
    $(document).on("click tap", ".disconnect", function(e) {
      e.preventDefault();
      if (_request != null) { _request.abort(); }
      show_toast(_panel.wait, $info_color);
      _request = $.ajax({
        type: "POST",
        dataType: "json",
        url: _panel.ajax,
        data: {
          action: _panel.action,
          nonce: _panel.nonce,
          wparam: "disconnect",
        },
        success: function(e) {
          if (e.success === true) { show_toast(e.data.msg, $success_color); }
          else { show_toast(e.data.msg, $error_color); }
        },
        error: function(e) { show_toast(_panel.unknown, $error_color); console.error(e); },
        complete: function(e) { },
      });
    });
    $(document).on("click tap", ".validate_markdown", function(e){
      e.preventDefault();
      var me = $(this);
      var md = me.parents(".sub-setting").find("textarea[data-slug='message']").length ? me.parents(".sub-setting").find("textarea[data-slug='message']") : false;
      validateMarkdown(md)
    });
    $(document).on("click tap", ".add-new-notif", function(e){
      e.preventDefault();
      var me = $(this), slug = $("select#notif_types").val();
      if (slug == "" || !slug) {
        show_toast(_panel.error_slug_empty, $error_color);
        $("select#notif_types").focus();
        return false;
      }
      if (!$(`.template-wrapper template#${slug}`).length) {
        show_toast(_panel.error_option_empty, $error_color);
        $("select#notif_types").focus();
        return false;
      }
      var title = $(`select#notif_types option[value="${slug}"]`);
      var category = $(title).prevAll("optgroup").first().attr("label");
      var new_container = $(".template-wrapper #sample_setting_row_wrapper").html().trim();
      var row_setting = $(`.template-wrapper #${slug}`).length ? $(`.template-wrapper #${slug}`).html().trim() : "";
      new_container = new_container.replace("{slug}", slug);
      new_container = new_container.replace("{category}", category);
      new_container = new_container.replace("{row_details}", row_setting);
      new_container = new_container.replace("{title}", title.text());
      var new_added = $(new_container).appendTo(workplace);
      new_added.find("[data-slug='_enabled']").prop("checked", true);
      scroll_element(new_added, 100)
      $(new_added).addClass('highlight').delay(1000).queue(function(){$(this).removeClass('highlight').dequeue();});
      build_notification_data();
    });
    $(document).on("click tap", ".export-import-notif", function(e){
      e.preventDefault();
      $("tfoot tr.type-textarea.notifications.toggle-export-import").toggleClass("hide");
      workplace.toggleClass("hide");
    });
    $(document).on("click tap", ".delete--entry", function(e){
      e.preventDefault();
      var me = $(this);
      me.parents("tr").addClass("highlight");
      setTimeout(function () {
        if (confirm(_panel.delete_confirm)) {
          me.parents("tr").remove();
          build_notification_data();
        }else{
          me.parents("tr").removeClass("highlight");
        }
      }, 50);
    });
    $(document).on("click tap", ".clear-all-notif", function(e){
      e.preventDefault();
      var me = $(this);
      if (confirm(_panel.delete_all)) {
        workplace.empty();
        build_notification_data();
      }
    });
    $(document).on("click tap", "copy", function(e){
      e.preventDefault();
      var me = $(this);
      copy_clipboard(me.text())
      show_toast(_panel.copied.replace("%s", me.text()), $success_color);
    });
    $(document).on("click tap", ".copy-code", function(e){
      e.preventDefault();
      var me = $(this);
      copy_clipboard($(".data-textarea textarea#notifications").val());
      show_toast(_panel.code_copied, $success_color);
    });

    $(document).on("keyup change", "tbody.workplace>tr[data-type] input, tbody.workplace>tr[data-type] textarea, tbody.workplace>tr[data-type] select", function(e) {
      build_notification_data();
    });

    function validateMarkdown(message) {
      // Get the message input value
      if (!message) { show_toast("Markdown is empty!"); return false; }
      var message = message.val().trim();
      // Remove content inside macros before validation
      var message = message.replace(/{[^}]+}/g, "");

      // Define validation rules for Markdown
      var rules = [
        {
          entity: "Bold",
          regex: /\*\*.*?\*\*|__.*?__/g,
          example: "**bold**, __bold__"
        },
        {
          entity: "Italic",
          regex: /\*.*?\*|_.*?_/g,
          example: "*italic*, _italic_"
        },
        {
          entity: "Monospace",
          regex: /`[^`]+`/g,
          example: "`monospace`"
        },
        {
          entity: "Preformatted Block",
          regex: /```[\s\S]*?```/g,
          example: "```\ncode\n```"
        },
        {
          entity: "Underline",
          regex: /<u>.*?<\/u>/g,
          example: "<u>underline</u>"
        },
        {
          entity: "Strikethrough",
          regex: /~~.*?~~|<s>.*?<\/s>|<strike>.*?<\/strike>|<del>.*?<\/del>/g,
          example: "~~strikethrough~~"
        },
        {
          entity: "Spoiler",
          regex: /\|\|.*?\|\|/g,
          example: "||spoiler||"
        },
        {
          entity: "Quote",
          regex: /^> .*$/gm,
          example: "> quote"
        },
        {
          entity: "Link",
          regex: /\[.*?\]\(https?:\/\/.*?\)/g,
          example: "[link text](https://example.com)"
        }
      ];

      if ((message.match(/\*/g) || []).length % 2 !== 0) {
        show_toast(_panel.md.asterisk, $error_color);
        return false;
      }
      if ((message.match(/_/g) || []).length % 2 !== 0) {
        show_toast(_panel.md.underscore, $error_color);
        return false;
      }
      if ((message.match(/`/g) || []).length % 2 !== 0) {
        show_toast(_panel.md.backtick, $error_color);
        return false;
      }
      if ((message.match(/```/g) || []).length % 2 !== 0) {
        show_toast(_panel.md.triple_backticks, $error_color);
        return false;
      }

      // Validate the message against each rule
      rules.forEach(function (rule) {
        if (!rule.regex.test(message)) {
          show_toast(_panel.md.invalid.replace("{entity}", rule.entity).replace("{example}", rule.example), $error_color);
          return false;
        }
      });

      show_toast(_panel.md.valid, $success_color);
    }
    // make json data
    function build_translation_data(container = ".repeater.translation-panel", data_inp = "#gettext_replace") {
      // console.log(`build_translation_data ${container} ~> ${data_inp}`);
      try {
        var gettext = { "gettext": [] };
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
    function build_notification_data(e) {
      var data_inp = ".type-textarea.notifications.toggle-export-import textarea#notifications";
      try {
        var notif = [];
        $(`table.workspace-wrapper-parent>tbody.workplace>tr[data-type]`).each(function(i, x) {
          var type = $(this).attr("data-type"); var config = {};
          $(this).find("[data-slug]").each(function(indexInArray, valueOfElement) {
            var el = $(valueOfElement), slug = el.attr("data-slug"), val = el.val();
            switch (el.attr("type")) {
              case "checkbox":
                val = el.prop("checked") ? "yes" : "no";
                break;
              default:
                val = el.val();
            }
            config[slug] = val;
          });
          notif[i] = {"type":type, "config": config};
        });
        console.log(notif);
        var jsonData = JSON.stringify(notif, " ", 2);
        $(data_inp).val(jsonData).trigger("change");
      } catch (e) {
        console.error("could not generate notif data");
      }
    }
    function reload_last_active_tab() {
      if (window.location.hash && "" !== window.location.hash) {
        $(".nav-tab[data-tab=" + window.location.hash.replace("#", "") + "]").trigger("click");
      } else {
        last = localStorage.getItem("bsdev-tg");
        if (last && "" != last) { $(".nav-tab[data-tab=" + last.replace("#", "") + "]").trigger("click"); }
      }
    }
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
    function copy_clipboard(data) {
      var $temp = $("<textarea>");
      $("body").append($temp);
      $temp.val(data).select();
      document.execCommand("copy");
      $temp.remove();
    }
    function scroll_element(element, offset = 0) {
      $("html, body").animate({ scrollTop: element.offset().top - offset }, 500);
    }
  });
})(jQuery);