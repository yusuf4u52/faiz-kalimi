(function ($) {
  $('[data-key="LazyLoad" ]').removeClass("hidden");
  var els = $(".gregdate");
  for (var i = 0; i < els.length; i++) {
    var el = els[i];
    var prop = el.tagName == "INPUT" ? "value" : "innerText";
    var greg = el[prop];
    var hijri = HijriDate.fromGregorian(new Date(greg));
    el[prop] = hijri.year + "-" + (+hijri.month + +1) + "-" + hijri.day;
    el[prop] = moment(el[prop], "YYYY-MM-DD").format("YYYY-MM-DD");
  }

  var els = $(".hijridate");
  for (var i = 0; i < els.length; i++) {
    var el = els[i];
    var prop = el.tagName == "INPUT" ? "value" : "innerText";
    var hijri = el[prop];
    el[prop] = moment(hijri, "iYYYY-iM-iD").format("iD iMMMM iYYYY");
  }

  $('.menu_type').click(function () {
    var type = $(this).val();
    $(this).closest('form').find('div.thaali').addClass('hidden');
    $(this).closest('form').find('div.miqaat').addClass('hidden');
    $(this).closest('form').find('div.' + type).removeClass('hidden');
  });
})(jQuery);

function stopThali_admin(
  thaaliId,
  active,
  hardStop,
  hardStopComment,
  successCallback,
  failureCallback
) {
  var data = "thaali_id=" + thaaliId + "&active=" + active;
  if (hardStop) {
    data += "&hardstop=1&hardstopcomment=" + hardStopComment;
  }
  $.ajax({
    method: "post",
    url: "_stop_thali_admin.php",
    async: true,
    data: data,
    success: function (data) {
      if (data.includes("success")) {
        alert("Thaali #" + thaaliId + " Operation Successfull!");
      } else if (data === "404") {
        alert(
          "Thaali #" +
          thaaliId +
          " does not exists or is already stopped. Contact Mustafa Manawar or Yusuf Rampur for further details."
        );
      } else {
        alert(
          "Something went wrong while stopping thaali #" +
          thaaliId +
          ". Please contact Mustafa Manawar or Yusuf Rampur"
        );
      }
      if (successCallback) {
        successCallback(data);
      }
    },
    error: function () {
      alert(
        "Something went wrong while stopping thaali #" +
        thaaliId +
        ". Please contact Mustafa Manawar or Yusuf Rampur"
      );
      if (failureCallback) {
        failureCallback();
      }
    },
  });
}

