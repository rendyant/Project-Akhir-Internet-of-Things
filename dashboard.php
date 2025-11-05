<?php
// index.php
// Full-page dashboard (pastel + greenhouse theme)
// Requires: db.php, api.php, control.php (from previous implementation)

// COMMENT: Jika kamu ingin server-side initial values, kamu bisa uncomment blok DB fetch.
// require_once 'db.php';
// $latest = [];
// $types = ['temp','hum','ldr','soil'];
// foreach ($types as $t) {
//     $stmt = $mysqli->prepare("SELECT value, created_at FROM sensors WHERE sensor_type=? ORDER BY created_at DESC LIMIT 1");
//     $stmt->bind_param('s', $t);
//     $stmt->execute();
//     $res = $stmt->get_result()->fetch_assoc();
//     $latest[$t] = $res ? $res['value'] : null;
//     $stmt->close();
// }
// $acts = [];
// $q = $mysqli->query("SELECT name,status FROM actuators");
// while ($r = $q->fetch_assoc()) $acts[$r['name']] = (int)$r['status'];
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1"/>
  <title>Greenhouse — Dashboard</title>

  <!-- Font: Quicksand -->
  <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@400;600;700&display=swap" rel="stylesheet">

  <!-- Tailwind CDN -->
  <script src="https://cdn.tailwindcss.com"></script>

  <!-- Chart.js -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

  <style>
    body { font-family: 'Quicksand', system-ui, -apple-system, 'Segoe UI', Roboto, 'Helvetica Neue', Arial; }
    .card { border-radius: 14px; box-shadow: 0 6px 18px rgba(16,24,40,0.06); }
    .soft-glow { box-shadow: 0 6px 20px rgba(99,102,241,0.06); }
    .pastel-border { border: 1px solid rgba(16,185,129,0.06); }
    .toggle-track { width: 56px; height: 30px; border-radius: 9999px; padding: 4px; display:inline-flex; align-items:center; cursor:pointer; }
    .toggle-knob { width:22px; height:22px; border-radius:9999px; background:white; box-shadow:0 2px 6px rgba(2,6,23,0.12); transform:translateX(0); transition:transform .22s ease; }
    .toggle-on .toggle-knob { transform: translateX(26px); }
  </style>
