<?php
require_once 'config/database.php';
require_once 'config/mail_config.php';
initSecureSession();

// --- BACKGROUND AJAX HANDLER PARA SA AUTO-SAVE SA DATABASE ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_slots'])) {
    $slots = intval($_POST['update_slots']);
    // I-update ang database para laging saved ang record!
    $pdo->prepare("UPDATE parking_status SET slots_available = ? WHERE id = 1")->execute([$slots]);
    echo "Saved to DB";
    exit; 
}
// -----------------------------------------------------------

if (!isset($_SESSION['authenticated_user_id'])) {
    header("Location: login.php");
    exit;
}

$stmt = $pdo->prepare("SELECT username, email FROM users WHERE id = ?");
$stmt->execute([$_SESSION['authenticated_user_id']]);
$currentUser = $stmt->fetch();

// Kunin ang pinakahuling saved state mula sa database pag-load ng page
$stmt = $pdo->query("SELECT slots_available FROM parking_status WHERE id = 1");
$dbSlots = $stmt->fetchColumn();
if ($dbSlots === false) { $dbSlots = 4; } // Default kung wala pang record
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IoT Smart Parking - Command Console</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background-color: #0f172a; color: #f8fafc; font-family: 'Segoe UI', Roboto, sans-serif; }
        .navbar-custom { background: linear-gradient(90deg, #1e293b 0%, #0f172a 100%); border-bottom: 2px solid #334155; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.5); }
        .glass-card { background: rgba(30, 41, 59, 0.7); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 16px; backdrop-filter: blur(12px); box-shadow: 0 10px 15px -3px rgba(0,0,0,0.3); transition: all 0.3s ease; }
        .glass-card:hover { transform: translateY(-5px); border-color: rgba(56, 189, 248, 0.4); }
        .parking-slot { border-radius: 12px; transition: all 0.4s ease; min-height: 130px; border: 2px dashed #475569; position: relative; overflow: hidden; }
        .slot-available { background: linear-gradient(145deg, rgba(34, 197, 94, 0.15), rgba(34, 197, 94, 0.05)); border-color: #22c55e; color: #4ade80; }
        .slot-occupied { background: linear-gradient(145deg, rgba(239, 68, 68, 0.15), rgba(239, 68, 68, 0.05)); border-color: #ef4444; color: #f87171; }
        .progress { height: 10px; background-color: #334155; border-radius: 10px; }
        .live-clock { font-family: 'Courier New', monospace; font-size: 1.1rem; letter-spacing: 1px; color: #38bdf8; background: rgba(0,0,0,0.3); padding: 5px 12px; border-radius: 8px; border: 1px solid rgba(56, 189, 248, 0.2); }
    </style>
</head>
<body>

<div id="protocolAlert" class="alert alert-danger text-center my-0 py-2 d-none fw-bold">
    ⚠️ WARNING: Binuksan mo ang dashboard via file://. Buksan ito via http://localhost/Smart_Parking/dashboard.php
</div>

<nav class="navbar navbar-custom py-3">
    <div class="container-fluid px-4">
        <span class="navbar-brand mb-0 h4 text-white fw-bold d-flex align-items-center">
            <i class="fa-solid fa-car-tunnel text-info me-3 fs-3"></i>Smart Parking Core
        </span>
        <div class="d-flex align-items-center gap-3">
            <div class="live-clock fw-bold d-none d-md-block" id="live-clock">
                <i class="fa-regular fa-clock me-1"></i> 00:00:00 AM
            </div>

            <button id="connectSerialBtn" class="btn btn-primary btn-sm fw-bold rounded-pill px-4 shadow">
                <i class="fa-brands fa-usb me-1"></i> Connect Node
            </button>
            <div class="text-end d-none d-md-block ms-2 border-start ps-3 border-secondary">
                <small class="text-slate-400 d-block" style="font-size: 0.75rem;">Operator Context</small>
                <span class="fw-medium text-light"><?= htmlspecialchars($currentUser['username'] ?? 'Guest') ?></span>
            </div>
            <a href="logout.php" class="btn btn-outline-danger btn-sm rounded-pill px-3 ms-2">Logout</a>
        </div>
    </div>
</nav>

<div class="container mt-5">
    <div class="row g-4 mb-5">
        <div class="col-md-4">
            <div class="card glass-card text-white p-4">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-uppercase text-secondary fw-bold small tracking-wider">Available Bays</h6>
                        <h1 class="fw-bold my-2 text-success" id="available-counter"><?= $dbSlots ?></h1>
                    </div>
                    <div class="bg-success bg-opacity-10 p-3 rounded-circle text-success"><i class="fa-solid fa-square-check fa-2xl"></i></div>
                </div>
                <div class="progress mt-3"><div id="available-progress" class="progress-bar bg-success" style="width: <?= ($dbSlots / 4) * 100 ?>%"></div></div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card glass-card text-white p-4">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-uppercase text-secondary fw-bold small">Occupied Bays</h6>
                        <h1 class="fw-bold my-2 text-danger" id="occupied-counter"><?= 4 - $dbSlots ?></h1>
                    </div>
                    <div class="bg-danger bg-opacity-10 p-3 rounded-circle text-danger"><i class="fa-solid fa-car-side fa-2xl"></i></div>
                </div>
                <div class="progress mt-3"><div id="occupied-progress" class="progress-bar bg-danger" style="width: <?= ((4 - $dbSlots) / 4) * 100 ?>%"></div></div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card glass-card text-white p-4">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-uppercase text-secondary fw-bold small">Hardware Uplink</h6>
                        <h4 class="fw-bold my-2 text-warning" id="serial-status"><i class="fa-solid fa-link-slash me-2"></i>Offline</h4>
                    </div>
                    <div class="bg-warning bg-opacity-10 p-3 rounded-circle text-warning"><i class="fa-solid fa-microchip fa-2xl"></i></div>
                </div>
                <small class="text-secondary mt-3 d-block fw-medium" id="sync-time">Awaiting Local USB handshake...</small>
            </div>
        </div>
    </div>

    <div class="row mb-5">
        <div class="col-12">
            <div class="card glass-card p-4">
                <h5 class="mb-4 text-white fw-bold"><i class="fa-solid fa-satellite-dish me-2 text-info"></i>Live Facility Mapping</h5>
                <div class="row g-3" id="parking-bays-container">
                    </div>
            </div>
        </div>
    </div>
</div>

<script>
    const maxSlots = 4;
    let initialDbSlots = <?= $dbSlots ?>; // Ang pinakahuling saved record sa Database
    let serialPort;
    let serialReader;
    let serialBuffer = "";

    // Security Check
    if (window.location.protocol === 'file:') {
        document.getElementById('protocolAlert').classList.remove('d-none');
        document.getElementById('connectSerialBtn').disabled = true;
    }

    // Live Clock Function
    function updateLiveClock() {
        const now = new Date();
        document.getElementById('live-clock').innerHTML = `<i class="fa-regular fa-clock me-1"></i> ` + now.toLocaleTimeString('en-US', { hour12: true });
    }
    setInterval(updateLiveClock, 1000);
    updateLiveClock();

    // Render Initial UI from Database state
    updateDashboardUI(initialDbSlots, false);

    document.getElementById('connectSerialBtn').addEventListener('click', async () => {
        if (!('serial' in navigator)) { alert("Web Serial API not supported in this browser."); return; }

        try {
            serialPort = await navigator.serial.requestPort();
            await serialPort.open({ baudRate: 9600 }); 

            // Update UI to Connected Status
            const statusEl = document.getElementById('serial-status');
            statusEl.innerHTML = '<i class="fa-solid fa-circle-check me-2"></i>Online';
            statusEl.classList.replace('text-warning', 'text-success');
            document.getElementById('sync-time').innerText = "Syncing database to hardware...";
            
            const btn = document.getElementById('connectSerialBtn');
            btn.classList.replace('btn-primary', 'btn-success');
            btn.innerHTML = '<i class="fa-solid fa-shield-check"></i> Connected';

            // TIMING FIX: 5 seconds delay to wait for Arduino to finish booting
            setTimeout(async () => {
                if(serialPort && serialPort.writable) {
                    const writer = serialPort.writable.getWriter();
                    await writer.write(new TextEncoder().encode("SET_SLOTS:" + initialDbSlots + "\n"));
                    writer.releaseLock();
                    document.getElementById('sync-time').innerText = "Database sync active & listening.";
                }
            }, 5000); 

            // Read Data Loop
            const textDecoder = new TextDecoderStream();
            const readableStreamClosed = serialPort.readable.pipeTo(textDecoder.writable);
            serialReader = textDecoder.readable.getReader();

            while (true) {
                const { value, done } = await serialReader.read();
                if (done) { serialReader.releaseLock(); break; }
                if (value) { processIncomingData(value); }
            }
        } catch (error) {
            console.error("Serial Connection Error:", error);
        }
    });

    function processIncomingData(data) {
        serialBuffer += data;
        let lines = serialBuffer.split('\n');
        serialBuffer = lines.pop(); 

        lines.forEach(line => {
            line = line.trim();
            if (line.startsWith("PC_SYNC:")) {
                let availableSlots = parseInt(line.split(":")[1]);
                if (!isNaN(availableSlots) && availableSlots >= 0 && availableSlots <= maxSlots) {
                    updateDashboardUI(availableSlots, true); // true = Mag-trigger ng database save
                }
            }
        });
    }

    function updateDashboardUI(available, saveToDb = false) {
        const occupied = maxSlots - available;
        
        // Update Metrics
        document.getElementById('available-counter').innerText = available;
        document.getElementById('occupied-counter').innerText = occupied;
        document.getElementById('available-progress').style.width = `${(available / maxSlots) * 100}%`;
        document.getElementById('occupied-progress').style.width = `${(occupied / maxSlots) * 100}%`;

        // Update Visual Map
        let bayHtml = '';
        for (let i = 1; i <= maxSlots; i++) {
            if (i <= occupied) {
                bayHtml += `
                    <div class="col-sm-6 col-md-3">
                        <div class="parking-slot slot-occupied d-flex flex-column justify-content-center align-items-center shadow">
                            <i class="fa-solid fa-car fa-2xl mb-3"></i>
                            <span class="fw-bold fs-5">BAY 0${i}</span>
                            <small class="badge bg-danger mt-2 px-3 py-1 rounded-pill">OCCUPIED</small>
                        </div>
                    </div>`;
            } else {
                bayHtml += `
                    <div class="col-sm-6 col-md-3">
                        <div class="parking-slot slot-available d-flex flex-column justify-content-center align-items-center shadow">
                            <i class="fa-solid fa-square-parking fa-2xl mb-3"></i>
                            <span class="fw-bold fs-5">BAY 0${i}</span>
                            <small class="badge bg-success mt-2 px-3 py-1 rounded-pill">AVAILABLE</small>
                        </div>
                    </div>`;
            }
        }
        document.getElementById('parking-bays-container').innerHTML = bayHtml;

        // AJAX POST para mai-save ang panibagong state sa backend database ng tahimik
        if(saveToDb) {
            initialDbSlots = available; 
            const formData = new FormData();
            formData.append('update_slots', available);
            fetch('dashboard.php', { method: 'POST', body: formData })
                .catch(err => console.error("Database save failed."));
        }
    }
</script>
</body>
</html>