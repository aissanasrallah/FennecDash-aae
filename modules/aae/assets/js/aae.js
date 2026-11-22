/**
 * Algerie Auto Export (AAE) module scripts
 * Pricing calculator, inline stock updates and invoice loading.
 */
(function ($) {
  "use strict";

  var settings = window.aaeSettings || {};
  var rates = {
    markup: parseFloat(settings.eurMarkup) || 0,
    dzd: parseFloat(settings.dzdRate) || 282,
  };

  function toNumber(value) {
    var number = parseFloat(String(value).replace(",", "."));

    return isNaN(number) ? 0 : number;
  }

  function formatMoney(value) {
    var number = parseFloat(value);

    if (isNaN(number)) {
      number = 0;
    }

    return (
      "$" +
      number.toLocaleString("en-US", {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
      })
    );
  }

  function setValue(selector, value, decimals) {
    var $field = $(selector);

    if (!$field.length) {
      return;
    }

    $field.val(parseFloat(value).toFixed(decimals));
  }

  /* ------------------------------------------------------------------
     Car form: live pricing
     ------------------------------------------------------------------ */

  function initPricingForm() {
    var $form = $("#aae-car-form");

    if (!$form.length) {
      return;
    }

    var $auto = $("#aae-auto-calc");
    var syncing = false;

    function isAuto() {
      return $auto.length && $auto.is(":checked");
    }

    function commission() {
      return toNumber($("#commission").val());
    }

    function dzdRate() {
      return rates.dzd > 0 ? rates.dzd : 282;
    }

    function updateTotalEur() {
      var total =
        toNumber($("#fob_price").val()) +
        toNumber($("#doc_fee").val()) +
        toNumber($("#vehicle_fee").val()) +
        toNumber($("#delivery_fee").val());

      setValue("#total_usd", total, 2);

      return total;
    }

    function cascadeFromTtc(ttc) {
      var dzd = ttc * dzdRate();
      setValue("#price_dzd", dzd, 2);
      setValue("#price_millions", dzd / 10000, 3);
    }

    function recalculateAll() {
      syncing = true;

      var totalEur = updateTotalEur();
      var priceEur = totalEur * (1 + rates.markup / 100);
      var ttc = priceEur + commission();

      setValue("#price_eur", priceEur, 2);
      setValue("#total_ttc", ttc, 2);
      cascadeFromTtc(ttc);

      syncing = false;
    }

    // Base inputs always refresh the EUR total; derived prices follow when auto is on.
    $form.on("input change", ".aae-price-base", function () {
      if (syncing) {
        return;
      }

      if (isAuto()) {
        recalculateAll();
      } else {
        updateTotalEur();
      }
    });

    // Editing a derived field recalculates the fields below it.
    $form.on("input", "#price_eur", function () {
      if (syncing || !isAuto()) {
        return;
      }

      syncing = true;

      var ttc = toNumber($(this).val()) + commission();
      setValue("#total_ttc", ttc, 2);
      cascadeFromTtc(ttc);

      syncing = false;
    });

    $form.on("input", "#total_ttc", function () {
      if (syncing || !isAuto()) {
        return;
      }

      syncing = true;
      cascadeFromTtc(toNumber($(this).val()));
      syncing = false;
    });

    $form.on("input", "#price_dzd", function () {
      if (syncing || !isAuto()) {
        return;
      }

      syncing = true;
      setValue("#price_millions", toNumber($(this).val()) / 10000, 3);
      syncing = false;
    });

    $form.on("input", "#price_millions", function () {
      if (syncing || !isAuto()) {
        return;
      }

      syncing = true;
      setValue("#price_dzd", toNumber($(this).val()) * 10000, 2);
      syncing = false;
    });

    $("#aae-recalculate").on("click", function (event) {
      event.preventDefault();

      $auto.prop("checked", true);
      recalculateAll();
    });

    $auto.on("change", function () {
      if ($(this).is(":checked")) {
        recalculateAll();
      }
    });

    // New vehicles start with the auto calculator enabled.
    if ($form.data("is-new") === 1 || $form.attr("data-is-new") === "1") {
      if ($auto.is(":checked")) {
        recalculateAll();
      }
    }
  }

  /* ------------------------------------------------------------------
     Car form: invoices for the selected customer
     ------------------------------------------------------------------ */

  function initInvoiceLoader() {
    var $client = $("#aae-client");
    var $invoice = $("#aae-invoice");

    if (!$client.length || !$invoice.length) {
      return;
    }

    var url = (settings.urls && settings.urls.invoicesForClient) || "";
    var previousClient = $client.val();

    $client.on("change", function () {
      var clientId = $(this).val();

      if (!url) {
        return;
      }

      if (!clientId) {
        $invoice.html(
          '<option value="">' + invoiceEmptyLabel + "</option>"
        );
        previousClient = "";
        return;
      }

      if (clientId === previousClient) {
        return;
      }

      previousClient = clientId;
      $invoice.prop("disabled", true);

      $.ajax({
        url: url + encodeURIComponent(clientId),
        type: "GET",
        dataType: "json",
        cache: false,
      })
        .done(function (response) {
          var html = '<option value="">' + invoiceEmptyLabel + "</option>";

          if (response && response.success) {
            $.each(response.invoices || [], function (index, invoice) {
              html +=
                '<option value="' +
                invoice.id +
                '">' +
                $("<div>").text(invoice.label).html() +
                "</option>";
            });
          }

          $invoice.html(html);
        })
        .fail(function () {
          $invoice.html(
            '<option value="">' + invoiceEmptyLabel + "</option>"
          );

          if (typeof alert_float === "function") {
            alert_float("danger", settings.lang.error || "Request failed");
          }
        })
        .always(function () {
          $invoice.prop("disabled", false);

          if ($invoice.hasClass("selectpicker")) {
            $invoice.selectpicker("refresh");
          }
        });
    });
  }

  /* ------------------------------------------------------------------
     Stock table: inline status and quantity updates
     ------------------------------------------------------------------ */

  function updateStats(stats) {
    if (!stats) {
      return;
    }

    $.each(stats, function (key, value) {
      var $target = $('[data-aae-stat="' + key + '"]');

      if (!$target.length) {
        return;
      }

      if (key.indexOf("value_") !== -1 || key === "stock_value_usd" || key === "stock_value_eur") {
        $target.text(formatMoney(value));
      } else {
        $target.text(value);
      }
    });
  }

  function flash($element, state) {
    $element
      .removeClass("aae-saving aae-saved aae-error")
      .addClass("aae-" + state);

    if (state !== "saving") {
      window.setTimeout(function () {
        $element.removeClass("aae-saved aae-error");
      }, 1800);
    }
  }

  function requestFailed(message) {
    if (typeof alert_float === "function") {
      alert_float("danger", message || settings.lang.error || "Request failed");
    }
  }

  function initStockTable() {
    var urls = settings.urls || {};

    $(document).on("change", ".aae-status-select", function () {
      var $select = $(this);
      var id = $select.data("id");
      var status = $select.val();
      var previous = $select.data("value");

      if (!id || !urls.updateStatus) {
        return;
      }

      flash($select, "saving");

      $.post(urls.updateStatus, { id: id, status: status })
        .done(function (response) {
          if (response && response.success) {
            $select
              .data("value", status)
              .removeClass(function (index, className) {
                return (className.match(/(^|\s)aae-status-\S+/g) || []).join(" ");
              })
              .addClass("aae-status-" + status);

            flash($select, "saved");
            updateStats(response.stats);
          } else {
            $select.val(previous);
            flash($select, "error");
            requestFailed(response ? response.message : null);
          }
        })
        .fail(function () {
          $select.val(previous);
          flash($select, "error");
          requestFailed();
        });
    });

    $(document).on("change", ".aae-qty-input", function () {
      var $input = $(this);
      var id = $input.data("id");
      var value = String($input.val()).trim();
      var previous = $input.data("value");
      var quantity = parseInt(value, 10);

      if (!id || !urls.updateQuantity) {
        return;
      }

      if (value === "" || isNaN(quantity) || quantity < 0) {
        $input.val(previous);
        flash($input, "error");

        return;
      }

      flash($input, "saving");

      $.post(urls.updateQuantity, { id: id, quantity: quantity })
        .done(function (response) {
          if (response && response.success) {
            $input.data("value", quantity).val(quantity);

            flash($input, "saved");
            updateStats(response.stats);
          } else {
            $input.val(previous);
            flash($input, "error");
            requestFailed(response ? response.message : null);
          }
        })
        .fail(function () {
          $input.val(previous);
          flash($input, "error");
          requestFailed();
        });
    });
  }

  /* ------------------------------------------------------------------
     Brand → model suggestions
     ------------------------------------------------------------------ */

  function normalizeKey(value) {
    return String(value || "").trim().toLowerCase();
  }

  function initBrandModels() {
    var $brand = $("#brand");
    var $model = $("#model");
    var $list = $("#aae-models");
    var map = window.aaeModelsByBrand || {};

    if (!$brand.length || !$model.length || !$list.length) {
      return;
    }

    // Case-insensitive brand index, because imported data is not normalised.
    var index = {};

    Object.keys(map).forEach(function (brandName) {
      var key = normalizeKey(brandName);

      if (!index[key]) {
        index[key] = [];
      }

      (map[brandName] || []).forEach(function (modelName) {
        var exists = index[key].some(function (existing) {
          return normalizeKey(existing) === normalizeKey(modelName);
        });

        if (!exists) {
          index[key].push(modelName);
        }
      });
    });

    function refresh() {
      var models = index[normalizeKey($brand.val())] || [];

      $list.empty();

      models.forEach(function (modelName) {
        $("<option>").attr("value", modelName).appendTo($list);
      });
    }

    $brand.on("input change", refresh);
    refresh();
  }

  /* ------------------------------------------------------------------
     Stock filters: models follow the selected brand
     ------------------------------------------------------------------ */

  function initStockModelFilter() {
    var $brand = $("#aae-filter-brand");
    var $model = $("#aae-filter-model");
    var map = window.aaeModelsByBrand || {};

    if (!$brand.length || !$model.length) {
      return;
    }

    var index = {};

    Object.keys(map).forEach(function (brandName) {
      var key = normalizeKey(brandName);

      if (!index[key]) {
        index[key] = [];
      }

      (map[brandName] || []).forEach(function (modelName) {
        var exists = index[key].some(function (existing) {
          return normalizeKey(existing) === normalizeKey(modelName);
        });

        if (!exists) {
          index[key].push(modelName);
        }
      });
    });

    var brandPlaceholder =
      $model.data("placeholder-brand") || "Select a brand first";
    var allModelsLabel = $model.data("placeholder-all") || "All models";

    function refresh(reset) {
      var key = normalizeKey($brand.val());
      var models = index[key] || [];

      $model.empty();

      $("<option>")
        .attr("value", "")
        .text(key ? allModelsLabel : brandPlaceholder)
        .appendTo($model);

      models.forEach(function (modelName) {
        $("<option>").attr("value", modelName).text(modelName).appendTo($model);
      });

      $model.prop("disabled", !key);

      if (reset) {
        $model.val("");
      }
    }

    $brand.on("change", function () {
      refresh(true);
    });

    refresh(false);
  }

  /* ------------------------------------------------------------------
     WhatsApp Business API: test connection button handler
     ------------------------------------------------------------------ */

  function aaeTestWhatsApp() {
    var $btn = $("#aae-whatsapp-test-btn");
    var original = $btn.html();

    if (!$btn.length) {
      // Fallback: find the button by text
      $btn = $("button").filter(function () {
        return $(this).text().trim().indexOf("Test") !== -1;
      });
    }

    $btn.prop("disabled", true).html('<i class="fa-solid fa-spinner fa-spin tw-mr-1"></i> Testing...');

    $.ajax({
      url: (settings.urls && settings.urls.whatsappTest) || admin_url('aae/whatsapp/test_connection'),
      type: "POST",
      dataType: "json",
      headers: {
        "X-Requested-With": "XMLHttpRequest",
      },
    })
      .done(function (response) {
        var message = response.message || (response.success ? "Connection successful" : "Connection failed");

        if (!response.success && response.error) {
          message += "\n\n" + response.error;
        }

        if (typeof alert_float === "function") {
          alert_float(response.success ? "success" : "danger", message);
        } else {
          alert(message);
        }
      })
      .fail(function () {
        if (typeof alert_float === "function") {
          alert_float("danger", "Request failed");
        } else {
          alert("Request failed");
        }
      })
      .always(function () {
        $btn.prop("disabled", false).html(original);
      });
  }

  /* ------------------------------------------------------------------
     Export order Messages tab: templates, WhatsApp/e-mail links, copy
     ------------------------------------------------------------------ */

  function initOrderMessages() {
    var data = window.aaeOrderMessages;

    if (!data || !$("#aae_msg_stage").length) {
      return;
    }

    var $stage = $("#aae_msg_stage");
    var $lang = $("#aae_msg_lang");
    var $body = $("#aae_msg_body");
    var $channel = $("#aae_msg_channel");
    var $wa = $("#aae_msg_wa");
    var $email = $("#aae_msg_email");
    var $copy = $("#aae_msg_copy");

    function render(template) {
      var message = String(template || "").replace(/\{([a-z_]+)\}/gi, function (match, key) {
        return Object.prototype.hasOwnProperty.call(data.context, key) ? data.context[key] : match;
      });

      // Always include the order link, even when the template itself does
      // not reference {order_link}.
      var link = data.context.order_link || "";

      if (link && message.indexOf(link) === -1) {
        message = message.replace(/\s+$/, "") + "\n\n" + link;
      }

      return message;
    }

    function currentTemplate() {
      var stage = $stage.val();
      var lang = $lang.val();

      if (!data.templates[stage] || !data.templates[stage][lang]) {
        return "";
      }

      return data.templates[stage][lang];
    }

    function refreshLinks() {
      var message = $body.val();

      $wa.attr(
        "href",
        data.phone
          ? "https://wa.me/" + data.phone + "?text=" + encodeURIComponent(message)
          : "#"
      );

      $email.attr(
        "href",
        data.email
          ? "mailto:" +
              data.email +
              "?subject=" +
              encodeURIComponent("Export #" + data.context.order) +
              "&body=" +
              encodeURIComponent(message)
          : "#"
      );
    }

    function refreshMessage() {
      $body.val(render(currentTemplate()));
      refreshLinks();
    }

    $stage.add($lang).on("change", refreshMessage);
    $body.on("input", refreshLinks);

    $wa.on("click", function (event) {
      if (!data.phone) {
        event.preventDefault();
        if (typeof alert_float === "function") {
          alert_float(
            "warning",
            (settings.lang && settings.lang.noPhone) ||
              "No phone number is available for this customer."
          );
        }
        return;
      }

      event.preventDefault();
      $channel.val("whatsapp");
      // Auto-submit form to log the message
      $("#aae_message_form").submit();
    });

    $email.on("click", function (event) {
      event.preventDefault();
      $channel.val("email");
      // Auto-submit form to log the message
      $("#aae_message_form").submit();
    });

    var $api = $("#aae_msg_wa_api");

    $api.on("click", function () {
      var $button = $(this);
      var original = $button.html();
      var message = $body.val();

      if (!message || !message.trim()) {
        if (typeof alert_float === "function") {
          alert_float("warning", (settings.lang && settings.lang.emptyMessage) || "The message is empty.");
        }
        return;
      }

      $button
        .prop("disabled", true)
        .html('<i class="fa-solid fa-spinner fa-spin tw-mr-1"></i>');

      $.ajax({
        url:
          ((settings.urls && settings.urls.whatsappSendApi) ||
            admin_url("aae/aae_order/send_whatsapp_api/")) + data.order,
        type: "POST",
        dataType: "json",
        data: {
          message: message,
          stage: $stage.val(),
          lang: $lang.val(),
        },
        headers: {
          "X-Requested-With": "XMLHttpRequest",
        },
      })
        .done(function (response) {
          var text = response.message || (response.success ? "" : data.sendFailed);

          if (!response.success && response.error) {
            text += "\n\n" + response.error;
          }

          if (typeof alert_float === "function") {
            alert_float(response.success ? "success" : "danger", text);
          } else {
            alert(text);
          }

          if (response.success) {
            $channel.val("whatsapp");
            // Auto-submit form to log the message
            $("#aae_message_form").submit();
          }
        })
        .fail(function (xhr) {
          var text = data.sendFailed || "WhatsApp could not send the message.";
          text += " (" + xhr.status + ")";

          if (typeof alert_float === "function") {
            alert_float("danger", text);
          } else {
            alert(text);
          }
        })
        .always(function () {
          $button.prop("disabled", false).html(original);
        });
    });

    $copy.on("click", function () {
      var $button = $(this);
      var message = $body.val();

      $channel.val("copy");

      var done = function () {
        $button.html('<i class="fa fa-check tw-mr-1"></i>' + data.copiedLabel);
        setTimeout(function () {
          $button.html('<i class="fa fa-copy tw-mr-1"></i>' + data.copyLabel);
        }, 1500);
      };

      if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(message).then(done);
      } else {
        var textarea = document.createElement("textarea");
        textarea.value = message;
        document.body.appendChild(textarea);
        textarea.select();
        document.execCommand("copy");
        document.body.removeChild(textarea);
        done();
      }
    });

    refreshMessage();
  }

  /* ------------------------------------------------------------------
     Order view: route the "record payment" modal to the chosen invoice
     ------------------------------------------------------------------ */

  function initInvoicePaymentModal() {
    if (!$("#aae_invoice_payment_target").length) {
      return;
    }

    $(document).on("click", "[data-invoice-target]", function () {
      $("#aae_invoice_payment_target").val($(this).data("invoice-target"));
      $("#aae_invoice_payment_number").text($(this).data("invoice-number"));
    });
  }

  /* ------------------------------------------------------------------
     Order view: send the estimate via WhatsApp and mark it as sent
     ------------------------------------------------------------------ */

  function initEstimateWhatsApp() {
    var $button = $("#aae_estimate_wa");
    var form = document.getElementById("aae_estimate_sent_form");

    if (!$button.length || !form) {
      return;
    }

    $button.on("click", function () {
      // Let the browser open WhatsApp in the new tab, then record the
      // delivery in the dossier (same behaviour as "Save & Send").
      setTimeout(function () {
        form.submit();
      }, 150);
    });
  }

  /* ------------------------------------------------------------------
     Boot
     ------------------------------------------------------------------ */

  var invoiceEmptyLabel = (settings.lang && settings.lang.noInvoice) || "-";

  /**
   * The admin layout exposes the CSRF token as `csrfData` (see
   * csrf_jquery_token()). Plain HTML form posts do not get it automatically,
   * so add a hidden field to every form rendered in the module area.
   */
  function injectCsrfTokens() {
    if (typeof csrfData === "undefined" || !csrfData.token_name) {
      return;
    }

    $("form").each(function () {
      var $form = $(this);

      if ($form.find('input[name="' + csrfData.token_name + '"]').length === 0) {
        $("<input>", {
          type: "hidden",
          name: csrfData.token_name,
          value: csrfData.hash,
        }).appendTo($form);
      }
    });
  }

  /* ------------------------------------------------------------------
     Export order form: prefill amounts from the selected vehicle
     ------------------------------------------------------------------ */

  function initOrderVehicleAutofill() {
    var $vehicle = $("#vehicle_id");

    if (!$vehicle.length) {
      return;
    }

    function applyVehiclePrices() {
      var $option = $vehicle.find("option:selected");
      var client = $option.attr("data-price-client");
      var supplier = $option.attr("data-price-supplier");

      if (typeof client !== "undefined" && client !== "") {
        $("#amount_client").val(client);
      }

      if (typeof supplier !== "undefined" && supplier !== "") {
        $("#amount_supplier").val(supplier);
      }
    }

    $vehicle.on("change", applyVehiclePrices);
    $vehicle.on("changed.bs.select", applyVehiclePrices);

    if ($vehicle.val()) {
      applyVehiclePrices();
    }
  }

  $(function () {
    injectCsrfTokens();
    initPricingForm();
    initInvoiceLoader();
    initStockTable();
    initBrandModels();
    initStockModelFilter();
    initOrderVehicleAutofill();
    initOrderMessages();
    initInvoicePaymentModal();
    initEstimateWhatsApp();

    // WhatsApp test connection button
    $("#aae-whatsapp-test-btn").on("click", aaeTestWhatsApp);
  });
})(jQuery);
