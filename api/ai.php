<?php

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$type = $input['type'] ?? 'chat';
$message = $input['message'] ?? '';

$apiKey = getenv('OPENROUTER_API_KEY') ?: "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";
$baseUrl = "https://openrouter.ai/api/v1/chat/completions";

$systemPrompt = "You are GanjiSmart, a premium AI financial partner for a Gen-Z investor. Your tone is corporate but relatable (using Kenayn slang like Chief, Bazu, Bigman, Mkuruu where appropriate). You are disciplined, focused on capital preservation and outlier returns. You handle money management first, investment second. Keep responses concise, premium, and professional.";

if ($type === 'daily_insight') {
    $prompt = "Give a one-sentence daily financial insight/reminder based on the current market spirit. Be highly disciplined and slightly slangy.";
} else {
    $prompt = $message;
}

$data = [
    "model" => "google/gemini-2.0-pro-exp-02-05:free",
    "messages" => [
        ["role" => "system", "content" => $systemPrompt],
        ["role" => "user", "content" => $prompt]
    ]
];

$ch = curl_init($baseUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer " . $apiKey,
    "Content-Type: application/json",
    "HTTP-Referer: http://localhost/Ganji", // Required by OpenRouter
    "X-Title: GanjiSmart"
]);

$response = curl_exec($ch);
curl_close($ch);

if ($response) {
    $decoded = json_decode($response, true);
    $reply = $decoded['choices'][0]['message']['content'] ?? "Chief, my brain's a bit foggy. Let's talk in a minute.";
    echo json_encode(['response' => $reply]);
} else {
    echo json_encode(['error' => 'API Error']);
}
