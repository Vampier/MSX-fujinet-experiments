<?php

$page = isset($_GET['page']) ? (int)$_GET['page'] : 101;
$page = max(100, min(999, $page));

$url = "https://teletekst-data.nos.nl/json/{$page}";
$json = @file_get_contents($url);

if ($json === false) {
    outputErrorPage($page);
    exit;
}

$data = json_decode($json, true);

if (!isset($data['content']) || trim($data['content']) === '' || 
    strpos($json, 'No content found') !== false) {
    outputErrorPage($page);
    exit;
}

try {
    $lines = parseTeletekst($data['content']);
    $lines = padTo24Lines($lines);
} catch (Exception $e) {
    outputErrorPage($page);
    exit;
}

$result = [
    'page'      => $page,
    'prevPage'  => $data['prevPage'] ?? null,
    'nextPage'  => $data['nextPage'] ?? null,
    'lines'     => $lines
];

header('Content-Type: application/json; charset=utf-8');
echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

// ====================== Helpers ======================

function outputErrorPage($page)
{
    $result = [
        'page'     => $page,
        'prevPage' => null,
        'nextPage' => null,
        'lines'    => generateErrorPage(),
        'error'    => '         Pagina niet beschikbaar'
    ];

    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}

function generateErrorPage(): array
{
    $lines = ["Pagina niet beschikbaar"];
    for ($i = 1; $i < 24; $i++) {
        $lines[] = "";
    }
    return $lines;
}

function padTo24Lines(array $lines): array
{
    while (count($lines) < 24) {
        $lines[] = "";
    }
    return array_slice($lines, 0, 24);
}

/**
 * Minimal cleaning - preserves original spacing as much as possible
 */
function parseTeletekst($html): array
{
    $resultLines = [];
    $rawLines = explode("\n", $html);

    foreach ($rawLines as $raw) {
        // Replace ONLY teletext special codes with space
        $raw = preg_replace('/&#xF0[0-9A-Fa-f]{2};/', ' ', $raw);

        // Decode
        $text = html_entity_decode($raw, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // Strip tags
        $text = strip_tags($text);

        // Keep ONLY printable ASCII, replace rest with space
        $text = preg_replace('/[^\x20-\x7E]/', ' ', $text);

        // Do NOT collapse spaces at all - keep original layout
        $resultLines[] = $text;
    }

    return $resultLines;
}
