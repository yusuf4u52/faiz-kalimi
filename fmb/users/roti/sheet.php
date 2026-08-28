<?php
include('../header.php');
include('../navbar.php');
require_once('helpers.php');
include('../getHijriDate.php');

// Load the current Monday first; navigation happens client-side afterward.
$initialWeekStart = week_start_monday(date('Y-m-d'));
$currentDate = date('Y-m-d');
$currentHijriFullDate = getHijriFullDate($currentDate);
$currentHijriMonthYear = preg_replace('/^\d+\s+/', '', $currentHijriFullDate);
?>

<div class="card">
    <div class="card-body">
        <div class="row align-items-center">
            <div class="col-6">
                <h2 class="mb-3">FMB Roti Sheet</h2>
            </div>
            <div class="col-6 text-end">
                <div class="roti-current-period" aria-label="Current month">
                    <h3><span id="roti-gregorian-month"><?php echo htmlspecialchars(date('F Y'), ENT_QUOTES, 'UTF-8'); ?></span> (<span id="roti-hijri-month"><?php echo htmlspecialchars($currentHijriMonthYear, ENT_QUOTES, 'UTF-8'); ?></span>)</h3>
                </div>
                <h4 id="roti-sheet-period" class="roti-sheet-period fw-semibold"></h4>
            </div>
        </div>

        <div class="row mb-3 gy-2 align-items-center">
            <div class="col-auto">
                <div class="btn-group" role="group" aria-label="Week navigation">
                    <button type="button" class="btn btn-outline-secondary" id="roti-week-prev">&laquo; Previous Week</button>
                    <button type="button" class="btn btn-outline-secondary" id="roti-week-today">This Week</button>
                    <button type="button" class="btn btn-outline-secondary" id="roti-week-next">Next Week &raquo;</button>
                </div>
            </div>
            <div class="col-auto ms-auto">
                <h6 id="roti-sheet-status" class="fw-semibold" role="status" aria-live="polite"></h6>
            </div>
        </div>

        <div id="roti-sheet-scroll" class="table-responsive">
            <table class="table table-bordered table-striped" id="roti-sheet-table">
                <thead>
                    <tr>
                        <th class="roti-col-sticky">Code</th>
                        <th class="roti-col-sticky2">Roti Maker</th>
                        <th class="roti-col-sticky3">Opening<br>Atta (KG)</th>
                        <th class="roti-col-sticky4">Opening<br>Oil (L)</th>
                        <th class="roti-col-sticky5">Given<br>Atta (KG)</th>
                        <th class="roti-col-sticky6">Given<br>Oil (L)</th>
                        <th colspan="7" class="text-center">Roti Received (Mon – Sun)</th>
                        <th class="roti-col-sticky7">Total<br>Roti</th>
                        <th class="roti-col-sticky8">Total<br>Amt (₹)</th>
                        <th class="roti-col-sticky9">Atta<br>Req (KG)</th>
                        <th class="roti-col-sticky10">Oil<br>Req (L)</th>
                        <th class="roti-col-sticky11">Closing Atta<br><small>(next wk opening)</small></th>
                        <th class="roti-col-sticky12">Closing Oil<br><small>(next wk opening)</small></th>
                    </tr>
                    <tr id="roti-sheet-day-header">
                        <th class="roti-col-sticky"></th>
                        <th class="roti-col-sticky2"></th>
                        <th colspan="4" class="roti-col-sticky3"></th>
                        <!-- 7 day-of-week <th> filled in by JS -->
                        <th colspan="6"></th>
                    </tr>
                </thead>
                <tbody id="roti-sheet-tbody"></tbody>
            </table>
        </div>
    </div>
</div>

