<?php

// Get the page parameter (can be number or "number-subnumber")
$pageInput = isset($_GET['page']) ? trim($_GET['page']) : '101';

// Allow only valid format: 100-999 or 100-1 up to 999-9
if (!preg_match('/^(\d{3})(?:-(\d+))?$/', $pageInput, $matches)) {
    $pageInput = '101'; // fallback
}

$page = $matches[1] ?? '101';           // main page number
$sub  = isset($matches[2]) ? $matches[2] : '';

$url = "https://teletekst-data.nos.nl/json/{$page}" . ($sub ? "-{$sub}" : "");

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
        'error'    => 'Pagina niet beschikbaar'
    ];

    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}

function generateErrorPage(): array
{
    $lines = ["         Pagina niet beschikbaar"];
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

        // Decode HTML entities first
        $text = html_entity_decode($raw, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // Convert accented characters to ASCII-safe equivalents
        $text = strtr($text, [
            'á' => 'a', 'à' => 'a', 'ä' => 'a', 'â' => 'a',
            'é' => 'e', 'è' => 'e', 'ë' => 'e', 'ê' => 'e',
            'í' => 'i', 'ì' => 'i', 'ï' => 'i', 'î' => 'i',
            'ó' => 'o', 'ò' => 'o', 'ö' => 'o', 'ô' => 'o',
            'ú' => 'u', 'ù' => 'u', 'ü' => 'u', 'û' => 'u',
            'ý' => 'y',
            'ñ' => 'n',
            'ç' => 'c',
            'Á' => 'A', 'À' => 'A', 'Ä' => 'A', 'Â' => 'A',
            'É' => 'E', 'È' => 'E', 'Ë' => 'E', 'Ê' => 'E',
            'Í' => 'I', 'Ì' => 'I', 'Ï' => 'I', 'Î' => 'I',
            'Ó' => 'O', 'Ò' => 'O', 'Ö' => 'O', 'Ô' => 'O',
            'Ú' => 'U', 'Ù' => 'U', 'Ü' => 'U', 'Û' => 'U',
            'Ñ' => 'N',
            'Ç' => 'C',
        ]);

        // Strip tags
        $text = strip_tags($text);

        // Keep ONLY printable ASCII, replace rest with space
        $text = preg_replace('/[^\x20-\x7E]/', ' ', $text);

        // Do NOT collapse spaces at all - keep original layout
        $resultLines[] = $text;
    }

    return $resultLines;
}
