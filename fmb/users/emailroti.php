<?php
// Partial include, expected to run inside emailmenu.php's scope (connection,
// helpers, and getHijriDate are already included there). Falls back to
// resolving menu_date from $_GET itself so it still behaves sensibly if
// included on its own.

$tomorrow_date = $_GET['menu_date'] ?? date('Y-m-d', strtotime('+ 1 day'));
if (!DateTime::createFromFormat('Y-m-d', $tomorrow_date)) {
    $tomorrow_date = date('Y-m-d', strtotime('+ 1 day'));
}

$day = date('l', strtotime($tomorrow_date));

// Default menu quantity for a given thalisize, from the day's base menu.
// Friday/Barnamaj thalis use the "small" quantity, and thalis with no
// recognised size (including the standalone "Roti" thalisize) default to 1.
function defaultRotiQtyForSize(?string $thalisize, int $mini, int $small, int $medium, int $large): int
{
    return match (strtolower(trim((string) $thalisize))) {
        'mini' => $mini,
        'small' => $small,
        'medium' => $medium,
        'large' => $large,
        'friday', 'barnamaj' => $small,
        default => 1,
    };
}

// Maps a thalisize to the bucket key used in $thaliSize / the report table.
function rotiBucketForSize(?string $thalisize): string
{
    return match (strtolower(trim((string) $thalisize))) {
        'mini' => 'mini',
        'small' => 'small',
        'medium' => 'medium',
        'large' => 'large',
        'friday' => 'friday',
        'barnamaj' => 'barnamaj',
        'roti' => 'roti',
        default => 'no size',
    };
}

function standardRotiQtyForSize(?string $thalisize): int
{
    return match (strtolower(trim((string) $thalisize))) {
        'mini', 'small', 'barnamaj' => 1,
        'friday', 'medium', 'large' => 2,
        default => 1,
    };
}

/**
 * Effective menu quantity for one thali: the user's own customization (if
 * they saved one) from $overridesByThaliId, otherwise the day's default
 * for their size. $overridesByThaliId is built with a single query up
 * front instead of one query per thali.
 */
function effectiveRotiQty(array $overridesByThaliId, string $thaliId, int $defaultQty): int
{
    $quantity = $overridesByThaliId[$thaliId] ?? $defaultQty;
    return min(max(0, (int) $quantity), max(0, $defaultQty));
}

$menu_item_result = db_query($link, "SELECT `menu_item` FROM menu_list WHERE `menu_date` = ? AND `menu_type` = 'thaali' LIMIT 1", "s", [$tomorrow_date]);

