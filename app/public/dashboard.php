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
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', 'Courier New', monospace;
        }

        :root {
            --primary-color: #0c7b93;
            --secondary-color: #00d2d3;
            --danger-color: #ff4757;
            --warning-color: #ffa502;
            --success-color: #2ed573;
            --dark-bg: #0a1929;
            --card-bg: #132f4c;
            --text-primary: #e6f7ff;
            --text-secondary: #a0c8e8;
            --border-color: #1e4976;
            --cyber-green: #00ff9d;
            --cyber-blue: #00b8ff;
        }

        body {
            background-color: var(--dark-bg);
            color: var(--text-primary);
            min-height: 100vh;
            overflow-x: hidden;
            line-height: 1.6;
        }

        /* Header Styles */
        .header {
            background: linear-gradient(135deg, #0a1929 0%, #132f4c 100%);
            padding: 1.2rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid var(--border-color);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
            position: relative;
            overflow: hidden;
        }

        .header::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 2px;
            background: linear-gradient(90deg, var(--cyber-green), var(--cyber-blue));
        }

        .logo-container {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .logo {
            font-size: 1.8rem;
            color: var(--cyber-green);
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.7; }
            100% { opacity: 1; }
        }

        .header h1 {
            font-size: 1.8rem;
            background: linear-gradient(to right, var(--cyber-green), var(--cyber-blue));
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            letter-spacing: 0.5px;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .user-icon {
            background-color: var(--card-bg);
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 1px solid var(--border-color);
        }

        .logout-btn {
            background-color: transparent;
            color: var(--text-primary);
            border: 1px solid var(--border-color);
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.3s;
            font-weight: 500;
        }

        .logout-btn:hover {
            background-color: rgba(255, 71, 87, 0.1);
            border-color: var(--danger-color);
            color: var(--danger-color);
        }

        /* Main Container */
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem;
        }

        /* Dashboard Info */
        .dashboard-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2.5rem;
        }

        .info-card {
            background-color: var(--card-bg);
            border-radius: 8px;
            padding: 1.5rem;
            border-left: 4px solid var(--primary-color);
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .info-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.3);
        }

        .info-card h3 {
            color: var(--cyber-green);
            margin-bottom: 0.8rem;
            font-size: 1.2rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .info-card p {
            color: var(--text-secondary);
            font-size: 0.95rem;
        }

        /* Alert Box */
        .alert-box {
            background-color: rgba(255, 71, 87, 0.1);
            border: 1px solid rgba(255, 71, 87, 0.3);
            border-radius: 8px;
            padding: 1.2rem;
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .alert-icon {
            color: var(--danger-color);
            font-size: 1.5rem;
        }

        .alert-box p {
            color: var(--text-primary);
        }

        /* Charts Container */
        .charts-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .chart-card {
            background-color: var(--card-bg);
            border-radius: 8px;
            padding: 1.5rem;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
            border: 1px solid var(--border-color);
        }

        .chart-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.2rem;
        }

        .chart-header h3 {
            color: var(--text-primary);
            font-size: 1.2rem;
        }

        .chart-info {
            background-color: rgba(12, 123, 147, 0.2);
            border-radius: 4px;
            padding: 6px 10px;
            font-size: 0.8rem;
            color: var(--text-secondary);
            cursor: help;
            position: relative;
        }

        .chart-info:hover .tooltip {
            display: block;
        }

        .tooltip {
            display: none;
            position: absolute;
            background-color: var(--dark-bg);
            border: 1px solid var(--border-color);
            border-radius: 4px;
            padding: 10px;
            width: 250px;
            top: 100%;
            right: 0;
            z-index: 10;
            font-size: 0.85rem;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
        }

        .chart-wrapper {
            position: relative;
            height: 250px;
            width: 100%;
        }

        canvas {
            width: 100% !important;
            height: 100% !important;
        }

        /* Footer */
        .footer {
            text-align: center;
            padding: 1.5rem;
            border-top: 1px solid var(--border-color);
            color: var(--text-secondary);
            font-size: 0.9rem;
            margin-top: 2rem;
        }

        .status-indicator {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-top: 1rem;
        }

        .indicator {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background-color: var(--success-color);
            animation: blink 1.5s infinite;
        }

        @keyframes blink {
            0% { opacity: 1; }
            50% { opacity: 0.5; }
            100% { opacity: 1; }
        }

        /* Responsive Styles */
        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                gap: 15px;
                padding: 1rem;
            }

            .container {
                padding: 1rem;
            }

            .charts-container {
                grid-template-columns: 1fr;
            }

            .dashboard-info {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 480px) {
            .chart-card {
                padding: 1rem;
            }

            .chart-wrapper {
                height: 200px;
            }
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

        <!-- <div id="map" style="height:400px"></div> -->

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
                            <th style="padding: 8px; border-bottom: 1px solid var(--border-color);">Info</th>
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
    fetch('/api/geo-heatmap').then(r=>r.json()).then(data=>{
    const map = L.map('map').setView([0,0],2);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);

    data.forEach(d=>{
        if (!d.lat || !d.lng) return;
        L.circle([d.lat,d.lng], {
        radius: d.attacks * 5000,
        fillOpacity: 0.4
        }).addTo(map).bindPopup(`${d.country}: ${d.attacks}`);
    });
    });
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
    let ipCounts = {};
    let statusCounts = {};

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
            <td style="padding:6px; border-bottom:1px solid var(--border-color);">${data.url || '-'}</td>
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