<?php
/**
 * Cuse Clouds — build the new-design home page as an SP Page Builder page.
 *
 * ⚠ UNVERIFIED DRAFT — NOT READY TO RUN ⚠
 * This file has never been syntax-checked or executed. The environment it was
 * written in refuses to lint or run anything that opens a database connection
 * to a production site, so the author could not validate a single line of it.
 * Do not point it at a live database on the strength of the comments below.
 * Before it is used: lint it (`php -l`), run it against a copy of the
 * database, and confirm the dry run reports a sane structure.
 *
 * WHY THIS EXISTS
 * SP Page Builder 6.8 has no JSON import, so a page can only be created by
 * inserting a row into #__sppagebuilder. This script does that once, then you
 * delete it.
 *
 * IT DOES NOT GUESS THE SCHEMA. It reads your existing page and reuses it:
 *   - the new DB row is cloned from an existing #__sppagebuilder row, so every
 *     NOT NULL column is satisfied without knowing the table layout;
 *   - the row/column scaffolding is cloned from your real layout, so the JSON
 *     shape matches this exact SPPB build;
 *   - each addon's settings are seeded from a real addon of the same type on
 *     your site where one exists, and only content keys are overridden.
 *
 * IT NEVER MODIFIES OR DELETES YOUR EXISTING PAGE. It only INSERTs a new one.
 *
 * USAGE — visit in a browser, in this order:
 *   /cc-install-home.php?t=TOKEN                  Dry run. Reports what it found
 *                                                 and what it would build. No writes.
 *   /cc-install-home.php?t=TOKEN&apply=1          Creates the page (unpublished).
 *   /cc-install-home.php?t=TOKEN&apply=1&publish=1  Creates it published.
 *
 * Then open SP Page Builder → Pages, preview it, and set it as your home page
 * from the menu manager when you are happy. Delete this file afterwards.
 */

const CC_TOKEN = '2d0e3f8d5fa576ec590c0ba5045215f3';

if (!isset($_GET['t']) || !hash_equals(CC_TOKEN, (string) $_GET['t'])) {
    http_response_code(404);
    exit;
}

header('Content-Type: text/plain; charset=utf-8');
header('X-Robots-Tag: noindex, nofollow');

$apply   = !empty($_GET['apply']);
$publish = !empty($_GET['publish']) ? 1 : 0;

require __DIR__ . '/configuration.php';
$cfg = new JConfig();

try {
    $pdo = new PDO(
        sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', $cfg->host, $cfg->db),
        $cfg->user,
        $cfg->password,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
    );
} catch (Throwable $e) {
    exit("DB CONNECT FAILED: " . $e->getMessage() . "\n");
}

$p = $cfg->dbprefix;
$log = static function ($s = '') { echo $s . "\n"; };

$log('Cuse Clouds — SPPB home page builder');
$log('mode: ' . ($apply ? 'APPLY' : 'DRY RUN (no writes)'));
$log(str_repeat('=', 66));

/* ---------------------------------------------------------------- source */

$rows = $pdo->query(
    "SELECT id, title, LENGTH(text) AS len FROM `{$p}sppagebuilder`
      WHERE text IS NOT NULL AND LENGTH(text) > 100
   ORDER BY LENGTH(text) DESC"
)->fetchAll();

if (!$rows) {
    exit("No SPPB page with a layout found — nothing to clone from. Aborting.\n");
}

$srcId = (int) $rows[0]['id'];
$log("cloning structure from page #{$srcId} \"{$rows[0]['title']}\" ({$rows[0]['len']} bytes)");

$src = $pdo->query("SELECT * FROM `{$p}sppagebuilder` WHERE id = {$srcId}")->fetch();
$layout = json_decode($src['text']);

if (!is_array($layout) || !$layout) {
    exit("Existing layout did not decode as an array. Aborting.\n");
}
$log('table columns: ' . implode(', ', array_keys($src)));
$log('source rows in layout: ' . count($layout));

/* Harvest one real addon per type, to seed settings from. */
$specimen = [];
$rowProto = null;
$colProto = null;

