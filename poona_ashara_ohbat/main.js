(function ($) {
  new DataTable("table#report", {
    displayLength: 25,
    responsive: true,
    layout: {
      topStart: {
        buttons: [
          {
            extend: "excelHtml5",
            className: "btn-light",
          },
          {
            extend: "print",
            className: "btn-light",
          },
        ],
      },
    },
  });
})(jQuery);
