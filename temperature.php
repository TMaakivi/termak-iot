<?php
date_default_timezone_set('Europe/Helsinki');

$latestFile  = "latest.json";
$historyFile = "history.json";
$configFile  = "config.json";

// Load target temperature (default 21 °C)
$targetTemp = 21;
if (file_exists($configFile)) {
    $cfg = json_decode(file_get_contents($configFile), true);
    if (isset($cfg["target_temperature"])) {
        $targetTemp = floatval($cfg["target_temperature"]);
    }
}

// Load last known valve state (default close)
$lastState = ["valve" => "close"];
if (file_exists($latestFile)) {
    $decoded = json_decode(file_get_contents($latestFile), true);
    if (is_array($decoded) && isset($decoded["valve"])) {
        $lastState = $decoded;
    }
}

// 🔑 Optional update: if target_temperature included in POST
if (isset($_POST['target_temperature'])) {
    $targetTemp = floatval($_POST['target_temperature']);
    file_put_contents($configFile, json_encode(["target_temperature" => $targetTemp]));
}

// Handle temperature input
if (isset($_POST['temperature'])) {
    $temperature = floatval($_POST['temperature']);
    $timestamp   = date("Y-m-d H:i:s");

    // Hysteresis: target ±1 °C
    $upper = $targetTemp + 1;
    $lower = $targetTemp - 1;

    if ($temperature >= $upper) {
        $valve = "open";
    } elseif ($temperature < $lower) {
        $valve = "close";
    } else {
        $valve = $lastState["valve"]; // keep previous
    }

    $data = [
        "temperature" => $temperature,
        "timestamp"   => $timestamp,
        "valve"       => $valve,
        "target"      => $targetTemp
    ];

    // Save latest
    file_put_contents($latestFile, json_encode($data));

    // Append to history (max 500 entries)
    $history = [];
    if (file_exists($historyFile)) {
        $decodedHistory = json_decode(file_get_contents($historyFile), true);
        if (is_array($decodedHistory)) {
            $history = $decodedHistory;
        }
    }
    $history[] = $data;
    if (count($history) > 500) {
        $history = array_slice($history, -500);
    }
    file_put_contents($historyFile, json_encode($history));

    header('Content-Type: application/json');
    echo json_encode($data);
} else {
    // If no POST, return latest state
    if (file_exists($latestFile)) {
        header('Content-Type: application/json');
        echo file_get_contents($latestFile);
    } else {
        header('Content-Type: application/json');
        echo json_encode(["error" => "No data available"]);
    }
}
?>
