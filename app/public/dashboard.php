<?php
session_start();

// ================= LOGIN CHECK =================
if (!($_SESSION['auth'] ?? false)) {
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cyber Security Dashboard</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&family=Roboto+Mono:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="./assets/css/dashboard.css">
    <style>
        /* Inline style untuk font family */
        body, h1, h2, h3, h4 {
            font-family: 'Roboto Mono', 'Segoe UI', monospace;
        }
        h1, .chart-header h3 {
            font-family: 'Orbitron', sans-serif;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="logo-container">
            <div class="logo">
                <i class="fas fa-shield-alt"></i>
            </div>
            <h1>Cyber Security Dashboard</h1>
        </div>
        <div class="user-info">
            <div class="user-icon">
                <i class="fas fa-user-secret"></i>
            </div>
            <button class="logout-btn" onclick="window.location.href='logout.php'">
                <i class="fas fa-sign-out-alt"></i> Logout
            </button>
        </div>
    </div>

    <div class="container">
        <div class="dashboard-info">
            <div class="info-card">
                <h3><i class="fas fa-broadcast-tower"></i> Realtime Monitoring</h3>
                <p>Dashboard ini menampilkan aktivitas jaringan secara realtime. Semua permintaan (request) yang masuk ke server akan langsung terlihat di sini, termasuk alamat IP pengunjung dan status respons.</p>
            </div>
            <div class="info-card">
                <h3><i class="fas fa-exclamation-triangle"></i> Deteksi Ancaman</h3>
                <p>Sistem akan secara otomatis mendeteksi aktivitas mencurigakan seperti permintaan yang tidak sah (status 401) dan memberi peringatan. IP dengan aktivitas tinggi akan ditampilkan di grafik "Top IP Address".</p>
            </div>
            <div class="info-card">
                <h3><i class="fas fa-chart-line"></i> Analisis Data</h3>
                <p>Tiga grafik berbeda menyajikan informasi penting: lalu lintas jaringan, status respons server, dan alamat IP paling aktif. Data diperbarui secara otomatis setiap ada aktivitas baru.</p>
            </div>
        </div>

        <div class="alert-box">
            <div class="alert-icon">
                <i class="fas fa-info-circle"></i>
            </div>
            <div>
                <p><strong>Perhatian:</strong> Dashboard ini menampilkan data sensitif keamanan jaringan. Hanya personel yang berwenang yang boleh mengakses informasi ini. Setiap aktivitas mencurigakan akan ditandai dengan notifikasi.</p>
            </div>
        </div>

        <div class="summary-grid">
        <div class="summary-card green">
            <h4>Total Requests (24h)</h4>
            <span id="sumRequests">-</span>
        </div>
        <div class="summary-card red">
            <h4>Blocked IP</h4>
            <span id="sumBlocked">-</span>
        </div>
        <div class="summary-card orange">
            <h4>Suspicious IP</h4>
            <span id="sumSuspicious">-</span>
        </div>
        <div class="summary-card purple">
            <h4>ML Anomaly</h4>
            <span id="sumAnomaly">-</span>
        </div>
        <div class="summary-card blue">
            <h4>Avg Response</h4>
            <span id="sumLatency">- ms</span>
        </div>
        </div>

        <div class="status-indicator">
            <div class="indicator"></div>
            <span>Sistem memantau aktivitas jaringan secara realtime...</span>
        </div>

        <div class="charts-container">
            <div class="chart-card">
                <div class="chart-header">
                    <h3><i class="fas fa-traffic-light"></i> Lalu Lintas Jaringan Realtime</h3>
                    <div class="chart-info">
                        <i class="fas fa-info-circle"></i>
                        <div class="tooltip">
                            Grafik ini menunjukkan jumlah permintaan (request) ke server setiap waktu. Garis biru menunjukkan trend aktivitas. Data diperbarui secara realtime.
                        </div>
                    </div>
                </div>
                <div class="chart-wrapper">
                    <canvas id="trafficChart"></canvas>
                </div>
            </div>

            <div class="chart-card">
                <div class="chart-header">
                    <h3><i class="fas fa-list-ol"></i> Status Respons Server</h3>
                    <div class="chart-info">
                        <i class="fas fa-info-circle"></i>
                        <div class="tooltip">
                            Diagram ini menunjukkan distribusi status respons server. Hijau = sukses (200), Merah = error (4xx/5xx), Oranye = pengalihan (3xx), Abu-abu = lainnya.
                        </div>
                    </div>
                </div>
                <div class="chart-wrapper">
                    <canvas id="statusChart"></canvas>
                </div>
            </div>

            <div class="chart-card">
                <div class="chart-header">
                    <h3><i class="fas fa-desktop"></i> Top 5 IP Address</h3>
                    <div class="chart-info">
                        <i class="fas fa-info-circle"></i>
                        <div class="tooltip">
                            Grafik ini menampilkan 5 alamat IP dengan aktivitas tertinggi. Berguna untuk mengidentifikasi sumber traffic utama atau potensi serangan DDoS.
                        </div>
                    </div>
                </div>
                <div class="chart-wrapper">
                    <canvas id="ipChart"></canvas>
                </div>
            </div>
        </div>

        <div class="chart-card">
        <div class="chart-header">
            <h3><i class="fas fa-skull-crossbones"></i> Active Threats</h3>
        </div>
        <input id="ipFilter" placeholder="Filter IP..." style="padding:8px;width:200px">
        <table style="width:100%;font-size:.85rem">
            <thead>
            <tr><th>IP</th><th>State</th><th>Score</th><th>Reason</th><th>Last Seen</th></tr>
            </thead>
            <tbody id="threatTable"></tbody>
        </table>
        </div>
<br>
<div class="chart-card">
  <div class="chart-header">
    <h3><i class="fas fa-search"></i> Investigasi IP</h3>
  </div>
  <input id="investigateIp" placeholder="Masukkan IP..." style="padding:8px;width:200px">
  <button onclick="investigate()">Investigate</button>
  <pre id="investigationResult" style="margin-top:10px;font-size:.8rem;max-height:300px;overflow:auto"></pre>
</div>
<br>
<div class="chart-card">
  <div class="chart-header">
    <h3><i class="fas fa-user-shield"></i> Manual Control</h3>
  </div>
  <input id="controlIp" placeholder="IP..." style="padding:8px;width:200px">
  <button onclick="manualBlock()">Block</button>
  <button onclick="manualWhitelist()">Whitelist</button>
  <button onclick="markFalsePositive()">False Positive</button>
</div>
<br>
<div class="chart-card">
  <div class="chart-header">
    <h3><i class="fas fa-clipboard-list"></i> Audit Trail</h3>
    <button onclick="loadAudit()">Refresh</button>
    <button onclick="exportAudit()">Export CSV</button>
  </div>
  <div style="max-height:300px;overflow:auto">
    <table style="width:100%">
      <thead><tr><th>Waktu</th><th>IP</th><th>Aksi</th><th>Reason</th><th>User</th></tr></thead>
      <tbody id="auditTable"></tbody>
    </table>
  </div>
</div>



        <!-- <div id="map" style="height:400px"></div> -->
<br>
        <div class="chart-card">
            <div class="chart-header">
                <h3><i class="fas fa-terminal"></i> Log Request Realtime</h3>
                <div class="chart-info">
                    <i class="fas fa-info-circle"></i>
                    <div class="tooltip">
                        Menampilkan daftar request yang masuk ke server, termasuk IP dan status HTTP. Update secara realtime.
                    </div>
                </div>
            </div>
            <div style="overflow-y:auto; max-height:300px;">
                <table id="logTable" style="width:100%; border-collapse: collapse; font-size:0.85rem;">
                    <thead>
                        <tr style="background-color: var(--card-bg); color: var(--cyber-green);">
                            <th style="padding: 8px; border-bottom: 1px solid var(--border-color);">Waktu</th>
                            <th style="padding: 8px; border-bottom: 1px solid var(--border-color);">IP Address</th>
                            <th style="padding: 8px; border-bottom: 1px solid var(--border-color);">Status</th>
                            <th style="padding: 8px; border-bottom: 1px solid var(--border-color);">method</th>
                            <th style="padding: 8px; border-bottom: 1px solid var(--border-color);">path</th>
                        </tr>
                    </thead>
                    <tbody id="logBody">
                        <!-- Data log akan muncul di sini -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="footer">
        <p>Cyber Security Dashboard &copy; 2023 | Sistem Pemantauan Jaringan Realtime | Hanya untuk penggunaan terotorisasi</p>
    </div>

    <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
    <script>
    function createBinaryRain() {
        const container = document.createElement('div');
        container.className = 'binary-rain';
        document.body.appendChild(container);
        
        for(let i = 0; i < 50; i++) {
            const digit = document.createElement('div');
            digit.className = 'binary-digit';
            digit.textContent = Math.random() > 0.5 ? '1' : '0';
            digit.style.left = `${Math.random() * 100}vw`;
            digit.style.animationDuration = `${Math.random() * 3 + 2}s`;
            digit.style.animationDelay = `${Math.random() * 2}s`;
            digit.style.opacity = Math.random() * 0.3 + 0.1;
            container.appendChild(digit);
        }
    }
    
    if(window.innerWidth > 768) {
        createBinaryRain();
    }
</script>
<script>
const investigateIp = document.getElementById('investigateIp');
const investigationResult = document.getElementById('investigationResult');
const controlIp = document.getElementById('controlIp');
const auditTable = document.getElementById('auditTable');
const threatTable = document.getElementById('threatTable');
const logBody = document.getElementById('logBody');
const sumRequests = document.getElementById('sumRequests');
const sumBlocked = document.getElementById('sumBlocked');
const sumSuspicious = document.getElementById('sumSuspicious');
const sumAnomaly = document.getElementById('sumAnomaly');
const sumLatency = document.getElementById('sumLatency');

// ===== Investigate IP =====
function investigate(){
  const ip = investigateIp.value;
  if(!ip) return;
  fetch(`/api/investigate.php?ip=${ip}`)
    .then(r => r.json())
    .then(d => {
      investigationResult.textContent = JSON.stringify(d,null,2);
    });
}

// ===== Manual Control =====
function manualBlock(){
  fetch('/api/block.php',{
    method:'POST',
    body: JSON.stringify({ip: controlIp.value}),
    headers:{'Content-Type':'application/json'}
  });
}

function manualWhitelist(){
  fetch('/api/whitelist.php',{
    method:'POST',
    body: JSON.stringify({ip: controlIp.value}),
    headers:{'Content-Type':'application/json'}
  });
}

function markFalsePositive(){
  fetch('/api/false-positive.php',{
    method:'POST',
    body: JSON.stringify({ip: controlIp.value}),
    headers:{'Content-Type':'application/json'}
  });
}

// ===== Load Audit Trail =====
function loadAudit(){
  fetch('/api/audit.php')
    .then(r => r.json())
    .then(list => {
      auditTable.innerHTML = '';
      list.forEach(a=>{
        auditTable.innerHTML += `<tr>
          <td>${a.ts}</td><td>${a.ip}</td><td>${a.action}</td><td>${a.reason}</td><td>${a.actor}</td>
        </tr>`;
      });
    });
}

// ===== Load Active Threats =====
function loadThreats(){
  fetch('/api/threats.php')
    .then(r => r.json())
    .then(list => {
      threatTable.innerHTML = '';
      list.forEach(t=>{
        threatTable.innerHTML += `<tr>
          <td>${t.ip}</td><td>${t.state}</td><td>${t.score}</td>
          <td>${t.reason}</td><td>${t.last_seen}</td>
        </tr>`;
      });
    });
}

// ===== Load Summary Stats =====
function loadSummary(){
  fetch('/api/summary.php')
    .then(r => r.json())
    .then(d => {
      sumRequests.innerText = d.requests;
      sumBlocked.innerText = d.blocked;
      sumSuspicious.innerText = d.suspicious;
      sumAnomaly.innerText = d.anomaly;
      sumLatency.innerText = d.avg_latency;
    });
}

// ===== Filter Log by IP =====
document.getElementById('ipFilter').addEventListener('input', e=>{
  const v = e.target.value;
  [...logBody.rows].forEach(r=>{
    r.style.display = r.cells[1].innerText.includes(v) ? '' : 'none';
  });
});

// ===== Polling periodik untuk semua stats dan tables =====
function startPolling(){
  loadSummary();
  loadAudit();
  loadThreats();
}

// Update setiap 5 detik
setInterval(startPolling, 5000);
startPolling(); // load pertama
</script>

<script>
    // ===== Initialize charts with cyber security theme =====
    const trafficCtx = document.getElementById('trafficChart').getContext('2d');
    const statusCtx  = document.getElementById('statusChart').getContext('2d');
    const ipCtx      = document.getElementById('ipChart').getContext('2d');

    // Cyber security color palette
    const cyberColors = {
        primary: '#00ff9d',
        secondary: '#00b8ff',
        danger: '#ff4757',
        warning: '#ffa502',
        success: '#2ed573',
        purple: '#9d4edd'
    };

    // ---- Traffic line chart ----
    const trafficChart = new Chart(trafficCtx, {
        type: 'line',
        data: { 
            labels: [], 
            datasets: [{ 
                label: 'Requests per Minute', 
                data: [], 
                borderColor: cyberColors.primary,
                backgroundColor: 'rgba(0, 255, 157, 0.1)',
                borderWidth: 2,
                fill: true,
                tension: 0.4
            }] 
        },
        options: { 
            animation: false,
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { labels: { color: '#e6f7ff' } } },
            scales: {
                x: { grid: { color: 'rgba(30, 73, 118, 0.3)' }, ticks: { color: '#a0c8e8' } },
                y: { beginAtZero: true, grid: { color: 'rgba(30, 73, 118, 0.3)' }, ticks: { color: '#a0c8e8' } }
            }
        }
    });

    // ---- Status pie chart ----
    const statusChart = new Chart(statusCtx, {
        type: 'doughnut',
        data: { labels: [], datasets: [{ data: [], backgroundColor: [cyberColors.success, cyberColors.danger, cyberColors.warning, '#aaaaaa'], borderWidth: 1, borderColor: '#132f4c' }] },
        options: { 
            animation: false,
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { position: 'bottom', labels: { color: '#e6f7ff', padding: 15 } } }
        }
    });

    // ---- Top IP bar chart ----
    const ipChart = new Chart(ipCtx, {
        type: 'bar',
        data: { labels: [], datasets: [{ label: 'Jumlah Request', data: [], backgroundColor: cyberColors.purple, borderColor: cyberColors.secondary, borderWidth: 1 }] },
        options: { 
            animation: false,
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { labels: { color: '#e6f7ff' } } },
            scales: {
                x: { beginAtZero: true, grid: { color: 'rgba(30, 73, 118, 0.3)' }, ticks: { color: '#a0c8e8' } },
                y: { grid: { color: 'rgba(30, 73, 118, 0.3)' }, ticks: { color: '#a0c8e8' } }
            }
        }
    });

    // ===== WebSocket connection =====
    const ws = new WebSocket("ws://127.0.0.1:8081");

    ws.onopen = () => console.log("WebSocket terhubung untuk monitoring realtime");
    ws.onerror = e => console.error("WebSocket error:", e);

    // Data structures for tracking
    var ipCounts = {};
    var statusCounts = {};

    // ---- Function to add log entry ----
    function addLogEntry(data) {
        const logBody = document.getElementById('logBody');
        const tr = document.createElement('tr');
        const now = new Date();
        const timeLabel = now.toLocaleTimeString();

        let statusColor = cyberColors.primary;
        if (data.status >= 500) statusColor = cyberColors.danger;
        else if (data.status >= 400) statusColor = cyberColors.warning;
        else if (data.status === 200) statusColor = cyberColors.success;

        tr.innerHTML = `
            <td style="padding:6px; border-bottom:1px solid var(--border-color);">${timeLabel}</td>
            <td style="padding:6px; border-bottom:1px solid var(--border-color);">${data.ip}</td>
            <td style="padding:6px; border-bottom:1px solid var(--border-color); color:${statusColor}; font-weight:600;">${data.status}</td>
            <td style="padding:6px; border-bottom:1px solid var(--border-color);">${data.method || '-'}</td>
            <td style="padding:6px; border-bottom:1px solid var(--border-color);">${data.path || '-'}</td>
        `;
        logBody.prepend(tr);
        if (logBody.rows.length > 50) logBody.removeChild(logBody.lastChild);
    }

    // ---- Function to show 401 alert ----
    function showSecurityAlert(ip) {
        const alertDiv = document.createElement('div');
        alertDiv.style.cssText = `
            position: fixed; top: 20px; right: 20px; background-color: rgba(255,71,87,0.9);
            color: white; padding: 15px; border-radius: 5px; z-index:1000; box-shadow: 0 4px 12px rgba(0,0,0,0.3);
            max-width:300px; animation: slideIn 0.5s ease-out;
        `;
        alertDiv.innerHTML = `
            <strong><i class="fas fa-exclamation-triangle"></i> Peringatan Keamanan!</strong>
            <p>Permintaan tidak sah (401) terdeteksi dari IP: ${ip}</p>
            <small>${new Date().toLocaleTimeString()}</small>
        `;
        document.body.appendChild(alertDiv);

        setTimeout(() => {
            alertDiv.style.animation = 'slideOut 0.5s ease-out';
            setTimeout(() => alertDiv.remove(), 500);
        }, 5000);

        if (!document.querySelector('#alert-animation')) {
            const style = document.createElement('style');
            style.id = 'alert-animation';
            style.textContent = `
                @keyframes slideIn { from { transform: translateX(100%); opacity:0; } to { transform: translateX(0); opacity:1; } }
                @keyframes slideOut { from { transform: translateX(0); opacity:1; } to { transform: translateX(100%); opacity:0; } }
            `;
            document.head.appendChild(style);
        }
    }

    // ---- WebSocket message handler (gabungan) ----
    ws.onmessage = e => {
        try {
            const d = JSON.parse(e.data);

            // Traffic chart
            const now = new Date();
            const timeLabel = `${now.getHours().toString().padStart(2,'0')}:${now.getMinutes().toString().padStart(2,'0')}:${now.getSeconds().toString().padStart(2,'0')}`;
            trafficChart.data.labels.push(timeLabel);
            trafficChart.data.datasets[0].data.push(d.count || 1);
            if(trafficChart.data.labels.length > 20){
                trafficChart.data.labels.shift();
                trafficChart.data.datasets[0].data.shift();
            }
            trafficChart.update();

            // Status chart
            let statusLabel = d.status;
            if (d.status === 200) statusLabel = "Sukses (200)";
            else if (d.status === 401) statusLabel = "Tidak Sah (401)";
            else if (d.status >= 400 && d.status < 500) statusLabel = `Client Error (${d.status})`;
            else if (d.status >= 500) statusLabel = `Server Error (${d.status})`;
            else if (d.status >= 300 && d.status < 400) statusLabel = `Pengalihan (${d.status})`;

            statusCounts[statusLabel] = (statusCounts[statusLabel] || 0) + 1;
            statusChart.data.labels = Object.keys(statusCounts);
            statusChart.data.datasets[0].data = Object.values(statusCounts);
            statusChart.update();

            // Top IP chart
            ipCounts[d.ip] = (ipCounts[d.ip] || 0) + 1;
            let sortedIPs = Object.entries(ipCounts).sort((a,b)=>b[1]-a[1]).slice(0,5);
            ipChart.data.labels = sortedIPs.map(x=>x[0]);
            ipChart.data.datasets[0].data = sortedIPs.map(x=>x[1]);
            ipChart.update();

            // Log table
            addLogEntry(d);

            // Alert 401
            if(d.status === 401) showSecurityAlert(d.ip);

        } catch(err){
            console.error("Error parsing WebSocket data:", err);
        }
    };

    // ===== Load initial stats from API =====
    fetch('./api/stats.php')
        .then(r => r.json())
        .then(d => {
            if (d.status) {
                d.status.forEach(s => {
                    let statusLabel = s.status;
                    if (s.status === 200) statusLabel = "Sukses (200)";
                    else if (s.status === 401) statusLabel = "Tidak Sah (401)";
                    else if (s.status >= 400 && s.status < 500) statusLabel = `Client Error (${s.status})`;
                    else if (s.status >= 500) statusLabel = `Server Error (${s.status})`;
                    else if (s.status >= 300 && s.status < 400) statusLabel = `Pengalihan (${s.status})`;
                    statusCounts[statusLabel] = s.c;
                });
                statusChart.data.labels = Object.keys(statusCounts);
                statusChart.data.datasets[0].data = Object.values(statusCounts);
                statusChart.update();
            }

            if (d.ip) {
                d.ip.forEach(ip => ipCounts[ip.ip] = ip.c);
                let topIPs = Object.entries(ipCounts).sort((a,b)=>b[1]-a[1]).slice(0,5);
                ipChart.data.labels = topIPs.map(x=>x[0]);
                ipChart.data.datasets[0].data = topIPs.map(x=>x[1]);
                ipChart.update();
            }
        })
        .catch(err => console.error("Gagal memuat data awal:", err));

        trafficChart.update();

</script>

</body>
</html>