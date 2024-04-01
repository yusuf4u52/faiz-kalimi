if ('serviceWorker' in navigator) {
    var offline_message = 1;
    window.addEventListener('load', function () {
        navigator.serviceWorker.register('/fmb/sw.js')
            .then(function (registration) {
                console.log('Service worker ready');
                if (registration.active) {
                    registration.update();
                }
                subOnlineOfflineIndicator();
            })
            .catch(function (error) { console.log('Registration failed with ' + error); });

        /****************** Start : Online/Offline Indicator ******************/

        // Variables & default values
        const snackbarTimeToHide = 5000; // 5s
        let isOffline = false,
            snackbarTimeoutHide = null,
            goOfflineMsg = 'You\'re offline <a href="javascript:location.reload()">refresh</a>',
            backOnlineMsg = 'You\'re back online <a href="javascript:location.reload()">refresh</a>';

        /**
        * Subscribe to online offline indicator
        */
        function subOnlineOfflineIndicator() {
            injectSnackbarHtml();
            injectSnackbarCss();
            runOnlineOfflineIndicator();
        }

        /**
         * Inject html of snackbar
         */
        function injectSnackbarHtml() {
            const container = document.createElement('div');
            container.className = 'snackbar';

            const parag = document.createElement('p');
            parag.id = 'snackbar-msg';
            container.appendChild(parag);

            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'snackbar-close';
            button.setAttribute('aria-label', 'Close ×');
            button.addEventListener('click', hideSnackbar);
            button.innerHTML = '&times;';

            container.appendChild(button);

            document.body.appendChild(container);

            window.addEventListener('online', runOnlineOfflineIndicator);
            window.addEventListener('offline', runOnlineOfflineIndicator);

            window.addEventListener('fetch', () => console.log("fetch"));

            // Clean snackbarTimeToHide varibale when user hover on the snackbar to prevent hide it
            container.addEventListener('mouseover', function () {
                if (snackbarTimeoutHide !== null)
                    clearTimeout(snackbarTimeoutHide);
            });

            // Call setTimeout and set snackbarTimeToHide variable to hide snackbar
            container.addEventListener('mouseout', function () {
                if (snackbarTimeoutHide !== null)
                    snackbarTimeoutHide = setTimeout(hideSnackbar, snackbarTimeToHide / 2);
            });
        }

        /**
         * Inject style css of snackbar
         */
        function injectSnackbarCss() {
            const css = `body.snackbar--show .snackbar {
              -webkit-transform: translateY(0);
              transform: translateY(0); 
          }
          .snackbar {
              box-sizing: border-box;
              background-color: #121213;
              color: #fff;
              padding: 10px 55px 10px 10px;
              position: fixed;
              z-index: 9999999999999999;
              left: 15px;
              bottom: 15px;
              border-radius: 5px 8px 8px 5px;
              max-width: 90%;
              min-height: 48px;
              line-height: 28px;
              font-size: 16px;
              -webkit-transform: translateY(150%);
              transform: translateY(150%);
              will-change: transform;
              -webkit-transition: -webkit-transform 200ms ease-in-out;
              -webkit-transition-delay: 0s;
                      transition-delay: 0s;
              -webkit-transition: -webkit-transform 200ms ease-in-out false;
              transition: -webkit-transform 200ms ease-in-out false;
              transition: transform 200ms ease-in-out false;
              transition: transform 200ms ease-in-out false, -webkit-transform 200ms ease-in-out false; 
          }
          .snackbar p {
              margin: 0;
              color: #fff;
              text-align: center; 
          }
          .snackbar .snackbar-close {
              position: absolute;
              top: 0;
              right: 0;
              width: 45px;
              height: 100%;
              padding: 0;
              background: #2a2a2a;
              border: none;
              font-size: 28px;
              font-weight: normal;
              border-radius: 0 5px 5px 0;
              color: #FFF;
              font-family: Arial, Helvetica, sans-serif;
          }
          .snackbar .snackbar-close:hover,
          .snackbar .snackbar-close:focus {
              background: #3f3f3f;
          }
          .snackbar a {
              color: #FFF;
              font-weight: bold;
              text-decoration: underline; 
          }`;

            const head = document.head || document.getElementsByTagName('head')[0];
            const style = document.createElement('style');

            style.type = 'text/css';
            if (style.styleSheet) {
                // This is required for IE8 and below.
                style.styleSheet.cssText = css;
            } else {
                style.appendChild(document.createTextNode(css));
            }

            head.appendChild(style);
        }

        /**
         * Show the state of the mode of connectivity to the user :  back onLine | go offLine
         */
        function runOnlineOfflineIndicator() {
            if (navigator.onLine) {
                if (isOffline === true) {
                    showSnackbar(backOnlineMsg);
                }
                isOffline = false;
            } else {
                if (offline_message == 1) {
                    showSnackbar(goOfflineMsg);
                    isOffline = true;
                }
            }
        }

        /**
         * Show with the given message in the snackbar
         * @param {String} msg 
         */
        function showSnackbar(msg) {
            document.getElementById('snackbar-msg').innerHTML = msg;
            document.body.classList.add('snackbar--show');

            clearTimeout(snackbarTimeoutHide);
            snackbarTimeoutHide = setTimeout(hideSnackbar, snackbarTimeToHide);
        }

        /**
        * Hide snackbar
        */
        function hideSnackbar() {
            document.body.classList.remove('snackbar--show');
        }

        /****************** End : Online/Offline Indicator ******************/

    });
}

window.addEventListener('load', function () {
    var manifestLink = document.querySelectorAll("link[rel='manifest']");
    if (manifestLink.length > 1) {
        for (var i = 0; i < manifestLink.length; i++) {
            var href = manifestLink[i].getAttribute("href");
            if (href.indexOf("manifest.json") == -1) {
                manifestLink[i].remove();
            }
        }
    }
    // fix for href="#" in Ios safari standalone
    var ua = window.navigator.userAgent;
    var iOS = ua.match(/iPad/i) || ua.match(/iPhone/i);
    var webkit = ua.match(/WebKit/i);
    var iOSSafari = iOS && webkit && !ua.match(/CriOS/i);

    if (iOSSafari && (window.matchMedia('(display-mode: standalone)').matches)) {
        setTimeout(function () {
            const anchor_fix = document.querySelectorAll("a[href='#']");
            if (anchor_fix.length > 1) {
                for (var i = 0; i < anchor_fix.length; i++) {
                    anchor_fix[i].setAttribute("href", "javascript:void(0);");
                }
            }
        }, 600);
    }
});