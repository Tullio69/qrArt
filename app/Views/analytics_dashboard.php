<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics Dashboard - qrArt</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .stat-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: transform 0.2s;
        }
        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }
        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
            color: #007bff;
        }
        .stat-label {
            color: #6c757d;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .chart-container {
            position: relative;
            height: 300px;
            margin-bottom: 20px;
        }
        .section-title {
            font-size: 1.5rem;
            font-weight: 600;
            margin: 30px 0 20px 0;
            color: #333;
        }
        .content-row {
            background: white;
            padding: 15px;
            margin-bottom: 10px;
            border-radius: 5px;
            border-left: 4px solid #007bff;
            transition: all 0.2s;
        }
        .content-row:hover {
            background: #f8f9fa;
            transform: translateX(5px);
        }
        .badge-device {
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.8rem;
            margin: 2px;
        }
        .loading {
            text-align: center;
            padding: 50px;
        }
        .navbar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .refresh-btn {
            position: fixed;
            bottom: 30px;
            right: 30px;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: #007bff;
            color: white;
            border: none;
            box-shadow: 0 4px 15px rgba(0,123,255,0.4);
            cursor: pointer;
            transition: all 0.3s;
        }
        .refresh-btn:hover {
            transform: scale(1.1);
            box-shadow: 0 6px 20px rgba(0,123,255,0.6);
        }
        .date-filter {
            background: white;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-dark">
        <div class="container-fluid">
            <span class="navbar-brand mb-0 h1">
                <i class="fas fa-chart-line"></i> qrArt Analytics Dashboard
            </span>
            <span class="text-white">
                <i class="far fa-clock"></i> <span id="lastUpdate">Caricamento...</span>
            </span>
        </div>
    </nav>

    <div class="container-fluid mt-4">
        <!-- Date Filter -->
        <div class="date-filter">
            <div class="row align-items-end">
                <div class="col-md-3">
                    <label>Data Inizio</label>
                    <input type="date" id="startDate" class="form-control">
                </div>
                <div class="col-md-3">
                    <label>Data Fine</label>
                    <input type="date" id="endDate" class="form-control">
                </div>
                <div class="col-md-3">
                    <button class="btn btn-primary" onclick="applyFilters()">
                        <i class="fas fa-filter"></i> Applica Filtri
                    </button>
                    <button class="btn btn-secondary ml-2" onclick="clearFilters()">
                        <i class="fas fa-times"></i> Reset
                    </button>
                </div>
            </div>
        </div>

        <div id="loadingIndicator" class="loading">
            <div class="spinner-border text-primary" role="status">
                <span class="sr-only">Caricamento...</span>
            </div>
            <p class="mt-3">Caricamento dati analytics...</p>
        </div>

        <div id="dashboardContent" style="display: none;">
            <!-- Summary Stats -->
            <div class="row">
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="stat-label">
                            <i class="fas fa-qrcode"></i> Scansioni Totali
                        </div>
                        <div class="stat-number" id="totalScans">0</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="stat-label">
                            <i class="fas fa-eye"></i> Visualizzazioni
                        </div>
                        <div class="stat-number" id="totalViews">0</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="stat-label">
                            <i class="fas fa-users"></i> Visitatori Unici
                        </div>
                        <div class="stat-number" id="uniqueVisitors">0</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="stat-label">
                            <i class="fas fa-file-alt"></i> Contenuti Attivi
                        </div>
                        <div class="stat-number" id="totalContents">0</div>
                    </div>
                </div>
            </div>

            <!-- Charts Row -->
            <div class="row mt-4">
                <div class="col-md-6">
                    <div class="stat-card">
                        <h5><i class="fas fa-chart-pie"></i> Distribuzione per Dispositivo</h5>
                        <div class="chart-container">
                            <canvas id="deviceChart"></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="stat-card">
                        <h5><i class="fas fa-language"></i> Distribuzione per Lingua</h5>
                        <div class="chart-container">
                            <canvas id="languageChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Timeline Chart -->
            <div class="row mt-4">
                <div class="col-md-12">
                    <div class="stat-card">
                        <h5><i class="fas fa-chart-line"></i> Timeline Eventi</h5>
                        <div class="chart-container" style="height: 400px;">
                            <canvas id="timelineChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Event Types -->
            <div class="row mt-4">
                <div class="col-md-12">
                    <div class="stat-card">
                        <h5><i class="fas fa-list"></i> Eventi per Tipo</h5>
                        <div class="chart-container">
                            <canvas id="eventTypesChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Top Contents -->
            <h2 class="section-title"><i class="fas fa-trophy"></i> Top Contenuti</h2>
            <div id="topContents"></div>

            <!-- Recent Events -->
            <h2 class="section-title"><i class="fas fa-clock"></i> Eventi Recenti (ultime 24h)</h2>
            <div id="recentEvents"></div>
        </div>
    </div>

    <button class="refresh-btn" onclick="loadDashboard()" title="Aggiorna dati">
        <i class="fas fa-sync-alt fa-2x"></i>
    </button>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>

    <script>
        let charts = {};
        let currentFilters = {};

        function loadDashboard() {
            $('#loadingIndicator').show();
            $('#dashboardContent').hide();

            // First check if analytics system is ready
            fetch('/api/analytics/health')
                .then(r => r.json())
                .then(health => {
                    if (!health.success) {
                        $('#loadingIndicator').hide();
                        showSetupMessage(health);
                        return;
                    }

                    // System is ready, load data
                    loadAnalyticsData();
                })
                .catch(error => {
                    $('#loadingIndicator').hide();
                    showErrorMessage('Impossibile verificare lo stato del sistema analytics', error);
                });
        }

        function loadAnalyticsData() {
            // Build query params
            let params = new URLSearchParams(currentFilters);

            Promise.all([
                fetch('/api/analytics/stats/overview').then(r => r.json()),
                fetch('/api/analytics/stats?' + params).then(r => r.json()),
                fetch('/api/analytics/metrics').then(r => r.json())
            ])
            .then(([overview, stats, metrics]) => {
                if (!overview.success || !stats.success || !metrics.success) {
                    showErrorMessage('Errore nel caricamento dei dati');
                    return;
                }

                renderOverview(overview.data);
                renderStats(stats.data);
                renderMetrics(metrics.data);

                $('#loadingIndicator').hide();
                $('#dashboardContent').show();
                $('#lastUpdate').text(new Date().toLocaleString('it-IT'));
            })
            .catch(error => {
                $('#loadingIndicator').hide();
                console.error('Error loading dashboard:', error);
                showErrorMessage('Errore nel caricamento dei dati', error);
            });
        }

        function showSetupMessage(health) {
            const message = `
                <div class="container mt-5">
                    <div class="alert alert-warning" role="alert">
                        <h4 class="alert-heading"><i class="fas fa-exclamation-triangle"></i> Sistema Analytics Non Configurato</h4>
                        <p><strong>Problema:</strong> ${health.message || health.error}</p>
                        <hr>
                        <h5>Istruzioni per la configurazione:</h5>
                        <ol>
                            <li>Verifica che il database MySQL sia in esecuzione</li>
                            <li>Controlla le credenziali del database in <code>app/Config/Database.php</code></li>
                            <li>Esegui le migrations per creare le tabelle analytics:
                                <pre class="mt-2"><code>php spark migrate</code></pre>
                            </li>
                        </ol>
                        ${health.missing_tables ? `<p><strong>Tabelle mancanti:</strong> ${health.missing_tables.join(', ')}</p>` : ''}
                        <button class="btn btn-primary mt-3" onclick="loadDashboard()">
                            <i class="fas fa-sync"></i> Riprova
                        </button>
                    </div>
                </div>
            `;
            $('#dashboardContent').html(message).show();
        }

        function showErrorMessage(message, error) {
            const errorHtml = `
                <div class="container mt-5">
                    <div class="alert alert-danger" role="alert">
                        <h4 class="alert-heading"><i class="fas fa-times-circle"></i> Errore</h4>
                        <p>${message}</p>
                        ${error ? `<pre class="mt-2">${error}</pre>` : ''}
                        <button class="btn btn-danger mt-3" onclick="loadDashboard()">
                            <i class="fas fa-sync"></i> Riprova
                        </button>
                    </div>
                </div>
            `;
            $('#dashboardContent').html(errorHtml).show();
        }

        function renderOverview(data) {
            $('#totalScans').text(data.totals.total_scans || 0);
            $('#totalViews').text(data.totals.total_views || 0);
            $('#uniqueVisitors').text(data.totals.unique_visitors || 0);
            $('#totalContents').text(data.top_contents.length || 0);
        }

        function renderStats(data) {
            // Device Chart
            if (charts.device) charts.device.destroy();
            const deviceCtx = document.getElementById('deviceChart').getContext('2d');
            const deviceData = data.device_stats || [];
            charts.device = new Chart(deviceCtx, {
                type: 'pie',
                data: {
                    labels: deviceData.map(d => d.device_type || 'Unknown'),
                    datasets: [{
                        data: deviceData.map(d => d.count),
                        backgroundColor: ['#007bff', '#28a745', '#ffc107', '#dc3545']
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'bottom' }
                    }
                }
            });

            // Language Chart
            if (charts.language) charts.language.destroy();
            const langCtx = document.getElementById('languageChart').getContext('2d');
            const langData = data.language_stats || [];
            charts.language = new Chart(langCtx, {
                type: 'doughnut',
                data: {
                    labels: langData.map(l => l.language || 'N/A'),
                    datasets: [{
                        data: langData.map(l => l.count),
                        backgroundColor: ['#667eea', '#764ba2', '#f093fb', '#4facfe']
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'bottom' }
                    }
                }
            });

            // Timeline Chart
            if (charts.timeline) charts.timeline.destroy();
            const timelineCtx = document.getElementById('timelineChart').getContext('2d');
            const timelineData = data.timeline || [];
            charts.timeline = new Chart(timelineCtx, {
                type: 'line',
                data: {
                    labels: timelineData.map(t => t.date),
                    datasets: [{
                        label: 'Eventi',
                        data: timelineData.map(t => t.count),
                        borderColor: '#007bff',
                        backgroundColor: 'rgba(0, 123, 255, 0.1)',
                        fill: true,
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false }
                    },
                    scales: {
                        y: { beginAtZero: true }
                    }
                }
            });

            // Event Types Chart
            if (charts.eventTypes) charts.eventTypes.destroy();
            const eventCtx = document.getElementById('eventTypesChart').getContext('2d');
            const eventData = data.event_counts || [];
            charts.eventTypes = new Chart(eventCtx, {
                type: 'bar',
                data: {
                    labels: eventData.map(e => e.event_type),
                    datasets: [{
                        label: 'Count',
                        data: eventData.map(e => e.count),
                        backgroundColor: '#007bff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false }
                    },
                    scales: {
                        y: { beginAtZero: true }
                    }
                }
            });
        }

        function renderMetrics(metrics) {
            const topContentsHtml = metrics.slice(0, 10).map(m => `
                <div class="content-row">
                    <div class="row align-items-center">
                        <div class="col-md-4">
                            <strong>${m.content_title || 'N/A'}</strong>
                            <br><small class="text-muted">${m.content_type || 'N/A'}</small>
                        </div>
                        <div class="col-md-2 text-center">
                            <i class="fas fa-qrcode"></i> ${m.total_scans || 0} scans
                        </div>
                        <div class="col-md-2 text-center">
                            <i class="fas fa-eye"></i> ${m.total_views || 0} views
                        </div>
                        <div class="col-md-2 text-center">
                            <i class="fas fa-users"></i> ${m.unique_visitors || 0} visitatori
                        </div>
                        <div class="col-md-2 text-center">
                            ${m.avg_completion_rate}% completamento
                        </div>
                    </div>
                </div>
            `).join('');
            $('#topContents').html(topContentsHtml);
        }

        function applyFilters() {
            const startDate = $('#startDate').val();
            const endDate = $('#endDate').val();

            currentFilters = {};
            if (startDate) currentFilters.start_date = startDate;
            if (endDate) currentFilters.end_date = endDate;

            loadDashboard();
        }

        function clearFilters() {
            $('#startDate').val('');
            $('#endDate').val('');
            currentFilters = {};
            loadDashboard();
        }

        // Initialize
        $(document).ready(function() {
            // Set default date range (last 7 days)
            const endDate = new Date();
            const startDate = new Date();
            startDate.setDate(startDate.getDate() - 7);

            $('#endDate').val(endDate.toISOString().split('T')[0]);
            $('#startDate').val(startDate.toISOString().split('T')[0]);

            loadDashboard();

            // Auto-refresh every 5 minutes
            setInterval(loadDashboard, 300000);
        });
    </script>
</body>
</html>
