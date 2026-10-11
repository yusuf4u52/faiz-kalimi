document.addEventListener("DOMContentLoaded", function () {
  document
    .querySelectorAll("form[data-follow-up-email]")
    .forEach(function (form) {
      const sleep = (ms) => new Promise((resolve) => setTimeout(resolve, ms));

      async function call(action) {
        const data = new FormData(form); // includes "report"
        data.set("action", action);

        const response = await fetch(form.action, {
          method: "POST",
          body: data,
          credentials: "same-origin",
          headers: { Accept: "application/json" },
        });

        const text = await response.text();
        try {
          return JSON.parse(text);
        } catch (e) {
          throw new Error(
            "Unexpected server response: " + text.slice(0, 150),
          );
        }
      }

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

        let status = form.parentElement.querySelector(".follow-up-status");
        if (!status) {
          status = document.createElement("div");
          status.setAttribute("role", "status");
          status.className = "follow-up-status";
          form.insertAdjacentElement("afterend", status);
        }
        const show = (type, text) => {
          status.className = "follow-up-status alert alert-" + type + " mt-2";
          status.textContent = text;
        };

        button.disabled = true;
        show("info", "Starting...");

        try {
          const start = await call("run");
          if (!start.ok) {
            throw new Error(start.message || "Could not start.");
          }

          if (start.busy) {
            show(
              "warning",
              start.message || "Already running — checking progress...",
            );
          } else if (start.already_done_today) {
            show(
              "success",
              "Already sent today: " + start.sent + " sent, " +
                start.failed + " failed, out of " + start.total + " total.",
            );
            return;
          } else if (start.total === 0) {
            show("warning", "No members with a valid email address to send to.");
            return;
          }

          // Poll status until the background run finishes.
          let networkFailures = 0;

          while (true) {
            await sleep(5000);

            let s;
            try {
              s = await call("status");
              networkFailures = 0;
            } catch (error) {
              networkFailures++;
              if (networkFailures > 6) {
                throw error;
              }
              show(
                "warning",
                "Connection problem, retrying (" + networkFailures + ")...",
              );
              continue;
            }

            if (!s.exists) {
              continue; // campaign file not written yet, keep polling
            }

            show(
              "info",
              "Sent " + s.sent + " of " + s.total +
                " · Failed " + s.failed +
                " · Remaining " + s.pending +
                (s.no_valid_email ? " · " + s.no_valid_email + " skipped (no email)" : ""),
            );

            if (s.status === "done") {
              show(
                s.failed > 0 ? "warning" : "success",
                "Done. " + s.sent + " sent, " + s.failed + " failed" +
                  (s.no_valid_email
                    ? " (" + s.no_valid_email + " skipped: missing/invalid email)"
                    : "") +
                  ".",
              );
              break;
            }
          }
        } catch (error) {
          show(
            "danger",
            error.message +
              ' Click "Email Members" again — anyone already emailed today ' +
              "will not be emailed twice.",
          );
        } finally {
          button.disabled = false;
        }
      });
    });
});
