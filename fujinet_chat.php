<?php
// ==================== CONFIG ====================
$logFile   = 'posts.json';
$countFile = 'count.json';

// Initialize files
if (!file_exists($logFile))  file_put_contents($logFile, json_encode([], JSON_PRETTY_PRINT));
if (!file_exists($countFile)) file_put_contents($countFile, json_encode(['lines' => 0], JSON_PRETTY_PRINT));

// ==================== HANDLE MESSAGE ====================

$successMessage = '';
$text = '';

// Accept from MSX (GET) and Web Form (POST)
if (isset($_GET['msg']))   $text = trim($_GET['msg']);
elseif (isset($_GET['data'])) $text = trim($_GET['data']);
elseif (isset($_POST['text'])) $text = trim($_POST['text']);
elseif (isset($_POST['msg']))  $text = trim($_POST['msg']);

if ($text !== '') {
    if (strlen($text) > 70) $text = substr($text, 0, 70);

    if (preg_match('/^[\x20-\x7E]*$/', $text)) {
        $entry = ['time' => date('Y-m-d H:i:s'), 'message' => $text];

        $posts = json_decode(file_get_contents($logFile), true) ?: [];
        $posts[] = $entry;
        file_put_contents($logFile, json_encode($posts, JSON_PRETTY_PRINT));
        file_put_contents($countFile, json_encode(['lines' => count($posts)], JSON_PRETTY_PRINT));

        $successMessage = "✅ Message posted successfully!";
    }
}

// ==================== JSON API: ?get=N (for live chat) ====================
if (isset($_GET['get'])) {
    $limit = max(1, (int)$_GET['get']);
    $posts = json_decode(file_get_contents($logFile), true) ?: [];
    $result = array_slice($posts, -$limit);

    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'count' => count($result),
        'messages' => $result
    ], JSON_PRETTY_PRINT);
    exit;
}

// ==================== JSON RESPONSE FOR MSX ====================
if (isset($_GET['msg']) || isset($_GET['data'])) {
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'ok',
        'message' => 'Message posted successfully',
        'total' => count(json_decode(file_get_contents($logFile), true) ?: [])
    ], JSON_PRETTY_PRINT);
    exit;
}

// ==================== NORMAL WEB PAGE ====================
$posts = json_decode(file_get_contents($logFile), true) ?: [];
$currentCount = count($posts);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MSX Chat Wall</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 30px auto; padding: 20px; background: #f8f9fa; }
        .chat { background: white; border: 1px solid #ddd; border-radius: 8px; padding: 15px; height: 450px; overflow-y: auto; margin-bottom: 20px; font-family: monospace; }
        .chat-line { margin: 6px 0; line-height: 1.4; }
        .time { color: #888; font-size: 0.85em; }
        input[type="text"] { width: 100%; padding: 12px; font-size: 16px; font-family: monospace; box-sizing: border-box; }
        .message { padding: 12px; margin: 15px 0; border-radius: 6px; font-weight: bold; }
        .success { background: #d4edda; color: #155724; }
        .error { background: #f8d7da; color: #721c24; }
        button { padding: 12px 24px; font-size: 16px; background: #007bff; color: white; border: none; border-radius: 6px; cursor: pointer; margin-top: 8px; }
        button:hover { background: #0056b3; }
    </style>
</head>
<body>
    <h1>💾 MSX Chat Wall <span id="status">(Live)</span></h1>

    <?php if ($successMessage): ?>
        <div class="message success"><?= $successMessage ?></div>
    <?php endif; ?>

    <h2>Chat Log (<span id="lineCount"><?= $currentCount ?></span> messages)</h2>
    <div class="chat" id="chat">
        <?php foreach ($posts as $p): ?>
            <div class="chat-line">
                <span class="time">[<?= htmlspecialchars($p['time']) ?>]</span> 
                <?= htmlspecialchars($p['message']) ?>
            </div>
        <?php endforeach; ?>
    </div>

    <form method="post">
        <input type="text" name="text" id="text" maxlength="70" required 
               placeholder="Type your message (max 70 chars)...">
        <br><br>
        <small>Characters left: <span id="count">70</span></small><br><br>
        <button type="submit">Post Message</button>
    </form>

    <script>
        let lastCount = <?= $currentCount ?>;
        const chatDiv = document.getElementById('chat');
        const lineCountSpan = document.getElementById('lineCount');
        const input = document.getElementById('text');
        const countSpan = document.getElementById('count');

        function updateCount() {
            let text = input.value.replace(/[^\x20-\x7E]/g, '');
            input.value = text;
            countSpan.textContent = 70 - text.length;
        }
        input.addEventListener('input', updateCount);
        updateCount();

        function scrollToBottom() { chatDiv.scrollTop = chatDiv.scrollHeight; }
        scrollToBottom();

        async function pollUpdates() {
            try {
                const res = await fetch('index.php?get=1000&' + Date.now());
                const data = await res.json();
                if (data.success && data.count > lastCount) {
                    chatDiv.innerHTML = '';
                    data.messages.forEach(msg => {
                        const div = document.createElement('div');
                        div.className = 'chat-line';
                        div.innerHTML = `<span class="time">[${msg.time}]</span> ${msg.message}`;
                        chatDiv.appendChild(div);
                    });
                    lastCount = data.count;
                    lineCountSpan.textContent = lastCount;
                    scrollToBottom();
                }
            } catch(e) {}
        }
        setInterval(pollUpdates, 3000);
    </script>
</body>
</html>
