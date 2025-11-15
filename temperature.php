<?php
date_default_timezone_set('Europe/Helsinki');

$latestFile  = "latest.json";
$historyFile = "history.json";
$configFile  = "config.json";

// Load min/max temperature defaults
$tempMin = 20.0;
$tempMax = 22.0;
if (file_exists($configFile)) {
    $cfg = json_decode(file_get_contents($configFile), true);
    if (isset($cfg["tempMin"])) $tempMin = floatval($cfg["tempMin"]);
    if (isset($cfg["tempMax"])) $tempMax = floatval($cfg["tempMax"]);
}

// Handle form POST to update min/max
if (isset($_POST['tempMin'])) $tempMin = floatval($_POST['tempMin']);
if (isset($_POST['tempMax'])) $tempMax = floatval($_POST['tempMax']);
file_put_contents($configFile, json_encode(["tempMin"=>$tempMin,"tempMax"=>$tempMax]));

// Load last known valve state (default close)
$lastState = ["valve"=>"close"];
if (file_exists($latestFile)) {
    $decoded = json_decode(file_get_contents($latestFile), true);
    if (is_array($decoded) && isset($decoded["valve"])) $lastState = $decoded;
}

// Handle temperature input from Arduino
if (isset($_POST['temperature'])) {
    $temperature = floatval($_POST['temperature']);
    $timestamp = date("Y-m-d H:i:s");

    // Determine valve state
    if ($temperature >= $tempMax) {
        $valve = "open";
    } elseif ($temperature <= $tempMin) {
        $valve = "close";
    } else {
        $valve = $lastState["valve"]; // keep previous
    }

    $data = [
        "temperature" => $temperature,
        "timestamp" => $timestamp,
        "valve" => $valve,
        "tempMin" => $tempMin,
        "tempMax" => $tempMax
    ];

    // Save latest
    file_put_contents($latestFile, json_encode($data));

    // Append to history (max 500 entries)
    $history = [];
    if (file_exists($historyFile)) {
        $decodedHistory = json_decode(file_get_contents($historyFile), true);
        if (is_array($decodedHistory)) $history = $decodedHistory;
    }
    $history[] = $data;
    if (count($history) > 500) $history = array_slice($history, -500);
    file_put_contents($historyFile, json_encode($history));

    header('Content-Type: application/json');
    echo json_encode($data);
} else {
    // Return latest state if no POST temperature
    if (file_exists($latestFile)) {
        header('Content-Type: application/json');
        echo file_get_contents($latestFile);
    } else {
        header('Content-Type: application/json');
        echo json_encode(["error"=>"No data available"]);
    }
}
?>
