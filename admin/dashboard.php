<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header("Location: ../login.php");
    exit;
}

include("../db/connection.php");

// Get counts for dashboard
$volunteers_count = $pdo->query("SELECT COUNT(*) FROM volunteers")->fetchColumn();
$events_count = $pdo->query("SELECT COUNT(*) FROM events")->fetchColumn();
$registrations_count = $pdo->query("SELECT COUNT(*) FROM event_registrations")->fetchColumn();
$certificates_count = $pdo->query("SELECT COUNT(*) FROM certificates")->fetchColumn();

// Get statistics for charts
$monthly_registrations_stmt = $pdo->query("
    SELECT DATE_FORMAT(registered_at, '%Y-%m') as month, COUNT(*) as count 
    FROM volunteers 
    WHERE registered_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(registered_at, '%Y-%m') 
    ORDER BY month
");
$monthly_registrations = $monthly_registrations_stmt->fetchAll();

// Get day-wise registrations for all time
$daywise_registrations_stmt = $pdo->query("
    SELECT DATE_FORMAT(registered_at, '%Y-%m-%d') as date, COUNT(*) as count 
    FROM volunteers 
    GROUP BY DATE_FORMAT(registered_at, '%Y-%m-%d') 
    ORDER BY date
");
$daywise_registrations = $daywise_registrations_stmt->fetchAll();

$event_participation_stmt = $pdo->query("
    SELECT e.title, COUNT(r.id) as participants
    FROM events e 
    LEFT JOIN event_registrations r ON e.event_id = r.event_id
    WHERE e.event_date >= DATE_SUB(NOW(), INTERVAL 3 MONTH)
    GROUP BY e.event_id 
    ORDER BY participants DESC 
    LIMIT 10
");
$event_participation = $event_participation_stmt->fetchAll();

$department_stats_stmt = $pdo->query("
    SELECT department, COUNT(*) as count 
    FROM volunteers 
    GROUP BY department 
    ORDER BY count DESC
");
$department_stats = $department_stats_stmt->fetchAll();

$certificate_stats_stmt = $pdo->query("
    SELECT 
        COUNT(*) as total_certs,
        SUM(CASE WHEN certificate_code LIKE 'CERT-120-%' OR certificate_type = '120_hours' THEN 1 ELSE 0 END) as cert_120,
        SUM(CASE WHEN certificate_code LIKE 'CERT-240-%' OR certificate_type = '240_hours' THEN 1 ELSE 0 END) as cert_240
    FROM certificates
");
$certificate_stats = $certificate_stats_stmt->fetch();

$volunteers_stmt = $pdo->query("SELECT * FROM volunteers ORDER BY registered_at DESC LIMIT 5");
$volunteers = $volunteers_stmt->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .dashboard-stats { display: flex; gap: 20px; margin-bottom: 30px; flex-wrap: wrap; }
        .stat-card { flex: 1; min-width: 200px; padding: 20px; background: #f8f9fa; border-radius: 10px; text-align: center; border: 1px solid #dee2e6; }
        .stat-number { font-size: 2em; font-weight: bold; color: #007bff; }
        .nav-menu { 
            background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
            padding: 18px 25px;
            border-radius: 8px;
            margin-bottom: 25px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.15);
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            align-items: center;
        }
        .nav-menu a { 
            color: white;
            text-decoration: none;
            padding: 10px 16px;
            border-radius: 6px;
            transition: all 0.3s ease;
            font-size: 0.95em;
            font-weight: 500;
            white-space: nowrap;
            border: 2px solid transparent;
        }
        .nav-menu a:hover { 
            background: #ffc107;
            color: #2c3e50;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(255, 193, 7, 0.4);
            border-color: #ffb300;
        }
        @media (max-width: 768px) {
            .nav-menu { padding: 12px 15px; gap: 6px; }
            .nav-menu a { padding: 8px 12px; font-size: 0.85em; }
        }
        .chart-container { 
            background: white; 
            padding: 20px; 
            border-radius: 10px; 
            box-shadow: 0 2px 10px rgba(0,0,0,0.1); 
            display: flex;
            flex-direction: column;
        }
        .chart-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; flex-wrap: wrap; gap: 10px; }
        .chart-title { font-size: 1.2em; font-weight: bold; color: #333; margin: 0; }
        .chart-type-selector { display: flex; gap: 5px; flex-wrap: wrap; }
        .chart-type-btn { 
            padding: 6px 12px; 
            font-size: 0.85em; 
            border: 1px solid #ddd; 
            background: #f8f9fa; 
            color: #333;
            border-radius: 5px; 
            cursor: pointer; 
            transition: all 0.3s;
        }
        .chart-type-btn:hover { background: #e9ecef; }
        .chart-type-btn.active { background: #007bff; color: white; border-color: #007bff; }
        .chart-wrapper { position: relative; height: 350px; width: 100%; display: flex; align-items: center; justify-content: center; }
        .dashboard-row { display: flex; gap: 20px; margin-bottom: 30px; }
        .dashboard-row > div { flex: 1; }
        @media (max-width: 768px) {
            .chart-wrapper { height: 300px; }
            .dashboard-row { flex-direction: column; gap: 15px; }
        }
    </style>
</head>
<body class="container my-5">
    <div class="nav-menu">
        <a href="dashboard.php">🏠 Home</a>
        <a href="add_event.php">➕ Add Event</a>
        <a href="view_events.php">📅 Manage Events</a>
        <a href="attendance.php">📝 Attendance</a>
        <a href="view_registrations.php">👥 Registrations</a>
        <a href="view_volunteers.php">📋 Volunteers</a>
        <a href="issue_certificates.php">🎓 Certificates</a>
        <a href="gallery_upload.php">� Upload Gallery</a>
        <a href="gallery_view.php">🖼️ View Gallery</a>
        <a href="manage_notifications.php">📢 Notifications</a>        <a href="view_feedback.php">💬 Feedback</a>        <a href="../logout.php">🚪 Logout</a>
    </div>

    <h2>Admin Dashboard 👨‍💼</h2>
    <p>Welcome, <?= htmlspecialchars($_SESSION['admin']); ?>!</p>

    <div class="dashboard-stats">
        <div class="stat-card">
            <div class="stat-number"><?= $volunteers_count ?></div>
            <div>Total Volunteers</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?= $events_count ?></div>
            <div>Total Events</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?= $registrations_count ?></div>
            <div>Registrations</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?= $certificates_count ?></div>
            <div>Certificates Issued</div>
        </div>
    </div>

    <div class="dashboard-row">
        <div>
            <div class="chart-container">
                <div class="chart-header">
                    <h4 class="chart-title">📈 Volunteer Registrations (Last 6 Months)</h4>
                    <div class="chart-type-selector">
                        <button class="chart-type-btn active" data-chart="registrations" data-type="line">Line</button>
                        <button class="chart-type-btn" data-chart="registrations" data-type="barVertical">Vertical Bar</button>
                        <button class="chart-type-btn" data-chart="registrations" data-type="barHorizontal">Horizontal Bar</button>
                    </div>
                </div>
                <div class="chart-wrapper">
                    <canvas id="registrationsChart"></canvas>
                </div>
            </div>
        </div>
        <div>
            <div class="chart-container">
                <div class="chart-header">
                    <h4 class="chart-title">🎓 Certificates Distribution</h4>
                    <div class="chart-type-selector">
                        <button class="chart-type-btn active" data-chart="certificates" data-type="doughnut">Doughnut</button>
                        <button class="chart-type-btn" data-chart="certificates" data-type="pie">Pie</button>
                        <button class="chart-type-btn" data-chart="certificates" data-type="bar">Bar</button>
                    </div>
                </div>
                <div class="chart-wrapper">
                    <canvas id="certificatesChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="dashboard-row">
        <div>
            <div class="chart-container">
                <div class="chart-header">
                    <h4 class="chart-title">👥 Volunteers by Department</h4>
                    <div class="chart-type-selector">
                        <button class="chart-type-btn active" data-chart="department" data-type="bar">Horizontal Bar</button>
                        <button class="chart-type-btn" data-chart="department" data-type="barVertical">Vertical Bar</button>
                        <button class="chart-type-btn" data-chart="department" data-type="pie">Pie</button>
                    </div>
                </div>
                <div class="chart-wrapper">
                    <canvas id="departmentChart"></canvas>
                </div>
            </div>
        </div>
        <div>
            <div class="chart-container">
                <div class="chart-header">
                    <h4 class="chart-title">📊 Top Events by Participation</h4>
                    <div class="chart-type-selector">
                        <button class="chart-type-btn active" data-chart="events" data-type="bar">Bar</button>
                        <button class="chart-type-btn" data-chart="events" data-type="line">Line</button>
                        <button class="chart-type-btn" data-chart="events" data-type="radar">Radar</button>
                    </div>
                </div>
                <div class="chart-wrapper">
                    <canvas id="eventsChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="dashboard-row">
        <div>
            <div class="chart-container">
                <div class="chart-header">
                    <h4 class="chart-title">📅 Overall Volunteer Registration (Day-wise)</h4>
                    <div class="chart-type-selector">
                        <button class="chart-type-btn active" data-chart="daywise" data-type="line">Line</button>
                        <button class="chart-type-btn" data-chart="daywise" data-type="barVertical">Vertical Bar</button>
                        <button class="chart-type-btn" data-chart="daywise" data-type="barHorizontal">Horizontal Bar</button>
                        <button class="chart-type-btn" data-chart="daywise" data-type="area">Area</button>
                    </div>
                </div>
                <div class="chart-wrapper" style="height: 400px;">
                    <canvas id="daywiseChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <h3 class="mt-5">Recent Volunteers</h3>
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Volunteer ID</th>
                <th>Name</th>
                <th>Email</th>
                <th>Department</th>
                <th>Year</th>
                <th>Registered At</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($volunteers as $v): ?>
            <tr>
                <td><?= htmlspecialchars($v['volunteer_id']); ?></td>
                <td><?= htmlspecialchars($v['name']); ?></td>
                <td><?= htmlspecialchars($v['email']); ?></td>
                <td><?= htmlspecialchars($v['department']); ?></td>
                <td><?= htmlspecialchars($v['year']); ?></td>
                <td><?= $v['registered_at']; ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <script>
        // Chart data storage
        const chartsData = {
            registrations: {
                labels: [<?php foreach ($monthly_registrations as $reg) echo "'" . date('M Y', strtotime($reg['month'] . '-01')) . "',"; ?>],
                data: [<?php foreach ($monthly_registrations as $reg) echo $reg['count'] . ','; ?>],
                backgroundColor: 'rgba(0, 123, 255, 0.1)',
                borderColor: '#007bff'
            },
            certificates: {
                labels: ['120-Hour Certificates', '240-Hour Certificates', 'Other Certificates'],
                data: [
                    <?= $certificate_stats['cert_120'] ?? 0 ?>,
                    <?= $certificate_stats['cert_240'] ?? 0 ?>,
                    <?= ($certificate_stats['total_certs'] ?? 0) - ($certificate_stats['cert_120'] ?? 0) - ($certificate_stats['cert_240'] ?? 0) ?>
                ],
                colors: ['#ffc107', '#28a745', '#17a2b8']
            },
            department: {
                labels: [<?php foreach ($department_stats as $dept) echo "'" . addslashes($dept['department']) . "',"; ?>],
                data: [<?php foreach ($department_stats as $dept) echo $dept['count'] . ','; ?>],
                backgroundColor: '#6f42c1'
            },
            events: {
                labels: [<?php foreach ($event_participation as $event) echo "'" . addslashes(substr($event['title'], 0, 20)) . (strlen($event['title']) > 20 ? '...' : '') . "',"; ?>],
                data: [<?php foreach ($event_participation as $event) echo $event['participants'] . ','; ?>],
                backgroundColor: '#20c997'
            },
            daywise: {
                labels: [<?php foreach ($daywise_registrations as $day) echo "'" . date('M d, Y', strtotime($day['date'])) . "',"; ?>],
                data: [<?php foreach ($daywise_registrations as $day) echo $day['count'] . ','; ?>],
                backgroundColor: 'rgba(244, 67, 54, 0.1)',
                borderColor: '#f44336'
            }
        };

        // Store chart instances
        let charts = {
            registrations: null,
            certificates: null,
            department: null,
            events: null,
            daywise: null
        };

        // Load saved preferences from localStorage
        function loadChartPreferences() {
            const prefs = {};
            prefs.registrations = localStorage.getItem('chart_type_registrations') || 'line';
            prefs.certificates = localStorage.getItem('chart_type_certificates') || 'doughnut';
            prefs.department = localStorage.getItem('chart_type_department') || 'bar';
            prefs.events = localStorage.getItem('chart_type_events') || 'bar';
            prefs.daywise = localStorage.getItem('chart_type_daywise') || 'line';
            return prefs;
        }

        // Get appropriate config based on chart type
        function getChartConfig(chartName, type) {
            const data = chartsData[chartName];
            
            if (chartName === 'registrations') {
                const configs = {
                    line: {
                        type: 'line',
                        data: {
                            labels: data.labels,
                            datasets: [{
                                label: 'New Volunteers',
                                data: data.data,
                                borderColor: data.borderColor,
                                backgroundColor: data.backgroundColor,
                                tension: 0.4,
                                fill: true
                            }]
                        }
                    },
                    barVertical: {
                        type: 'bar',
                        data: {
                            labels: data.labels,
                            datasets: [{
                                label: 'New Volunteers',
                                data: data.data,
                                backgroundColor: data.borderColor
                            }]
                        }
                    },
                    barHorizontal: {
                        type: 'bar',
                        data: {
                            labels: data.labels,
                            datasets: [{
                                label: 'New Volunteers',
                                data: data.data,
                                backgroundColor: data.borderColor
                            }]
                        },
                        options: { indexAxis: 'y' }
                    }
                };
                return configs[type];
            }
            
            if (chartName === 'certificates') {
                const configs = {
                    doughnut: {
                        type: 'doughnut',
                        data: {
                            labels: data.labels,
                            datasets: [{
                                data: data.data,
                                backgroundColor: data.colors
                            }]
                        }
                    },
                    pie: {
                        type: 'pie',
                        data: {
                            labels: data.labels,
                            datasets: [{
                                data: data.data,
                                backgroundColor: data.colors
                            }]
                        }
                    },
                    bar: {
                        type: 'bar',
                        data: {
                            labels: data.labels,
                            datasets: [{
                                label: 'Certificates',
                                data: data.data,
                                backgroundColor: data.colors
                            }]
                        }
                    }
                };
                return configs[type];
            }
            
            if (chartName === 'department') {
                const configs = {
                    bar: {
                        type: 'bar',
                        data: {
                            labels: data.labels,
                            datasets: [{
                                label: 'Volunteers',
                                data: data.data,
                                backgroundColor: data.backgroundColor
                            }]
                        },
                        options: { indexAxis: 'y' }
                    },
                    barVertical: {
                        type: 'bar',
                        data: {
                            labels: data.labels,
                            datasets: [{
                                label: 'Volunteers',
                                data: data.data,
                                backgroundColor: data.backgroundColor
                            }]
                        }
                    },
                    pie: {
                        type: 'pie',
                        data: {
                            labels: data.labels,
                            datasets: [{
                                data: data.data,
                                backgroundColor: ['#6f42c1', '#17a2b8', '#ffc107', '#28a745', '#dc3545', '#fd7e14', '#6c757d', '#343a40', '#007bff', '#e83e8c']
                            }]
                        }
                    }
                };
                return (type === 'barVertical') ? configs.barVertical : configs[type];
            }
            
            if (chartName === 'events') {
                const configs = {
                    bar: {
                        type: 'bar',
                        data: {
                            labels: data.labels,
                            datasets: [{
                                label: 'Participants',
                                data: data.data,
                                backgroundColor: data.backgroundColor
                            }]
                        }
                    },
                    line: {
                        type: 'line',
                        data: {
                            labels: data.labels,
                            datasets: [{
                                label: 'Participants',
                                data: data.data,
                                borderColor: data.backgroundColor,
                                backgroundColor: 'rgba(32, 201, 151, 0.1)',
                                tension: 0.4,
                                fill: true
                            }]
                        }
                    },
                    radar: {
                        type: 'radar',
                        data: {
                            labels: data.labels,
                            datasets: [{
                                label: 'Participants',
                                data: data.data,
                                borderColor: data.backgroundColor,
                                backgroundColor: 'rgba(32, 201, 151, 0.2)'
                            }]
                        }
                    }
                };
                return configs[type];
            }
            
            if (chartName === 'daywise') {
                const configs = {
                    line: {
                        type: 'line',
                        data: {
                            labels: data.labels,
                            datasets: [{
                                label: 'Volunteers Registered',
                                data: data.data,
                                borderColor: data.borderColor,
                                backgroundColor: data.backgroundColor,
                                tension: 0.4,
                                fill: true
                            }]
                        },
                        options: {
                            scales: {
                                y: {
                                    title: {
                                        display: true,
                                        text: 'Number of Volunteers Registered'
                                    }
                                },
                                x: {
                                    title: {
                                        display: true,
                                        text: 'Date of Registration'
                                    }
                                }
                            }
                        }
                    },
                    barVertical: {
                        type: 'bar',
                        data: {
                            labels: data.labels,
                            datasets: [{
                                label: 'Volunteers Registered',
                                data: data.data,
                                backgroundColor: data.borderColor
                            }]
                        },
                        options: {
                            scales: {
                                y: {
                                    title: {
                                        display: true,
                                        text: 'Number of Volunteers Registered'
                                    }
                                },
                                x: {
                                    title: {
                                        display: true,
                                        text: 'Date of Registration'
                                    }
                                }
                            }
                        }
                    },
                    barHorizontal: {
                        type: 'bar',
                        data: {
                            labels: data.labels,
                            datasets: [{
                                label: 'Volunteers Registered',
                                data: data.data,
                                backgroundColor: data.borderColor
                            }]
                        },
                        options: { 
                            indexAxis: 'y',
                            scales: {
                                y: {
                                    title: {
                                        display: true,
                                        text: 'Date of Registration'
                                    }
                                },
                                x: {
                                    title: {
                                        display: true,
                                        text: 'Number of Volunteers Registered'
                                    }
                                }
                            }
                        }
                    },
                    area: {
                        type: 'line',
                        data: {
                            labels: data.labels,
                            datasets: [{
                                label: 'Volunteers Registered',
                                data: data.data,
                                borderColor: data.borderColor,
                                backgroundColor: data.backgroundColor,
                                tension: 0,
                                fill: true,
                                pointRadius: 0
                            }]
                        },
                        options: {
                            scales: {
                                y: {
                                    title: {
                                        display: true,
                                        text: 'Number of Volunteers Registered'
                                    }
                                },
                                x: {
                                    title: {
                                        display: true,
                                        text: 'Date of Registration'
                                    }
                                }
                            }
                        }
                    }
                };
                return configs[type];
            }
        }

        // Initialize chart
        function createChart(chartName, type) {
            const canvasId = chartName + 'Chart';
            const ctx = document.getElementById(canvasId);
            if (!ctx) return;

            // Destroy old chart
            if (charts[chartName]) {
                charts[chartName].destroy();
            }

            const config = getChartConfig(chartName, type);
            const options = {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1,
                            callback: function(value) {
                                if (Number.isInteger(value)) {
                                    return value;
                                }
                                return '';
                            }
                        }
                    },
                    x: {
                        ticks: {
                            callback: function(value) {
                                return value;
                            }
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: chartName !== 'registrations' && chartName !== 'events' && chartName !== 'daywise',
                        position: 'bottom'
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let label = context.dataset.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                if (context.parsed.y !== null) {
                                    label += Math.round(context.parsed.y);
                                } else if (context.parsed !== null) {
                                    label += Math.round(context.parsed);
                                }
                                return label;
                            }
                        }
                    }
                }
            };

            if (config.options) {
                // Deep merge for scales to preserve both global and config-specific scales
                if (config.options.scales) {
                    options.scales = options.scales || {};
                    Object.keys(config.options.scales).forEach(axisKey => {
                        options.scales[axisKey] = Object.assign(options.scales[axisKey] || {}, config.options.scales[axisKey]);
                    });
                }
                // Merge other options properties
                Object.keys(config.options).forEach(key => {
                    if (key !== 'scales') {
                        options[key] = config.options[key];
                    }
                });
            }

            charts[chartName] = new Chart(ctx, {
                type: config.type,
                data: config.data,
                options: options
            });

            // Save preference
            localStorage.setItem('chart_type_' + chartName, type);
        }

        // Event listeners for chart type buttons
        document.querySelectorAll('.chart-type-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const chartName = this.dataset.chart;
                const chartType = this.dataset.type;

                // Update active button
                document.querySelectorAll(`[data-chart="${chartName}"]`).forEach(b => {
                    b.classList.remove('active');
                });
                this.classList.add('active');

                // Create new chart
                createChart(chartName, chartType);
            });
        });

        // Initialize all charts on page load
        const preferences = loadChartPreferences();
        createChart('registrations', preferences.registrations);
        createChart('certificates', preferences.certificates);
        createChart('department', preferences.department);
        createChart('events', preferences.events);
        createChart('daywise', preferences.daywise);
    </script>
</body>
</html>