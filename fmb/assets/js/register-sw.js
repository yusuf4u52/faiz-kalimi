if ("serviceWorker" in navigator) {
  let deferredPrompt;

  window.addEventListener("load", function () {
    navigator.serviceWorker
      .register(pwa_sw.url)
      .then(function (registration) {
        console.log("Service Worker Registered");

        if (registration.active) {
          registration.update();
        }

        if (
          typeof firebase !== "undefined" &&
          typeof pushnotification_load_messaging === "function"
        ) {
          const messaging = firebase.messaging();
          messaging.useServiceWorker(registration);
          pushnotification_load_messaging();
        }

        subOnlineOfflineIndicator();
      })
      .catch(function (error) {
        console.error("Service Worker registration failed:", error);
      });

    /* -------------------------------
   ADD TO HOME SCREEN PROMPT
-------------------------------- */

    window.addEventListener("beforeinstallprompt", (e) => {
      console.log("Install banner ready");

      e.preventDefault();

      deferredPrompt = e;

      const installBtn = document.createElement("button");

      installBtn.innerText = "Install FMB App";
      installBtn.id = "installPWA";

      installBtn.style.position = "fixed";
      installBtn.style.bottom = "20px";
      installBtn.style.right = "20px";
      installBtn.style.padding = "10px 16px";
      installBtn.style.background = "#c36d29";
      installBtn.style.color = "#fff";
      installBtn.style.border = "none";
      installBtn.style.borderRadius = "6px";
      installBtn.style.zIndex = "9999";

      document.body.appendChild(installBtn);

      installBtn.addEventListener("click", async () => {
        installBtn.remove();

        deferredPrompt.prompt();

        const { outcome } = await deferredPrompt.userChoice;

        console.log("User choice:", outcome);

        deferredPrompt = null;
      });
    });

    /* -------------------------------
   ONLINE OFFLINE INDICATOR
-------------------------------- */

    const snackbarTimeToHide = 5000;
    let isOffline = false;
    let snackbarTimeoutHide = null;

    function subOnlineOfflineIndicator() {
      injectSnackbarHtml();
      injectSnackbarCss();
      runOnlineOfflineIndicator();
    }

    function injectSnackbarHtml() {
      if (document.querySelector(".snackbar")) return;

      const container = document.createElement("div");
      container.className = "snackbar";

      const parag = document.createElement("p");
      parag.id = "snackbar-msg";

      container.appendChild(parag);

      document.body.appendChild(container);

      window.addEventListener("online", runOnlineOfflineIndicator);
      window.addEventListener("offline", runOnlineOfflineIndicator);
    }

    function injectSnackbarCss() {
      const css = `
body.snackbar--show .snackbar {
transform: translateY(0);
}

.snackbar {
background:#121213;
color:#fff;
padding:10px;
position:fixed;
bottom:15px;
left:15px;
border-radius:5px;
transform:translateY(150%);
transition:transform 0.2s ease-in-out;
z-index:9999;
}
`;

      const style = document.createElement("style");
      style.appendChild(document.createTextNode(css));
      document.head.appendChild(style);
    }

    function runOnlineOfflineIndicator() {
      if (navigator.onLine) {
        if (isOffline === true) {
          showSnackbar("You're back online");
        }

        isOffline = false;
      } else {
        showSnackbar("You are currently offline");
        isOffline = true;
      }
    }

    function showSnackbar(msg) {
      document.getElementById("snackbar-msg").innerHTML = msg;

      document.body.classList.add("snackbar--show");

      clearTimeout(snackbarTimeoutHide);

      snackbarTimeoutHide = setTimeout(() => {
        document.body.classList.remove("snackbar--show");
      }, snackbarTimeToHide);
    }
  });
}