</head>
<body class="bg-gradient-to-b from-green-50 via-white to-pink-50 min-h-screen">

  <div class="max-w-7xl mx-auto p-6">
    <header class="mb-6 text-center">
      <h1 class="text-4xl font-bold text-emerald-700">🌿 Greenhouse Dashboard</h1>
      <p class="text-sm text-emerald-500 mt-1">Real-time monitoring & control — pastel theme</p>
    </header>

    <!-- Top summary cards -->
    <section class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
      <!-- Card: Suhu -->
      <div class="card bg-gradient-to-br from-orange-50 to-orange-100 p-5 pastel-border">
        <div class="flex items-center justify-between">
          <div>
            <div class="text-sm text-orange-600">Suhu Udara</div>
            <div id="card-temp" class="text-3xl font-bold text-orange-800">- °C</div>
            <div id="sub-temp" class="text-xs text-gray-500">Terakhir: -</div>
          </div>
          <div class="text-4xl">🌡️</div>
        </div>
      </div>

      <!-- Card: Kelembapan Udara -->
      <div class="card bg-gradient-to-br from-blue-50 to-blue-100 p-5 pastel-border">
        <div class="flex items-center justify-between">
          <div>
            <div class="text-sm text-blue-600">Kelembapan Udara</div>
            <div id="card-hum" class="text-3xl font-bold text-blue-800">- %</div>
            <div id="sub-hum" class="text-xs text-gray-500">Terakhir: -</div>
          </div>
          <div class="text-4xl">💧</div>
        </div>
      </div>

      <!-- Card: Intensitas Cahaya -->
      <div class="card bg-gradient-to-br from-yellow-50 to-yellow-100 p-5 pastel-border">
        <div class="flex items-center justify-between">
          <div>
            <div class="text-sm text-yellow-600">Intensitas Cahaya</div>
            <div id="card-ldr" class="text-3xl font-bold text-yellow-800">- lx</div>
            <div id="sub-ldr" class="text-xs text-gray-500">Terakhir: -</div>
          </div>
          <div class="text-4xl">🔆</div>
        </div>
      </div>

      <!-- Card: Kelembapan Tanah -->
      <div class="card bg-gradient-to-br from-emerald-50 to-emerald-100 p-5 pastel-border">
        <div class="flex items-center justify-between">
          <div>
            <div class="text-sm text-emerald-700">Kelembapan Tanah</div>
            <div id="card-soil" class="text-3xl font-bold text-emerald-900">- %</div>
            <div id="sub-soil" class="text-xs text-gray-500">Terakhir: -</div>
          </div>
          <div class="text-4xl">🌱</div>
        </div>
      </div>
    </section>

    <!-- Charts area (3 charts stacked on grid) -->
    <section class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
      <div class="card bg-white p-4">
        <div class="flex items-center justify-between mb-3">
          <h3 class="text-md font-semibold text-gray-700">Suhu (°C)</h3>
          <div class="text-xs text-gray-500">Realtime / 24 titik terakhir</div>
        </div>
        <canvas id="chartTemp" height="180"></canvas>
      </div>

      <div class="card bg-white p-4">
        <div class="flex items-center justify-between mb-3">
          <h3 class="text-md font-semibold text-gray-700">Kelembapan Tanah (%)</h3>
          <div class="text-xs text-gray-500">Realtime / 24 titik terakhir</div>
        </div>
        <canvas id="chartSoil" height="180"></canvas>
      </div>

      <div class="card bg-white p-4">
        <div class="flex items-center justify-between mb-3">
          <h3 class="text-md font-semibold text-gray-700">Intensitas Cahaya (lx)</h3>
          <div class="text-xs text-gray-500">Realtime / 24 titik terakhir</div>
        </div>
        <canvas id="chartLight" height="180"></canvas>
      </div>
    </section>

    <!-- Controls + Logs -->
    <section class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <!-- Controls -->
      <div class="card bg-gradient-to-br from-white to-emerald-50 p-5">
        <h3 class="text-lg font-semibold text-gray-700 mb-4">Kontrol Aktuator</h3>

        <!-- ACTUATOR ROW TEMPLATE (repeat for each) -->
        <!-- COMMENT: Ganti icon, label, dan data-act sesuai aktuator di DB -->
        <div class="flex items-center justify-between mb-4">
          <div class="flex items-center space-x-3">
            <div class="text-3xl">💧</div>
            <div>
              <div class="text-sm text-gray-500">Pompa</div>
              <div id="status-pump" class="text-xl font-semibold text-pink-600">OFF</div>
            </div>
          </div>
          <div>
            <div id="toggle-pump" class="toggle-track bg-gray-200 rounded-full inline-flex p-1" data-act="pump" role="switch" aria-checked="false">
              <div class="toggle-knob"></div>
            </div>
          </div>
        </div>

        <div class="flex items-center justify-between mb-4">
          <div class="flex items-center space-x-3">
            <div class="text-3xl">💨</div>
            <div>
              <div class="text-sm text-gray-500">Kipas</div>
              <div id="status-fan" class="text-xl font-semibold text-sky-600">OFF</div>
            </div>
          </div>
          <div>
            <div id="toggle-fan" class="toggle-track bg-gray-200 rounded-full inline-flex p-1" data-act="fan" role="switch" aria-checked="false">
              <div class="toggle-knob"></div>
            </div>
          </div>
        </div>

        <div class="flex items-center justify-between">
          <div class="flex items-center space-x-3">
            <div class="text-3xl">💡</div>
            <div>
              <div class="text-sm text-gray-500">Lampu</div>
              <div id="status-light" class="text-xl font-semibold text-yellow-600">OFF</div>
            </div>
          </div>
          <div>
            <div id="toggle-light" class="toggle-track bg-gray-200 rounded-full inline-flex p-1" data-act="light" role="switch" aria-checked="false">
              <div class="toggle-knob"></div>
            </div>
          </div>
        </div>

        <p class="text-xs text-gray-400 mt-4">*Manual override — klik tombol untuk mengubah status aktuator.</p>
      </div>

      <!-- Logs: larger column -->
      <div class="card col-span-2 bg-white p-5">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-lg font-semibold text-gray-700">Log Aktivitas Terakhir</h3>
          <button id="btn-clear-logs" class="text-xs text-rose-500 hover:underline">Bersihkan</button>
        </div>

        <div id="logList" class="space-y-3 max-h-[420px] overflow-y-auto pr-2">
          <div class="text-gray-400">Memuat log...</div>
        </div>
      </div>
    </section>

    <footer class="mt-6 text-center text-xs text-gray-400">Dashboard prototype • ubah threshold & koneksi MCU untuk produksi</footer>
  </div>

