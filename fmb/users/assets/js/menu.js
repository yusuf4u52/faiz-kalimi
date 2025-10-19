(function ($) {
  //stop thali
  $("input#status").on("click", function () {
    if ($(this).is(":checked")) {
      $(this).next().html("Start");
      $(this).next().attr("style", "color:#198754");
      $(this).closest("div#status").next().removeClass("d-none");
    } else {
      $(this).next().html("Stop");
      $(this).next().attr("style", "color:#dc3545");
      $(this).closest("div#status").next().addClass("d-none");
    }
  });

  // Edit menu for users
  $(".btn-minus").on("click", function () {
    var $input = $(this).next("input");
    var minCount = $input.attr("min");
    var count = parseFloat($input.val());
    //if (count <= 1) {
    count = parseFloat($input.val()) - 0.5;
    count = count < minCount ? minCount : count;
    /*} else {
      count = parseFloat($input.val()) - 1;
      count = count < minCount ? minCount : count;
    }*/
    $(this).parent().find(".btn-plus").removeAttr("disabled", "disabled");
    if (count == minCount) {
      $(this).attr("disabled", "disabled");
    }
    $input.val(count);
    $input.change();
    return false;
  });

  $(".btn-plus").on("click", function () {
    var $input = $(this).prev("input");
    var maxCount = $input.attr("max");
    var count = parseFloat($input.val());
    /*if (count >= 1) {
      count = parseFloat($input.val()) + 1;
      count = count > maxCount ? maxCount : count;
    } else {*/
    count = parseFloat($input.val()) + 0.5;
    count = count > maxCount ? maxCount : count;
    //}
    $(this).parent().find(".btn-minus").removeAttr("disabled", "disabled");
    if (count == maxCount) {
      $(this).attr("disabled", "disabled");
    }
    $input.val(count);
    $input.change();
    return false;
  });

  $("#changemenu").on("submit", function () {
    var total = 0;
    var sabjiqty = parseFloat($("#sabjiqty").val());
    var tarkariqty = parseFloat($("#tarkariqty").val());
    var riceqty = parseFloat($("#riceqty").val());
    if (sabjiqty > 0) {
      if (sabjiqty == 0.5) {
        total = total + 1;
      } else {
        total = total + sabjiqty;
      }
    }
    if (tarkariqty > 0) {
      if (tarkariqty == 0.5) {
        total = total + 1;
      } else {
        total = total + tarkariqty;
      }
    }
    if (riceqty > 0) {
      if (riceqty == 0.5) {
        total = total + 1;
      } else {
        total = total + riceqty;
      }
    }
    if (total > 4) {
      $(".modal-body #validate").remove();
      $(".modal-body").append(
        '<div id="validate" class="row text-center"><div class="col-xs-12"><small class="text-danger">Total should not be greater than 4. Also 0.5 will also be count as 1</small><div>'
      );
      return false;
    }
    $(".modal-body #validate").remove();
    return true;
  });

  var calendar;
  var Calendar = FullCalendar.Calendar;
  var events = [];

  if (scheds !== undefined) {
    Object.keys(scheds).map((k) => {
      var row = scheds[k];
      var title = "";
      var color = "#ffffff";
      var textColor = "#000000";
      if (row.menu_type == "miqaat") {
        if (row?.menu_item?.miqaat !== undefined) {
          title += row?.menu_item?.miqaat;
        }
        color = "#ffffff";
        textColor = "#198754";
      }
      if (row.menu_type == "thaali") {
        if (row?.status == "stop") {
          color = "#ffffff";
          textColor = "#dc3545";
        }
        if (row?.menu_item?.sabji?.item !== undefined) {
          title += row?.menu_item?.sabji?.item + "<br/>";
        }
        if (row?.menu_item?.tarkari?.item !== undefined) {
          title += row?.menu_item?.tarkari?.item + "<br/>";
        }
        if (row?.menu_item?.rice?.item !== undefined) {
          title += row?.menu_item?.rice?.item + "<br/>";
        }
        if (row?.menu_item?.roti?.item !== undefined) {
          title += row?.menu_item?.roti?.item + "<br/>";
        }
        if (row?.menu_item?.extra?.item !== undefined) {
          title += row?.menu_item?.extra?.item;
        }
      }
      events.push({
        id: row.id,
        title: title,
        start: row.menu_date,
        color: color,
        textColor: textColor,
      });
    });

    var date = new Date();
    var d = date.getDate(),
      m = date.getMonth(),
      y = date.getFullYear(),
      calendar = new Calendar(document.getElementById("calendar"), {
        headerToolbar: {
          left: "prev,next",
          right: "dayGridMonth,dayGridWeek",
          center: "title",
        },
        views: {
          dayGridMonth: {
            titleFormat: { year: "numeric", month: "short" },
          },
          dayGridWeek: {
            titleFormat: { year: "2-digit", month: "short", day: "numeric" },
          },
        },
        selectable: true,
        themeSystem: "bootstrap5",
        contentHeight: "auto",
        editable: true,
        events: events,
        eventClick: function (info) {
          $("#changemenu")[0].reset();
          var editmenu = $("#editmenu");
          var feedmenu = $("#feedbackmenu");
          var id = info.event.id;
          if (!!scheds[id]) {
            editmenu.find("input#menu_id").val(id);
            editmenu.find(".modal-body #validate").remove();
            feedmenu.find("input#menu_id").val(id);
            feedmenu.find(".modal-body #validate").remove();
            var GivenDate = new Date(scheds[id].menu_date).toLocaleString(
              "en-US",
              { timeZone: "Asia/Kolkata" }
            );
            GivenDate = new Date(GivenDate);
            GivenDate.setDate(GivenDate.getDate() - 1);
            GivenDate.setHours(17, 0, 0, 0);
            var MenuDate = new Date(scheds[id].menu_date).toLocaleString(
              "en-US",
              { timeZone: "Asia/Kolkata" }
            );
            MenuDate = new Date(MenuDate);
            MenuDate.setHours(13, 0, 0, 0);
            var Sunday = new Date(scheds[id].menu_date).toLocaleString(
              "en-US",
              { timeZone: "Asia/Kolkata" }
            );
            Sunday = new Date(Sunday);
            Sunday.setDate(Sunday.getDate() + ((7 - Sunday.getDay()) % 7));
            Sunday.setHours(20, 0, 0, 0);
            var CurrentDate = new Date().toLocaleString("en-US", {
              timeZone: "Asia/Kolkata",
            });
            CurrentDate = new Date(CurrentDate);
            const menu_date = new Date(scheds[id].menu_date);
            if (scheds[id]?.menu_type == "miqaat") {
              editmenu
                .find(".modal-title")
                .html(
                  "Miqaat on <strong>" + menu_date.toDateString() + "</strong>"
                );
              editmenu.find("div#status").addClass("d-none");
              editmenu.find("div#thali").addClass("d-none");
              editmenu.find("button.edit-menu").addClass("d-none");
              editmenu.find("button.rsvp-end").addClass("d-none");
              if (scheds[id]?.menu_item?.miqaat !== undefined) {
                editmenu.find("div#miqaat").removeClass("d-none");
                editmenu
                  .find("div#miqaat")
                  .html("<h3>" + scheds[id].menu_item.miqaat + "</h3>");
              }
            }
            if (scheds[id]?.menu_type == "thaali") {
              editmenu
                .find(".modal-title")
                .html(
                  "View/Edit Menu of <strong>" +
                    menu_date.toDateString() +
                    "</strong>"
                );
              editmenu.find("div#miqaat").addClass("d-none");
              editmenu.find("div#status").removeClass("d-none");
              editmenu.find("div#thali").removeClass("d-none");
              feedmenu
                .find(".modal-title")
                .html(
                  "Feedback Menu of <strong>" +
                    menu_date.toDateString() +
                    "</strong>"
                );
              if (scheds[id]?.status == "stop") {
                editmenu.find("input#status").removeAttr("checked", "checked");
                editmenu.find("label#status").html("Stop");
                editmenu.find("label#status").attr("style", "color:#dc3545");
                editmenu.find("div#thali").addClass("d-none");
              } else {
                editmenu.find("input#status").attr("checked", "checked");
                editmenu.find("label#status").html("Start");
                editmenu.find("label#status").attr("style", "color:#198754");
                editmenu.find("div#thali").removeClass("d-none");
                editmenu.find("button.feedback").removeClass("d-none");
              }
              if (scheds[id]?.menu_item?.sabji?.item !== undefined) {
                editmenu.find("div#sabji").removeClass("d-none");
                editmenu.find("input#sabji").removeAttr("disabled", "disabled");
                editmenu
                  .find("input#sabjiqty")
                  .removeAttr("disabled", "disabled");
                editmenu
                  .find("label#sabji")
                  .html(scheds[id].menu_item.sabji.item);
                editmenu
                  .find("input#sabji")
                  .val(scheds[id].menu_item.sabji.item);
                editmenu
                  .find("input#sabjiqty")
                  .val(scheds[id].menu_item.sabji.qty);
                if (scheds[id]?.max_item?.sabji?.item !== undefined) {
                  if (
                    scheds[id]?.menu_item?.sabji?.qty <
                    scheds[id]?.max_item?.sabji?.qty
                  ) {
                    editmenu
                      .find("input#sabjiqty")
                      .parent()
                      .find(".btn-plus")
                      .removeAttr("disabled", "disabled");
                  } else {
                    editmenu
                      .find("input#sabjiqty")
                      .parent()
                      .find(".btn-plus")
                      .attr("disabled", "disabled");
                  }
                  if (scheds[id]?.menu_item?.sabji?.qty > 0) {
                    editmenu
                      .find("input#sabjiqty")
                      .parent()
                      .find(".btn-minus")
                      .removeAttr("disabled", "disabled");
                  } else {
                    editmenu
                      .find("input#sabjiqty")
                      .parent()
                      .find(".btn-minus")
                      .attr("disabled", "disabled");
                  }
                  editmenu
                    .find("input#sabjiqty")
                    .attr("max", scheds[id].max_item.sabji.qty);
                } else {
                  editmenu
                    .find("input#sabjiqty")
                    .parent()
                    .find(".btn-plus")
                    .attr("disabled", "disabled");
                  editmenu
                    .find("input#sabjiqty")
                    .attr("max", scheds[id].menu_item.sabji.qty);
                }
                if (scheds[id]?.menu_item?.sabji?.qty != 0) {
                  feedmenu.find("div#sabji").removeClass("d-none");
                  feedmenu
                    .find("input#sabji")
                    .val(scheds[id].menu_item.sabji.item);
                  feedmenu
                    .find("label#sabji")
                    .html(scheds[id].menu_item.sabji.item);
                  feedmenu
                    .find("input.sabjirating")
                    .attr("required", "required");
                  if (scheds[id]?.menu_feed?.sabji?.rating !== undefined) {
                    let sabjirating = scheds[id].menu_feed.sabji.rating;
                    sabjirating = sabjirating.toLowerCase();
                    sabjirating = sabjirating.replace(" ", "-");
                    feedmenu
                      .find("input#sabjirating-" + sabjirating)
                      .attr("checked", "checked");
                  } else {
                    feedmenu
                      .find("input.sabjirating")
                      .removeAttr("checked", "checked");
                  }
                }
              } else {
                editmenu.find("div#sabji").addClass("d-none");
                editmenu.find("input#sabji").attr("disabled", "disabled");
                editmenu.find("input#sabjiqty").attr("disabled", "disabled");
                editmenu.find("label#sabji").html("");
                editmenu.find("input#sabji").val("");
                editmenu.find("input#sabjiqty").val("");
                editmenu.find("input#sabjiqty").removeAttr("max");
                editmenu
                  .find("input#sabjiqty")
                  .parent()
                  .find(".btn-minus")
                  .removeAttr("disabled", "disabled");
                editmenu
                  .find("input#sabjiqty")
                  .parent()
                  .find(".btn-plus")
                  .removeAttr("disabled", "disabled");
                feedmenu.find("div#sabji").addClass("d-none");
                feedmenu.find("input#sabji").val("");
                feedmenu.find("label#sabji").html("");
                feedmenu.find("label#sabji").html("");
                feedmenu
                  .find("input.sabjirating")
                  .removeAttr("required", "required");
              }
              if (scheds[id]?.menu_item?.tarkari?.item !== undefined) {
                editmenu.find("div#tarkari").removeClass("d-none");
                editmenu
                  .find("input#tarkari")
                  .removeAttr("disabled", "disabled");
                editmenu
                  .find("input#tarkariqty")
                  .removeAttr("disabled", "disabled");
                editmenu
                  .find("label#tarkari")
                  .html(scheds[id].menu_item.tarkari.item);
                editmenu
                  .find("input#tarkari")
                  .val(scheds[id].menu_item.tarkari.item);
                editmenu
                  .find("input#tarkariqty")
                  .val(scheds[id].menu_item.tarkari.qty);
                if (scheds[id]?.max_item?.tarkari?.item !== undefined) {
                  if (
                    scheds[id]?.menu_item?.tarkari?.qty <
                    scheds[id]?.max_item?.tarkari?.qty
                  ) {
                    editmenu
                      .find("input#tarkariqty")
                      .parent()
                      .find(".btn-plus")
                      .removeAttr("disabled", "disabled");
                  } else {
                    editmenu
                      .find("input#tarkariqty")
                      .parent()
                      .find(".btn-plus")
                      .attr("disabled", "disabled");
                  }
                  if (scheds[id]?.menu_item?.tarkari?.qty > 0) {
                    editmenu
                      .find("input#tarkariqty")
                      .parent()
                      .find(".btn-minus")
                      .removeAttr("disabled", "disabled");
                  } else {
                    editmenu
                      .find("input#tarkariqty")
                      .parent()
                      .find(".btn-minus")
                      .attr("disabled", "disabled");
                  }
                  editmenu
                    .find("input#tarkariqty")
                    .attr("max", scheds[id].max_item.tarkari.qty);
                } else {
                  editmenu
                    .find("input#tarkariqty")
                    .parent()
                    .find(".btn-plus")
                    .attr("disabled", "disabled");
                  editmenu
                    .find("input#tarkariqty")
                    .attr("max", scheds[id].menu_item.tarkari.qty);
                }
                if (scheds[id]?.menu_item?.tarkari?.qty != 0) {
                  feedmenu.find("div#tarkari").removeClass("d-none");
                  feedmenu
                    .find("input#tarkari")
                    .val(scheds[id].menu_item.tarkari.item);
                  feedmenu
                    .find("label#tarkari")
                    .html(scheds[id].menu_item.tarkari.item);
                  feedmenu
                    .find("input.tarkarirating")
                    .attr("required", "required");
                  if (scheds[id]?.menu_feed?.tarkari?.rating !== undefined) {
                    let tarkarirating = scheds[id].menu_feed.tarkari.rating;
                    tarkarirating = tarkarirating.toLowerCase();
                    tarkarirating = tarkarirating.replace(" ", "-");
                    feedmenu
                      .find("input#tarkarirating-" + tarkarirating)
                      .attr("checked", "checked");
                  } else {
                    feedmenu
                      .find("input.tarkarirating")
                      .removeAttr("checked", "checked");
                  }
                }
              } else {
                editmenu.find("div#tarkari").addClass("d-none");
                editmenu.find("input#tarkari").attr("disabled", "disabled");
                editmenu.find("input#tarkariqty").attr("disabled", "disabled");
                editmenu.find("label#tarkari").html("");
                editmenu.find("input#tarkari").val("");
                editmenu.find("input#tarkariqty").val("");
                editmenu.find("input#tarkari").removeAttr("max");
                editmenu
                  .find("input#tarkariqty")
                  .parent()
                  .find(".btn-minus")
                  .removeAttr("disabled", "disabled");
                editmenu
                  .find("input#tarkariqty")
                  .parent()
                  .find(".btn-plus")
                  .removeAttr("disabled", "disabled");
                feedmenu.find("div#tarkari").addClass("d-none");
                feedmenu.find("input#tarkari").val("");
                feedmenu.find("label#tarkari").html("");
                feedmenu
                  .find("input.tarkarirating")
                  .removeAttr("required", "required");
              }
              if (scheds[id]?.menu_item?.rice?.item !== undefined) {
                editmenu.find("div#rice").removeClass("d-none");
                editmenu.find("input#rice").removeAttr("disabled", "disabled");
                editmenu
                  .find("input#riceqty")
                  .removeAttr("disabled", "disabled");
                editmenu
                  .find("label#rice")
                  .html(scheds[id].menu_item.rice.item);
                editmenu.find("input#rice").val(scheds[id].menu_item.rice.item);
                editmenu
                  .find("input#riceqty")
                  .val(scheds[id].menu_item.rice.qty);
                if (scheds[id]?.max_item?.rice?.item !== undefined) {
                  if (
                    scheds[id]?.menu_item?.rice?.qty <
                    scheds[id]?.max_item?.rice?.qty
                  ) {
                    editmenu
                      .find("input#riceqty")
                      .parent()
                      .find(".btn-plus")
                      .removeAttr("disabled", "disabled");
                  } else {
                    editmenu
                      .find("input#riceqty")
                      .parent()
                      .find(".btn-plus")
                      .attr("disabled", "disabled");
                  }
                  if (scheds[id]?.menu_item?.rice?.qty > 0) {
                    editmenu
                      .find("input#riceqty")
                      .parent()
                      .find(".btn-minus")
                      .removeAttr("disabled", "disabled");
                  } else {
                    editmenu
                      .find("input#riceqty")
                      .parent()
                      .find(".btn-minus")
                      .attr("disabled", "disabled");
                  }
                  editmenu
                    .find("input#riceqty")
                    .attr("max", scheds[id].max_item.rice.qty);
                } else {
                  editmenu
                    .find("input#riceqty")
                    .parent()
                    .find(".btn-plus")
                    .attr("disabled", "disabled");
                  editmenu
                    .find("input#riceqty")
                    .attr("max", scheds[id].menu_item.rice.qty);
                }
                if (scheds[id]?.menu_item?.rice?.qty != 0) {
                  feedmenu.find("div#rice").removeClass("d-none");
                  feedmenu
                    .find("input#rice")
                    .val(scheds[id].menu_item.rice.item);
                  feedmenu
                    .find("label#rice")
                    .html(scheds[id].menu_item.rice.item);
                  feedmenu
                    .find("input.ricerating")
                    .attr("required", "required");
                  if (scheds[id]?.menu_feed?.rice?.rating !== undefined) {
                    let ricerating = scheds[id].menu_feed.rice.rating;
                    ricerating = ricerating.toLowerCase();
                    ricerating = ricerating.replace(" ", "-");
                    feedmenu
                      .find("input#ricerating-" + ricerating)
                      .attr("checked", "checked");
                  } else {
                    feedmenu
                      .find("input.ricerating")
                      .removeAttr("checked", "checked");
                  }
                }
              } else {
                editmenu.find("div#rice").addClass("d-none");
                editmenu.find("input#rice").attr("disabled", "disabled");
                editmenu.find("input#riceqty").attr("disabled", "disabled");
                editmenu.find("label#rice").html("");
                editmenu.find("input#rice").val("");
                editmenu.find("input#riceqty").val("");
                editmenu.find("input#riceqty").removeAttr("max");
                editmenu
                  .find("input#riceqty")
                  .parent()
                  .find(".btn-minus")
                  .removeAttr("disabled", "disabled");
                editmenu
                  .find("input#riceqty")
                  .parent()
                  .find(".btn-plus")
                  .removeAttr("disabled", "disabled");
                feedmenu.find("div#rice").addClass("d-none");
                feedmenu.find("input#rice").val("");
                feedmenu.find("label#rice").html("");
                feedmenu
                  .find("input.ricerating")
                  .removeAttr("required", "required");
              }
              if (scheds[id]?.menu_item?.roti?.item !== undefined) {
                editmenu.find("div#roti").removeClass("d-none");
                editmenu.find("input#roti").removeAttr("disabled", "disabled");
                editmenu
                  .find("input#rotiqty")
                  .removeAttr("disabled", "disabled");
                editmenu
                  .find("label#roti")
                  .html(scheds[id].menu_item.roti.item);
                editmenu.find("input#roti").val(scheds[id].menu_item.roti.item);
                feedmenu.find("div#roti").removeClass("d-none");
                feedmenu.find("input#roti").val(scheds[id].menu_item.roti.item);
                feedmenu
                  .find("label#roti")
                  .html(scheds[id].menu_item.roti.item);
                if (scheds[id]?.menu_item?.roti?.qty !== undefined) {
                  editmenu
                    .find("input#rotiqty")
                    .val(scheds[id].menu_item.roti.qty);
                } else {
                  if (scheds[id].thalisize == "Mini") {
                    editmenu
                      .find("input#rotiqty")
                      .val(scheds[id].menu_item.roti.tqty);
                  }
                  if (scheds[id].thalisize == "Small") {
                    editmenu
                      .find("input#rotiqty")
                      .val(scheds[id].menu_item.roti.sqty);
                  }
                  if (scheds[id].thalisize == "Medium") {
                    editmenu
                      .find("input#rotiqty")
                      .val(scheds[id].menu_item.roti.mqty);
                  }
                  if (scheds[id].thalisize == "Large") {
                    editmenu
                      .find("input#rotiqty")
                      .val(scheds[id].menu_item.roti.lqty);
                  }
                }
                if (scheds[id]?.menu_item?.roti?.qty != 0) {
                  feedmenu.find("div#roti").removeClass("d-none");
                  feedmenu
                    .find("input#roti")
                    .val(scheds[id].menu_item.roti.item);
                  feedmenu
                    .find("label#roti")
                    .html(scheds[id].menu_item.roti.item);
                  feedmenu
                    .find("input.rotirating")
                    .attr("required", "required");
                  if (scheds[id]?.menu_feed?.roti?.rating !== undefined) {
                    let rotirating = scheds[id].menu_feed.roti.rating;
                    rotirating = rotirating.toLowerCase();
                    rotirating = rotirating.replace(" ", "-");
                    feedmenu
                      .find("input#rotirating-" + rotirating)
                      .attr("checked", "checked");
                  } else {
                    feedmenu
                      .find("input.rotirating")
                      .removeAttr("checked", "checked");
                  }
                }
              } else {
                editmenu.find("div#roti").addClass("d-none");
                editmenu.find("input#roti").attr("disabled", "disabled");
                editmenu.find("input#rotiqty").attr("disabled", "disabled");
                editmenu.find("label#roti").html("");
                editmenu.find("input#roti").val("");
                editmenu.find("input#rotiqty").val("");
                feedmenu.find("div#roti").addClass("d-none");
                feedmenu.find("input#roti").val("");
                feedmenu.find("label#roti").html("");
                feedmenu
                  .find("input.rotirating")
                  .removeAttr("required", "required");
              }
              if (scheds[id]?.menu_item?.extra?.item !== undefined) {
                editmenu.find("div#extra").removeClass("d-none");
                editmenu.find("input#extra").removeAttr("disabled", "disabled");
                editmenu
                  .find("input#extraqty")
                  .removeAttr("disabled", "disabled");
                editmenu
                  .find("label#extra")
                  .html(scheds[id].menu_item.extra.item);
                editmenu
                  .find("input#extra")
                  .val(scheds[id].menu_item.extra.item);
                editmenu
                  .find("input#extraqty")
                  .val(scheds[id].menu_item.extra.qty);
                feedmenu.find("div#extra").removeClass("d-none");
                feedmenu
                  .find("input#extra")
                  .val(scheds[id].menu_item.extra.item);
                feedmenu
                  .find("label#extra")
                  .html(scheds[id].menu_item.extra.item);
                if (scheds[id]?.menu_item?.extra?.qty != 0) {
                  feedmenu.find("div#extra").removeClass("d-none");
                  feedmenu
                    .find("input#extra")
                    .val(scheds[id].menu_item.extra.item);
                  feedmenu
                    .find("label#extra")
                    .html(scheds[id].menu_item.extra.item);
                  feedmenu
                    .find("input.extrarating")
                    .attr("required", "required");
                  if (scheds[id]?.menu_feed?.extra?.rating !== undefined) {
                    let extrarating = scheds[id].menu_feed.extra.rating;
                    extrarating = extrarating.toLowerCase();
                    extrarating = extrarating.replace(" ", "-");
                    feedmenu
                      .find("input#extrarating-" + extrarating)
                      .attr("checked", "checked");
                  } else {
                    feedmenu
                      .find("input.extrarating")
                      .removeAttr("checked", "checked");
                  }
                }
              } else {
                editmenu.find("div#extra").addClass("d-none");
                editmenu.find("input#extra").attr("disabled", "disabled");
                editmenu.find("input#extraqty").attr("disabled", "disabled");
                editmenu.find("label#extra").html("");
                editmenu.find("input#extra").val("");
                editmenu.find("input#extraqty").val("");
                feedmenu.find("div#extra").addClass("d-none");
                feedmenu.find("input#extra").val("");
                feedmenu.find("label#extra").html("");
                feedmenu
                  .find("input.extrarating")
                  .removeAttr("required", "required");
              }
              if (scheds[id]?.feedback !== undefined) {
                feedmenu.find("textarea#feedback").val(scheds[id].feedback);
              } else {
                feedmenu.find("textarea#feedback").val("");
              }
              if (CurrentDate > GivenDate && CurrentDate < MenuDate) {
                editmenu.find("button.edit-menu").addClass("d-none");
                editmenu.find("button.feedback").addClass("d-none");
                editmenu.find("button.rsvp-end").removeClass("d-none");
              } else if (CurrentDate > MenuDate) {
                editmenu.find("button.edit-menu").addClass("d-none");
                if (scheds[id]?.status == "stop") {
                  editmenu.find("button.feedback").addClass("d-none");
                  editmenu.find("button.rsvp-end").removeClass("d-none");
                } else {
                  editmenu.find("button.feedback").removeClass("d-none");
                  editmenu.find("button.rsvp-end").addClass("d-none");
                }
              } else {
                editmenu.find("button.edit-menu").removeClass("d-none");
                editmenu.find("button.rsvp-end").addClass("d-none");
                editmenu.find("button.feedback").addClass("d-none");
              }
              if (CurrentDate > Sunday) {
                feedmenu.find("button.submit-feedback").addClass("d-none");
              } else {
                feedmenu.find("button.submit-feedback").removeClass("d-none");
              }
            }
            editmenu.find("#edit").attr("data-id", id);
            editmenu.modal("show");
          } else {
            alert("Event is undefined");
          }
        },
        eventContent: function (info) {
          return { html: info.event.title };
        },
      });
    calendar.render();
  }
})(jQuery);
