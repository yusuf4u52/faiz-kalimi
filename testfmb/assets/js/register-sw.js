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
       INSTALL BANNER
       Auto-appears on mobile (Android via beforeinstallprompt,
       iOS via manual "Add to Home Screen" instructions since iOS
       has no native install prompt at all).
    -------------------------------- */

    const DISMISS_KEY = "fmb_install_dismissed_at";
    const DISMISS_DAYS = 14;

    const isStandalone =
      window.matchMedia("(display-mode: standalone)").matches ||
      window.navigator.standalone === true;

    const isIos =
      /iPhone|iPad|iPod/i.test(navigator.userAgent) && !window.MSStream;

    const isMobile =
      /Android|iPhone|iPad|iPod/i.test(navigator.userAgent) ||
      window.matchMedia("(max-width: 767px)").matches;

    const addToHomeDisabled = pwa_sw.disable_addtohome === "1";
    const desktopAllowed = !!pwa_sw.enableOnDesktop;

    function recentlyDismissed() {
      const dismissedAt = localStorage.getItem(DISMISS_KEY);
      if (!dismissedAt) return false;
      const daysSince = (Date.now() - Number(dismissedAt)) / 86400000;
      return daysSince < DISMISS_DAYS;
    }

    function rememberDismissal() {
      localStorage.setItem(DISMISS_KEY, String(Date.now()));
    }

    function injectInstallBannerCss() {
      if (document.getElementById("fmb-install-banner-style")) return;

      const css = `
.fmb-install-banner {
  position: fixed;
  left: 0;
  right: 0;
  bottom: 0;
  transform: translateY(110%);
  transition: transform 0.25s ease-in-out;
  background: #fff;
  color: #212529;
  box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.15);
  padding: 12px 16px;
  display: flex;
  align-items: center;
  gap: 12px;
  z-index: 10000;
  font-family: inherit;
}
.fmb-install-banner.fmb-install-banner--show { transform: translateY(0); }
.fmb-install-banner img { width: 40px; height: 40px; border-radius: 8px; flex-shrink: 0; }
.fmb-install-banner__text { flex: 1; min-width: 0; }
.fmb-install-banner__title { font-weight: 600; font-size: 0.95em; margin: 0; }
.fmb-install-banner__subtitle { font-size: 0.8em; color: #6c757d; margin: 2px 0 0; }
.fmb-install-banner__install {
  background: #c36d29;
  color: #fff;
  border: none;
  border-radius: 6px;
  padding: 8px 14px;
  font-size: 0.85em;
  font-weight: 600;
  flex-shrink: 0;
  white-space: nowrap;
}
.fmb-install-banner__close {
  background: none;
  border: none;
  font-size: 1.3em;
  line-height: 1;
  color: #6c757d;
  padding: 4px;
  flex-shrink: 0;
}
@media (min-width: 768px) {
  .fmb-install-banner { left: auto; right: 20px; bottom: 20px; max-width: 360px; border-radius: 10px; }
}
`;

      const style = document.createElement("style");
      style.id = "fmb-install-banner-style";
      style.appendChild(document.createTextNode(css));
      document.head.appendChild(style);
    }

    async function handleInstallClick() {
      hideInstallBanner();
      if (!deferredPrompt) return;

      deferredPrompt.prompt();
      const { outcome } = await deferredPrompt.userChoice;
      console.log("User choice:", outcome);
      deferredPrompt = null;
    }

    function buildBanner({ subtitle, showInstallButton }) {
      injectInstallBannerCss();

      let banner = document.getElementById("fmb-install-banner");
      if (banner) return banner;

      banner = document.createElement("div");
      banner.id = "fmb-install-banner";
      banner.className = "fmb-install-banner";
      banner.innerHTML = `
        <img src="/testfmb/assets/img/logo-192x192.png" alt="" />
        <div class="fmb-install-banner__text">
          <p class="fmb-install-banner__title">Install FMB App</p>
          <p class="fmb-install-banner__subtitle">${subtitle}</p>
        </div>
        ${showInstallButton ? '<button type="button" class="fmb-install-banner__install">Install</button>' : ""}
        <button type="button" class="fmb-install-banner__close" aria-label="Dismiss">&times;</button>
      `;

      document.body.appendChild(banner);

      banner
        .querySelector(".fmb-install-banner__close")
        .addEventListener("click", () => {
          hideInstallBanner();
          rememberDismissal();
        });

      const installBtn = banner.querySelector(".fmb-install-banner__install");
      if (installBtn) installBtn.addEventListener("click", handleInstallClick);

      return banner;
    }

    function showInstallBanner(options) {
      const banner = buildBanner(options);
      requestAnimationFrame(() =>
        banner.classList.add("fmb-install-banner--show"),
      );
    }

    function hideInstallBanner() {
      const banner = document.getElementById("fmb-install-banner");
      if (banner) banner.classList.remove("fmb-install-banner--show");
    }

    function removeInstallBanner() {
      const banner = document.getElementById("fmb-install-banner");
      if (banner) banner.remove();
    }

    // Android / desktop Chrome: native install signal
    window.addEventListener("beforeinstallprompt", (e) => {
      console.log("Install banner ready");
      e.preventDefault();
      deferredPrompt = e;

      if (addToHomeDisabled || isStandalone || recentlyDismissed()) return;
      if (!isMobile && !desktopAllowed) return;

      showInstallBanner({
        subtitle:
          "Add FMB to your home screen for quick, offline-friendly access.",
        showInstallButton: true,
      });
    });

    window.addEventListener("appinstalled", () => {
      console.log("PWA installed");
      removeInstallBanner();
      deferredPrompt = null;
    });

    // iOS Safari: no beforeinstallprompt support at all, so show
    // manual "Add to Home Screen" instructions instead.
    if (isIos && !isStandalone && !addToHomeDisabled && !recentlyDismissed()) {
      showInstallBanner({
        subtitle: 'Tap the Share icon, then "Add to Home Screen".',
        showInstallButton: false,
      });
    }

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