<script>
/* -------------------------
   Configuration / Helpers
   ------------------------- */
const API = 'api.php';           // COMMENT: ubah jika endpoint berbeda
const CONTROL = 'control.php';  // COMMENT: ubah jika endpoint berbeda

async function fetchJSON(url) {
  const r = await fetch(url, {cache:'no-store'});
  return r.json();
}

/* -------------------------
   Charts init (3 separate)
   ------------------------- */
let chartTemp, chartSoil, chartLight;
function initCharts() {
  chartTemp = new Chart(document.getElementById('chartTemp'), {
    type:'line',
    data:{ labels:[], datasets:[
      { label:'Suhu (°C)', data:[], borderColor:'#f97316', backgroundColor:'rgba(249,115,22,0.06)', tension:0.3, pointRadius:2 }
    ]},
    options:{ responsive:true, plugins:{legend:{display:false}}, scales:{ y:{beginAtZero:false} } }
  });

  chartSoil = new Chart(document.getElementById('chartSoil'), {
    type:'line',
    data:{ labels:[], datasets:[
      { label:'Soil (%)', data:[], borderColor:'#10b981', backgroundColor:'rgba(16,185,129,0.06)', tension:0.3, pointRadius:2 }
    ]},
    options:{ responsive:true, plugins:{legend:{display:false}}, scales:{ y:{beginAtZero:true} } }
  });

  chartLight = new Chart(document.getElementById('chartLight'), {
    type:'line',
    data:{ labels:[], datasets:[
      { label:'Light (lx)', data:[], borderColor:'#facc15', backgroundColor:'rgba(250,204,21,0.06)', tension:0.3, pointRadius:2 }
    ]},
    options:{ responsive:true, plugins:{legend:{display:false}}, scales:{ y:{beginAtZero:true} } }
  });
}

/* -------------------------
   Update dashboard (cards)
   ------------------------- */
async function updateCards() {
  try {
    const j = await fetchJSON(API + '?mode=latest');
    if (!j.success) return;
    const s = j.sensors;
    const a = j.actuators;

    // Cards
    if (s.temp) { document.getElementById('card-temp').textContent = s.temp.value + ' °C'; document.getElementById('sub-temp').textContent = 'Terakhir: ' + new Date(s.temp.ts || s.temp.ts).toLocaleTimeString(); }
    if (s.hum) { document.getElementById('card-hum').textContent = s.hum.value + ' %'; document.getElementById('sub-hum').textContent = 'Terakhir: ' + new Date(s.hum.ts || s.hum.ts).toLocaleTimeString(); }
    if (s.ldr) { document.getElementById('card-ldr').textContent = s.ldr.value + ' lx'; document.getElementById('sub-ldr').textContent = 'Terakhir: ' + new Date(s.ldr.ts || s.ldr.ts).toLocaleTimeString(); }
    if (s.soil) { document.getElementById('card-soil').textContent = s.soil.value + ' %'; document.getElementById('sub-soil').textContent = 'Terakhir: ' + new Date(s.soil.ts || s.soil.ts).toLocaleTimeString(); }

    // Actuator statuses (and toggle visuals)
    ['pump','fan','light'].forEach(id=>{
      const st = a[id] && a[id].status ? true : false;
      document.getElementById('status-'+id).textContent = st ? 'ON' : 'OFF';
      // toggle track class
      const t = document.getElementById('toggle-'+id);
      if (!t) return;
      t.setAttribute('aria-checked', st ? 'true' : 'false');
      if (st) t.classList.add('toggle-on'); else t.classList.remove('toggle-on');
      // color status label (cute colors)
      const lbl = document.getElementById('status-'+id);
      if (id==='pump') lbl.style.color = st ? '#ef476f' : '#9ca3af';
      if (id==='fan') lbl.style.color = st ? '#0ea5e9' : '#9ca3af';
      if (id==='light') lbl.style.color = st ? '#facc15' : '#9ca3af';
    });

  } catch (err) {
    console.error('updateCards error', err);
  }
}

/* -------------------------
   Update charts (history)
   ------------------------- */
