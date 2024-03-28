<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="javascript/jquery.dynatable.js?v=1"></script>
<script src="javascript/bootstrap-3.3.6.min.js?v=1"></script>
<script src="javascript/moment-2.11.1-min.js?v=1"></script>
<script src="javascript/moment-hijri.js?v=1"></script>
<script src="javascript/hijriDate.js?v=1"></script>
<script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.5/main.min.js"></script>
<script src="javascript/index.js?v=1"></script>
<script src="src/custom.js?v=1"></script>
<script type="text/javascript">
    if ("serviceWorker" in navigator) {
        window.addEventListener("load", function() {
        navigator.serviceWorker
            .register("/fmb/sw.js")
            .then(res => console.log("service worker registered"))
            .catch(err => console.log("service worker not registered", err))
        })
    }
</script>
