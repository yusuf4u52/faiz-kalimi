<?php
/**
 * Bulk-assign FMB Musaid: shared helpers + the Assign bar/modal for an EXISTING DataTable.
 * Used by the list pages and by musaid-assign.php (the save endpoint).
 *
 * Page usage:
 *   require_once('../musaid-bulk.php');            // near the top, after helpers.php
 *   first <th>  : <?php echo musaid_all_checkbox(); ?>Sabeel No
 *   first <td>  : <?php echo musaid_checkbox($values['thali_pk']); ?>...
 *   Musaid <th> : add the attribute  data-musaid-col
 *   Musaid <td> : <?php echo e(musaid_label($values['fmb_musaid'] ?? null)); ?>
 *   before footer: <?php musaid_bulk_ui(); ?>
 */
require_once __DIR__ . '/helpers.php';

// Users whose users.role contains this text (case-insensitive) are FMB Musaids.
// Change it here if your role value is spelled differently.
const MUSAID_ROLE_LIKE = '%musaid%';

/** [user id => display name] of every user with the FMB Musaid role. */
function musaid_users(): array
{
    static $cache = null;
    if ($cache !== null) {
        return $cache;
    }
    $cache = [];
    $res = db_query($GLOBALS['link'], "SELECT * FROM users ORDER BY name ASC");
    while ($u = mysqli_fetch_assoc($res)) {
        $label = '';
        foreach (['name', 'full_name', 'fullname', 'username', 'user_name', 'email'] as $k) {
            if (!empty($u[$k])) {
                $label = (string) $u[$k];
                break;
            }
        }
        $cache[(int) $u['id']] = $label !== '' ? $label : 'User #' . $u['id'];
    }
    asort($cache, SORT_NATURAL | SORT_FLAG_CASE);
    return $cache;
}

/** Name shown in the FMB Musaid column for a stored fmb_musaid value. */
function musaid_label($id): string
{
    if ($id === null || $id === '' || (int) $id === 0) {
        return '';
    }
    return musaid_users()[(int) $id] ?? (string) $id;
}

function musaid_can_assign(): bool
{
    return user_email_in(FOLLOW_UP_ACCESS_EMAILS);
}

function musaid_checkbox($thaliId): string
{
    return musaid_can_assign()
        ? '<input type="checkbox" class="form-check-input musaid-row me-2" value="' . (int) $thaliId . '" aria-label="Select member">'
        : '';
}

function musaid_all_checkbox(): string
{
    return musaid_can_assign()
        ? '<input type="checkbox" class="form-check-input musaid-all me-2" aria-label="Select all members">'
        : '';
}

