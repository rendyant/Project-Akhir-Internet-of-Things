<!doctype html>
<html lang="id" class="light">
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1"/>
  <title>Greenhouse — Dashboard</title>

  <!-- Font: Inter -->
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

  <!-- Tailwind CDN -->
  <script src="https://cdn.tailwindcss.com"></script>

  <!-- Chart.js -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

  <!-- Lucide Icons -->
  <script src="https://unpkg.com/lucide@latest"></script>

  <style>
    * { font-family: 'Inter', sans-serif; }
    
    :root {
      --accent: #10b981;
      --accent-dark: #059669;
    }

    body {
      -webkit-font-smoothing: antialiased;
      -moz-osx-font-smoothing: grayscale;
    }

    /* Card with subtle shadow */
    .card {
      border-radius: 16px;
      box-shadow: 0 1px 3px rgba(0,0,0,0.08);
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
      border: 1px solid rgba(0,0,0,0.05);
    }

    .card:hover {
      box-shadow: 0 4px 12px rgba(0,0,0,0.12);
      transform: translateY(-2px);
    }

    /* Toggle Switch Modern */
    .toggle-switch {
      position: relative;
      width: 52px;
      height: 28px;
      background: #e5e7eb;
      border-radius: 28px;
      cursor: pointer;
      transition: all 0.3s ease;
      border: none;
      outline: none;
    }

    .toggle-switch::after {
      content: '';
      position: absolute;
      top: 3px;
      left: 3px;
      width: 22px;
      height: 22px;
      background: white;
      border-radius: 50%;
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
      box-shadow: 0 2px 4px rgba(0,0,0,0.2);
    }

    .toggle-switch.active {
      background: var(--accent);
    }

    .toggle-switch.active::after {
      left: 27px;
    }

    /* Status badge */
    .status-badge {
      display: inline-block;
      padding: 4px 12px;
      border-radius: 12px;
      font-size: 0.75rem;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }

    .status-on {
      background: #d1fae5;
      color: #065f46;
    }

    .status-off {
      background: #fee2e2;
      color: #991b1b;
    }

    /* Dark mode adjustments */
    .dark .card {
      box-shadow: 0 1px 3px rgba(0,0,0,0.3);
      border-color: rgba(255,255,255,0.1);
    }

    .dark .toggle-switch {
      background: #374151;
    }

    /* Toast notification */
    #toast {
      position: fixed;
      right: 24px;
      bottom: 24px;
      padding: 16px 24px;
      border-radius: 12px;
      background: var(--accent);
      color: white;
      opacity: 0;
      transform: translateY(20px);
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
      pointer-events: none;
      z-index: 9999;
      box-shadow: 0 10px 25px rgba(0,0,0,0.2);
      display: flex;
      align-items: center;
      gap: 10px;
      font-weight: 500;
    }

    #toast.show {
      opacity: 1;
      transform: translateY(0);
      pointer-events: auto;
    }

    #toast.error {
      background: #ef4444;
    }

    #toast.warning {
      background: #f59e0b;
    }

    /* Log item animation */
    .log-item {
      animation: slideIn 0.3s ease;
    }

    @keyframes slideIn {
      from {
        opacity: 0;
        transform: translateX(-10px);
      }
      to {
        opacity: 1;
        transform: translateX(0);
      }
    }

    /* Pulse animation for active status */
    .pulse-active {
      animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
    }

    @keyframes pulse {
      0%, 100% {
        opacity: 1;
      }
      50% {
        opacity: .7;
      }
    }

    /* Chart container height control */
    .chart-container {
      position: relative;
      height: 240px;
    }

    /* Scrollbar styling */
    .custom-scrollbar::-webkit-scrollbar {
      width: 6px;
    }

    .custom-scrollbar::-webkit-scrollbar-track {
      background: #f1f5f9;
      border-radius: 10px;
    }

    .custom-scrollbar::-webkit-scrollbar-thumb {
      background: #cbd5e1;
      border-radius: 10px;
    }

    .custom-scrollbar::-webkit-scrollbar-thumb:hover {
      background: #94a3b8;
    }

    .dark .custom-scrollbar::-webkit-scrollbar-track {
      background: #1e293b;
    }

    .dark .custom-scrollbar::-webkit-scrollbar-thumb {
      background: #475569;
    }
  </style>

  <script>
    tailwind.config = { darkMode: 'class' };
    if (localStorage.theme === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
      document.documentElement.classList.add('dark');
    } else {
      document.documentElement.classList.remove('dark');
    }
  </script>
