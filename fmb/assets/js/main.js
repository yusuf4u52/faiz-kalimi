(($) => {
  "use strict";

  $("a[href='#']").click((e) => e.preventDefault());

  // -----------------------------------------------------------------------
  // Select2 dropdowns
  // -----------------------------------------------------------------------
  $(document).ready(() => {
    if (!$.fn.select2) {
      return;
    }

    $("#addrdistribute .form-select").select2({
      dropdownParent: $("div#addrdistribute"),
      theme: "bootstrap-5",
    });
    $("#addrrecieved .form-select").select2({
      dropdownParent: $("div#addrrecieved"),
      theme: "bootstrap-5",
    });
    $("#society").select2({
      theme: "bootstrap-5",
    });
  });

  // -----------------------------------------------------------------------
  // Shared DataTables config helpers
  // -----------------------------------------------------------------------
  const exportButtonsLayout = () => ({
    topStart: {
      buttons: [
        { extend: "excelHtml5", className: "btn-light" },
        { extend: "print", className: "btn-light" },
      ],
    },
  });

  // Turns each column's footer cell into a live search box.
  // (Behaviour, including the existing keyup-guard, is unchanged.)
  function attachFooterSearch(tableApi) {
    tableApi.columns().every(function () {
      const column = this;
      const title = column.footer().textContent;

      const input = document.createElement("input");
      input.placeholder = title;
      input.className = "form-control form-control-sm";
      column.footer().replaceChildren(input);

      input.addEventListener("keyup", () => {
        if (column.search() !== this.value) {
          column.search(input.value).draw();
        }
      });
    });
  }

  // Inserts a "<strong>group</strong>" divider row whenever column 0 changes.
  function groupRowDrawCallback(colspan) {
    return function () {
      const api = this.api();
      const rows = api.rows({ page: "current" }).nodes();
      let last = null;

      api
        .column(0, { page: "current" })
        .data()
        .each((group, i) => {
          if (last !== group) {
            $(rows)
              .eq(i)
              .before(
                `<tr class="group"><td colspan="${colspan}"><strong>${group}</strong></td></tr>`,
              );
            last = group;
          }
        });
    };
  }

  // -----------------------------------------------------------------------
  // DataTables
  // -----------------------------------------------------------------------
  new DataTable("table.display", {
    responsive: true,
    order: [[0, "desc"]],
  });

  new DataTable("table#rotireport", {
    displayLength: 25,
    responsive: true,
    layout: exportButtonsLayout(),
  });

  const transporterlist = new DataTable("table#transporterlist", {
    displayLength: 25,
    responsive: true,
    columnDefs: [{ searchable: false, orderable: false, targets: 0 }],
    order: [[1, "asc"]],
    layout: exportButtonsLayout(),
    initComplete() {
      attachFooterSearch(this.api());
    },
  });

  transporterlist.on("draw.dt", function () {
    const pageInfo = transporterlist.page.info();
    transporterlist
      .column(0, { page: "current" })
      .nodes()
      .each((cell, i) => {
        cell.innerHTML = i + 1 + pageInfo.start;
      });
  });

  new DataTable("table#userfeedmenu", {
    displayLength: 25,
    responsive: true,
    layout: exportButtonsLayout(),
    initComplete() {
      attachFooterSearch(this.api());
    },
  });

  new DataTable("table#roti", {
    columnDefs: [{ visible: false, targets: 0 }],
    order: [[0, "desc"]],
    displayLength: 25,
    responsive: true,
    layout: exportButtonsLayout(),
    drawCallback: groupRowDrawCallback(6),
  });

  new DataTable("table#thalicount", {
    columnDefs: [{ visible: false, targets: 0 }],
    order: [[0, "desc"]],
    displayLength: 25,
    responsive: true,
    layout: exportButtonsLayout(),
    drawCallback: groupRowDrawCallback(9),
  });

  // -----------------------------------------------------------------------
  // Stop-thali date pickers (user vs admin cutoff times)
  // -----------------------------------------------------------------------
  function initStopDatepicker(
    selector,
    cutoffHour,
    cutoffMinute,
    cutoffSecond,
  ) {
    const now = new Date();
    const tomorrow = new Date();
    tomorrow.setDate(now.getDate() + 1);
    const dayAfterTomorrow = new Date();
    dayAfterTomorrow.setDate(now.getDate() + 2);

    const cutoffTime = new Date();
    cutoffTime.setHours(cutoffHour, cutoffMinute, cutoffSecond);

    const startDate = now < cutoffTime ? tomorrow : dayAfterTomorrow;

    $(`${selector} .input-daterange`).datepicker({
      startDate,
      autoclose: true,
      format: "yyyy-mm-dd",
      daysOfWeekDisabled: 0,
    });

    if (now >= cutoffTime) {
      $(`${selector} .input-daterange`).datepicker("setDate", dayAfterTomorrow);
    }
  }

  $(document).ready(() => {
    initStopDatepicker("#user_stop", 17, 0, 0); // 5:00 pm cutoff
    initStopDatepicker("#admin_stop", 23, 59, 59); // end-of-day cutoff
  });

  $("#rotipayment .input-daterange").datepicker({
    autoclose: true,
    daysOfWeekDisabled: 0,
  });

  // -----------------------------------------------------------------------
  // Society "Other" field toggle
  // -----------------------------------------------------------------------
  $(document).ready(() => {
    const toggleSocietyFields = () => {
      const isOther = $("#society").val() === "Other";
      $("#society_name_wrapper, #society_address_wrapper").toggle(isOther);
      $("#society_name_input, #society_address_input").prop(
        "required",
        isOther,
      );
    };

    toggleSocietyFields();
    $("#society").change(toggleSocietyFields);
  });

  // -----------------------------------------------------------------------
  // Gregorian / Hijri date field conversion
  // -----------------------------------------------------------------------
  $(".gregdate").each(function () {
    const prop = this.tagName === "INPUT" ? "value" : "innerText";
    const hijri = HijriDate.fromGregorian(new Date(this[prop]));
    const isoLike = `${hijri.year}-${+hijri.month + 1}-${hijri.day}`;
    this[prop] = moment(isoLike, "YYYY-MM-DD").format("YYYY-MM-DD");
  });

  $(".hijridate").each(function () {
    const prop = this.tagName === "INPUT" ? "value" : "innerText";
    this[prop] = moment(this[prop], "iYYYY-iM-iD").format("iD iMMMM iYYYY");
  });

  $('[data-key="LazyLoad"]').removeClass("hidden");

  // -----------------------------------------------------------------------
  // Menu type toggle (thaali / miqaat)
  // -----------------------------------------------------------------------
  $(".menu_type").click(function () {
    const type = $(this).val();
    const $form = $(this).closest("form");
    $form.find("div.thaali, div.miqaat").addClass("d-none");
    $form.find(`div.${type}`).removeClass("d-none");
  });

  // -----------------------------------------------------------------------
  // Stop thali (admin)
  // -----------------------------------------------------------------------
  $('[data-key="stopthaali"]').click(function () {
    stopThali_admin(
      $(this).attr("data-thali"),
      $(this).attr("data-active"),
      false,
      false,
    );
  });

  $('[data-key="stoppermanant"]').click(function () {
    if (!confirm("Are you sure you want to permanently stop this thali?"))
      return;

    const clearHub = confirm(
      "Press OK to clear pending hub or CANCEL to go ahead with stop permanent without clearing!",
    )
      ? "true"
      : "false";

    $.post(
      "stop_permanant.php",
      { Thaliid: $(this).data("thali"), clear: clearHub },
      () => {
        alert("Thali Stopped Successfully and Number released to be re-used");
        location.reload();
      },
    );
  });
})(jQuery);

function stopThali_admin(thaaliId, active, hardStop, hardStopComment) {
  let data = `thaali_id=${thaaliId}&active=${active}`;
  if (hardStop) {
    data += `&hardstop=1&hardstopcomment=${hardStopComment}`;
  }

  $.ajax({
    method: "post",
    url: "/fmb/users/_stop_thali_admin.php",
    async: true,
    data,
    success(data) {
      if (data.includes("success")) {
        alert(`Thaali #${thaaliId} Operation Successfull!`);
      } else if (data === "404") {
        alert(
          `Thaali #${thaaliId} does not exists or is already stopped. Contact Mustafa Manawar or Yusuf Rampur for further details.`,
        );
      } else {
        alert(
          `Something went wrong while stopping thaali #${thaaliId}. Please contact Mustafa Manawar or Yusuf Rampur`,
        );
      }
      location.reload();
    },
    error() {
      alert(
        `Something went wrong while stopping thaali #${thaaliId}. Please contact Mustafa Manawar or Yusuf Rampur`,
      );
      location.reload();
    },
  });
}
