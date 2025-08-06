"use strict";
(function ($) {
  new DataTable("table#thali", {
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
    initComplete: function () {
      this.api()
        .columns()
        .every(function () {
          let column = this;
          let title = column.footer().textContent;

          // Create input element
          let input = document.createElement("input");
          input.placeholder = title;
          input.className = "form-control form-control-sm";
          column.footer().replaceChildren(input);

          // Event listener for user input
          input.addEventListener("keyup", () => {
            if (column.search() !== this.value) {
              column.search(input.value).draw();
            }
          });
        });
    },
  });
})(jQuery);
