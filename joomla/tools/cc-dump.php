<?php
/**
 * ONE-TIME, READ-ONLY diagnostic for the Cuse Clouds Joomla site.
 *
 * Purpose: read the existing SP Page Builder page layouts so a new page can be
 * authored against the real SPPB 6.8 schema instead of a guessed one.
 *
 * It performs SELECT queries only — it never writes, updates, or deletes.
 * It is gated behind a one-time token and MUST be deleted straight after use.
 *
 * Usage:  /cc-dump.php?t=<token>            → list all SPPB pages
 *         /cc-dump.php?t=<token>&page=<id>  → dump one page's layout JSON
 */

const CC_TOKEN = '2d0e3f8d5fa576ec590c0ba5045215f3';

if (!isset($_GET['t']) || !hash_equals(CC_TOKEN, (string) $_GET['t'])) {
    http_response_code(404);
    exit;
}

header('Content-Type: application/json; charset=utf-8');
header('X-Robots-Tag: noindex, nofollow');

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
    echo json_encode(['error' => 'db_connect_failed', 'message' => $e->getMessage()]);
    exit;
}

$p = $cfg->dbprefix;

/** Which columns does #__sppagebuilder actually have? */
$columns = [];
foreach ($pdo->query("SHOW COLUMNS FROM `{$p}sppagebuilder`") as $row) {
    $columns[] = ['field' => $row['Field'], 'type' => $row['Type']];
}

$pageId = isset($_GET['page']) ? (int) $_GET['page'] : 0;

if (!$pageId) {
    $stmt = $pdo->query(
        "SELECT id, title, extension, extension_view, view_id, published, LENGTH(text) AS layout_bytes
           FROM `{$p}sppagebuilder`
       ORDER BY id"
    );
    $pages = $stmt->fetchAll();

    /* Which menu item is the site's home, and what does it point at? */
    $home = $pdo->query(
        "SELECT id, title, alias, link, template_style_id
           FROM `{$p}menu`
          WHERE home = 1 AND published = 1
          LIMIT 1"
    )->fetch();

    echo json_encode([
        'sppagebuilder_columns' => $columns,
        'pages'                 => $pages,
        'home_menu_item'        => $home,
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM `{$p}sppagebuilder` WHERE id = :id");
$stmt->execute([':id' => $pageId]);
$page = $stmt->fetch();

if (!$page) {
    echo json_encode(['error' => 'page_not_found', 'id' => $pageId]);
    exit;
}

$layout = json_decode($page['text'] ?? '[]');

/** Summarise the tree so the shape is obvious without dumping megabytes. */
$summarise = static function ($nodes, $depth = 0) use (&$summarise) {
    $out = [];
    foreach ((array) $nodes as $node) {
        $entry = [
            'keys' => array_keys(get_object_vars((object) $node)),
        ];
        if (isset($node->type)) {
            $entry['type'] = $node->type;
        }
        if (isset($node->settings)) {
            $entry['setting_keys'] = array_keys(get_object_vars($node->settings));
        }
        if (!empty($node->columns)) {
            $entry['columns'] = $summarise($node->columns, $depth + 1);
        }
        if (!empty($node->addons)) {
            $entry['addons'] = $summarise($node->addons, $depth + 1);
        }
        $out[] = $entry;
    }
    return $out;
};

echo json_encode([
    'id'        => $page['id'],
    'title'     => $page['title'],
    'published' => $page['published'],
    'row_count' => is_array($layout) ? count($layout) : 0,
    'shape'     => $summarise($layout),
    'first_row' => is_array($layout) && isset($layout[0]) ? $layout[0] : null,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