$walk = static function ($nodes) use (&$walk, &$specimen, &$rowProto, &$colProto) {
    foreach ((array) $nodes as $n) {
        if (isset($n->columns)) {
            if ($rowProto === null) { $rowProto = $n; }
            foreach ($n->columns as $c) {
                if ($colProto === null) { $colProto = $c; }
                if (!empty($c->addons)) { $walk($c->addons); }
            }
        }
        if (isset($n->type) && !isset($specimen[$n->type])) {
            $specimen[$n->type] = $n;
        }
        if (!empty($n->children)) { $walk($n->children); }
    }
};
$walk($layout);

$log('addon types found on your site: ' . (implode(', ', array_keys($specimen)) ?: 'none'));
$log('row prototype: ' . ($rowProto ? implode(',', array_keys(get_object_vars($rowProto))) : 'MISSING'));
$log('col prototype: ' . ($colProto ? implode(',', array_keys(get_object_vars($colProto))) : 'MISSING'));

if (!$rowProto || !$colProto) {
    exit("Could not find a row/column prototype in the existing layout. Aborting.\n");
}

/* ------------------------------------------------------------- builders */

$uid = static function () {
    return sprintf(
        '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        random_int(0, 0xffff), random_int(0, 0xffff), random_int(0, 0xffff),
        random_int(0, 0x0fff) | 0x4000, random_int(0, 0x3fff) | 0x8000,
        random_int(0, 0xffff), random_int(0, 0xffff), random_int(0, 0xffff)
    );
};

$clone = static function ($o) { return json_decode(json_encode($o)); };

/** Build an addon by copying a real one of the same type and overriding keys. */
$addon = static function ($type, array $set) use ($specimen, $clone, $uid) {
    if (isset($specimen[$type])) {
        $a = $clone($specimen[$type]);
        if (!isset($a->settings) || !is_object($a->settings)) { $a->settings = new stdClass(); }
    } else {
        $a = (object) ['type' => $type, 'settings' => new stdClass()];
    }
    $a->type = $type;
    $a->id   = $uid();
    unset($a->children);
    foreach ($set as $k => $v) { $a->settings->$k = $v; }
    return $a;
};

/** Build a row of equal-width columns, each holding a list of addons. */
$row = static function (array $columnsOfAddons, array $rowSettings = []) use ($rowProto, $colProto, $clone, $uid) {
    $r = $clone($rowProto);
    $r->id = $uid();
    if (!isset($r->settings) || !is_object($r->settings)) { $r->settings = new stdClass(); }
    foreach ($rowSettings as $k => $v) { $r->settings->$k = $v; }

    $n = max(1, count($columnsOfAddons));
    $r->columns = [];
    foreach ($columnsOfAddons as $addons) {
        $c = $clone($colProto);
        $c->id = $uid();
        if (!isset($c->settings) || !is_object($c->settings)) { $c->settings = new stdClass(); }
        $c->settings->width = round(100 / $n, 4);
        $c->addons = array_values($addons);
        $r->columns[] = $c;
    }
    return $r;
};

$h  = static function ($text, $tag = 'h2', $align = 'center') use ($addon) {
    return $addon('heading', ['title' => $text, 'heading_selector' => $tag,
        'alignment' => (object) ['xl' => $align, 'lg' => '', 'md' => '', 'sm' => '', 'xs' => '']]);
};
$tx = static function ($html) use ($addon) { return $addon('text_block', ['text' => $html]); };
$bt = static function ($text, $url) use ($addon) {
    return $addon('button', ['text' => $text, 'type' => 'primary',
        'link' => $url, 'button_link' => $url, 'url' => $url]);
};

/* ---------------------------------------------------------- page content */

$LIC   = 'OCM-RETL-26-000487';
$ADDR  = '900 E Fayette St, Syracuse, NY 13210';
$PHONE = '(315) 214-4017';
$MAIL  = 'cs@cuseclouds.com';