<script>
(function () {
    'use strict';

    const dayHeaderRow = document.getElementById('roti-sheet-day-header');
    const tbodyEl = document.getElementById('roti-sheet-tbody');
    const statusEl = document.getElementById('roti-sheet-status');
    const periodEl = document.getElementById('roti-sheet-period');
    const gregorianMonthEl = document.getElementById('roti-gregorian-month');
    const hijriMonthEl = document.getElementById('roti-hijri-month');
    const tableEl = document.getElementById('roti-sheet-table');
    const nextWeekButton = document.getElementById('roti-week-next');

    let currentWeek = null;      // last-loaded matrix from the server
    let cellGrid = [];           // [rowIndex] -> array of cell descriptors, for patching after save
    let pendingEdits = new Map();
    let saveTimer = null;
    let dataTable = null;

    function updateNextWeekButton() {
        const today = new Date();
        const saturdayOrLater = today.getDay() === 6 || today.getDay() === 0;
        const currentWeekStart = addDays(localIsoDate(), 1 - (today.getDay() || 7));
        const isCurrentWeek = currentWeek && currentWeek.week_start === currentWeekStart;
        const canLoadNextWeek = !isCurrentWeek || saturdayOrLater;

        nextWeekButton.disabled = !canLoadNextWeek;
        nextWeekButton.title = canLoadNextWeek
            ? 'Load next week'
            : 'Next week becomes available on Saturday';
    }

    function fmt(n, dp) {
        return Number(n || 0).toFixed(dp === undefined ? 3 : dp);
    }

    function setStatus(text, cls) {
        statusEl.textContent = text;
        statusEl.className = 'me-3 fw-semibold' + (cls ? ' ' + cls : '');
    }

    function addDays(iso, n) {
        const [year, month, day] = iso.split('-').map(Number);
        const d = new Date(Date.UTC(year, month - 1, day + n));
        return d.toISOString().slice(0, 10);
    }

    function localIsoDate() {
        const now = new Date();
        const pad = (value) => String(value).padStart(2, '0');
        return now.getFullYear() + '-' + pad(now.getMonth() + 1) + '-' + pad(now.getDate());
    }

    function shortLabel(iso) {
        const d = new Date(iso + 'T00:00:00');
        return d.toLocaleDateString(undefined, { weekday: 'short', day: '2-digit', month: 'short' });
    }

    function buildDayHeader(dates) {
        // Rebuild just the 7 day-of-week <th> cells in the second header row.
        dayHeaderRow.innerHTML = '';
        const stickyBlank1 = document.createElement('th');
        stickyBlank1.className = 'roti-col-sticky';
        const stickyBlank2 = document.createElement('th');
        stickyBlank2.className = 'roti-col-sticky2';
        dayHeaderRow.appendChild(stickyBlank1);
        dayHeaderRow.appendChild(stickyBlank2);
        const blank4 = document.createElement('th');
        blank4.colSpan = 4;
        dayHeaderRow.appendChild(blank4);
        dates.forEach((d) => {
            const th = document.createElement('th');
            th.innerHTML = shortLabel(d).replace(', ', '<br>');
            dayHeaderRow.appendChild(th);
        });
        const blank6 = document.createElement('th');
        blank6.colSpan = 6;
        dayHeaderRow.appendChild(blank6);
    }

    function makeInputCell(value, decimals, extraClass, onCommit) {
        const td = document.createElement('td');
        const input = document.createElement('input');
        input.type = 'number';
        input.step = 'any';
        input.min = '0';
        input.className = 'roti-input' + (extraClass ? ' ' + extraClass : '');
        input.value = decimals > 0 ? fmt(value, decimals) : Math.round(value || 0);
        input.addEventListener('change', () => {
            let v = parseFloat(input.value);
            if (isNaN(v)) v = 0;
            if (v < 0) v = 0;
            input.value = decimals > 0 ? fmt(v, decimals) : Math.round(v);
            onCommit(v);
        });
        td.appendChild(input);
        return { td, input };
    }

    function makeComputedCell(value, decimals, bold, pendingBadge) {
        const td = document.createElement('td');
        td.className = 'roti-computed' + (bold ? ' fw-bold' : '');
        const span = document.createElement('span');
        setComputedSpan(span, value, decimals, pendingBadge);
        td.appendChild(span);
        return { td, span };
    }

    function setComputedSpan(span, value, decimals, pendingBadge) {
        const num = Number(value) || 0;
        span.textContent = fmt(num, decimals);
        if (pendingBadge) {
            span.className = num < -0.0001 ? 'roti-pending-bad' : 'roti-pending-ok';
        }
    }

    function buildBody(matrix) {
        tbodyEl.innerHTML = '';
        cellGrid = [];

        matrix.rows.forEach((row) => {
            const tr = document.createElement('tr');
            const rowCells = [];

            const codeTd = document.createElement('td');
            codeTd.className = 'roti-col-sticky';
            codeTd.textContent = row.code;
            tr.appendChild(codeTd);
            rowCells.push({ kind: 'fixed' });

            const nameTd = document.createElement('td');
            nameTd.className = 'roti-col-sticky2';
            nameTd.textContent = row.full_name;
            tr.appendChild(nameTd);
            rowCells.push({ kind: 'fixed' });

            const openingAtta = makeInputCell(row.opening_atta, 2, 'roti-opening', (v) => queueDistributionEdit(row, 'opening_atta', v));
            openingAtta.td.classList.add('roti-col-sticky3');
            tr.appendChild(openingAtta.td); rowCells.push({ kind: 'input-opening', field: 'opening_atta', input: openingAtta.input });

            const openingOil = makeInputCell(row.opening_oil, 2, 'roti-opening', (v) => queueDistributionEdit(row, 'opening_oil', v));
            openingOil.td.classList.add('roti-col-sticky4');
            tr.appendChild(openingOil.td); rowCells.push({ kind: 'input-opening', field: 'opening_oil', input: openingOil.input });

            const givenAtta = makeInputCell(row.given_atta, 2, '', (v) => queueDistributionEdit(row, 'given_atta', v));
            givenAtta.td.classList.add('roti-col-sticky5');
            tr.appendChild(givenAtta.td); rowCells.push({ kind: 'input-given', field: 'given_atta', input: givenAtta.input });

            const givenOil = makeInputCell(row.given_oil, 2, '', (v) => queueDistributionEdit(row, 'given_oil', v));
            givenOil.td.classList.add('roti-col-sticky6');
            tr.appendChild(givenOil.td); rowCells.push({ kind: 'input-given', field: 'given_oil', input: givenOil.input });

            row.daily.forEach((val, di) => {
                const cell = makeInputCell(val, 0, '', (v) => queueReceivedEdit(row, matrix.dates[di], v, di));
                tr.appendChild(cell.td);
                rowCells.push({ kind: 'input-received', dayIndex: di, input: cell.input });
            });

            const totalRoti = makeComputedCell(row.total_roti, 0, true, false);
            const totalAmt = makeComputedCell(row.total_amt, 0, true, false);
            const attaReq = makeComputedCell(row.atta_required, 2, false, false);
            const oilReq = makeComputedCell(row.oil_required, 3, false, false);
            const closingAtta = makeComputedCell(row.closing_atta, 2, false, true);
            const closingOil = makeComputedCell(row.closing_oil, 3, false, true);

            tr.appendChild(totalRoti.td); rowCells.push({ kind: 'computed', field: 'total_roti', dp: 0, span: totalRoti.span });
            tr.appendChild(totalAmt.td); rowCells.push({ kind: 'computed', field: 'total_amt', dp: 0, span: totalAmt.span });
            tr.appendChild(attaReq.td); rowCells.push({ kind: 'computed', field: 'atta_required', dp: 2, span: attaReq.span });
            tr.appendChild(oilReq.td); rowCells.push({ kind: 'computed', field: 'oil_required', dp: 3, span: oilReq.span });
            tr.appendChild(closingAtta.td); rowCells.push({ kind: 'computed', field: 'closing_atta', dp: 2, badge: true, span: closingAtta.span });
            tr.appendChild(closingOil.td); rowCells.push({ kind: 'computed', field: 'closing_oil', dp: 3, badge: true, span: closingOil.span });

            tbodyEl.appendChild(tr);
            cellGrid.push(rowCells);
        });
    }

    function render(matrix) {
        if (dataTable) {
            dataTable.destroy();
            dataTable = null;
        }
        currentWeek = matrix;
        buildDayHeader(matrix.dates);
        buildBody(matrix);
        if (window.DataTable) {
            dataTable = new DataTable(tableEl, {
                paging: false,
                ordering: false,
                info: false,
                layout: {
                    topStart: 'search',
                    topEnd: null,
                    bottomStart: null,
                    bottomEnd: null,
                },
            });
        }
        attachKeyboardNav();
        periodEl.textContent = matrix.week_start + ' – ' + matrix.week_end + '  (' + matrix.hijri_label + ')';
        const weekStartDate = new Date(matrix.week_start + 'T00:00:00');
        gregorianMonthEl.textContent = weekStartDate.toLocaleDateString(undefined, { month: 'long', year: 'numeric' });
        hijriMonthEl.textContent = matrix.hijri_month_year;
        updateNextWeekButton();
        setStatus('');
    }

    // ---- Excel-style keyboard navigation across every <input> in the grid ----
    function attachKeyboardNav() {
        const rows = Array.from(tbodyEl.querySelectorAll('tr'));
        rows.forEach((tr) => {
            const rowInputs = Array.from(tr.querySelectorAll('input.roti-input'));
            rowInputs.forEach((input, colIndex) => {
                input.addEventListener('keydown', (e) => {
                    const rowIndex = rows.indexOf(tr);

                    function focusAt(r, c) {
                        const targetRow = rows[r];
                        if (!targetRow) return;
                        const targetInputs = Array.from(targetRow.querySelectorAll('input.roti-input'));
                        const target = targetInputs[Math.min(c, targetInputs.length - 1)];
                        if (target) { target.focus(); target.select(); }
                    }

                    if (e.key === 'ArrowUp') { e.preventDefault(); focusAt(rowIndex - 1, colIndex); }
                    else if (e.key === 'ArrowDown' || e.key === 'Enter') { e.preventDefault(); input.blur(); focusAt(rowIndex + 1, colIndex); }
                    else if (e.key === 'ArrowLeft' && input.selectionStart === 0) { e.preventDefault(); focusAt(rowIndex, colIndex - 1); }
                    else if (e.key === 'ArrowRight' && input.selectionStart === input.value.length) { e.preventDefault(); focusAt(rowIndex, colIndex + 1); }
                    // Tab falls through to the browser's normal DOM-order tab sequence.
                });
            });
        });
    }

    // ---- Local recompute for instant feedback, then queue the AJAX save ----
    function recomputeRow(row) {
        const totalRoti = row.daily.reduce((a, b) => a + (Number(b) || 0), 0);
        row.total_roti = totalRoti;
        row.total_amt = totalRoti * currentWeek.amount_per_roti;
        row.atta_required = totalRoti / 40;
        row.oil_required = totalRoti / 400;
        row.closing_atta = (Number(row.opening_atta) || 0) + (Number(row.given_atta) || 0) - row.atta_required;
        row.closing_oil = (Number(row.opening_oil) || 0) + (Number(row.given_oil) || 0) - row.oil_required;
    }

    function refreshRowCells(rowIndex) {
        const row = currentWeek.rows[rowIndex];
        cellGrid[rowIndex].forEach((cell) => {
            if (cell.kind === 'computed') {
                setComputedSpan(cell.span, row[cell.field], cell.dp, !!cell.badge);
            }
        });
    }

    function queueDistributionEdit(row, field, value) {
        row[field] = value;
        const rowIndex = currentWeek.rows.indexOf(row);
        recomputeRow(row);
        refreshRowCells(rowIndex);

        const key = row.maker_id + '|distribution';
        pendingEdits.set(key, {
            type: 'distribution',
            maker_id: row.maker_id,
            week_start: currentWeek.week_start,
            opening_atta: row.opening_atta,
            given_atta: row.given_atta,
            opening_oil: row.opening_oil,
            given_oil: row.given_oil,
        });
        scheduleSave();
    }

    function queueReceivedEdit(row, date, value, dayIndex) {
        row.daily[dayIndex] = value;
        const rowIndex = currentWeek.rows.indexOf(row);
        recomputeRow(row);
        refreshRowCells(rowIndex);

        const key = row.maker_id + '|' + date + '|received';
        pendingEdits.set(key, {
            type: 'received',
            maker_id: row.maker_id,
            date: date,
            roti_recieved: value,
        });
        scheduleSave();
    }

    function scheduleSave() {
        setStatus('Editing…', 'saving');
        clearTimeout(saveTimer);
        saveTimer = setTimeout(flushEdits, 700);
    }

    function flushEdits() {
        if (pendingEdits.size === 0) return;
        const edits = Array.from(pendingEdits.values());
        pendingEdits.clear();
        setStatus('Saving…', 'saving');

        fetch('api/sheet_save.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ edits }),
        })
            .then((r) => r.json())
            .then((resp) => {
                if (!resp.ok) throw new Error(resp.error || 'Save failed');
                applyServerRefresh(resp.rows);
                setStatus('All changes saved', 'saved');
            })
            .catch((err) => setStatus('Save failed: ' + err.message, 'error'));
    }

    // Reconcile with the server's freshly recomputed figures (source of
    // truth) after a save, without touching whatever input the maker is
    // currently typing into.
    function applyServerRefresh(freshRows) {
        const byMakerId = new Map(freshRows.map((r) => [r.maker_id, r]));
        currentWeek.rows.forEach((row, rowIndex) => {
            const fresh = byMakerId.get(row.maker_id);
            if (!fresh) return;
            Object.assign(row, fresh);
            refreshRowCells(rowIndex);
        });
    }

    function loadWeek(anchorDate) {
        setStatus('Loading…', 'saving');
        fetch('api/sheet_week.php?date=' + encodeURIComponent(anchorDate))
            .then((r) => r.json())
            .then(render)
            .catch((err) => setStatus('Failed to load: ' + err.message, 'error'));
    }

    document.getElementById('roti-week-prev').addEventListener('click', () => loadWeek(addDays(currentWeek.week_start, -7)));
    nextWeekButton.addEventListener('click', () => {
        if (!nextWeekButton.disabled) loadWeek(addDays(currentWeek.week_start, 7));
    });
    document.getElementById('roti-week-today').addEventListener('click', () => loadWeek(localIsoDate()));

    window.addEventListener('beforeunload', (e) => {
        if (pendingEdits.size > 0) {
            flushEdits();
            e.preventDefault();
            e.returnValue = '';
        }
    });

    loadWeek(<?php echo json_encode($initialWeekStart); ?>);
})();
</script>

<?php include('../footer.php'); ?>
