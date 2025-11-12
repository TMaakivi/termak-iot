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

// Handle form submission to update target
if (isset($_POST['target_temperature'])) {
    $targetTemp = floatval($_POST['target_temperature']);
    file_put_contents($configFile, json_encode(["target_temperature" => $targetTemp]));
    $message = "Target temperature updated to $targetTemp °C";
}

// Load latest and history
$latestData  = null;
$historyData = [];
if (file_exists($latestFile)) {
    $decoded = json_decode(file_get_contents($latestFile), true);
    if (is_array($decoded)) $latestData = $decoded;
}
if (file_exists($historyFile)) {
    $decodedHistory = json_decode(file_get_contents($historyFile), true);
    if (is_array($decodedHistory)) $historyData = $decodedHistory;
}
// Clear history
if (isset($_POST['clear_history'])) {
    file_put_contents($historyFile, json_encode([]));
    $message = "History cleared successfully.";
}

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Termak IoT Dashboard</title>
    <meta http-equiv="refresh" content="10">
    <style>
        body { font-family: Arial, sans-serif; margin: 30px; }
        table { border-collapse: collapse; width: 700px; margin-bottom: 20px; }
        th, td { border: 1px solid #ccc; padding: 8px; text-align: center; }
        th { background-color: #f2f2f2; }
        form { margin-bottom: 20px; }
        .temp-control {
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        .temp-control input {
            font-size: 22px;
            text-align: center;
            width: 100px;
            padding: 10px;
        }
        .temp-control .arrow {
            font-size: 26px;
            padding: 10px 15px;
            cursor: pointer;
            border: 1px solid #666;
            background: #eee;
            border-radius: 6px;
        }
        .temp-control .arrow:hover {
            background: #ddd;
        }
    </style>
</head>
<body>
    <h2>Termak IoT Dashboard</h2>

    <form method="POST" id="targetForm">
        <label>Target Temperature (°C):</label>
        <div class="temp-control">
            <button type="button" class="arrow" onclick="changeTemp(-0.5)">▼</button>
            <input type="number" step="0.1" name="target_temperature" id="targetTemp" value="<?= htmlspecialchars($targetTemp) ?>">
            <button type="button" class="arrow" onclick="changeTemp(0.5)">▲</button>
        </div>
        <noscript><button type="submit">Save</button></noscript>
    </form>
    <form method="POST">
        <button type="submit" name="clear_history" onclick="return confirm('Clear all history?')">Clear History</button>
    </form>
    <?php if (isset($message)) echo "<p><b>$message</b></p>"; ?>

    <?php if ($latestData): ?>
        <h3>Latest Reading</h3>
        <table>
            <tr>
                <th>Timestamp</th>
                <th>Temperature (°C)</th>
                <th>Valve Status</th>
                <th>Target (°C)</th>
            </tr>
            <tr>
                <td><?= htmlspecialchars($latestData["timestamp"] ?? "—") ?></td>
                <td><?= htmlspecialchars($latestData["temperature"] ?? "—") ?></td>
                <td><?= htmlspecialchars($latestData["valve"] ?? "—") ?></td>
                <td><?= htmlspecialchars($latestData["target"] ?? $targetTemp) ?></td>
            </tr>
        </table>
    <?php else: ?>
        <p>No temperature data available.</p>
    <?php endif; ?>

    <?php if (!empty($historyData)): ?>
        <h3>History (Last <?= count($historyData) ?> readings)</h3>
        <table>
            <tr>
                <th>Timestamp</th>
                <th>Temperature (°C)</th>
                <th>Valve Status</th>
                <th>Target (°C)</th>
            </tr>
            <?php foreach (array_reverse($historyData) as $entry): ?>
                <tr>
                    <td><?= htmlspecialchars($entry["timestamp"] ?? "—") ?></td>
                    <td><?= htmlspecialchars($entry["temperature"] ?? "—") ?></td>
                    <td><?= htmlspecialchars($entry["valve"] ?? "—") ?></td>
                    <td><?= htmlspecialchars($entry["target"] ?? $targetTemp) ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php endif; ?>

    <script>
    function changeTemp(delta) {
        const input = document.getElementById("targetTemp");
        let val = parseFloat(input.value) || 0;
        val += delta;
        input.value = val.toFixed(1);
        autoSave(); // save immediately
    }

    function autoSave() {
        const form = document.getElementById("targetForm");
        const formData = new FormData(form);

        fetch("", {
            method: "POST",
            body: formData
        })
        .then(r => r.text())
        .then(() => {
            console.log("Saved new target");
            location.reload(); // reload to show updated value in tables
        });
    }
    </script>
</body>
</html>
