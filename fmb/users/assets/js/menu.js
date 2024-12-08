(function ($) {

  //stop thali
  $('input#status').on('click', function () {
    if ($(this).is(':checked')) {
      $(this).next().html('Start');
      $(this).next().attr('style','color:#3C5A05');
      $(this).closest('div#status').next().removeClass('d-none');
    } else {
      $(this).next().html('Stop');
      $(this).next().attr('style','color:#ff0000');
      $(this).closest('div#status').next().addClass('d-none');
    }
  });
  
  // Edit menu for users
  $('.btn-minus').on('click', function () {
    var $input = $(this).next('input');
    var minCount = $input.attr('min');
    var count = parseFloat($input.val());
    //if (count <= 1) {
      count = parseFloat($input.val()) - 0.5;
      count = count < minCount ? minCount : count;
    /*} else {
      count = parseFloat($input.val()) - 1;
      count = count < minCount ? minCount : count;
    }*/
    $(this).parent().find(".btn-plus").removeAttr('disabled','disabled');
    if(count == minCount) {
      $(this).attr('disabled','disabled');
    }
    $input.val(count);
    $input.change();
    return false;
  });

  $('.btn-plus').on('click', function () {
    var $input = $(this).prev('input');
    var maxCount = $input.attr('max');
    var count = parseFloat($input.val());
    /*if (count >= 1) {
      count = parseFloat($input.val()) + 1;
      count = count > maxCount ? maxCount : count;
    } else {*/
      count = parseFloat($input.val()) + 0.5;
      count = count > maxCount ? maxCount : count;
    //}
    $(this).parent().find(".btn-minus").removeAttr('disabled','disabled');
    if(count == maxCount) {
      $(this).attr('disabled','disabled');
    }
    $input.val(count);
    $input.change();
    return false;
  });

  $('#changemenu').on("submit", function () {
    var total = 0;
    var sabjiqty = parseFloat($('#sabjiqty').val());
    var tarkariqty = parseFloat($('#tarkariqty').val());
    var riceqty = parseFloat($('#riceqty').val());
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
      $('.modal-body #validate').remove();
      $('.modal-body').append('<div id="validate" class="row text-center"><div class="col-xs-12"><small class="text-danger">Total should not be greater than 4. Also 0.5 will also be count as 1</small><div>');
      return false;
    }
    $('.modal-body #validate').remove();
    return true
  });

  var calendar;
  var Calendar = FullCalendar.Calendar;
  var events = [];

  if (scheds !== undefined) {
    Object.keys(scheds).map((k) => {
      var row = scheds[k];
      var title = '';
      var color = '#FFFFFF';
      var textColor = '#3D1F10';
      if (row.menu_type == 'miqaat') {
        if (row?.menu_item?.miqaat !== undefined) {
          title += row?.menu_item?.miqaat;
        }
        color = '#3C5A05';
        textColor = '#FFFFFF';
      }
      if (row.menu_type == 'thaali') {
        if (row?.status == 'stop') {
          color = '#ff0000';
          textColor = '#FFFFFF';
        }
        if (row?.menu_item?.sabji?.item !== undefined) {
          title += row?.menu_item?.sabji?.item + '<br/>';
        }
        if (row?.menu_item?.tarkari?.item !== undefined) {
          title += row?.menu_item?.tarkari?.item + '<br/>';
        }
        if (row?.menu_item?.rice?.item !== undefined) {
          title += row?.menu_item?.rice?.item + '<br/>';
        }
        if (row?.menu_item?.roti?.item !== undefined) {
          title += row?.menu_item?.roti?.item + '<br/>';
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
        textColor: textColor
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
          titleFormat: { year: 'numeric', month: 'short' }
        },
        dayGridWeek:  {
          titleFormat: { year: '2-digit', month: 'short', day: 'numeric' }
        },
      },
      selectable: true,
      themeSystem: "bootstrap5",
      contentHeight: "auto",
      editable: true,
      events: events,
      eventClick: function (info) {
        $("#changemenu")[0].reset();
        var details = $("#editmenu");
        var id = info.event.id;
        if (!!scheds[id]) {
          console.log(scheds[id]?.status);
          details.find("input#menu_id").val(id);
          details.find('.modal-body #validate').remove();
          var GivenDate = new Date(scheds[id].menu_date).toLocaleString("en-US", {timeZone: "Asia/Kolkata"});
          GivenDate = new Date(GivenDate);
          GivenDate.setDate(GivenDate.getDate() - 1);
          GivenDate.setHours(20, 0, 0, 0);
          var CurrentDate = new Date().toLocaleString("en-US", {timeZone: "Asia/Kolkata"});
          CurrentDate = new Date(CurrentDate);
          const menu_date = new Date(scheds[id].menu_date);
          if (scheds[id]?.menu_type == 'miqaat') {
            details.find(".modal-title").html('Miqaat on <strong>' + menu_date.toDateString() + '</strong>');
            details.find("div#status").addClass('d-none');
            details.find("div#thali").addClass('d-none');
            details.find("button.edit-menu").addClass('d-none');
            details.find("button.rsvp-end").addClass('d-none');
            if (scheds[id]?.menu_item?.miqaat !== undefined) {
              details.find("div#miqaat").removeClass('d-none');
              details.find("div#miqaat").html('<h3>' + scheds[id].menu_item.miqaat + '</h3>');
            }
          }
          if (scheds[id]?.menu_type == 'thaali') {
            details.find(".modal-title").html('View/Edit Menu of <strong>' +   menu_date.toDateString() + '</strong>');
            details.find("div#miqaat").addClass('d-none');
            details.find("div#status").removeClass('d-none');
            details.find("div#thali").removeClass('d-none');
            if(scheds[id]?.status == 'stop') {
              details.find("input#status").removeAttr('checked', 'checked');
              details.find("label#status").html('Stop');
              details.find("label#status").attr('style','color:#ff0000');
              details.find("div#thali").addClass('d-none');
            } else {
              details.find("input#status").attr('checked', 'checked');
              details.find("label#status").html('Start');
              details.find("label#status").attr('style','color:#3C5A05');
              details.find("div#thali").removeClass('d-none');
            }
            if (scheds[id]?.menu_item?.sabji?.item !== undefined) {
              details.find("div#sabji").removeClass('d-none');
              details.find("input#sabji").removeAttr('disabled', 'disabled');
              details.find("input#sabjiqty").removeAttr('disabled', 'disabled');
              details.find("label#sabji").html(scheds[id].menu_item.sabji.item);
              details.find("input#sabji").val(scheds[id].menu_item.sabji.item);
              details.find("input#sabjiqty").val(scheds[id].menu_item.sabji.qty);
              if (scheds[id]?.max_item?.sabji?.item !== undefined) {
                if(scheds[id]?.menu_item?.sabji?.qty < scheds[id]?.max_item?.sabji?.qty) {
                  details.find('input#sabjiqty').parent().find(".btn-plus").removeAttr('disabled','disabled');
                } else {
                  details.find('input#sabjiqty').parent().find(".btn-plus").attr('disabled','disabled');
                }
                if(scheds[id]?.menu_item?.sabji?.qty > 0 ) {
                  details.find('input#sabjiqty').parent().find(".btn-minus").removeAttr('disabled','disabled');
                } else {
                  details.find('input#sabjiqty').parent().find(".btn-minus").attr('disabled','disabled');
                }
                details.find("input#sabjiqty").attr('max', scheds[id].max_item.sabji.qty);
              } else {
                details.find('input#sabjiqty').parent().find(".btn-plus").attr('disabled','disabled');
                details.find("input#sabjiqty").attr('max', scheds[id].menu_item.sabji.qty);
              }
            } else {
              details.find("div#sabji").addClass('d-none');
              details.find("input#sabji").attr('disabled', 'disabled');
              details.find("input#sabjiqty").attr('disabled', 'disabled');
              details.find("label#sabji").html('');
              details.find("input#sabji").val('');
              details.find("input#sabjiqty").val('');
              details.find("input#sabjiqty").removeAttr('max');
              details.find('input#sabjiqty').parent().find(".btn-minus").removeAttr('disabled','disabled');
              details.find('input#sabjiqty').parent().find(".btn-plus").removeAttr('disabled','disabled');
            }
            if (scheds[id]?.menu_item?.tarkari?.item !== undefined) {
              details.find("div#tarkari").removeClass('d-none');
              details.find("input#tarkari").removeAttr('disabled', 'disabled');
              details.find("input#tarkariqty").removeAttr('disabled', 'disabled');
              details.find("label#tarkari").html(scheds[id].menu_item.tarkari.item);
              details.find("input#tarkari").val(scheds[id].menu_item.tarkari.item);
              details.find("input#tarkariqty").val(scheds[id].menu_item.tarkari.qty);
              if (scheds[id]?.max_item?.tarkari?.item !== undefined) {
                if(scheds[id]?.menu_item?.tarkari?.qty < scheds[id]?.max_item?.tarkari?.qty) {
                  details.find('input#tarkariqty').parent().find(".btn-plus").removeAttr('disabled','disabled');
                } else {
                  details.find('input#tarkariqty').parent().find(".btn-plus").attr('disabled','disabled');
                }
                if(scheds[id]?.menu_item?.tarkari?.qty > 0 ) {
                  details.find('input#tarkariqty').parent().find(".btn-minus").removeAttr('disabled','disabled');
                } else {
                  details.find('input#tarkariqty').parent().find(".btn-minus").attr('disabled','disabled');
                }
                details.find("input#tarkariqty").attr('max', scheds[id].max_item.tarkari.qty);
              } else {
                details.find('input#tarkariqty').parent().find(".btn-plus").attr('disabled','disabled');
                details.find("input#tarkariqty").attr('max', scheds[id].menu_item.tarkari.qty);
              }
            } else {
              details.find("div#tarkari").addClass('d-none');
              details.find("input#tarkari").attr('disabled', 'disabled');
              details.find("input#tarkariqty").attr('disabled', 'disabled');
              details.find("label#tarkari").html('');
              details.find("input#tarkari").val('');
              details.find("input#tarkariqty").val('');
              details.find("input#tarkari").removeAttr('max');
              details.find('input#tarkariqty').parent().find(".btn-minus").removeAttr('disabled','disabled');
              details.find('input#tarkariqty').parent().find(".btn-plus").removeAttr('disabled','disabled');
            }
            if (scheds[id]?.menu_item?.rice?.item !== undefined) {
              details.find("div#rice").removeClass('d-none');
              details.find("input#rice").removeAttr('disabled', 'disabled');
              details.find("input#riceqty").removeAttr('disabled', 'disabled');
              details.find("label#rice").html(scheds[id].menu_item.rice.item);
              details.find("input#rice").val(scheds[id].menu_item.rice.item);
              details.find("input#riceqty").val(scheds[id].menu_item.rice.qty);
              if (scheds[id]?.max_item?.rice?.item !== undefined) {
                if(scheds[id]?.menu_item?.rice?.qty < scheds[id]?.max_item?.rice?.qty) {
                  details.find('input#riceqty').parent().find(".btn-plus").removeAttr('disabled','disabled');
                } else {
                  details.find('input#riceqty').parent().find(".btn-plus").attr('disabled','disabled');
                } 
                if(scheds[id]?.menu_item?.rice?.qty > 0) {
                  details.find('input#riceqty').parent().find(".btn-minus").removeAttr('disabled','disabled');
                } else {
                  details.find('input#riceqty').parent().find(".btn-minus").attr('disabled','disabled');
                }
                details.find("input#riceqty").attr('max', scheds[id].max_item.rice.qty);
              } else {
                details.find('input#riceqty').parent().find(".btn-plus").attr('disabled','disabled');
                details.find("input#riceqty").attr('max', scheds[id].menu_item.rice.qty);
              }
            } else {
              details.find("div#rice").addClass('d-none');
              details.find("input#rice").attr('disabled', 'disabled');
              details.find("input#riceqty").attr('disabled', 'disabled');
              details.find("label#rice").html('');
              details.find("input#rice").val('');
              details.find("input#riceqty").val('');
              details.find("input#riceqty").removeAttr('max');
              details.find('input#riceqty').parent().find(".btn-minus").removeAttr('disabled','disabled');
              details.find('input#riceqty').parent().find(".btn-plus").removeAttr('disabled','disabled');
            }
            if (scheds[id]?.menu_item?.roti?.item !== undefined) {
              details.find("div#roti").removeClass('d-none');
              details.find("input#roti").removeAttr('disabled', 'disabled');
              details.find("input#rotiqty").removeAttr('disabled', 'disabled');
              details.find("label#roti").html(scheds[id].menu_item.roti.item);
              details.find("input#roti").val(scheds[id].menu_item.roti.item);
              if (scheds[id]?.menu_item?.roti?.qty !== undefined) {
                details.find("input#rotiqty").val(scheds[id].menu_item.roti.qty);
              } else {
                if (scheds[id].thalisize == 'Mini') {
                  details.find("input#rotiqty").val(scheds[id].menu_item.roti.tqty);
                }
                if (scheds[id].thalisize == 'Small') {
                  details.find("input#rotiqty").val(scheds[id].menu_item.roti.sqty);
                }
                if (scheds[id].thalisize == 'Medium') {
                  details.find("input#rotiqty").val(scheds[id].menu_item.roti.mqty);
                }
                if (scheds[id].thalisize == 'Large') {
                  details.find("input#rotiqty").val(scheds[id].menu_item.roti.lqty);
                }
              }
            } else {
              details.find("div#roti").addClass('d-none');
              details.find("input#roti").attr('disabled', 'disabled');
              details.find("input#rotiqty").attr('disabled', 'disabled');
              details.find("label#roti").html('');
              details.find("input#roti").val('');
              details.find("input#rotiqty").val('');
            }
            if (scheds[id]?.menu_item?.extra?.item !== undefined) {
              details.find("div#extra").removeClass('d-none');
              details.find("input#extra").removeAttr('disabled', 'disabled');
              details.find("input#extraqty").removeAttr('disabled', 'disabled');
              details.find("label#extra").html(scheds[id].menu_item.extra.item);
              details.find("input#extra").val(scheds[id].menu_item.extra.item);
              details.find("input#extraqty").val(scheds[id].menu_item.extra.qty);
            } else {
              details.find("div#extra").addClass('d-none');
              details.find("input#extra").attr('disabled', 'disabled');
              details.find("input#extraqty").attr('disabled', 'disabled');
              details.find("label#extra").html('');
              details.find("input#extra").val('');
              details.find("input#extraqty").val('');
            }
            if (CurrentDate > GivenDate) {
              details.find("button.edit-menu").addClass('d-none');
              details.find("button.rsvp-end").removeClass('d-none');
            } else {
              details.find("button.edit-menu").removeClass('d-none');
              details.find("button.rsvp-end").addClass('d-none');
            }
          }
          details.find("#edit").attr("data-id", id);
          details.modal("show");
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
