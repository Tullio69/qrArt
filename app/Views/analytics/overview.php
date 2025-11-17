<?= $this->extend('templates/main') ?>

<?= $this->section('content') ?>
<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800"><?= $pageTitle ?? 'Analytics Dashboard' ?></h1>
        <div class="d-flex
            <div class="input-group mr-2" style="width: 250px;">
                <select class="form-control" id="dateRange">
                    <option value="today" <?= ($selectedRange ?? '7days') === 'today' ? 'selected' : '' ?>>Today</option>
                    <option value="yesterday" <?= ($selectedRange ?? '7days') === 'yesterday' ? 'selected' : '' ?>>Yesterday</option>
                    <option value="7days" <?= ($selectedRange ?? '7days') === '7days' ? 'selected' : '' ?>>Last 7 Days</option>
                    <option value="30days" <?= ($selectedRange ?? '7days') === '30days' ? 'selected' : '' ?>>Last 30 Days</option>
                    <option value="90days" <?= ($selectedRange ?? '7days') === '90days' ? 'selected' : '' ?>>Last 90 Days</option>
                    <option value="custom">Custom Range</option>
                </select>
            </div>
            <div id="customDateRange" class="input-group" style="display: none; width: 300px;">
                <input type="date" class="form-control" id="startDate" value="<?= $startDate ?? '' ?>">
                <input type="date" class="form-control" id="endDate" value="<?= $endDate ?? '' ?>">
                <div class="input-group-append">
                    <button class="btn btn-primary" id="applyDateRange" type="button">Apply</button>
                </div>
            </div>
        </div>
    </div>

    <?php if (isset($error)): ?>
        <div class="alert alert-danger">
            <?= esc($error) ?>
            <?php if (strpos($error, 'Google Analytics') !== false): ?>
                <div class="mt-2">
                    <p>Make sure you have set up the following environment variables in your <code>.env</code> file:</p>
                    <pre>GA_CREDENTIALS_PATH=/path/to/your/credentials.json
GA_PROPERTY_ID=YOUR_GA4_PROPERTY_ID</pre>
                </div>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <!-- Summary Cards -->
        <div class="row">
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-primary shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Users</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?= number_format($totalUsers ?? 0) ?></div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-users fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-success shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Total Sessions</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?= number_format($totalSessions ?? 0) ?></div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-chart-line fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-info shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Page Views</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?= number_format($totalPageViews ?? 0) ?></div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-eye fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-warning shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">QR Scans</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?= number_format($totalScans ?? 0) ?></div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-qrcode fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Row -->
        <div class="row">
            <!-- Sessions Chart -->
            <div class="col-xl-8 col-lg-7">
                <div class="card shadow mb-4">
                    <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                        <h6 class="m-0 font-weight-bold text-primary">Sessions Overview</h6>
                    </div>
                    <div class="card-body">
                        <div class="chart-area">
                            <canvas id="sessionsChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Top QR Codes -->
            <div class="col-xl-4 col-lg-5">
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Top QR Codes</h6>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($topQRCodes)): ?>
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>QR Code</th>
                                            <th>Scans</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($topQRCodes as $qr): ?>
                                            <tr>
                                                <td><?= esc($qr['name'] ?: 'QR #' . $qr['qr_code_id']) ?></td>
                                                <td><?= number_format($qr['total_scans']) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <p>No scan data available.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Second Row -->
        <div class="row">
            <!-- Bounce Rate & Avg. Session Duration -->
            <div class="col-lg-6">
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Engagement Metrics</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 text-center">
                                <div class="mb-3">
                                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Avg. Session Duration</div>
                                    <div class="h3 font-weight-bold text-gray-800">
                                        <?= isset($avgSessionDuration) ? gmdate('H:i:s', (int)$avgSessionDuration) : '0:00' ?>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 text-center">
                                <div class="mb-3">
                                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Bounce Rate</div>
                                    <div class="h3 font-weight-bold text-gray-800">
                                        <?= isset($avgBounceRate) ? number_format($avgBounceRate, 1) . '%' : '0%' ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Scans by Date -->
            <div class="col-lg-6">
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">QR Code Scans</h6>
                    </div>
                    <div class="card-body">
                        <div class="chart-bar">
                            <canvas id="scansChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<!-- Page level plugins -->
<script src="/vendor/chart.js/Chart.min.js"></script>

<script>
// Date range picker functionality
$(document).ready(function() {
    // Toggle custom date range
    $('#dateRange').on('change', function() {
        if ($(this).val() === 'custom') {
            $('#customDateRange').show();
        } else {
            $('#customDateRange').hide();
            loadData($(this).val());
        }
    });

    // Apply custom date range
    $('#applyDateRange').on('click', function() {
        const startDate = $('#startDate').val();
        const endDate = $('#endDate').val();
        
        if (startDate && endDate) {
            loadData('custom', startDate, endDate);
        } else {
            alert('Please select both start and end dates');
        }
    });

    // Initialize charts if data is available
    <?php if (!empty($dailyStats)): ?>
    initCharts(
        <?= json_encode($dailyStats) ?>,
        <?= json_encode($scansByDate ?? []) ?>
    );
    <?php endif; ?>
});

