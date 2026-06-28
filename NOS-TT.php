<?php

$page = isset($_GET['page']) ? (int)$_GET['page'] : 101;
$page = max(100, min(999, $page));

$plainMode = isset($_GET['plain']) && $_GET['plain'] == 1;

$url = "https://teletekst-data.nos.nl/json/{$page}";
$json = file_get_contents($url);

if ($json === false) {
    http_response_code(502);
    die(json_encode(['error' => "Could not fetch page {$page}"]));
}

$data = json_decode($json, true);

function parseTeletekst($html, $plainMode) {
    $resultLines = [];
    $firstLine = true;

    $rawLines = explode("\n", $html);

    foreach ($rawLines as $raw) {
        if ($raw === '') continue;

        // Add [#N] only if not in plain mode and not first line
        if (!$firstLine && !$plainMode) {
            $resultLines[] = "[#N]";
        }
        $firstLine = false;

        // Convert Teletext special codes to spaces
        $raw = preg_replace('/&#xF0[0-9A-Fa-f]{2};/', ' ', $raw);

        $text = html_entity_decode($raw, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML('<root>' . $raw . '</root>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();

        $xpath = new DOMXPath($dom);
        $currentText = '';

        foreach ($xpath->query('//text() | //span') as $node) {
            if ($node->nodeType === XML_TEXT_NODE) {
                $part = $node->textContent;
                $currentText .= $part;
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

$lines = parseTeletekst($data['content'] ?? '', $plainMode);

$result = [
    'page'      => $page,
    'prevPage'  => $data['prevPage'] ?? null,
    'nextPage'  => $data['nextPage'] ?? null,
    'lines'     => $lines
];

header('Content-Type: application/json; charset=utf-8');
echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
