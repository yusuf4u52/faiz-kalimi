document.addEventListener("DOMContentLoaded", function () {
  document
    .querySelectorAll("form[data-follow-up-email]")
    .forEach(function (form) {
      form.addEventListener("submit", async function (event) {
        event.preventDefault();

        if (
          !window.confirm(
            "Send pending amount emails to all members in this list?",
          )
        ) {
          return;
        }

        const button = form.querySelector('button[type="submit"]');
        const originalText = button.textContent;
        const status = document.createElement("div");
        status.className = "alert alert-info mt-2";
        status.setAttribute("role", "status");
        form.insertAdjacentElement("afterend", status);
        button.disabled = true;
        status.textContent = "Sending emails...";

        let offset = 0;
        let sent = 0;
        let failed = 0;

        try {
          do {
            const data = new FormData(form);
            data.append("batch", "1");
            data.append("batch_size", "5");
            data.append("offset", String(offset));

            const response = await fetch(form.action, {
              method: "POST",
              body: data,
              credentials: "same-origin",
              headers: { Accept: "application/json" },
            });

            if (!response.ok) {
              throw new Error(
                "The server returned HTTP " + response.status + ".",
              );
            }

            const result = await response.json();
            if (!result.ok) {
              throw new Error(result.message || "The email request failed.");
            }

            sent += Number(result.sent || 0);
            failed += Number(result.failed || 0);
            offset = Number(result.next_offset || offset);
            status.textContent =
              "Sending emails... " + sent + " sent, " + failed + " failed.";

            if (!result.has_more) {
              break;
            }
          } while (true);

          status.className = "alert alert-success mt-2";
          status.textContent = sent + " email(s) sent, " + failed + " failed.";
        } catch (error) {
          status.className = "alert alert-danger mt-2";
          status.textContent =
            error.message + " The completed batches were not repeated.";
        } finally {
          button.disabled = false;
          button.textContent = originalText;
        }
      });
    });
});
