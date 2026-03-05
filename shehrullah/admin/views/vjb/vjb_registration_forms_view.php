<style>
.vjb-forms-wrapper {
    font-size: 14px;
}
.vjb-form-page {
    width: 210mm;
    min-height: 297mm;
    margin: 0 auto 12px auto;
    padding: 8mm;
    box-sizing: border-box;
    border: 1px solid #777;
    background: #fff;
    page-break-inside: avoid;
}
.takhmeen-page {
    border: 1px solid #999;
    background: #efefef;
    padding: 6mm;
    direction: rtl;
    font-size: 13px;
    color: #111;
    height: 100%;
    box-sizing: border-box;
}
.tk-frame {
    border: 2px solid #8d8d8d;
    min-height: 100%;
    padding: 7mm 5mm;
    position: relative;
}
.tk-corner {
    width: 10px;
    height: 10px;
    border-color: #8d8d8d;
    border-style: solid;
    position: absolute;
}
.tk-corner.tl { top: 6px; left: 6px; border-width: 3px 0 0 3px; }
.tk-corner.tr { top: 6px; right: 6px; border-width: 3px 3px 0 0; }
.tk-corner.bl { bottom: 6px; left: 6px; border-width: 0 0 3px 3px; }
.tk-corner.br { bottom: 6px; right: 6px; border-width: 0 3px 3px 0; }
.tk-head {
    display: grid;
    grid-template-columns: 120px 1fr 120px;
    gap: 10px;
    align-items: start;
}
.tk-box {
    border: 1px solid #666;
    min-height: 34px;
    padding: 4px 6px;
    text-align: center;
    font-size: 12px;
}
.tk-year {
    text-align: center;
    margin-top: 6px;
    font-size: 12px;
}
.tk-center-title {
    text-align: center;
    line-height: 1.35;
}
.tk-center-title .main {
    font-size: 28px;
    font-weight: 700;
}
.tk-center-title .sub {
    font-size: 15px;
}
.tk-place {
    text-align: right;
    padding-top: 16px;
    font-size: 16px;
}
.tk-place-line {
    display: inline-block;
    width: 70px;
    border-bottom: 1px solid #444;
    height: 12px;
    vertical-align: middle;
}
.tk-note-para {
    margin: 10px 0 12px;
    text-align: right;
    line-height: 1.5;
    min-height: 58px;
}
.tk-split {
    display: grid;
    grid-template-columns: 1fr 1.9fr;
    gap: 8px;
    direction: ltr;
}
.tk-mini,
.tk-main {
    width: 100%;
    border-collapse: collapse;
    table-layout: fixed;
    background: #efefef;
}
.tk-mini th,
.tk-mini td,
.tk-main th,
.tk-main td {
    border: 1px solid #333;
    padding: 4px 6px;
    height: 28px;
    vertical-align: middle;
    direction: rtl;
}
.tk-mini th,
.tk-main th {
    font-weight: 700;
    text-align: center;
}
.tk-main .amt {
    text-align: center;
    width: 24%;
}
.tk-main .cnt {
    text-align: center;
    width: 14%;
}
.tk-main .men {
    text-align: center;
    width: 14%;
}
.tk-main .item {
    width: 48%;
}
.tk-total-row {
    margin-top: 8px;
    border: 1px solid #333;
    min-height: 28px;
    display: grid;
    grid-template-columns: 1fr 1.2fr;
    align-items: center;
    padding: 0 8px;
    font-weight: 700;
}
.tk-inwords {
    margin: 6px 0 10px;
    border-bottom: 1px solid #333;
    min-height: 24px;
    display: flex;
    align-items: end;
    justify-content: center;
    font-size: 12px;
}
.tk-bottom {
    border: 1px solid #333;
    min-height: 110px;
    padding: 8px;
    display: grid;
    grid-template-columns: 1fr 170px;
    gap: 8px;
}
.tk-bottom-left {
    direction: ltr;
    font-size: 12px;
}
.tk-row {
    margin-bottom: 10px;
}
.tk-line {
    display: inline-block;
    border-bottom: 1px solid #333;
    min-height: 14px;
    vertical-align: middle;
}
.tk-line.w-120 { width: 120px; }
.tk-line.w-140 { width: 140px; }
.tk-line.w-160 { width: 160px; }
.tk-line.w-220 { width: 220px; }
.tk-line.w-320 { width: 320px; }
.tk-sign-box {
    border: 1px solid #666;
    text-align: center;
    padding: 8px 6px;
    font-size: 13px;
}
.tk-sign-line {
    margin-top: 12px;
    border-bottom: 1px solid #333;
    height: 12px;
}
.vjb-table {
    width: 100%;
    border-collapse: collapse;
    table-layout: fixed;
    font-size: 14px;
}
.vjb-table td {
    border: 1px solid #777;
    padding: 3px 8px;
    vertical-align: middle;
}
.vjb-label {
    font-weight: 700;
    width: 13%;
    white-space: nowrap;
}
.vjb-value {
    font-weight: 600;
}
.vjb-logo-cell {
    width: 13%;
    text-align: center;
    vertical-align: top !important;
    padding-top: 8px !important;
}
.vjb-logo-cell img {
    width: 52px;
    height: 52px;
    object-fit: contain;
}
.vjb-head-main {
    text-align: center;
    font-weight: 700;
    font-size: 15px;
    line-height: 1.25;
    text-transform: uppercase;
}
.vjb-head-sub {
    margin-top: 18px;
    font-size: 15px;
}
.vjb-row-sm td {
    height: 42px;
}
.vjb-row-md td {
    height: 50px;
}
.vjb-row-gap td {
    height: 56px;
}
.vjb-band-blue {
    background: #5b8fe3;
    font-weight: 700;
    text-align: center;
    text-decoration: underline;
}
.vjb-band-cyan {
    background: #11ace3;
    font-weight: 700;
    text-align: center;
    text-decoration: underline;
}
.vjb-year {
    text-align: center;
    font-weight: 700;
    font-size: 15px;
}
.vjb-prev-value {
    margin-top: 2px;
    font-size: 13px;
    font-weight: 600;
}
.vjb-prev-row-value {
    text-align: center;
    font-size: 14px;
    font-weight: 600;
}
.vjb-sign-box {
    height: 128px;
    vertical-align: top !important;
    position: relative;
    font-size: 13px;
}
.vjb-sign-title {
    font-weight: 700;
    text-align: center;
    margin-top: 2px;
    letter-spacing: 0.02em;
}
.vjb-sign-label {
    position: absolute;
    bottom: 4px;
    left: 0;
    right: 0;
    text-align: center;
    font-size: 15px;
    font-weight: 700;
}
.vjb-sign-box .vjb-sign-top {
    font-size: 14px;
}
@page {
    size: A4;
    margin: 0;
}
@media print {
    body * {
        visibility: hidden !important;
    }
    .vjb-forms-wrapper,
    .vjb-forms-wrapper * {
        visibility: visible !important;
    }
    .vjb-forms-wrapper {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
        margin: 0 !important;
        padding: 0 !important;
    }
    html,
    body {
        width: auto;
        margin: 0;
        padding: 0;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
    .card,
    .card-body {
        border: none !important;
        box-shadow: none !important;
        padding: 0 !important;
        margin: 0 !important;
        position: static !important;
    }
    .container,
    .container-fluid,
    .content-wrapper,
    .main-content {
        margin: 0 !important;
        padding: 0 !important;
        position: static !important;
    }
    .d-print-none {
        display: none !important;
    }
    .vjb-form-page {
        width: 210mm;
        min-height: 0;
        height: 297mm;
        border: none;
        margin: 0;
        padding: 8mm;
        page-break-after: always;
        break-after: page;
        page-break-inside: avoid;
        break-inside: avoid;
    }
    .vjb-form-page:last-of-type {
        page-break-after: auto;
        break-after: auto;
    }
    .vjb-band-blue,
    .vjb-band-cyan {
        background: #d9d9d9 !important;
        color: #000 !important;
    }
}
</style>
<div class="d-flex justify-content-end mb-2 d-print-none">
    <button type="button" class="btn btn-light btn-sm" onclick="window.print()">Print</button>
</div>
<div class="vjb-forms-wrapper">
    <?php foreach ($records as $row): ?>
        <?php
        $vajebaatPrev = trim((string)($row->vajebaat_prev ?? ''));
        $annualNiyazPrev = trim((string)($row->annual_niyaz_prev ?? ''));
        $ikramPrev = trim((string)($row->ikram_prev ?? ''));
        $husainiStatus = trim((string)($row->husaini_scheme_status_prev ?? ''));
        $sector = trim((string)($row->sector ?? ''));
        $subSector = trim((string)($row->sub_sector ?? ''));
        $slotDate = '';
        if (!empty($row->slot_date)) {
            $slotTimestamp = strtotime((string)$row->slot_date);
            $slotDate = $slotTimestamp ? date('d-m-Y', $slotTimestamp) : (string)$row->slot_date;
        }
        ?>
        <div class="vjb-form-page">
            <table class="vjb-table">
                <colgroup>
                    <col style="width:13%">
                    <col style="width:16%">
                    <col style="width:14%">
                    <col style="width:16%">
                    <col style="width:13%">
                    <col style="width:28%">
                </colgroup>
                <tr>
                    <td class="vjb-logo-cell">
                        <picture>
                            <source srcset="/shehrullah/assets/img/logo.avif" type="image/avif">
                            <img src="/shehrullah/assets/img/logo.png" alt="Kalimi Mohalla Logo" loading="lazy" decoding="async" fetchpriority="low" width="52" height="52">
                        </picture>
                    </td>
                    <td colspan="5" class="vjb-head-main">
                        DAWOODI BOHRA JAMAAT TRUST, KALIMI MOHALLA, POONA JAMAAT
                        <div class="vjb-head-sub">VAJEBAAT TAKHMEEN FORM <?= h($current_year) ?>H</div>
                    </td>
                </tr>
                <tr class="vjb-row-sm">
                    <td class="vjb-label">Sabil #</td>
                    <td class="vjb-value"><?= h($row->sabeel ?? '') ?></td>
                    <td class="vjb-label">Name</td>
                    <td colspan="3" class="vjb-value"><?= h($row->Full_Name ?? '') ?></td>
                </tr>
                <tr class="vjb-row-sm">
                    <td class="vjb-label">ITS #</td>
                    <td class="vjb-value"><?= h($row->ITS_ID ?? '') ?></td>
                    <td class="vjb-label">Mobile #</td>
                    <td class="vjb-value"><?= h($row->Mobile ?? '') ?></td>
                    <td class="vjb-label">HOF/FM #</td>
                    <td class="vjb-value"></td>
                </tr>
                <tr class="vjb-row-sm">
                    <td class="vjb-label">Address #</td>
                    <td colspan="3" class="vjb-value"><?= h($row->Address ?? '') ?></td>
                    <td class="vjb-label">Date</td>
                    <td class="vjb-value"><?= h($slotDate) ?></td>
                </tr>
                <tr class="vjb-row-sm">
                    <td class="vjb-label">Sector</td>
                    <td class="vjb-value"><?= h($sector) ?></td>
                    <td class="vjb-label">Sub Sector</td>
                    <td class="vjb-value"><?= h($subSector) ?></td>
                    <td class="vjb-label">ITS Data Verification Status</td>
                    <td class="vjb-value"></td>
                </tr>

                <tr><td colspan="6" class="vjb-band-blue">Vajebaat</td></tr>
                <tr>
                    <td colspan="3" class="vjb-band-blue vjb-year">
                        <?= h($vajebaat_prev_label) ?>
                    </td>
                    <td colspan="3" class="vjb-band-blue vjb-year"><?= h($vajebaat_curr_label) ?></td>
                </tr>
                <tr class="vjb-row-gap">
                    <td colspan="3" class="vjb-prev-row-value"><?php if ($vajebaatPrev !== ''): ?><div class="vjb-prev-value"><?= h($vajebaatPrev) ?></div><?php endif; ?></td>
                    <td colspan="3"></td>
                </tr>

                <tr><td colspan="6" class="vjb-band-cyan">Annual Niyaz</td></tr>
                <tr>
                    <td colspan="3" class="vjb-band-cyan vjb-year">
                        <?= h($niyaz_prev_label) ?>
                    </td>
                    <td colspan="3" class="vjb-band-cyan vjb-year"><?= h($niyaz_curr_label) ?></td>
                </tr>
                <tr class="vjb-row-gap">
                    <td colspan="3" class="vjb-prev-row-value"><?php if ($annualNiyazPrev !== ''): ?><div class="vjb-prev-value"><?= h($annualNiyazPrev) ?></div><?php endif; ?></td>
                    <td colspan="3"></td>
                </tr>

                <tr><td colspan="6" class="vjb-band-blue">Ikram</td></tr>
                <tr>
                    <td colspan="3" class="vjb-band-blue vjb-year">
                        <?= h($ikram_prev_label) ?>
                    </td>
                    <td colspan="3" class="vjb-band-blue vjb-year"><?= h($ikram_curr_label) ?></td>
                </tr>
                <tr class="vjb-row-gap">
                    <td colspan="3" class="vjb-prev-row-value"><?php if ($ikramPrev !== ''): ?><div class="vjb-prev-value"><?= h($ikramPrev) ?></div><?php endif; ?></td>
                    <td colspan="3"></td>
                </tr>

                <tr>
                    <td colspan="3" class="vjb-sign-box">
                        <div class="vjb-sign-top">Sign:</div>
                        <div class="vjb-sign-label">Abde Syedna</div>
                    </td>
                    <td colspan="3" class="vjb-sign-box" style="padding: 0;">
                        <div class="vjb-band-cyan vjb-sign-title" style="padding: 10px 8px; text-decoration: none;">HUSAINI SCHEME ACCOUNT STATUS</div>
                        <div style="padding: 10px 8px; font-weight: 700; font-size: 14px; text-align: center;"><?= h($husainiStatus) ?></div>
                    </td>
                </tr>
                <tr>
                    <td colspan="3" class="vjb-sign-box">
                        <div class="vjb-sign-top">Sign:</div>
                        <div class="vjb-sign-label">Secretary / Treasurer</div>
                    </td>
                    <td colspan="3" class="vjb-sign-box">
                        <div class="vjb-sign-top">Sign:</div>
                        <div class="vjb-sign-label">Aamil Saheb</div>
                    </td>
                </tr>
            </table>
        </div>
        <div class="vjb-form-page">
            <div class="takhmeen-page">
                <div class="tk-frame">
                    <span class="tk-corner tl"></span>
                    <span class="tk-corner tr"></span>
                    <span class="tk-corner bl"></span>
                    <span class="tk-corner br"></span>

                    <div class="tk-head">
                        <div>
                            <div class="tk-box">رسمي نمبر:</div>
                            <div class="tk-year"><?= h($current_year) ?>هـ<br><?= h(date('Y')) ?></div>
                        </div>
                        <div class="tk-center-title">
                            <div class="main">القوة المباركة اليمنية</div>
                            <div class="sub">المبلغ الذي تبرع بحقه الواجب في رأس المال</div>
                            <div class="sub">زكوة الفطر / صلة الإمام عليه السلام / الحقوق الواجبات</div>
                        </div>
                        <div class="tk-place">موضع: <span class="tk-place-line"></span></div>
                    </div>

                    <div class="tk-note-para">
                        عـبـدِ سـيـدنـا الـطـاهـر الـمـكـرم قـدم هـذا الـفـورم لـبـيـان مـقـدار الـواجـبـات،
                        عـلـى حـسـب الـمـقـرر مـن جـهـة الـدعـوة.
                    </div>

                    <div class="tk-split">
                        <table class="tk-mini">
                            <tr>
                                <th>رسم</th>
                                <th>الواجبات</th>
                            </tr>
                            <tr><td></td><td>زكوة المال</td></tr>
                            <tr><td></td><td>صلة الإمام ع م</td></tr>
                            <tr><td></td><td>الخمس</td></tr>
                            <tr><td></td><td>الكفارة</td></tr>
                            <tr><td></td><td>منة وغيرها</td></tr>
                            <tr><td></td><td>النجوى</td></tr>
                            <tr><td></td><td><strong>جملة (B):</strong></td></tr>
                        </table>

                        <table class="tk-main">
                            <tr>
                                <th class="amt">مبلغ رقم<br>Rs.</th>
                                <th class="cnt">تعداد</th>
                                <th class="men">مرد/بئر</th>
                                <th class="item"></th>
                            </tr>
                            <tr><td class="amt"></td><td class="cnt"></td><td class="men"></td><td class="item">زكوة الفطر - مرد</td></tr>
                            <tr><td class="amt"></td><td class="cnt"></td><td class="men"></td><td class="item">زكوة الفطر - بئر</td></tr>
                            <tr><td class="amt"></td><td class="cnt"></td><td class="men"></td><td class="item">زكوة الفطر - غير بالغ</td></tr>
                            <tr><td class="amt"></td><td class="cnt"></td><td class="men"></td><td class="item">زكوة الفطر - حمل</td></tr>
                            <tr><td class="amt"></td><td class="cnt"></td><td class="men"></td><td class="item">زكوة الفطر - اموات</td></tr>
                            <tr><td class="amt"></td><td colspan="3"><strong>جملة:</strong></td></tr>
                            <tr><td class="amt"></td><td colspan="3">صلة الإمام ع م</td></tr>
                            <tr><td class="amt"></td><td colspan="3">نذر للمقام ع م</td></tr>
                            <tr><td class="amt"></td><td colspan="3">داعي العصر ع ط نجوى</td></tr>
                            <tr><td class="amt"></td><td colspan="3"><strong>جملة (A):</strong></td></tr>
                        </table>
                    </div>

                    <div class="tk-total-row">
                        <div></div>
                        <div>جملة المبلغ (A + B):</div>
                    </div>
                    <div class="tk-inwords">(In Words: Rs. ________________________________Only)</div>

                    <div class="tk-bottom">
                        <div class="tk-bottom-left">
                            <div class="tk-row">اداء كردن نى صحيح: <span class="tk-line w-220"></span></div>
                            <div class="tk-row">نام عبد/امة سيدنا المنعم ع م: <span class="tk-line w-320"><?= h($row->Full_Name ?? '') ?></span></div>
                            <div class="tk-row">ITS ID: <span class="tk-line w-120"><?= h($row->ITS_ID ?? '') ?></span> &nbsp;&nbsp; Contact No: <span class="tk-line w-140"><?= h($row->Mobile ?? '') ?></span></div>
                            <div class="tk-row">Address: <span class="tk-line w-320"><?= h($row->Address ?? '') ?></span></div>
                        </div>
                        <div class="tk-sign-box">
                            وصول كردانلا دستخط
                            <div class="tk-sign-line"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>