async function updateCharts() {
  try {
    const temp = await fetchJSON(API + '?mode=history&type=temp&limit=24');
    const soil = await fetchJSON(API + '?mode=history&type=soil&limit=24');
    const light = await fetchJSON(API + '?mode=history&type=ldr&limit=24');

    // temp
    chartTemp.data.labels = temp.data.map(p=>new Date(p.ts).toLocaleTimeString());
    chartTemp.data.datasets[0].data = temp.data.map(p=>p.value);
    chartTemp.update();

    // soil
    chartSoil.data.labels = soil.data.map(p=>new Date(p.ts).toLocaleTimeString());
    chartSoil.data.datasets[0].data = soil.data.map(p=>p.value);
    chartSoil.update();

    // light
    chartLight.data.labels = light.data.map(p=>new Date(p.ts).toLocaleTimeString());
    chartLight.data.datasets[0].data = light.data.map(p=>p.value);
    chartLight.update();

  } catch (err) {
    console.error('updateCharts error', err);
  }
}

/* -------------------------
   Update logs (feed)
   ------------------------- */
async function updateLogs() {
  try {
    const res = await fetchJSON(API + '?mode=logs&limit=30');
    const container = document.getElementById('logList');
    container.innerHTML = '';
    if (!res.success || !res.logs.length) {
      container.innerHTML = '<div class="text-gray-400">Tidak ada log.</div>';
      return;
    }
    res.logs.forEach(l => {
      // map actuator names -> icon + color
      let icon='⚙️', color='bg-gray-100', txtColor='#374151';
      if (l.name.toLowerCase().includes('pump')) { icon='💧'; color='bg-pink-50'; txtColor='#ef476f'; }
      else if (l.name.toLowerCase().includes('fan')) { icon='💨'; color='bg-sky-50'; txtColor='#0ea5e9'; }
      else if (l.name.toLowerCase().includes('light')) { icon='💡'; color='bg-yellow-50'; txtColor='#f59e0b'; }

      const row = document.createElement('div');
      row.className = 'p-3 rounded-lg border flex items-start space-x-3 hover:shadow-sm transition';
      row.innerHTML = `
        <div class="text-2xl">${icon}</div>
        <div class="flex-1">
          <div class="flex items-center justify-between">
            <div class="font-semibold" style="color:${txtColor}">${l.name}</div>
            <div class="text-xs text-gray-400">${new Date(l.created_at).toLocaleString()}</div>
          </div>
          <div class="text-sm text-gray-600 mt-1">${l.action}</div>
        </div>
      `;
      container.appendChild(row);
    });
  } catch (err) {
    console.error('updateLogs error', err);
  }
}

/* -------------------------
   Toggle control handlers
   ------------------------- */
function initToggles() {
  ['pump','fan','light'].forEach(id=>{
    const t = document.getElementById('toggle-'+id);
    if (!t) return;
    t.addEventListener('click', async () => {
      const current = t.getAttribute('aria-checked') === 'true';
      const action = current ? 'off' : 'on';
      // disable until response
      t.style.pointerEvents = 'none';
      try {
        const res = await fetch(CONTROL, {
          method:'POST',
          headers:{'Content-Type':'application/json'},
          body: JSON.stringify({ actuator: id, action })
        });
        const j = await res.json();
        if (j.success) {
          await updateCards();
          await updateLogs();
        } else {
          alert('Gagal: ' + (j.message || 'unknown'));
        }
      } catch (err) {
        console.error('toggle error', err);
        alert('Request control gagal');
      } finally {
        t.style.pointerEvents = 'auto';
      }
    });
  });
}

/* -------------------------
   Clear logs button
   ------------------------- */
// COMMENT: You can implement server-side endpoint to clear logs; here we confirm and call control.php?mode=clear (if implemented)
// For now this just warns.
document.getElementById?.('btn-clear-logs')?.addEventListener('click', (e)=>{
  if (!confirm('Bersihkan log dari database? Pastikan endpoint server tersedia.')) return;
  // Example fetch to clear (unimplemented):
  // fetch(API + '?mode=clear', { method:'POST' }).then(...);
});

/* -------------------------
   Init
   ------------------------- */
async function init() {
  initCharts();
  await updateCards();
  await updateCharts();
  await updateLogs();
  initToggles();
  // polling
  setInterval(updateCards, 8000);
  setInterval(updateCharts, 60000);
  setInterval(updateLogs, 15000);
}
init();
</script>
</body>
</html>