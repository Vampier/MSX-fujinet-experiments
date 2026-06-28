<?php

$page = isset($_GET['page']) ? (int)$_GET['page'] : 101;
$page = max(100, min(999, $page));

$plainMode = isset($_GET['plain']) && $_GET['plain'] == 1;

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
    $lines = parseTeletekst($data['content'], $plainMode);
    
    if (count($lines) < 5) {
        outputErrorPage($page);
        exit;
    }
} catch (Exception $e) {
    outputErrorPage($page);
    exit;
}

// Success
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
    $lines = generateErrorPage();
    
    $result = [
        'page'     => $page,
        'prevPage' => null,
        'nextPage' => null,
        'lines'    => $lines,
        'error'    => 'Pagina niet beschikbaar'
    ];

    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}

function generateErrorPage(): array
{
    $lines = [];
    
    $title = "Pagina niet beschikbaar";
    $padding = str_repeat(' ', (40 - mb_strlen($title)) / 2);
    $lines[] = $padding . $title . str_repeat(' ', 40 - mb_strlen($padding . $title)); // ensure exactly 40 chars
    
    // All other lines are just ONE space long as requested
    $empty = ' ';
    for ($i = 1; $i < 24; $i++) {
        $lines[] = $empty;
    }
    
    return $lines;
}

function parseTeletekst($html, $plainMode) {
    $resultLines = [];
    $firstLine = true;

    $rawLines = explode("\n", $html);

    foreach ($rawLines as $raw) {
        if ($raw === '') continue;

        if (!$firstLine && !$plainMode) {
            $resultLines[] = "[#N]";
        }
        $firstLine = false;

        $raw = preg_replace('/&#xF0[0-9A-Fa-f]{2};/', ' ', $raw);

        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML('<root>' . $raw . '</root>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();

        $xpath = new DOMXPath($dom);
        $currentText = '';

        foreach ($xpath->query('//text() | //span') as $node) {
            if ($node->nodeType === XML_TEXT_NODE) {
                $currentText .= $node->textContent;
            } elseif ($node->nodeName === 'span' && !$plainMode) {
                $class = $node->getAttribute('class');
                $colorCode = '';
                if (strpos($class, 'green')  !== false) $colorCode = '3';
                elseif (strpos($class, 'red')    !== false) $colorCode = '9';
                elseif (strpos($class, 'yellow') !== false) $colorCode = 'A';
                elseif (strpos($class, 'cyan')   !== false) $colorCode = '7';
                elseif (strpos($class, 'blue')   !== false) $colorCode = '4';

                if ($colorCode !== '') {
                    if ($currentText !== '') {
                        $resultLines[] = $currentText;
                        $currentText = '';
                    }
                    $resultLines[] = "[#C{$colorCode}]";
                }
            }
        }

        if ($currentText !== '') {
            $resultLines[] = $currentText;
        }
    }

    return $resultLines;
}