$cats = [
    ['FLOWER', 'Hand-selected strains', 'flowers.jpg'],
    ['PRE-ROLLS', 'Ready when you are', 'pre-rolls.jpg'],
    ['EDIBLES', 'Gummies, chocolates &amp; more', 'edibles.jpg'],
    ['VAPES &amp; CARTRIDGES', 'Carts, disposables &amp; pods', 'vapes-cartridges.jpg'],
    ['TOPICALS', 'Balms &amp; lotions', 'topicals.jpg'],
    ['TINCTURES', 'Precise, measured drops', 'tinctures.jpg'],
    ['CONCENTRATES', 'Wax, shatter &amp; rosin', 'concentrates.jpg'],
    ['ACCESSORIES', 'Everything you need', 'accessories.jpg'],
];

$tile = static function ($c) use ($addon) {
    return $addon('image_overlay', [
        'title'     => $c[0],
        'text'      => $c[1],
        'image'     => 'images/ccc/' . $c[2],
        'alt_text'  => $c[0] . ' at Cuse Clouds Cannabis',
    ]);
};

$new = [];

/* 1. Keep the existing hero slider exactly as it is — it already works and is
      already editable in the builder. Clone it rather than rebuild it. */
$heroCloned = false;
foreach ($layout as $r0) {
    $json = json_encode($r0);
    if (strpos($json, 'js_slideshow') !== false || strpos($json, 'sp_slider') !== false) {
        $c = $clone($r0);
        $c->id = $uid();
        $new[] = $c;
        $heroCloned = true;
        break;
    }
}
$log('hero slider: ' . ($heroCloned ? 'cloned from the existing page' : 'NOT FOUND — page will start at the intro'));

/* 2. Intro */
$new[] = $row([[
    $h('Cannabis Done <span class="cc-accent">Right.</span>', 'h1'),
    $tx('<p style="text-align:center">New York State licensed adult-use cannabis, in the heart of Syracuse. '
      . 'Lab-tested products, staff who will actually talk you through the menu, and no pressure.</p>'
      . '<p style="text-align:center"><strong>' . $ADDR . '</strong> &middot; ' . $PHONE
      . ' &middot; ' . $MAIL . '</p>'),
]]);

/* 3. Products — two rows of four tiles */
$new[] = $row([[ $h('Shop by <span class="cc-accent">Category</span>') ]]);
$new[] = $row(array_map(static function ($c) use ($tile) { return [$tile($c)]; }, array_slice($cats, 0, 4)));
$new[] = $row(array_map(static function ($c) use ($tile) { return [$tile($c)]; }, array_slice($cats, 4, 4)));

/* 4. Visit — copy, and the site's own map addon beside it */
$mapAddon = null;
foreach (['openstreetmap', 'gmap'] as $t) {
    if (isset($specimen[$t])) { $mapAddon = $clone($specimen[$t]); $mapAddon->id = $uid(); break; }
}
$log('map addon: ' . ($mapAddon ? $mapAddon->type . ' cloned from your page' : 'none found — visit section will be text only'));

$visitLeft = [
    $h('Plan Your <span class="cc-accent">Visit</span>', 'h2', 'left'),
    $tx('<p><strong>' . $ADDR . '</strong></p>'
      . '<p>Mon&ndash;Fri 9:00 AM &ndash; 11:00 PM<br>Sat&ndash;Sun 9:00 AM &ndash; 10:00 PM</p>'
      . '<p>Phone: ' . $PHONE . '<br>Email: ' . $MAIL . '</p>'
      . '<p>Bring a valid government-issued photo ID. You must be 21 or older to enter.</p>'),
];
$new[] = $mapAddon ? $row([$visitLeft, [$mapAddon]]) : $row([$visitLeft]);

/* 5. In-store & delivery */
$new[] = $row([[
    $h('In-Store &amp; <span class="cc-accent">Delivery</span>'),
    $tx('<p style="text-align:center">Shop in person at 900 E Fayette St, or call ' . $PHONE
      . ' to order for pickup and delivery across Syracuse. '
      . 'Delivery is part of our licensed activity under license # ' . $LIC . '.</p>'),
]]);