/** The floating "N selected - Assign" bar, the Musaid modal, and the script. */
function musaid_bulk_ui(string $endpoint = '../musaid-assign.php'): void
{
    if (!musaid_can_assign()) {
        return;
    }
    $users = musaid_users();
    ?>
    <div id="musaid-bar" class="d-none align-items-center gap-3 bg-body border rounded shadow px-3 py-2"
         style="position:fixed;bottom:16px;left:50%;transform:translateX(-50%);z-index:1040">
        <span><strong id="musaid-count">0</strong> selected</span>
        <button type="button" class="btn btn-primary btn-sm" id="musaid-open">Assign FMB Musaid</button>
    </div>

    <div class="modal fade" id="musaid-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Assign FMB Musaid</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <?php if ($users) { ?>
                        <label for="musaid-select" class="form-label">Assign the selected members to:</label>
                        <select id="musaid-select" class="form-select">
                            <option value="">Select FMB Musaid</option>
                            <?php foreach ($users as $id => $label) { ?>
                                <option value="<?php echo (int) $id; ?>"><?php echo e($label); ?></option>
                            <?php } ?>
                        </select>
                    <?php } else { ?>
                        <p class="mb-0 text-danger">No users with the FMB Musaid role were found.</p>
                        <select id="musaid-select" class="d-none"></select>
                    <?php } ?>
                    <div id="musaid-msg" class="mt-3 small"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="musaid-confirm"<?php echo $users ? '' : ' disabled'; ?>>Confirm</button>
                </div>
            </div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function () {
        var ENDPOINT = <?php echo json_encode($endpoint, JSON_UNESCAPED_SLASHES); ?>;
        var TABLE = '#transporterlist';
        var bar = document.getElementById('musaid-bar');
        var countEl = document.getElementById('musaid-count');
        var modalEl = document.getElementById('musaid-modal');
        var select = document.getElementById('musaid-select');
        var msg = document.getElementById('musaid-msg');
        var confirmBtn = document.getElementById('musaid-confirm');

        function api() {
            return (window.jQuery && jQuery.fn.dataTable && jQuery.fn.dataTable.isDataTable(TABLE))
                ? jQuery(TABLE).DataTable() : null;
        }
        function esc(s) {
            return String(s).replace(/[&<>"']/g, function (c) {
                return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
            });
        }
        // Checkboxes of the rows matching the current search, across ALL pages.
        function filteredBoxes() {
            var t = api();
            var rows = t ? t.rows({ search: 'applied' }).nodes().toArray()
                         : Array.prototype.slice.call(document.querySelectorAll(TABLE + ' tbody tr'));
            var out = [];
            rows.forEach(function (tr) {
                var b = tr.querySelector('.musaid-row');
                if (b) out.push(b);
            });
            return out;
        }
        function selected() {
            return filteredBoxes().filter(function (b) { return b.checked; });
        }
        function refresh() {
            var boxes = filteredBoxes();
            var n = boxes.filter(function (b) { return b.checked; }).length;
            countEl.textContent = n;
            bar.classList.toggle('d-none', n === 0);
            bar.classList.toggle('d-flex', n > 0);
            document.querySelectorAll('.musaid-all').forEach(function (a) {
                a.checked = boxes.length > 0 && n === boxes.length;
                a.indeterminate = n > 0 && n < boxes.length;
            });
        }
        function say(text, isError) {
            msg.className = 'mt-3 small ' + (isError ? 'text-danger' : 'text-success');
            msg.textContent = text;
        }
        function applyLabel(boxes, label) {
            var th = document.querySelector(TABLE + ' thead th[data-musaid-col]');
            if (!th) { location.reload(); return; }
            var col = th.cellIndex, t = api();
            boxes.forEach(function (b) {
                var tr = b.closest('tr');
                if (t) t.cell(tr, col).data(esc(label));
                else tr.cells[col].textContent = label;
            });
            if (t) t.draw(false); // keeps the current search and page
        }

        // Clicking the select-all box must not sort the column it sits in.
        document.addEventListener('click', function (e) {
            if (e.target.closest && e.target.closest('.musaid-all')) e.stopPropagation();
        }, true);
        document.addEventListener('change', function (e) {
            var el = e.target;
            if (el.classList.contains('musaid-all')) {
                filteredBoxes().forEach(function (b) { b.checked = el.checked; });
                refresh();
            } else if (el.classList.contains('musaid-row')) {
                refresh();
            }
        });
        if (window.jQuery) jQuery(document).on('draw.dt', TABLE, refresh);

        document.getElementById('musaid-open').addEventListener('click', function () {
            msg.textContent = '';
            select.value = '';
            bootstrap.Modal.getOrCreateInstance(modalEl).show();
        });

        confirmBtn.addEventListener('click', function () {
            var boxes = selected();
            if (!boxes.length) { say('Select at least one member.', true); return; }
            if (!select.value) { say('Choose an FMB Musaid.', true); return; }

            var body = new URLSearchParams();
            body.append('musaid', select.value);
            boxes.forEach(function (b) { body.append('ids[]', b.value); });

            confirmBtn.disabled = true;
            fetch(ENDPOINT, {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: body
            })
            .then(function (r) {
                return r.json().catch(function () { return { ok: false, message: 'Unexpected response from the server.' }; });
            })
            .then(function (data) {
                if (!data.ok) { say(data.message || 'Assignment failed.', true); return; }
                applyLabel(boxes, data.label);
                boxes.forEach(function (b) { b.checked = false; });
                bootstrap.Modal.getOrCreateInstance(modalEl).hide();
                refresh();
            })
            .catch(function () { say('Network error. Nothing was changed.', true); })
            .finally(function () { confirmBtn.disabled = false; });
        });
    });
    </script>
    <?php
}