</head>
<body class="bg-gradient-to-br from-slate-50 via-white to-emerald-50 dark:from-gray-900 dark:via-gray-900 dark:to-gray-800 min-h-screen text-gray-800 dark:text-gray-200 transition-colors duration-300">

  <!-- Toast Notification -->
  <div id="toast">
    <i data-lucide="check-circle" class="w-5 h-5"></i>
    <span id="toast-message">Pesan</span>
  </div>

  <div class="max-w-7xl mx-auto p-4 sm:p-6 lg:p-8">
    <!-- Header -->
    <header class="mb-8 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
      <div>
        <div class="flex items-center gap-3 mb-2">
          <div class="w-12 h-12 bg-gradient-to-br from-emerald-500 to-teal-600 rounded-xl flex items-center justify-center shadow-lg">
            <i data-lucide="sprout" class="w-7 h-7 text-white"></i>
          </div>
          <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Greenhouse Monitor</h1>
            <p class="text-sm text-emerald-600 dark:text-emerald-400 font-medium">Real-time Smart Control System</p>
          </div>
        </div>
      </div>

      <div class="flex items-center gap-3">
        <!-- Notification Settings -->
        <button id="btn-notif-settings" 
          class="p-2.5 rounded-xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700 transition-all"
          title="Pengaturan Notifikasi">
          <i data-lucide="bell" class="w-5 h-5 text-gray-600 dark:text-gray-400"></i>
        </button>

        <!-- Dark Mode Toggle -->
        <button id="toggle-dark-mode" 
          class="p-2.5 rounded-xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700 transition-all"
          title="Toggle Dark Mode">
          <i data-lucide="moon" class="w-5 h-5 text-gray-600 dark:text-gray-400"></i>
        </button>

        <!-- Refresh Button -->
        <button id="btn-refresh" 
          class="px-4 py-2.5 rounded-xl bg-emerald-500 hover:bg-emerald-600 text-white font-medium transition-all shadow-sm hover:shadow-md flex items-center gap-2">
          <i data-lucide="refresh-cw" class="w-4 h-4"></i>
          <span class="hidden sm:inline">Refresh</span>
        </button>
      </div>
    </header>

    <!-- Sensor Cards -->
    <section class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6 mb-8">
      <!-- Temperature Card -->
      <div class="card bg-gradient-to-br from-orange-50 to-red-50 dark:from-orange-900/20 dark:to-red-900/20 p-6">
        <div class="flex items-start justify-between mb-4">
          <div class="p-3 bg-orange-100 dark:bg-orange-900/40 rounded-xl">
            <i data-lucide="thermometer" class="w-6 h-6 text-orange-600 dark:text-orange-400"></i>
          </div>
          <span class="text-xs font-medium text-orange-600 dark:text-orange-400 bg-orange-100 dark:bg-orange-900/40 px-3 py-1 rounded-full">
            LIVE
          </span>
        </div>
        <div class="text-sm text-gray-600 dark:text-gray-400 mb-1">Suhu Udara</div>
        <div id="card-temp" class="text-3xl font-bold text-gray-900 dark:text-white mb-1">-- °C</div>
        <div id="sub-temp" class="text-xs text-gray-500 dark:text-gray-500">Terakhir: --</div>
      </div>

      <!-- Humidity Card -->
      <div class="card bg-gradient-to-br from-blue-50 to-cyan-50 dark:from-blue-900/20 dark:to-cyan-900/20 p-6">
        <div class="flex items-start justify-between mb-4">
          <div class="p-3 bg-blue-100 dark:bg-blue-900/40 rounded-xl">
            <i data-lucide="droplets" class="w-6 h-6 text-blue-600 dark:text-blue-400"></i>
          </div>
          <span class="text-xs font-medium text-blue-600 dark:text-blue-400 bg-blue-100 dark:bg-blue-900/40 px-3 py-1 rounded-full">
            LIVE
          </span>
        </div>
        <div class="text-sm text-gray-600 dark:text-gray-400 mb-1">Kelembapan Udara</div>
        <div id="card-hum" class="text-3xl font-bold text-gray-900 dark:text-white mb-1">-- %</div>
        <div id="sub-hum" class="text-xs text-gray-500 dark:text-gray-500">Terakhir: --</div>
      </div>

      <!-- Soil Moisture Card -->
      <div class="card bg-gradient-to-br from-emerald-50 to-teal-50 dark:from-emerald-900/20 dark:to-teal-900/20 p-6">
        <div class="flex items-start justify-between mb-4">
          <div class="p-3 bg-emerald-100 dark:bg-emerald-900/40 rounded-xl">
            <i data-lucide="droplet" class="w-6 h-6 text-emerald-600 dark:text-emerald-400"></i>
          </div>
          <span class="text-xs font-medium text-emerald-600 dark:text-emerald-400 bg-emerald-100 dark:bg-emerald-900/40 px-3 py-1 rounded-full">
            LIVE
          </span>
        </div>
        <div class="text-sm text-gray-600 dark:text-gray-400 mb-1">Kelembapan Tanah</div>
        <div id="card-soil" class="text-3xl font-bold text-gray-900 dark:text-white mb-1">-- %</div>
        <div id="sub-soil" class="text-xs text-gray-500 dark:text-gray-500">Terakhir: --</div>
      </div>

      <!-- Light Intensity Card -->
      <div class="card bg-gradient-to-br from-yellow-50 to-amber-50 dark:from-yellow-900/20 dark:to-amber-900/20 p-6">
        <div class="flex items-start justify-between mb-4">
          <div class="p-3 bg-yellow-100 dark:bg-yellow-900/40 rounded-xl">
            <i data-lucide="sun" class="w-6 h-6 text-yellow-600 dark:text-yellow-400"></i>
          </div>
          <span class="text-xs font-medium text-yellow-600 dark:text-yellow-400 bg-yellow-100 dark:bg-yellow-900/40 px-3 py-1 rounded-full">
            LIVE
          </span>
        </div>
        <div class="text-sm text-gray-600 dark:text-gray-400 mb-1">Intensitas Cahaya</div>
        <div id="card-ldr" class="text-3xl font-bold text-gray-900 dark:text-white mb-1">-- lx</div>
        <div id="sub-ldr" class="text-xs text-gray-500 dark:text-gray-500">Terakhir: --</div>
      </div>
    </section>

    <!-- Charts Section - 1 ROW -->
    <section class="mb-8">
      <div class="flex items-center justify-between mb-4">
        <h2 class="text-xl font-bold text-gray-900 dark:text-white">Grafik Monitoring</h2>
        <span class="text-sm text-gray-500 dark:text-gray-400">24 Data Terakhir</span>
      </div>
      
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 sm:gap-6">
        <!-- Combined DHT Chart (Suhu + Kelembapan) -->
        <div class="card bg-white dark:bg-gray-800 p-5">
          <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-4 flex items-center gap-2">
            <i data-lucide="activity" class="w-4 h-4 text-emerald-600"></i>
            Suhu & Kelembapan Udara
          </h3>
          <div class="chart-container">
            <canvas id="chartDHT"></canvas>
          </div>
        </div>

        <!-- Soil Moisture Chart -->
        <div class="card bg-white dark:bg-gray-800 p-5">
          <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-4 flex items-center gap-2">
            <i data-lucide="droplet" class="w-4 h-4 text-emerald-600"></i>
            Kelembapan Tanah
          </h3>
          <div class="chart-container">
            <canvas id="chartSoil"></canvas>
          </div>
        </div>

        <!-- Light Intensity Chart -->
        <div class="card bg-white dark:bg-gray-800 p-5 lg:col-span-2">
          <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-4 flex items-center gap-2">
            <i data-lucide="sun" class="w-4 h-4 text-yellow-600"></i>
            Intensitas Cahaya
          </h3>
          <div class="chart-container">
            <canvas id="chartLight"></canvas>
          </div>
        </div>
      </div>
    </section>

    <!-- Control & Logs Section -->
    <section class="grid grid-cols-1 lg:grid-cols-3 gap-4 sm:gap-6">
      <!-- Actuator Controls -->
      <div class="card bg-white dark:bg-gray-800 p-6">
        <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-6 flex items-center gap-2">
          <i data-lucide="settings" class="w-5 h-5 text-emerald-600"></i>
          Kontrol Aktuator
        </h3>

        <div class="space-y-5">
          <!-- Pump Control -->
          <div class="flex items-center justify-between p-4 bg-gradient-to-r from-pink-50 to-rose-50 dark:from-pink-900/10 dark:to-rose-900/10 rounded-xl">
            <div class="flex items-center gap-3">
              <div class="p-2 bg-pink-100 dark:bg-pink-900/30 rounded-lg">
                <i data-lucide="droplets" class="w-5 h-5 text-pink-600 dark:text-pink-400"></i>
              </div>
              <div>
                <div class="text-sm font-medium text-gray-700 dark:text-gray-300">Pompa Air</div>
                <span id="status-pump" class="status-badge status-off">OFF</span>
              </div>
            </div>
            <button id="btn-pump" class="toggle-switch" data-code="pump"></button>
          </div>

          <!-- Fan Control -->
          <div class="flex items-center justify-between p-4 bg-gradient-to-r from-sky-50 to-blue-50 dark:from-sky-900/10 dark:to-blue-900/10 rounded-xl">
            <div class="flex items-center gap-3">
              <div class="p-2 bg-sky-100 dark:bg-sky-900/30 rounded-lg">
                <i data-lucide="wind" class="w-5 h-5 text-sky-600 dark:text-sky-400"></i>
              </div>
              <div>
                <div class="text-sm font-medium text-gray-700 dark:text-gray-300">Kipas Ventilasi</div>
                <span id="status-fan" class="status-badge status-off">OFF</span>
              </div>
            </div>
            <button id="btn-fan" class="toggle-switch" data-code="fan"></button>
          </div>

          <!-- Light Control -->
          <div class="flex items-center justify-between p-4 bg-gradient-to-r from-yellow-50 to-amber-50 dark:from-yellow-900/10 dark:to-amber-900/10 rounded-xl">
            <div class="flex items-center gap-3">
              <div class="p-2 bg-yellow-100 dark:bg-yellow-900/30 rounded-lg">
                <i data-lucide="lightbulb" class="w-5 h-5 text-yellow-600 dark:text-yellow-400"></i>
              </div>
              <div>
                <div class="text-sm font-medium text-gray-700 dark:text-gray-300">Lampu Grow</div>
                <span id="status-light" class="status-badge status-off">OFF</span>
              </div>
            </div>
            <button id="btn-light" class="toggle-switch" data-code="light"></button>
          </div>
        </div>
      </div>

      <!-- Activity Logs -->
      <div class="card bg-white dark:bg-gray-800 p-6 lg:col-span-2">
        <div class="flex items-center justify-between mb-6">
          <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
            <i data-lucide="scroll-text" class="w-5 h-5 text-emerald-600"></i>
            Log Aktivitas
          </h3>
          <button id="btn-clear-logs" class="text-sm text-red-500 hover:text-red-600 dark:text-red-400 font-medium flex items-center gap-1 transition-colors">
            <i data-lucide="trash-2" class="w-4 h-4"></i>
            Hapus Log
          </button>
        </div>

        <div id="logList" class="space-y-2 max-h-[320px] overflow-y-auto custom-scrollbar pr-2">
          <div class="text-center text-gray-400 py-8">
            <i data-lucide="loader" class="w-6 h-6 mx-auto mb-2 animate-spin"></i>
            <p class="text-sm">Memuat log aktivitas...</p>
          </div>
        </div>
      </div>
    </section>

    <!-- Notification Settings Modal -->
    <div id="notif-modal" class="hidden fixed inset-0 bg-black/50 backdrop-blur-sm z-50 flex items-center justify-center p-4">
      <div class="card bg-white dark:bg-gray-800 p-6 max-w-md w-full max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between mb-6">
          <h3 class="text-xl font-bold text-gray-900 dark:text-white">Pengaturan Notifikasi</h3>
          <button id="close-notif-modal" class="p-1 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg">
            <i data-lucide="x" class="w-5 h-5"></i>
          </button>
        </div>

        <div class="space-y-6">
          <!-- Telegram Settings -->
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              <i data-lucide="send" class="w-4 h-4 inline mr-1"></i>
              Telegram Bot Token
            </label>
            <input type="text" id="telegram-token" placeholder="Masukkan bot token..." 
              class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-emerald-500 outline-none">
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              <i data-lucide="hash" class="w-4 h-4 inline mr-1"></i>
              Telegram Chat ID
            </label>
            <input type="text" id="telegram-chatid" placeholder="Masukkan chat ID..." 
              class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-emerald-500 outline-none">
          </div>

          <!-- Threshold Settings -->
          <div class="border-t border-gray-200 dark:border-gray-700 pt-4">
            <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">Batas Peringatan</h4>
            
            <div class="space-y-3">
              <div>
                <label class="text-xs text-gray-600 dark:text-gray-400">Suhu Maksimal (°C)</label>
                <input type="number" id="threshold-temp" value="35" 
                  class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-sm">
              </div>
              
              <div>
                <label class="text-xs text-gray-600 dark:text-gray-400">Kelembapan Tanah Minimal (%)</label>
                <input type="number" id="threshold-soil" value="30" 
                  class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-sm">
              </div>
            </div>
          </div>

          <button id="save-notif-settings" 
            class="w-full px-4 py-3 rounded-lg bg-emerald-500 hover:bg-emerald-600 text-white font-medium transition-colors flex items-center justify-center gap-2">
            <i data-lucide="save" class="w-4 h-4"></i>
            Simpan Pengaturan
          </button>
        </div>
      </div>
    </div>

    <footer class="mt-8 text-center text-xs text-gray-400 dark:text-gray-600 pb-4">
      Greenhouse Monitoring System v3.0 • Smart Agriculture Solution
    </footer>
  </div>

  <script>
    // ========== GLOBAL VARIABLES ==========
    let chartDHT, chartSoil, chartLight;
    let notificationSettings = {
      telegram: { token: '', chatId: '' },
      thresholds: { temp: 35, soil: 30 }
    };

    // Load settings from localStorage
    if (localStorage.getItem('notifSettings')) {
      notificationSettings = JSON.parse(localStorage.getItem('notifSettings'));
    }

    // ========== DARK MODE TOGGLE ==========
    document.getElementById('toggle-dark-mode').addEventListener('click', () => {
      const html = document.documentElement;
      const isDark = html.classList.contains('dark');
      
      if (isDark) {
        html.classList.remove('dark');
        localStorage.theme = 'light';
      } else {
        html.classList.add('dark');
        localStorage.theme = 'dark';
      }
      
      // Redraw charts with new theme
      setTimeout(initCharts, 100);
    });

    // ========== NOTIFICATION MODAL ==========
    const notifModal = document.getElementById('notif-modal');
    document.getElementById('btn-notif-settings').addEventListener('click', () => {
      // Load current settings
      document.getElementById('telegram-token').value = notificationSettings.telegram.token;
      document.getElementById('telegram-chatid').value = notificationSettings.telegram.chatId;
      document.getElementById('threshold-temp').value = notificationSettings.thresholds.temp;
      document.getElementById('threshold-soil').value = notificationSettings.thresholds.soil;
      
      notifModal.classList.remove('hidden');
    });

    document.getElementById('close-notif-modal').addEventListener('click', () => {
      notifModal.classList.add('hidden');
    });

    document.getElementById('save-notif-settings').addEventListener('click', () => {
      notificationSettings = {
        telegram: {
          token: document.getElementById('telegram-token').value,
          chatId: document.getElementById('telegram-chatid').value
        },
        thresholds: {
          temp: parseFloat(document.getElementById('threshold-temp').value),
          soil: parseFloat(document.getElementById('threshold-soil').value)
        }
      };
      
      localStorage.setItem('notifSettings', JSON.stringify(notificationSettings));
      showToast('Pengaturan notifikasi berhasil disimpan!', 'success');
      notifModal.classList.add('hidden');
    });

    // ========== TOAST NOTIFICATION ==========
    function showToast(message, type = 'success') {
      const toast = document.getElementById('toast');
      const toastMsg = document.getElementById('toast-message');
      
      toast.className = type === 'error' ? 'error' : (type === 'warning' ? 'warning' : '');
      toastMsg.textContent = message;
      toast.classList.add('show');
      
      setTimeout(() => {
        toast.classList.remove('show');
      }, 3000);
    }

    // ========== SEND TELEGRAM NOTIFICATION ==========
    async function sendTelegramAlert(message) {
      const { token, chatId } = notificationSettings.telegram;
      
      if (!token || !chatId) {
        console.log('Telegram not configured');
        return;
      }

      try {
        const url = `https://api.telegram.org/bot${token}/sendMessage`;
        await fetch(url, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({
            chat_id: chatId,
            text: `🚨 *GREENHOUSE ALERT*\n\n${message}`,
            parse_mode: 'Markdown'
          })
        });
        console.log('Telegram alert sent:', message);
      } catch (error) {
        console.error('Failed to send Telegram alert:', error);
      }
    }

    // ========== CHECK THRESHOLDS ==========
    function checkThresholds(data) {
      const { temperature, soil } = data.latest;
      const { temp: maxTemp, soil: minSoil } = notificationSettings.thresholds;
      
      if (temperature && temperature > maxTemp) {
        sendTelegramAlert(`⚠️ Suhu terlalu tinggi: ${temperature}°C (Batas: ${maxTemp}°C)`);
        showToast(`Peringatan: Suhu ${temperature}°C melebihi batas!`, 'warning');
      }
      
      if (soil && soil < minSoil) {
        sendTelegramAlert(`⚠️ Kelembapan tanah rendah: ${soil}% (Minimal: ${minSoil}%)`);
        showToast(`Peringatan: Tanah kering ${soil}%!`, 'warning');
      }
    }

    // ========== INIT CHARTS ==========
    function initCharts() {
      const isDark = document.documentElement.classList.contains('dark');
      const gridColor = isDark ? 'rgba(255,255,255,0.1)' : 'rgba(0,0,0,0.05)';
      const textColor = isDark ? '#9ca3af' : '#6b7280';

      Chart.defaults.color = textColor;
      Chart.defaults.borderColor = gridColor;

      const commonOptions = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: {
            display: true,
            position: 'top',
            labels: {
              usePointStyle: true,
              padding: 15,
              font: { size: 11, weight: '500' }
            }
          },
          tooltip: {
            backgroundColor: isDark ? '#1f2937' : '#ffffff',
            titleColor: isDark ? '#f3f4f6' : '#111827',
            bodyColor: isDark ? '#d1d5db' : '#374151',
            borderColor: isDark ? '#374151' : '#e5e7eb',
            borderWidth: 1,
            padding: 12,
            displayColors: true,
            callbacks: {
              title: (items) => {
                const time = items[0].label;
                return time ? `Waktu: ${time}` : '';
              }
            }
          }
        },
        scales: {
          x: {
            grid: { display: false },
            ticks: { maxRotation: 45, minRotation: 45, font: { size: 10 } }
          },
          y: {
            beginAtZero: true,
            grid: { color: gridColor },
            ticks: { font: { size: 10 } }
          }
        }
      };

      // DHT Combined Chart (Temp + Humidity)
      const ctxDHT = document.getElementById('chartDHT').getContext('2d');
      if (chartDHT) chartDHT.destroy();
      chartDHT = new Chart(ctxDHT, {
        type: 'line',
        data: {
          labels: [],
          datasets: [
            {
              label: 'Suhu (°C)',
              data: [],
              borderColor: '#f97316',
              backgroundColor: 'rgba(249, 115, 22, 0.1)',
              tension: 0.4,
              borderWidth: 2,
              pointRadius: 3,
              pointHoverRadius: 5,
              fill: true
            },
            {
              label: 'Kelembapan (%)',
              data: [],
              borderColor: '#3b82f6',
              backgroundColor: 'rgba(59, 130, 246, 0.1)',
              tension: 0.4,
              borderWidth: 2,
              pointRadius: 3,
              pointHoverRadius: 5,
              fill: true
            }
          ]
        },
        options: {
          ...commonOptions,
          scales: {
            ...commonOptions.scales,
            y: {
              ...commonOptions.scales.y,
              title: {
                display: true,
                text: 'Nilai',
                font: { size: 11, weight: '600' }
              }
            }
          }
        }
      });

      // Soil Moisture Chart
      const ctxSoil = document.getElementById('chartSoil').getContext('2d');
      if (chartSoil) chartSoil.destroy();
      chartSoil = new Chart(ctxSoil, {
        type: 'line',
        data: {
          labels: [],
          datasets: [{
            label: 'Kelembapan Tanah (%)',
            data: [],
            borderColor: '#10b981',
            backgroundColor: 'rgba(16, 185, 129, 0.1)',
            tension: 0.4,
            borderWidth: 2,
            pointRadius: 3,
            pointHoverRadius: 5,
            fill: true
          }]
        },
        options: commonOptions
      });

      // Light Intensity Chart
      const ctxLight = document.getElementById('chartLight').getContext('2d');
      if (chartLight) chartLight.destroy();
      chartLight = new Chart(ctxLight, {
        type: 'line',
        data: {
          labels: [],
          datasets: [{
            label: 'Intensitas Cahaya (lx)',
            data: [],
            borderColor: '#eab308',
            backgroundColor: 'rgba(234, 179, 8, 0.1)',
            tension: 0.4,
            borderWidth: 2,
            pointRadius: 3,
            pointHoverRadius: 5,
            fill: true
          }]
        },
        options: commonOptions
      });
    }

    // ========== UPDATE CHARTS ==========
    function updateCharts(history) {
      // DHT Chart (Temperature + Humidity combined)
      if (history.temp && history.temp.length > 0) {
        const tempData = [...history.temp].reverse();
        const labels = tempData.map(d => {
          const dt = new Date(d.ts);
          return dt.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
        });
        
        chartDHT.data.labels = labels;
        chartDHT.data.datasets[0].data = tempData.map(d => d.value); // Temperature
        
        // Get humidity data (assuming same timestamps)
        if (history.hum && history.hum.length > 0) {
          const humData = [...history.hum].reverse();
          chartDHT.data.datasets[1].data = humData.map(d => d.value);
        }
        
        chartDHT.update('none');
      }

      // Soil Chart - FIXED: Now showing soil moisture correctly
      if (history.soil && history.soil.length > 0) {
        const soilData = [...history.soil].reverse();
        const labels = soilData.map(d => {
          const dt = new Date(d.ts);
          return dt.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
        });
        
        chartSoil.data.labels = labels;
        chartSoil.data.datasets[0].data = soilData.map(d => d.value);
        chartSoil.update('none');
      }

      // Light Chart - FIXED: Now showing light intensity correctly
      if (history.light && history.light.length > 0) {
        const lightData = [...history.light].reverse();
        const labels = lightData.map(d => {
          const dt = new Date(d.ts);
          return dt.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
        });
        
        chartLight.data.labels = labels;
        chartLight.data.datasets[0].data = lightData.map(d => d.value);
        chartLight.update('none');
      }
    }

    // ========== FETCH DATA ==========
    async function fetchData() {
      try {
        // Gunakan path relatif atau absolute tergantung struktur folder
        const apiUrl = 'api/get_data.php';
        console.log('Fetching data from:', apiUrl);
        
        const response = await fetch(apiUrl, {
          method: 'GET',
          headers: {
            'Content-Type': 'application/json',
          },
          cache: 'no-cache'
        });

        console.log('Response status:', response.status);

        if (!response.ok) {
          throw new Error(`HTTP error! status: ${response.status}`);
        }

        const data = await response.json();
        console.log('Data received:', data);

        if (!data.success) {
          showToast('Gagal mengambil data dari server', 'error');
          return;
        }

        // Update sensor cards
        const { latest, history, actuators, logs } = data;

        // Temperature
        document.getElementById('card-temp').textContent = 
          latest.temperature ? `${latest.temperature.toFixed(1)} °C` : '-- °C';
        document.getElementById('sub-temp').textContent = 
          latest.ts ? `Terakhir: ${new Date(latest.ts).toLocaleTimeString('id-ID')}` : 'Terakhir: --';

        // Humidity
        document.getElementById('card-hum').textContent = 
          latest.humidity ? `${latest.humidity.toFixed(1)} %` : '-- %';
        document.getElementById('sub-hum').textContent = 
          latest.ts ? `Terakhir: ${new Date(latest.ts).toLocaleTimeString('id-ID')}` : 'Terakhir: --';

        // Soil Moisture
        document.getElementById('card-soil').textContent = 
          latest.soil ? `${latest.soil.toFixed(1)} %` : '-- %';
        document.getElementById('sub-soil').textContent = 
          latest.ts ? `Terakhir: ${new Date(latest.ts).toLocaleTimeString('id-ID')}` : 'Terakhir: --';

        // Light Intensity
        document.getElementById('card-ldr').textContent = 
          latest.light ? `${latest.light} lx` : '-- lx';
        document.getElementById('sub-ldr').textContent = 
          latest.ts ? `Terakhir: ${new Date(latest.ts).toLocaleTimeString('id-ID')}` : 'Terakhir: --';

        // Update charts with humidity data added
        const historyWithHumidity = {
          ...history,
          hum: history.temp.map((item, index) => ({
            ts: item.ts,
            value: latest.humidity || 0 // Using latest humidity for demo
          }))
        };
        updateCharts(historyWithHumidity);

        // Update actuator states
        updateActuatorUI(actuators);

        // Update logs
        updateLogs(logs);

        // Check thresholds and send alerts
        checkThresholds(data);

      } catch (error) {
        console.error('Error fetching data:', error);
        console.error('Error details:', {
          message: error.message,
          name: error.name,
          stack: error.stack
        });
        
        // Show more specific error message
        let errorMsg = 'Koneksi ke server gagal';
        if (error.message.includes('Failed to fetch')) {
          errorMsg = 'Server tidak dapat dijangkau. Pastikan:\n1. File api/get_data.php ada\n2. Web server berjalan\n3. Path folder benar';
        } else if (error.message.includes('404')) {
          errorMsg = 'File API tidak ditemukan (404)';
        } else if (error.message.includes('500')) {
          errorMsg = 'Server error (500). Cek error log PHP';
        }
        
        showToast(errorMsg, 'error');
        
        // Update UI to show error state
        document.getElementById('card-temp').textContent = 'Error';
        document.getElementById('card-hum').textContent = 'Error';
        document.getElementById('card-soil').textContent = 'Error';
        document.getElementById('card-ldr').textContent = 'Error';
      }
    }

    // ========== UPDATE ACTUATOR UI ==========
    function updateActuatorUI(actuators) {
      ['pump', 'fan', 'light'].forEach(code => {
        const btn = document.getElementById(`btn-${code}`);
        const statusEl = document.getElementById(`status-${code}`);
        
        if (actuators[code]) {
          const isOn = actuators[code].status === 1;
          
          if (isOn) {
            btn.classList.add('active');
            statusEl.textContent = 'ON';
            statusEl.className = 'status-badge status-on pulse-active';
          } else {
            btn.classList.remove('active');
            statusEl.textContent = 'OFF';
            statusEl.className = 'status-badge status-off';
          }
        }
      });
    }

    // ========== UPDATE LOGS ==========
    function updateLogs(logs) {
      const logList = document.getElementById('logList');
      
      if (logs.length === 0) {
        logList.innerHTML = '<div class="text-center text-gray-400 py-8">Belum ada aktivitas</div>';
        return;
      }

      logList.innerHTML = logs.map(log => {
        const time = new Date(log.created_at);
        const timeStr = time.toLocaleString('id-ID', {
          day: '2-digit',
          month: 'short',
          hour: '2-digit',
          minute: '2-digit'
        });

        return `
          <div class="log-item flex items-start gap-3 p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
            <div class="p-1.5 bg-emerald-100 dark:bg-emerald-900/30 rounded-lg mt-0.5">
              <i data-lucide="zap" class="w-3.5 h-3.5 text-emerald-600 dark:text-emerald-400"></i>
            </div>
            <div class="flex-1 min-w-0">
              <p class="text-sm text-gray-700 dark:text-gray-300">${log.detail}</p>
              <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">${timeStr}</p>
            </div>
          </div>
        `;
      }).join('');

      // Reinitialize icons
      lucide.createIcons();
    }

    // ========== ACTUATOR CONTROL ==========
    async function toggleActuator(code, currentState) {
      const newState = currentState ? 0 : 1;
      
      try {
        const formData = new FormData();
        formData.append('code', code);
        formData.append('status', newState);

        const response = await fetch('api/update_actuator.php', {
          method: 'POST',
          body: formData
        });

        const result = await response.json();

        if (result.success) {
          showToast(`${code.toUpperCase()} berhasil di${newState ? 'aktifkan' : 'nonaktifkan'}`, 'success');
          fetchData(); // Refresh data
        } else {
          showToast('Gagal mengubah status aktuator', 'error');
        }
      } catch (error) {
        console.error('Error toggling actuator:', error);
        showToast('Koneksi ke server gagal', 'error');
      }
    }

    // ========== ACTUATOR BUTTON LISTENERS ==========
    ['pump', 'fan', 'light'].forEach(code => {
      const btn = document.getElementById(`btn-${code}`);
      btn.addEventListener('click', () => {
        const isActive = btn.classList.contains('active');
        toggleActuator(code, isActive);
      });
    });

    // ========== CLEAR LOGS ==========
    document.getElementById('btn-clear-logs').addEventListener('click', async () => {
      if (!confirm('Yakin ingin menghapus semua log?')) return;

      try {
        const response = await fetch('api/clear_logs.php');
        const text = await response.text();
        
        showToast('Log berhasil dihapus', 'success');
        fetchData();
      } catch (error) {
        showToast('Gagal menghapus log', 'error');
      }
    });

    // ========== REFRESH BUTTON ==========
    document.getElementById('btn-refresh').addEventListener('click', () => {
      const btn = document.getElementById('btn-refresh');
      const icon = btn.querySelector('i');
      icon.classList.add('animate-spin');
      
      fetchData().then(() => {
        setTimeout(() => {
          icon.classList.remove('animate-spin');
          showToast('Data berhasil diperbarui!', 'success');
        }, 500);
      });
    });

    // ========== INITIALIZATION ==========
    window.addEventListener('DOMContentLoaded', () => {
      lucide.createIcons();
      initCharts();
      fetchData();
      
      // Auto refresh every 5 seconds
      setInterval(fetchData, 5000);
    });
  </script>
</body>
</html>