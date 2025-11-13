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

// Handle form submission
if (isset($_POST['tempMin'])) $tempMin = floatval($_POST['tempMin']);
if (isset($_POST['tempMax'])) $tempMax = floatval($_POST['tempMax']);
file_put_contents($configFile, json_encode(["tempMin"=>$tempMin,"tempMax"=>$tempMax]));

// Handle Clear History
$message = "";
if (isset($_POST['clear_history'])) {
    if (file_exists($historyFile)) unlink($historyFile);
    $historyData = [];
    $message = "History cleared successfully.";
}

// Load latest and history
$latestData  = file_exists($latestFile) ? json_decode(file_get_contents($latestFile), true) : null;
$historyData = file_exists($historyFile) ? json_decode(file_get_contents($historyFile), true) : [];
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
.temp-control { display:inline-flex; align-items:center; gap:5px; }
.temp-control input { font-size:22px; text-align:center; width:100px; padding:10px; }
.temp-control .arrow { font-size:26px; padding:10px 15px; cursor:pointer; border:1px solid #666; background:#eee; border-radius:6px; }
.temp-control .arrow:hover { background:#ddd; }
button.clear-btn { padding:10px 15px; cursor:pointer; margin-top:10px; background:#f66; border:none; border-radius:6px; color:white; }
button.clear-btn:hover { background:#d44; }
.valve-status { font-size:18px; margin-top:10px; font-weight:bold; }
.valve-open { color:green; }
.valve-close { color:red; }
</style>
</head>
<body>
<h2>Termak IoT Dashboard</h2>

<form method="POST" id="configForm">
    <label>Min Temperature (°C):</label>
    <div class="temp-control">
        <button type="button" class="arrow" onclick="changeTemp('min',-0.5)">▼</button>
        <input type="number" step="0.1" name="tempMin" id="tempMin" value="<?= htmlspecialchars($tempMin) ?>">
        <button type="button" class="arrow" onclick="changeTemp('min',0.5)">▲</button>
    </div>
    <br><br>
    <label>Max Temperature (°C):</label>
    <div class="temp-control">
        <button type="button" class="arrow" onclick="changeTemp('max',-0.5)">▼</button>
        <input type="number" step="0.1" name="tempMax" id="tempMax" value="<?= htmlspecialchars($tempMax) ?>">
        <button type="button" class="arrow" onclick="changeTemp('max',0.5)">▲</button>
    </div>
    <br>
</form>

<?php if ($latestData && isset($latestData["valve"])): ?>
    <div class="valve-status <?= $latestData["valve"] === "open" ? 'valve-open' : 'valve-close' ?>">
        Valve: <?= htmlspecialchars(strtoupper($latestData["valve"])) ?>
    </div>
<?php endif; ?>

<form method="POST" onsubmit="return confirm('Clear all history?');">
    <button type="submit" name="clear_history" value="1" class="clear-btn">Clear History</button>
</form>



<?php if ($message): ?>
    <p><b><?= htmlspecialchars($message) ?></b></p>
<?php endif; ?>

<?php if ($latestData): ?>
<h3>Latest Reading</h3>
<table>
<tr><th>Timestamp</th><th>Temperature (°C)</th><th>Valve</th><th>Min</th><th>Max</th></tr>
<tr>
<td><?= htmlspecialchars($latestData["timestamp"] ?? "—") ?></td>
<td><?= htmlspecialchars($latestData["temperature"] ?? "—") ?></td>
<td><?= htmlspecialchars($latestData["valve"] ?? "—") ?></td>
<td><?= htmlspecialchars($latestData["tempMin"] ?? $tempMin) ?></td>
<td><?= htmlspecialchars($latestData["tempMax"] ?? $tempMax) ?></td>
</tr>
</table>
<?php endif; ?>

<?php if (!empty($historyData)): ?>
<h3>History (Last <?= count($historyData) ?> readings)</h3>
<table>
<tr><th>Timestamp</th><th>Temperature (°C)</th><th>Valve</th><th>Min</th><th>Max</th></tr>
<?php foreach(array_reverse($historyData) as $entry): ?>
<tr>
<td><?= htmlspecialchars($entry["timestamp"] ?? "—") ?></td>
<td><?= htmlspecialchars($entry["temperature"] ?? "—") ?></td>
<td><?= htmlspecialchars($entry["valve"] ?? "—") ?></td>
<td><?= htmlspecialchars($entry["tempMin"] ?? "—") ?></td>
<td><?= htmlspecialchars($entry["tempMax"] ?? "—") ?></td>
</tr>
<?php endforeach; ?>
</table>
<?php endif; ?>

<script>
function changeTemp(type, delta){
    const input = document.getElementById(type=='min'?'tempMin':'tempMax');
    let val = parseFloat(input.value)||0;
    val += delta;
    input.value = val.toFixed(1);
    autoSave();
}

function autoSave(){
    const form = document.getElementById("configForm");
    const formData = new FormData(form);
    fetch("", {method:"POST", body: formData})
    .then(r=>r.text())
    .then(()=>{ location.reload(); });
}
</script>

</body>
</html>