/* 6. First-time visitors + learning */
$new[] = $row([
    [ $h('First Time <span class="cc-accent">Here?</span>', 'h3', 'left'),
      $tx('<p>Our staff will walk you through formats, strengths and what to expect. '
        . 'Start low, go slow &mdash; especially with edibles, which can take up to two hours to take effect.</p>') ],
    [ $h('Cannabis <span class="cc-accent">Learning Center</span>', 'h3', 'left'),
      $tx('<p>Flower, pre-rolls, edibles, vapes, topicals, tinctures and concentrates &mdash; '
        . 'what each one is, how it is measured, and how to store it safely away from children and pets.</p>') ],
]);

/* 7. Licensed & verified */
$new[] = $row([[
    $h('Fully Licensed &amp; <span class="cc-accent">Verified</span>'),
    $tx('<p style="text-align:center">On The Bus Inc. d/b/a Cuse Clouds &middot; '
      . 'Licensed Adult-Use Cannabis Retail Dispensary<br>'
      . 'New York State OCM License # <strong>' . $LIC . '</strong></p>'
      . '<p style="text-align:center">You can confirm this licence directly with the New York State '
      . 'Office of Cannabis Management.</p>'),
    $bt('Verify with NYS OCM', 'https://cannabis.ny.gov/dispensary-location-verification'),
]]);

$log('rows to be written: ' . count($new));
$json = json_encode($new, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
if ($json === false) {
    exit('json_encode failed: ' . json_last_error_msg() . "\n");
}
$log('layout size: ' . strlen($json) . ' bytes');

/* ------------------------------------------------------------- the write */

$title = 'Home — New Design';

if (!$apply) {
    $log('');
    $log('DRY RUN — nothing written.');
    $log('Re-run with &apply=1 to create the page (add &publish=1 to publish it).');
    $log('');
    $log('Preview of the row/column shape that will be stored:');
    foreach ($new as $i => $r) {
        $cols = isset($r->columns) ? count($r->columns) : 0;
        $types = [];
        foreach (($r->columns ?? []) as $c) {
            foreach (($c->addons ?? []) as $a) { $types[] = $a->type ?? '?'; }
        }
        $log(sprintf('  row %-2d  %d column(s)  [%s]', $i, $cols, implode(', ', $types)));
    }
    exit;
}

/* Clone the source DB row so every NOT NULL column is satisfied, then
   override only what identifies this as a new page. */
$record = $src;
unset($record['id']);
$record['title'] = $title;
$record['text']  = $json;

foreach (['alias' => 'home-new-design', 'published' => $publish, 'ordering' => 0,
          'checked_out' => 0, 'hits' => 0] as $k => $v) {
    if (array_key_exists($k, $record)) { $record[$k] = $v; }
}
foreach (['created', 'modified'] as $k) {
    if (array_key_exists($k, $record)) { $record[$k] = gmdate('Y-m-d H:i:s'); }
}
if (array_key_exists('checked_out_time', $record)) { $record['checked_out_time'] = null; }

$cols   = array_keys($record);
$place  = implode(', ', array_map(static fn($c) => ':' . $c, $cols));
$sql    = "INSERT INTO `{$p}sppagebuilder` (`" . implode('`, `', $cols) . "`) VALUES ({$place})";

try {
    $pdo->beginTransaction();
    $stmt = $pdo->prepare($sql);
    foreach ($record as $k => $v) { $stmt->bindValue(':' . $k, $v); }
    $stmt->execute();
    $newId = (int) $pdo->lastInsertId();
    $pdo->commit();
} catch (Throwable $e) {
    if ($pdo->inTransaction()) { $pdo->rollBack(); }
    exit("INSERT FAILED (nothing was changed): " . $e->getMessage() . "\n");
}

$log('');
$log("CREATED page #{$newId} \"{$title}\" — published=" . $publish);
$log('');
$log('Next:');
$log('  1. Joomla admin → SP Page Builder → Pages → open "' . $title . '" and preview it.');
$log('  2. When you are happy, point your Home menu item at it (Menus → Main Menu → Home).');
$log('  3. Rename /index.html to /index-static.html so Joomla serves the front page again.');
$log('  4. DELETE this file (/cc-install-home.php).');
$log('');
$log("Rollback: delete page #{$newId} in SP Page Builder. Your original page #{$srcId} was never touched.");