if ($menu_item_result->num_rows > 0) {
    $row_menu = $menu_item_result->fetch_assoc();
    $menu_item = decode_menu_item($row_menu['menu_item']);
    $roti = $menu_item['roti']['item'] ?? '';

    if (!empty($roti)) {
        $mini = (int) ($menu_item['roti']['tqty'] ?? 0);
        $small = (int) ($menu_item['roti']['sqty'] ?? 0);
        $medium = (int) ($menu_item['roti']['mqty'] ?? 0);
        $large = (int) ($menu_item['roti']['lqty'] ?? 0);
        $msgroti = '';

        // Build the per-thali menu overrides once, instead of one query per thali.
        $overridesByThaliId = [];
        $overrideResult = db_query($link, "SELECT thali, menu_item FROM user_menu WHERE menu_date = ?", "s", [$tomorrow_date]);
        while ($row = mysqli_fetch_assoc($overrideResult)) {
            $item = decode_menu_item($row['menu_item']);
            if (!empty($item['roti']['item']) && isset($item['roti']['qty'])) {
                $overridesByThaliId[$row['thali']] = [
                    'item' => trim((string) $item['roti']['item']),
                    'qty' => (int) $item['roti']['qty'],
                ];
            }
        }

        $transporterResult = db_query($link, "SELECT DISTINCT `Transporter` FROM thalilist WHERE Active = 1 ORDER BY Transporter");
        $transporters = [];
        while ($row_trans = mysqli_fetch_assoc($transporterResult)) {
            $transporters[] = $row_trans['Transporter'];
        }

        $buckets = ['mini', 'small', 'medium', 'large', 'friday', 'barnamaj', 'no size'];
        if ($roti === 'Roti') {
            $buckets[] = 'roti';
        }

        $thaliSize = [];
        $rotiDetails = '';
        $rotiDetailTransporters = [];
        $hijridate = getHijriDate($tomorrow_date);
        $msgroti .= "<br/><h3>" . e($roti) . " Count on " . e($hijridate) . " " . e($day) . " - " . e($tomorrow_date) . "</h3><br/><br/>";
        $rotiTable = "<table border='1'><tr><td style='padding: 2px 10px 2px 10px;'>Size</td>";

        foreach ($transporters as $transporterName) {
            $rotiTable .= "<td style='padding: 2px 10px 2px 10px;'>" . e((string) $transporterName) . "</td>";

            foreach ($buckets as $bucket) {
                $thaliSize[$bucket][$transporterName] = 0;
            }

            $thaliRows = db_query(
                $link,
                "SELECT id, tiffinno, thalisize, extraRoti, `NAME`, CONTACT, wingflat, society
                 FROM thalilist WHERE Active = 1 AND `Transporter` = ? ORDER BY tiffinno",
                "s",
                [$transporterName]
            );
            while ($row = mysqli_fetch_assoc($thaliRows)) {
                $bucket = rotiBucketForSize($row['thalisize']);
                if (!in_array($bucket, $buckets, true)) {
                    continue; // e.g. a 'Roti' thalisize thali on a day the roti item isn't literally "Roti"
                }

                $originalQty = defaultRotiQtyForSize($row['thalisize'], $mini, $small, $medium, $large);
                $isRotiItem = strcasecmp(trim((string) $roti), 'Roti') === 0;
                $userOverride = $overridesByThaliId[$row['id']] ?? null;
                if (!$isRotiItem) {
                    if ($userOverride === null || strcasecmp($userOverride['item'], trim((string) $roti)) !== 0) {
                        continue;
                    }
                    $qty = max(0, $userOverride['qty']);
                } else {
                    if ($userOverride !== null && strcasecmp($userOverride['item'], 'Roti') !== 0) {
                        $userOverride = null;
                    }
                    $defaultQty = $originalQty + max(0, (int) ($row['extraRoti'] ?? 0));
                    $qty = effectiveRotiQty(
                        $userOverride === null ? [] : [$row['id'] => $userOverride['qty']],
                        $row['id'],
                        $defaultQty
                    );
                }

                $thaliSize[$bucket][$transporterName] += $qty;

                $wasQuantityEdited = $userOverride !== null
                    && strcasecmp($userOverride['item'], (string) $roti) === 0
                    && $userOverride['qty'] !== $originalQty;
                if ($wasQuantityEdited) {
                    if (!isset($rotiDetailTransporters[$transporterName])) {
                        $rotiDetails .= "<b>" . e((string) $transporterName) . "</b><br/>";
                        $rotiDetailTransporters[$transporterName] = true;
                    }
                    $rotiDetails .= "<b>" . e((string) $qty) . " " . e((string) $roti) . "</b> - ";
                    $detailFields = [
                        $row['tiffinno'],
                        $row['thalisize'],
                        $row['NAME'],
                        $row['CONTACT'],
                        $row['wingflat'],
                        $row['society'],
                    ];
                    $rotiDetails .= e(implode(' - ', $detailFields)) . "<br/><br/>";
                }
            }

            $thaliSize["Total"][$transporterName] = array_sum(array_map(
                fn($bucket) => $thaliSize[$bucket][$transporterName],
                $buckets
            ));
        }
        $rotiTable .= "<td style='padding: 2px 10px 2px 10px;'>Total</td></tr>";

        if ($rotiDetails !== '') {
            $msgroti .= $rotiDetails;
        }

        foreach ($thaliSize as $size => $sizeCount) {
            $totalSizeCount = 0;
            $rotiTable .= "<tr><td style='padding: 2px 10px 2px 10px;'>" . e($size) . "</td>";
            foreach ($transporters as $transporterName) {
                $totalSizeCount += $sizeCount[$transporterName];
                $rotiTable .= "<td style='padding: 2px 10px 2px 10px;'>" . e((string) $sizeCount[$transporterName]) . "</td>";
            }
            $rotiTable .= "<td style='padding: 2px 10px 2px 10px;'>" . e((string) $totalSizeCount) . "</td></tr>";
        }

        $rotiTable .= "</table>";
        $msgroti .= $rotiTable;

        if ($roti === 'Roti') {
            $totalCount = 0;
            $totalCount += array_sum($thaliSize["mini"]) * 2;
            $totalCount += array_sum($thaliSize["small"]) * 4;
            $totalCount += array_sum($thaliSize["medium"]) * 4;
            $totalCount += array_sum($thaliSize["large"]) * 4;
            $totalCount += array_sum($thaliSize["friday"]) * 4;
            $totalCount += array_sum($thaliSize["barnamaj"]) * 2;
            $totalCount += array_sum($thaliSize["no size"]) * 2;
            $totalCount += array_sum($thaliSize["roti"]) * 4;
        } else {
            $totalCount = $totalSizeCount ?? 0;
        }

        echo $msgroti .= "<br/><b>Total " . e($roti) . " Count is " . e((string) $totalCount) . "</b>";

        $subject = $roti . ' update ' . $tomorrow_date;
        sendEmail(ROTI_UPDATE_EMAILS, $subject, $msgroti, null, null, true);
    } else {
        echo "Tomorrow no roti.";
    }
} else {
    echo "Skipping email as no thali on Miqaat or any other reason.";
    exit;
}
