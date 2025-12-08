<?php
// ================== BACKEND: AI ENDPOINT (same file) ==================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['ai'])) {
    header('Content-Type: application/json');

    $input       = json_decode(file_get_contents('php://input'), true);
    $userMessage = trim($input['message'] ?? '');
    $lang        = $input['lang']  ?? 'en';
    $topic       = $input['topic'] ?? 'general';

    if ($userMessage === '') {
        echo json_encode(['reply' => 'Empty message.']);
        exit;
    }

    // TODO: yahan apna real API key daalo
    $apiKey = 'hfU3NZHtxf7xf8emWbUR5T3BlbkFJnC9yMh9KQm9l7KLcqdaKPgbuudYbhyjVXyPzYbHQcKu-OUJ2oLg15CB7rybujLVTONOcPdh-IA';

    // ---- System prompt banana ----
    if ($lang === 'hi') {
        $basePrompt = "You are a friendly shopping assistant for FLASH MARKET. Speak mainly in Hindi with simple English product words, short sentences and emojis. ";
    } else {
        $basePrompt = "You are a friendly human-like shopping assistant for FLASH MARKET. Speak in short, clear English with a friendly tone and emojis. ";
    }

    switch ($topic) {
        case 'products':
            $topicText = "Focus on helping users find products. Understand category, gender, size, color and budget. Suggest 3 matching items with name and price, then ask which one they prefer.";
            break;
        case 'orders':
            $topicText = "Focus on order status and delivery issues. Ask for order ID or phone number and explain clearly what they can do next.";
            break;
        case 'returns':
            $topicText = "Focus on returns and exchanges. Ask which product and what went wrong (size, color, damage) and explain a simple return process.";
            break;
        default:
            $topicText = "Answer general questions about products, orders, payments, offers and policies in a concise, friendly way.";
    }

    $systemPrompt = $basePrompt . $topicText;

    // ---- Responses API payload ----
    $payload = [
        "model" => "gpt-4.1-mini",
        "input" => [
            ["role" => "system", "content" => $systemPrompt],
            ["role" => "user",   "content" => $userMessage]
        ]
    ];

    $ch = curl_init("https://api.openai.com/v1/responses");
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey
        ],
        CURLOPT_POSTFIELDS     => json_encode($payload),
        CURLOPT_RETURNTRANSFER => true,
    ]);

    $result = curl_exec($ch);

    if ($result === false) {
        echo json_encode(['reply' => 'cURL error: ' . curl_error($ch)]);
        exit;
    }

    curl_close($ch);

    // Debug ke liye save kar sakta hai (optional)
    // file_put_contents('ai_debug.log', $result . PHP_EOL, FILE_APPEND);

    $data = json_decode($result, true);

    // Agar API error ho
    if (isset($data['error'])) {
        echo json_encode(['reply' => 'API error: ' . ($data['error']['message'] ?? 'Unknown error')]);
        exit;
    }

    // Responses API se text nikalna
    $reply = 'No reply from AI.';
    if (isset($data['output']['choices'][0]['message']['content'])) {
        $parts = $data['output']['choices'][0]['message']['content'];
        $textParts = [];
        foreach ($parts as $p) {
            if (($p['type'] ?? '') === 'output_text' && isset($p['text'])) {
                $textParts[] = $p['text'];
            }
        }
        if (!empty($textParts)) {
            $reply = implode("\n", $textParts);
        }
    }

    echo json_encode(['reply' => $reply]);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Flash Market – AI Chat</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
    :root {
        --primary: #ff9800;
        --bg: #06132b;
        --bg-dark: #04101f;
        --text-color: #fff;
    }
    body {
        margin: 0;
        background: #04101f;
        color: var(--text-color);
        font-family: system-ui, sans-serif;
    }
    .chat-btn {
        position: fixed;
        right: 16px;
        bottom: 20px;
        z-index: 9999;
        border: none;
        background: var(--primary);
        color: #000;
        font-weight: 600;
        padding: 10px 18px;
        border-radius: 995px;
        cursor: pointer;
        box-shadow: 0 4px 14px rgba(0, 0, 0, .5);
    }
    .chat-box {
        position: fixed;
        right: 16px;
        top: 0;
        width: 320px;
        max-height: 1200px;
        background: var(--bg);
        color: var(--text-color);
        border-radius: 4px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, .6);
        display: none;
        flex-direction: column;
        overflow: hidden;
        z-index: 9999;
        font-size: 13px;
    }
    .chat-header {
        background: var(--primary);
        color: #000;
        padding: 8px;
        display: flex;
        align-items: center;
        gap: 6px;
        font-weight: 700;
    }
    .chat-header select {
        background: #ff9800;
        border: 1px solid #aa6b00;
        border-radius: 6px;
        font-size: 11px;
        padding: 2px 4px;
        cursor: pointer;
    }
    .chat-header .spacer { flex: 1; }
    .chat-messages {
        padding: 8px;
        background: var(--bg-dark);
        flex: 1;
        overflow-y: auto;
    }
    .msg {
        max-width: 80%;
        margin: 4px 0;
        padding: 6px 8px;
        border-radius: 8px;
    }
    .msg.user {
        margin-left: auto;
        background: var(--primary);
        color: #000;
    }
    .msg.bot {
        background: #1b2744;
    }
    .chat-input {
        display: flex;
        border-top: 1px solid #222;
        padding: 8px;
        width: 100%;
        box-sizing: border-box;
        gap: 4px;
        background: var(--bg);
    }
    .chat-input input {
        flex: 1;
        border: 1px solid #333;
        background: #1b2744;
        color: var(--text-color);
        padding: 8px;
        font-size: 13px;
        outline: none;
        border-radius: 6px;
    }
    .chat-input button {
        border: none;
        background: var(--primary);
        color: #000;
        padding: 0 12px;
        font-weight: 600;
        cursor: pointer;
        border-radius: 6px;
        min-width: 55px;
    }
    @media (max-width:600px) {
        .chat-box {
            width: 100%;
            right: 0;
            bottom: 0;
            max-height: 65vh;
            border-radius: 12px 12px 0 0;
        }
        .chat-btn {
            right: 50%;
            transform: translateX(50%);
        }
    }
    </style>
