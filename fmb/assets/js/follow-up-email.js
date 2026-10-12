document.addEventListener("DOMContentLoaded", function () {
  document
    .querySelectorAll("form[data-follow-up-email]")
    .forEach(function (form) {
      const sleep = (ms) => new Promise((resolve) => setTimeout(resolve, ms));
      const button = form.querySelector('button[type="submit"]');

      // One status box per form, reused across clicks.
      let status = form.parentElement.querySelector(".follow-up-status");
      if (!status) {
        status = document.createElement("div");
        status.setAttribute("role", "status");
        status.className = "follow-up-status d-none";
        form.insertAdjacentElement("afterend", status);
      }
      const show = (type, text) => {
        status.className = "follow-up-status alert alert-" + type + " mt-2";
        status.textContent = text;
      };

      // Every request is short on the server side (about 20s at most). A 504,
      // a dropped connection or a timeout is treated as temporary: the server
      // keeps its own record of who was emailed, so asking again is safe.
      async function call(action) {
        const data = new FormData(form); // includes "report"
        data.set("action", action);

        const controller = new AbortController();
        const timer = setTimeout(() => controller.abort(), 55000);
        let response, text;
        try {
          response = await fetch(form.action, {
            method: "POST",
            body: data,
            credentials: "same-origin",
            headers: { Accept: "application/json" },
            signal: controller.signal,
          });
          text = await response.text();
        } catch (error) {
          const e = new Error("Could not reach the server (" + error.message + ").");
          e.transient = true;
          throw e;
        } finally {
          clearTimeout(timer);
        }

        let json = null;
        try {
          json = JSON.parse(text);
        } catch (ignore) {}

        if (json === null) {
          const e = new Error(
            response.status === 504 || response.status === 502 || response.status === 503
              ? "The server took too long to answer (HTTP " + response.status + ")."
              : "Unexpected server response (HTTP " + response.status + ").",
          );
          e.transient = true;
          throw e;
        }
        if (!response.ok || json.ok === false) {
          throw new Error(json.message || "The server returned HTTP " + response.status + ".");
        }
        return json;
      }

      async function callWithRetry(action, tries) {
        for (let attempt = 1; ; attempt++) {
          try {
            return await call(action);
          } catch (error) {
            if (!error.transient || attempt >= tries) {
              throw error;
            }
            show("warning", error.message + " Retrying (" + attempt + ")...");
            await sleep(Math.min(30000, attempt * 5000));
          }
        }
      }

      const line = (s) =>
        "Sent " + s.sent + " of " + s.total +
        " · Failed " + s.failed +
        " · Remaining " + s.pending +
        (s.no_valid_email ? " · " + s.no_valid_email + " skipped (no valid email)" : "") +
        (s.imported ? " · " + s.imported + " had already been emailed earlier today" : "");

      // On page load, show where today's run stands (does not send anything).
      (async function showExisting() {
        try {
          const s = await call("status");
          if (!s.exists) {
            return;
          }
          if (s.status === "done") {
            show(
              s.failed > 0 ? "warning" : "success",
              "Today's run is finished. " + line(s),
            );
          } else {
            show("info", "Unfinished run from " + s.date + ". " + line(s) + ' — click "Email Members" to continue.');
          }
        } catch (ignore) {}
      })();

      form.addEventListener("submit", async function (event) {
        event.preventDefault();

        if (!window.confirm("Send pending amount emails to all members in this list?")) {
          return;
        }

        button.disabled = true;
        show("info", "Preparing recipient list...");

        try {
          const start = await callWithRetry("start", 4);

          if (start.total === 0) {
            show("warning", "No members with a valid email address to send to.");
            return;
          }
          if (start.status === "done") {
            show(
              start.failed > 0 ? "warning" : "success",
              "Already finished today — nobody is emailed twice. " + line(start),
            );
            return;
          }

          let failures = 0;
          let s = start;

          while (true) {
            try {
              s = await call("work");
              failures = 0;
            } catch (error) {
              if (!error.transient || ++failures > 8) {
                throw error;
              }
              show("warning", error.message + " Retrying (" + failures + ")...");
              await sleep(Math.min(30000, failures * 5000));
              continue;
            }

            if (s.exists === false) {
              throw new Error("The queue file could not be found on the server.");
            }

            show("info", line(s) + (s.errors && s.errors.length ? " · Last error: " + s.errors[0] : ""));

            if (s.status === "done") {
              break;
            }

            // Someone else (cron or another tab) is sending right now: just watch.
            let wait = s.busy ? 3 : Math.max(Number(s.wait || 0), 0);
            if (wait < 1) {
              await sleep(300);
              continue;
            }
            while (wait > 0) {
              if (wait > 5) {
                show(
                  "warning",
                  line(s) + " · Pausing " + wait + "s before the next attempt (the mail server is being paced). " +
                    "You can keep this tab open.",
                );
              }
              await sleep(1000);
              wait--;
            }
          }

          show(
            s.failed > 0 ? "warning" : "success",
            "Done. " + s.sent + " sent, " + s.failed + " failed" +
              (s.no_valid_email ? " (" + s.no_valid_email + " skipped: missing/invalid email)" : "") + "." +
              (s.failed > 0 && s.errors && s.errors.length ? " Example error: " + s.errors[0] : ""),
          );
        } catch (error) {
          show(
            "danger",
            error.message +
              ' Click "Email Members" again to carry on — anyone already emailed will not be emailed twice.',
          );
        } finally {
          button.disabled = false;
        }
      });
    });
});
