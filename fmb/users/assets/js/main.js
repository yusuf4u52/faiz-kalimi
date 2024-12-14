(function ($) {

    $("a[href='#']").click(function (e) {
        e.preventDefault();
    });

    new DataTable('table.display', {
        responsive: true,
        ordering: false,
    });

    $(document).ready(function() {
        var now = new Date();
        
        // Define tomorrow's date
        var tomorrow = new Date();
        tomorrow.setDate(now.getDate() + 1);

        var dayAfterTomorrow = new Date();
        dayAfterTomorrow.setDate(now.getDate() + 2);
        
        // Define the time limit for today
        var cutoffTime = new Date();
        cutoffTime.setHours(18, 0, 0); // 8:00 pm

        var acutoffTime = new Date();
        acutoffTime.setHours(23, 59, 59); // 12:00 pm
        
        // Determine if tomorrow should be selectable
        var startDate = now < cutoffTime ? tomorrow : dayAfterTomorrow;

        var astartDate = now < acutoffTime ? tomorrow : dayAfterTomorrow;
        
        // Initialize datepicker
        $('#user_stop .input-daterange').datepicker ({
            startDate: startDate,
            autoclose: true,
            daysOfWeekDisabled: 0
        });

        $('#admin_stop .input-daterange').datepicker ({
            startDate: astartDate,
            autoclose: true,
            daysOfWeekDisabled: 0
        });
        
        // Disable tomorrow if after cutoff time
        if (now >= cutoffTime) {
            $('#user_stop .input-daterange').datepicker('setDate', dayAfterTomorrow); // Reset selected date if after 8 pm
        }

        if (now >= acutoffTime) {
            $('#admin_stop .input-daterange').datepicker('setDate', dayAfterTomorrow); // Reset selected date if after 8 pm
        }
    });

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
        $(this).closest('form').find('div.thaali').addClass('d-none');
        $(this).closest('form').find('div.miqaat').addClass('d-none');
        $(this).closest('form').find('div.' + type).removeClass('d-none');
    });

    var receiptForm = $('#receiptForm');
    receiptForm.hide();
    $('[data-key="payhoob"]').click(function () {
        $('[name="receipt_thali"]', receiptForm).val($(this).attr('data-thali'));
        receiptForm.show();
    });
    
    $('[name="save"]').click(function () {
        var data = '';
        $('input[type!="button"]', receiptForm).each(function () {
            data = data + $(this).attr('name') + '=' + $(this).val() + '&';
        });
        data = data + "payment_type=" + $('#payment_type').val();
        $.ajax({
            method: 'post',
            url: '_payhoob.php',
            async: 'false',
            data: data,
            success: function (data) {
                if (data.includes("Success")) {
                    alert('Hoob sucessfully updated.');
                    receiptForm.hide();
                    location.reload();
                    // } else if(data == 'DuplicateReceiptNo') {
                    //   alert('Receipt number already exists in database');
                } else {
                    alert(data);
                }
            },
            error: function () {
                alert('Try again');
            }
        });
    });

    $('[name="cancel"]').click(function () {
        receiptForm.hide();
    });

    $('[data-key="stoppermanant"]').click(function () {
        var c = confirm("Are you sure you want to permanently stop this thali?");
        if (c == false) {
            return;
        }
        var clearHub;
        var r = confirm("Press OK to clear pending hub or CANCEL to go ahead with stop permanent without clearing!");
        if (r == true) {
            clearHub = "true";
        } else {
            clearHub = "false";
        }
        $.post("stop_permanant.php", {
            Thaliid: $(this).data("thali"),
            clear: clearHub
        },
            function (data, status) {
                alert("Thali Stopped Successfully and Number released to be re-used");
                location.reload();
            });
    });

    $('#payment_type').on('change', function () {
        if ($(this).val() === "Cash") {
            $("#transaction_id").hide()
        } else {
            $("#transaction_id").show()
        }
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