</head>
<body>
    <button class="chat-btn" id="ai-open">💬 Chat</button>

    <div class="chat-box" id="ai-box">
        <div class="chat-header">
            <span>Assistant</span>
            <select id="lang-select">
                <option value="en" selected>EN</option>
                <option value="hi">HI</option>
            </select>
            <select id="topic-select">
                <option value="general" selected>General</option>
                <option value="products">Products</option>
                <option value="orders">Orders</option>
                <option value="returns">Returns</option>
            </select>
            <span class="spacer"></span>
        </div>

        <div class="chat-messages" id="ai-msgs">
            <div class="msg bot">
                Hi! Choose language and topic above, then type your question about products, orders, or returns.
            </div>
        </div>

        <div class="chat-input">
            <input type="text" id="ai-input" placeholder="Type your message...">
            <button id="ai-send">Send</button>
        </div>
    </div>

    <script>
    const openBtn  = document.getElementById('ai-open');
    const box      = document.getElementById('ai-box');
    const input    = document.getElementById('ai-input');
    const sendBtn  = document.getElementById('ai-send');
    const msgs     = document.getElementById('ai-msgs');
    const langSel  = document.getElementById('lang-select');
    const topicSel = document.getElementById('topic-select');

    openBtn.onclick = () => {
        box.style.display = 'flex';
        openBtn.style.visibility = 'hidden';
        input.focus();
    };

    function addMsg(text, type) {
        const div = document.createElement('div');
        div.className = 'msg ' + type;
        div.textContent = text;
        msgs.appendChild(div);
        msgs.scrollTop = msgs.scrollHeight;
    }

    async function callAI(message) {
        const typing = document.createElement('div');
        typing.className = 'msg bot';
        typing.textContent = 'Typing...';
        msgs.appendChild(typing);
        msgs.scrollTop = msgs.scrollHeight;

        const lang  = langSel.value;
        const topic = topicSel.value;

        try {
            const res = await fetch('?ai=1', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ message, lang, topic })
            });

            const data = await res.json();
            typing.textContent = data.reply || 'No reply from AI.';
            msgs.scrollTop = msgs.scrollHeight;
        } catch (e) {
            console.error(e);
            typing.textContent = 'Could not connect to server.';
        }
    }

    async function sendMessage() {
        const q = input.value.trim();
        if (!q) return;
        addMsg(q, 'user');
        input.value = '';
        await callAI(q);
    }

    sendBtn.onclick = sendMessage;
    input.addEventListener('keydown', e => {
        if (e.key === 'Enter') {
            e.preventDefault();
            sendMessage();
        }
    });
    </script>
</body>
</html>
