(function ($) {
  
  $(window).scroll(function () {
    var top = $(document).scrollTop();
    $('.splash').css({
      'background-position': '0px -' + (top / 3).toFixed(2) + 'px'
    });
    if (top > 50)
      $('#home > .navbar').removeClass('navbar-transparent');
    else
      $('#home > .navbar').addClass('navbar-transparent');
  });

  $("a[href='#']").click(function (e) {
    e.preventDefault();
  });

  var $button = $("<div id='source-button' class='btn btn-primary btn-xs'>&lt; &gt;</div>").click(function () {
    var html = $(this).parent().html();
    html = cleanSource(html);
    $("#source-modal pre").text(html);
    $("#source-modal").modal();
  });

  $('.bs-component [data-toggle="popover"]').popover();
  $('.bs-component [data-toggle="tooltip"]').tooltip();

  $(".bs-component").hover(function () {
    $(this).append($button);
    $button.show();
  }, function () {
    $button.hide();
  });

  function cleanSource(html) {
    html = html.replace(/×/g, "&close;")
      .replace(/«/g, "&laquo;")
      .replace(/»/g, "&raquo;")
      .replace(/←/g, "&larr;")
      .replace(/→/g, "&rarr;");

    var lines = html.split(/\n/);

    lines.shift();
    lines.splice(-1, 1);

    var indentSize = lines[0].length - lines[0].trim().length,
      re = new RegExp(" {" + indentSize + "}");

    lines = lines.map(function (line) {
      if (line.match(re)) {
        line = line.substring(indentSize);
      }

      return line;
    });

    lines = lines.join("\n");

    return lines;
  }

  // Edit menu for users
  $('.btn-minus').on('click', function () {
    var $input = $(this).parent().siblings('input');
    var minCount = $input.attr('min');
    var count = parseFloat($input.val());
    if (count <= 1) {
      count = parseFloat($input.val()) - 0.5;
      count = count < minCount ? minCount : count;
    } else {
      count = parseFloat($input.val()) - 1;
      count = count < minCount ? minCount : count;
    }
    $(this).closest('.input-group').find(".btn-plus").removeClass('disabled');
    if(count == minCount) {
      $(this).addClass('disabled');
    }
    $input.val(count);
    $input.change();
    return false;
  })

  $('.btn-plus').on('click', function () {
    var $input = $(this).parent().siblings('input');
    var maxCount = $input.attr('max');
    var count = parseFloat($input.val());
    if (count >= 1) {
      count = parseFloat($input.val()) + 1;
      count = count > maxCount ? maxCount : count;
    } else {
      count = parseFloat($input.val()) + 0.5;
      count = count > maxCount ? maxCount : count;
    }
    $(this).closest('.input-group').find(".btn-minus").removeClass('disabled');
    if(count == maxCount) {
      $(this).addClass('disabled');
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
      if (row.menu_type == 'miqaat') {
        if (row?.menu_item?.miqaat !== undefined) {
          title += row?.menu_item?.miqaat;
        }
      }
      if (row.menu_type == 'thaali') {
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
      selectable: true,
      themeSystem: "bootstrap",
      contentHeight: "auto",
      editable: true,
      events: events,
      eventClick: function (info) {
        $("#changemenu")[0].reset();
        var details = $("#editmenu");
        var id = info.event.id;
        if (!!scheds[id]) {
          details.find("input#menu_id").val(id);
          details.find('.modal-body #validate').remove();
          var GivenDate = new Date(scheds[id].menu_date);
          GivenDate.setDate(GivenDate.getDate() - 1);
          GivenDate.setHours(20, 0, 0, 0);
          var CurrentDate = new Date();
          const menu_date = new Date(scheds[id].menu_date);
          if (scheds[id]?.menu_type == 'miqaat') {
            details.find(".modal-title").html('Miqaat on <strong>' + menu_date.toDateString() + '</strong>');
            details.find("div#sabji").attr('style', 'display:none');
            details.find("div#tarkari").attr('style', 'display:none');
            details.find("div#rice").attr('style', 'display:none');
            details.find("div#roti").attr('style', 'display:none');
            details.find("div#extra").attr('style', 'display:none');
            details.find("button.edit-menu").addClass('hidden');
            details.find("button.rsvp-end").addClass('hidden');
            if (scheds[id]?.menu_item?.miqaat !== undefined) {
              details.find("div#miqaat").removeAttr('style', 'display:none');
              details.find("div#miqaat").html('<h3>' + scheds[id].menu_item.miqaat + '</h3>');
            }
          }
          if (scheds[id]?.menu_type == 'thaali') {
            details.find(".modal-title").html('View/Edit Menu of <strong>' +   menu_date.toDateString() + '</strong>');
            details.find("div#miqaat").attr('style', 'display:none');
            if (scheds[id]?.menu_item?.sabji?.item !== undefined) {
              details.find("div#sabji").removeAttr('style', 'display:none');
              details.find("input#sabji").removeAttr('disabled', 'disabled');
              details.find("input#sabjiqty").removeAttr('disabled', 'disabled');
              details.find("label#sabji").html(scheds[id].menu_item.sabji.item);
              details.find("input#sabji").val(scheds[id].menu_item.sabji.item);
              details.find("input#sabjiqty").val(scheds[id].menu_item.sabji.qty);
              if (scheds[id]?.max_item?.sabji?.item !== undefined) {
                details.find("input#sabjiqty").attr('max', scheds[id].max_item.sabji.qty);
                if(scheds[id]?.menu_item?.sabji?.qty == scheds[id]?.max_item?.sabji?.qty) {
                  details.find('input#sabjiqty').closest('.input-group').find(".btn-plus").addClass('disabled');
                } else if(scheds[id]?.menu_item?.sabji?.qty == 0 ) {
                  details.find('input#sabjiqty').closest('.input-group').find(".btn-minus").addClass('disabled');
                }
              }
            } else {
              details.find("div#sabji").attr('style', 'display:none');
              details.find("input#sabji").attr('disabled', 'disabled');
              details.find("input#sabjiqty").attr('disabled', 'disabled');
              details.find("label#sabji").html('');
              details.find("input#sabji").val('');
              details.find("input#sabjiqty").val('');
              details.find("input#sabjiqty").removeAttr('max');
              details.find('input#sabjiqty').closest('.input-group').find(".btn-minus").removeClass('disabled');
              details.find('input#sabjiqty').closest('.input-group').find(".btn-plus").removeClass('disabled');
            }
            if (scheds[id]?.menu_item?.tarkari?.item !== undefined) {
              details.find("div#tarkari").removeAttr('style', 'display:none');
              details.find("input#tarkari").removeAttr('disabled', 'disabled');
              details.find("input#tarkariqty").removeAttr('disabled', 'disabled');
              details.find("label#tarkari").html(scheds[id].menu_item.tarkari.item);
              details.find("input#tarkari").val(scheds[id].menu_item.tarkari.item);
              details.find("input#tarkariqty").val(scheds[id].menu_item.tarkari.qty);
              if (scheds[id]?.max_item?.tarkari?.item !== undefined) {
                details.find("input#tarkariqty").attr('max', scheds[id].max_item.tarkari.qty);
                if(scheds[id]?.menu_item?.tarkari?.qty == scheds[id]?.max_item?.tarkari?.qty) {
                  details.find('input#tarkariqty').closest('.input-group').find(".btn-plus").addClass('disabled');
                } else if(scheds[id]?.menu_item?.tarkari?.qty == 0 ) {
                  details.find('input#tarkariqty').closest('.input-group').find(".btn-minus").addClass('disabled');
                }
              }
            } else {
              details.find("div#tarkari").attr('style', 'display:none');
              details.find("input#tarkari").attr('disabled', 'disabled');
              details.find("input#tarkariqty").attr('disabled', 'disabled');
              details.find("label#tarkari").html('');
              details.find("input#tarkari").val('');
              details.find("input#tarkariqty").val(''); 
              details.find("input#tarkari").removeAttr('max');
              details.find('input#tarkariqty').closest('.input-group').find(".btn-minus").removeClass('disabled');
              details.find('input#tarkariqty').closest('.input-group').find(".btn-plus").removeClass('disabled');
            }
            if (scheds[id]?.menu_item?.rice?.item !== undefined) {
              details.find("div#rice").removeAttr('style', 'display:none');
              details.find("input#rice").removeAttr('disabled', 'disabled');
              details.find("input#riceqty").removeAttr('disabled', 'disabled');
              details.find("label#rice").html(scheds[id].menu_item.rice.item);
              details.find("input#rice").val(scheds[id].menu_item.rice.item);
              details.find("input#riceqty").val(scheds[id].menu_item.rice.qty);
              if (scheds[id]?.max_item?.rice?.item !== undefined) {
                details.find("input#riceqty").attr('max', scheds[id].max_item.rice.qty);
                if(scheds[id]?.menu_item?.rice?.qty == scheds[id]?.max_item?.rice?.qty) {
                  details.find('input#riceqty').closest('.input-group').find(".btn-plus").addClass('disabled');
                } else if(scheds[id]?.menu_item?.rice?.qty == 0) {
                  details.find('input#riceqty').closest('.input-group').find(".btn-minus").addClass('disabled');
                }
              }
            } else {
              details.find("div#rice").attr('style', 'display:none');
              details.find("input#rice").attr('disabled', 'disabled');
              details.find("input#riceqty").attr('disabled', 'disabled');
              details.find("label#rice").html('');
              details.find("input#rice").val('');
              details.find("input#riceqty").val('');
              details.find("input#riceqty").removeAttr('max');
              details.find('input#riceqty').closest('.input-group').find(".btn-minus").removeClass('disabled');
              details.find('input#riceqty').closest('.input-group').find(".btn-plus").removeClass('disabled');
            }
            if (scheds[id]?.menu_item?.roti?.item !== undefined) {
              details.find("div#roti").removeAttr('style', 'display:none');
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
              details.find("div#roti").attr('style', 'display:none');
              details.find("input#roti").attr('disabled', 'disabled');
              details.find("input#rotiqty").attr('disabled', 'disabled');
              details.find("label#roti").html('');
              details.find("input#roti").val('');
              details.find("input#rotiqty").val('');
            }
            if (scheds[id]?.menu_item?.extra?.item !== undefined) {
              details.find("div#extra").removeAttr('style', 'display:none');
              details.find("input#extra").removeAttr('disabled', 'disabled');
              details.find("input#extraqty").removeAttr('disabled', 'disabled');
              details.find("label#extra").html(scheds[id].menu_item.extra.item);
              details.find("input#extra").val(scheds[id].menu_item.extra.item);
              details.find("input#extraqty").val(scheds[id].menu_item.extra.qty);
            } else {
              details.find("div#extra").attr('style', 'display:none');
              details.find("input#extra").attr('disabled', 'disabled');
              details.find("input#extraqty").attr('disabled', 'disabled');
              details.find("label#extra").html('');
              details.find("input#extra").val('');
              details.find("input#extraqty").val('');
            }
            if (CurrentDate > GivenDate) {
              details.find("button.edit-menu").addClass('hidden');
              details.find("button.rsvp-end").removeClass('hidden');
            } else {
              details.find("button.edit-menu").removeClass('hidden');
              details.find("button.rsvp-end").addClass('hidden');
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
