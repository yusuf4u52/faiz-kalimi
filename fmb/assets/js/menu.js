(($) => {
  "use strict";

  const ROTI_SIZE_FIELDS = { mini: "tqty", medium: "mqty", large: "lqty" };
  const rotiSizeField = (size) =>
    ROTI_SIZE_FIELDS[
      String(size || "")
        .trim()
        .toLowerCase()
    ] || "sqty";
  const ratingSlug = (rating) => rating?.toLowerCase().replace(" ", "-");

  // ---------------------------------------------------------------------
  // Stop / Start thali toggle
  // ---------------------------------------------------------------------
  $("input#status").on("click", function () {
    const isOn = $(this).is(":checked");
    $(this)
      .next()
      .html(isOn ? "Start" : "Stop")
      .css("color", isOn ? "#198754" : "#dc3545");
    $(this).closest("div#status").next().toggleClass("d-none", !isOn);
  });

  // ---------------------------------------------------------------------
  // Quantity steppers (+ / -)
  // ---------------------------------------------------------------------
  const stepQuantity = ($btn, direction) => {
    const $input = direction < 0 ? $btn.next("input") : $btn.prev("input");
    const step = parseFloat($input.attr("step")) || 0.5;
    const bound = parseFloat($input.attr(direction < 0 ? "min" : "max"));

    let value = parseFloat($input.val()) + direction * step;
    value = direction < 0 ? Math.max(value, bound) : Math.min(value, bound);

    const $opposite = $btn
      .parent()
      .find(direction < 0 ? ".btn-plus" : ".btn-minus");
    $opposite.prop("disabled", false);
    $btn.prop("disabled", value === bound);

    $input.val(value).trigger("change");
    return false;
  };

  $(".btn-minus").on("click", function () {
    return stepQuantity($(this), -1);
  });
  $(".btn-plus").on("click", function () {
    return stepQuantity($(this), 1);
  });

  if (typeof scheds === "undefined") return;

  // ---------------------------------------------------------------------
  // Build calendar events from the schedule map
  // ---------------------------------------------------------------------
  const THALI_ITEM_KEYS = ["sabji", "tarkari", "rice", "roti", "extra"];

  const buildEventTitle = (row) => {
    if (row.menu_type === "miqaat") return row?.menu_item?.miqaat ?? "";
    if (row.menu_type !== "thaali") return "";
    return THALI_ITEM_KEYS.map((key) => row?.menu_item?.[key]?.item)
      .filter(Boolean)
      .join("<br/>");
  };

  const events = Object.values(scheds).map((row) => {
    const isMiqaat = row.menu_type === "miqaat";
    const isStoppedThali = row.menu_type === "thaali" && row.status === "stop";
    return {
      id: row.id,
      title: buildEventTitle(row),
      start: row.menu_date,
      color: "#ffffff",
      textColor: isMiqaat ? "#198754" : isStoppedThali ? "#dc3545" : "#000000",
    };
  });

  // ---------------------------------------------------------------------
  // Edit-menu item renderer, shared by sabji / tarkari / rice
  // (identical show/hide + min/max + feedback-rating behaviour)
  // ---------------------------------------------------------------------
  function renderSimpleItem(schedule, editmenu, feedmenu, key) {
    const item = schedule?.menu_item?.[key];
    const maxItem = schedule?.max_item?.[key];
    const feed = schedule?.menu_feed?.[key];

    const editDiv = editmenu.find(`div#${key}`);
    const editInput = editmenu.find(`input#${key}`);
    const editQtyInput = editmenu.find(`input#${key}qty`);
    const editLabel = editmenu.find(`label#${key}`);
    const feedDiv = feedmenu.find(`div#${key}`);
    const feedInput = feedmenu.find(`input#${key}`);
    const feedLabel = feedmenu.find(`label#${key}`);
    const feedRating = feedmenu.find(`input.${key}rating`);

    if (item?.item === undefined) {
      editDiv.addClass("d-none");
      editInput.prop("disabled", true).val("");
      editQtyInput.prop("disabled", true).val("").removeAttr("max");
      editLabel.html("");
      editQtyInput
        .parent()
        .find(".btn-minus, .btn-plus")
        .prop("disabled", false);

      feedDiv.addClass("d-none");
      feedInput.val("");
      feedLabel.html("");
      feedRating.removeAttr("required");
      return;
    }

    editDiv.removeClass("d-none");
    editInput.prop("disabled", false).val(item.item);
    editQtyInput.prop("disabled", false).val(item.qty);
    editLabel.html(item.item);

    const qtyButtons = editQtyInput.parent();
    if (maxItem?.item !== undefined) {
      qtyButtons.find(".btn-plus").prop("disabled", !(item.qty < maxItem.qty));
      qtyButtons.find(".btn-minus").prop("disabled", !(item.qty > 0));
      editQtyInput.attr("max", maxItem.qty);
    } else {
      qtyButtons.find(".btn-plus").prop("disabled", true);
      editQtyInput.attr("max", item.qty);
    }

    if (item.qty !== 0) {
      feedDiv.removeClass("d-none");
      feedInput.val(item.item);
      feedLabel.html(item.item);
      feedRating.attr("required", "required").prop("checked", false);
      const rating = ratingSlug(feed?.rating);
      if (rating)
        feedmenu.find(`input#${key}rating-${rating}`).prop("checked", true);
    }
  }

  // ---------------------------------------------------------------------
  // Roti: same pattern as above, plus thali-size dependent quantities
  // ---------------------------------------------------------------------
  function renderRoti(schedule, editmenu, feedmenu) {
    const item = schedule?.menu_item?.roti;
    const maxItem = schedule?.max_item?.roti;
    const feed = schedule?.menu_feed?.roti;
    const size = schedule.thalisize;
    const sizeField = rotiSizeField(size);

    const editDiv = editmenu.find("div#roti");
    const editInput = editmenu.find("input#roti");
    const editQtyInput = editmenu.find("input#rotiqty");
    const editLabel = editmenu.find("label#roti");
    const feedDiv = feedmenu.find("div#roti");
    const feedInput = feedmenu.find("input#roti");
    const feedLabel = feedmenu.find("label#roti");
    const feedRating = feedmenu.find("input.rotirating");

    if (item?.item === undefined) {
      editDiv.addClass("d-none");
      editInput.prop("disabled", true).val("");
      editQtyInput.prop("disabled", true).val("").removeAttr("max");
      editLabel.html("");
      editQtyInput
        .parent()
        .find(".btn-minus, .btn-plus")
        .prop("disabled", false);

      feedDiv.addClass("d-none");
      feedInput.val("");
      feedLabel.html("");
      feedRating.removeAttr("required");
      return;
    }

    editDiv.removeClass("d-none");
    editInput.prop("disabled", false).val(item.item);
    editQtyInput.prop("disabled", false);
    editQtyInput.attr("step", "1");
    editLabel.html(item.item);

    let currentQty =
      item.qty !== undefined ? Number(item.qty) : Number(item[sizeField] || 0);
    if (item.qty === undefined && item.item?.trim().toLowerCase() === "roti") {
      currentQty += Math.max(0, Number(schedule.extraRoti || 0));
    }
    editQtyInput.val(currentQty);

    const qtyButtons = editQtyInput.parent();
    if (maxItem?.item !== undefined) {
      const configuredLimit = Number(schedule.rotiMaxQty);
      const fallbackLimit =
        Number(maxItem[sizeField] || 0) +
        (maxItem.item.trim().toLowerCase() === "roti"
          ? Math.max(0, Number(schedule.extraRoti || 0))
          : 0);
      const boundedLimit = Math.max(
        0,
        Number.isFinite(configuredLimit) ? configuredLimit : fallbackLimit,
      );
      currentQty = Math.min(Math.max(0, currentQty), boundedLimit);
      editQtyInput.val(currentQty);
      qtyButtons
        .find(".btn-plus")
        .prop("disabled", !(currentQty < boundedLimit));
      qtyButtons.find(".btn-minus").prop("disabled", !(currentQty > 0));
      editQtyInput.attr("max", boundedLimit);
    } else {
      qtyButtons.find(".btn-plus").prop("disabled", true);
      editQtyInput.attr("max", currentQty);
    }

    // NOTE: replicated verbatim from the legacy logic, including the loose
    // `!= 0` checks on fields that may be `undefined` for the "wrong" size.
    const showFeedback =
      (size === "Mini" && item.tqty != 0) ||
      (size === "Medium" && item.mqty != 0) ||
      (size === "Large" && item.lqty != 0) ||
      item.sqty != 0;

    if (showFeedback) {
      feedDiv.removeClass("d-none");
      feedInput.val(item.item);
      feedLabel.html(item.item);
      feedRating.attr("required", "required").prop("checked", false);
      const rating = ratingSlug(feed?.rating);
      if (rating)
        feedmenu.find(`input#rotirating-${rating}`).prop("checked", true);
    }
  }

  // ---------------------------------------------------------------------
  // Extra: no max-item limits, otherwise similar pattern
  // ---------------------------------------------------------------------
  function renderExtra(schedule, editmenu, feedmenu) {
    const item = schedule?.menu_item?.extra;
    const feed = schedule?.menu_feed?.extra;

    const editDiv = editmenu.find("div#extra");
    const editInput = editmenu.find("input#extra");
    const editQtyInput = editmenu.find("input#extraqty");
    const editLabel = editmenu.find("label#extra");
    const feedDiv = feedmenu.find("div#extra");
    const feedInput = feedmenu.find("input#extra");
    const feedLabel = feedmenu.find("label#extra");
    const feedRating = feedmenu.find("input.extrarating");

    if (item?.item === undefined) {
      editDiv.addClass("d-none");
      editInput.prop("disabled", true).val("");
      editQtyInput.prop("disabled", true).val("");
      editLabel.html("");

      feedDiv.addClass("d-none");
      feedInput.val("");
      feedLabel.html("");
      feedRating.removeAttr("required");
      return;
    }

    editDiv.removeClass("d-none");
    editInput.prop("disabled", false).val(item.item);
    editQtyInput.prop("disabled", false).val(item.qty);
    editLabel.html(item.item);

    feedDiv.removeClass("d-none");
    feedInput.val(item.item);
    feedLabel.html(item.item);

    if (item.qty != 0) {
      feedRating.attr("required", "required").prop("checked", false);
      const rating = ratingSlug(feed?.rating);
      if (rating)
        feedmenu.find(`input#extrarating-${rating}`).prop("checked", true);
    }
  }

  // ---------------------------------------------------------------------
  // Miqaat modal
  // ---------------------------------------------------------------------
  function renderMiqaat(schedule, editmenu, menuDate) {
    editmenu
      .find(".modal-title")
      .html(`Miqaat on <strong>${menuDate.toDateString()}</strong>`);
    editmenu.find("div#status, div#thali").addClass("d-none");
    editmenu.find("button.edit-menu, button.rsvp-end").addClass("d-none");

    if (schedule?.menu_item?.miqaat !== undefined) {
      editmenu
        .find("div#miqaat")
        .removeClass("d-none")
        .html(`<h3>${schedule.menu_item.miqaat}</h3>`);
    }
  }

  // ---------------------------------------------------------------------
  // Thaali modal
  // ---------------------------------------------------------------------
  function renderThaali(schedule, editmenu, feedmenu, menuDate) {
    editmenu
      .find(".modal-title")
      .html(`View/Edit Menu of <strong>${menuDate.toDateString()}</strong>`);
    feedmenu
      .find(".modal-title")
      .html(`Feedback Menu of <strong>${menuDate.toDateString()}</strong>`);
    editmenu.find("div#miqaat").addClass("d-none");
    editmenu.find("div#status, div#thali").removeClass("d-none");

    const isStopped = schedule.status === "stop";
    editmenu.find("input#status").prop("checked", !isStopped);
    editmenu
      .find("label#status")
      .html(isStopped ? "Stop" : "Start")
      .css("color", isStopped ? "#dc3545" : "#198754");
    editmenu.find("div#thali").toggleClass("d-none", isStopped);
    if (!isStopped) editmenu.find("button.feedback").removeClass("d-none");

    ["sabji", "tarkari", "rice"].forEach((key) =>
      renderSimpleItem(schedule, editmenu, feedmenu, key),
    );
    renderRoti(schedule, editmenu, feedmenu);
    renderExtra(schedule, editmenu, feedmenu);

    feedmenu.find("textarea#feedback").val(schedule.feedback ?? "");
  }

  // ---------------------------------------------------------------------
  // Cutoff-based button visibility (edit / feedback / rsvp-end / submit)
  // ---------------------------------------------------------------------
  function updateButtonVisibility(
    schedule,
    editmenu,
    feedmenu,
    now,
    editCutoffStart,
    menuCutoff,
    feedbackCutoff,
  ) {
    if (now > editCutoffStart && now < menuCutoff) {
      editmenu.find("button.edit-menu, button.feedback").addClass("d-none");
      editmenu.find("button.rsvp-end").removeClass("d-none");
    } else if (now > menuCutoff) {
      editmenu.find("button.edit-menu").addClass("d-none");
      const isStopped = schedule.status === "stop";
      editmenu.find("button.feedback").toggleClass("d-none", isStopped);
      editmenu.find("button.rsvp-end").toggleClass("d-none", !isStopped);
    } else {
      editmenu.find("button.edit-menu").removeClass("d-none");
      editmenu.find("button.rsvp-end, button.feedback").addClass("d-none");
    }

    feedmenu
      .find("button.submit-feedback")
      .toggleClass("d-none", now > feedbackCutoff);
  }

  // ---------------------------------------------------------------------
  // Event click: populate + open the relevant modal
  // ---------------------------------------------------------------------
  const toIST = (date) =>
    new Date(date.toLocaleString("en-US", { timeZone: "Asia/Kolkata" }));

  function handleEventClick(info) {
    $("#changemenu")[0].reset();

    const editmenu = $("#editmenu");
    const feedmenu = $("#feedbackmenu");
    const id = info.event.id;
    const schedule = scheds[id];

    if (!schedule) {
      alert("Event is undefined");
      return;
    }

    editmenu.find(".modal-body #validate").remove();
    feedmenu.find(".modal-body #validate").remove();
    editmenu.find("input#menu_id").val(id);
    feedmenu.find("input#menu_id").val(id);

    const menuDate = new Date(schedule.menu_date);

    const editCutoffStart = toIST(menuDate);
    editCutoffStart.setDate(editCutoffStart.getDate() - 1);
    editCutoffStart.setHours(17, 0, 0, 0);

    const menuCutoff = toIST(menuDate);
    menuCutoff.setHours(13, 0, 0, 0);

    const feedbackCutoff = toIST(menuDate);
    feedbackCutoff.setDate(
      feedbackCutoff.getDate() + ((7 - feedbackCutoff.getDay()) % 7),
    );
    feedbackCutoff.setHours(20, 0, 0, 0);

    const now = toIST(new Date());

    if (schedule.menu_type === "miqaat") {
      renderMiqaat(schedule, editmenu, menuDate);
    } else if (schedule.menu_type === "thaali") {
      renderThaali(schedule, editmenu, feedmenu, menuDate);
      updateButtonVisibility(
        schedule,
        editmenu,
        feedmenu,
        now,
        editCutoffStart,
        menuCutoff,
        feedbackCutoff,
      );
    }

    editmenu.find("#edit").attr("data-id", id);
    editmenu.modal("show");
  }

  // ---------------------------------------------------------------------
  // Calendar init
  // ---------------------------------------------------------------------
  const calendar = new FullCalendar.Calendar(
    document.getElementById("calendar"),
    {
      headerToolbar: {
        left: "prev,next",
        right: "dayGridMonth,dayGridWeek",
        center: "title",
      },
      views: {
        dayGridMonth: { titleFormat: { year: "numeric", month: "short" } },
        dayGridWeek: {
          titleFormat: { year: "2-digit", month: "short", day: "numeric" },
        },
      },
      selectable: true,
      themeSystem: "bootstrap5",
      contentHeight: "auto",
      editable: true,
      events,
      eventContent: (info) => ({ html: info.event.title }),
      eventClick: handleEventClick,
    },
  );

  calendar.render();
})(jQuery);