// Load data for the selected date range
function loadData(range, startDate = '', endDate = '') {
    let url = `/analytics/overview?range=${range}`;
    
    if (range === 'custom' && startDate && endDate) {
        url += `&start_date=${startDate}&end_date=${endDate}`;
    }
    
    window.location.href = url;
}

// Initialize charts
function initCharts(dailyStats, scansData) {
    // Sessions Chart
    const ctx1 = document.getElementById('sessionsChart').getContext('2d');
    const dates = dailyStats.map(stat => stat.date);
    
    new Chart(ctx1, {
        type: 'line',
        data: {
            labels: dates,
            datasets: [{
                label: 'Sessions',
                data: dailyStats.map(stat => stat.sessions),
                borderColor: '#4e73df',
                backgroundColor: 'rgba(78, 115, 223, 0.05)',
                borderWidth: 2,
                pointRadius: 3,
                pointBackgroundColor: '#4e73df',
                pointBorderColor: '#4e73df',
                pointHoverRadius: 5,
                pointHoverBackgroundColor: '#4e73df',
                pointHitRadius: 10,
                pointBorderWidth: 2,
                fill: true
            }, {
                label: 'Users',
                data: dailyStats.map(stat => stat.users),
                borderColor: '#1cc88a',
                backgroundColor: 'rgba(28, 200, 138, 0.05)',
                borderWidth: 2,
                pointRadius: 3,
                pointBackgroundColor: '#1cc88a',
                pointBorderColor: '#1cc88a',
                pointHoverRadius: 5,
                pointHoverBackgroundColor: '#1cc88a',
                pointHitRadius: 10,
                pointBorderWidth: 2,
                fill: true
            }]
        },
        options: {
            maintainAspectRatio: false,
            layout: {
                padding: {
                    left: 10,
                    right: 25,
                    top: 25,
                    bottom: 0
                }
            },
            scales: {
                x: {
                    grid: {
                        display: false,
                        drawBorder: false
                    },
                    ticks: {
                        maxTicksLimit: 7
                    }
                },
                y: {
                    beginAtZero: true,
                    ticks: {
                        maxTicksLimit: 5,
                        padding: 10
                    },
                    grid: {
                        color: 'rgb(234, 236, 244)',
                        borderDash: [2],
                        borderColor: 'transparent',
                        drawBorder: true,
                        drawTicks: false
                    }
                }
            },
            plugins: {
                legend: {
                    display: true,
                    position: 'top',
                },
                tooltip: {
                    backgroundColor: 'rgb(255,255,255)',
                    bodyColor: '#858796',
                    titleMarginBottom: 10,
                    titleColor: '#6e707e',
                    titleFontSize: 14,
                    borderColor: '#dddfeb',
                    borderWidth: 1,
                    xPadding: 15,
                    yPadding: 15,
                    displayColors: false,
                    intersect: false,
                    mode: 'index',
                    caretPadding: 10,
                }
            }
        }
    });

    // Scans Chart
    if (scansData && scansData.length > 0) {
        const ctx2 = document.getElementById('scansChart').getContext('2d');
        const scanDates = [...new Set(scansData.map(scan => scan.scan_date))];
        const scanCounts = scanDates.map(date => {
            return scansData
                .filter(scan => scan.scan_date === date)
                .reduce((sum, scan) => sum + parseInt(scan.scan_count), 0);
        });

        new Chart(ctx2, {
            type: 'bar',
            data: {
                labels: scanDates,
                datasets: [{
                    label: 'Scans',
                    data: scanCounts,
                    backgroundColor: '#4e73df',
                    hoverBackgroundColor: '#2e59d9',
                    borderColor: '#4e73df',
                }]
            },
            options: {
                maintainAspectRatio: false,
                layout: {
                    padding: {
                        left: 10,
                        right: 25,
                        top: 25,
                        bottom: 0
                    }
                },
                scales: {
                    x: {
                        grid: {
                            display: false,
                            drawBorder: false
                        },
                        ticks: {
                            maxTicksLimit: 6
                        }
                    },
                    y: {
                        beginAtZero: true,
                        ticks: {
                            maxTicksLimit: 5,
                            padding: 10
                        },
                        grid: {
                            color: 'rgb(234, 236, 244)',
                            borderDash: [2],
                            borderColor: 'transparent',
                            drawBorder: true,
                            drawTicks: false
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: 'rgb(255,255,255)',
                        bodyColor: '#858796',
                        titleMarginBottom: 10,
                        titleColor: '#6e707e',
                        titleFontSize: 14,
                        borderColor: '#dddfeb',
                        borderWidth: 1,
                        xPadding: 15,
                        yPadding: 15,
                        displayColors: false,
                        intersect: false,
                        mode: 'index',
                        caretPadding: 10,
                    }
                }
            }
        });
    }
}
</script>
<?= $this->endSection() ?>
