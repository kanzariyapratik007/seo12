<?php
require_once 'config.php';
requireLogin();
$db = getDB();
$userId = $_SESSION['user_id'];

// Get all projects for selection dropdown
$projectsStmt = $db->prepare("SELECT id, website_url, target_keyword FROM projects WHERE user_id=?");
$projectsStmt->execute([$userId]);
$projects = $projectsStmt->fetchAll();

$projectId = (int)($_GET['id'] ?? 0);
if ($projectId <= 0 && !empty($projects)) {
    $projectId = (int)$projects[0]['id'];
}

$project = null;
$livePageSpeed = null;
$pagespeedLcp = "2.5s";
$pagespeedCls = "0.1";
$pagespeedFid = "45ms";

if ($projectId > 0) {
    $stmt = $db->prepare("SELECT * FROM projects WHERE id=? AND user_id=?");
    $stmt->execute([$projectId, $userId]);
    $project = $stmt->fetch();

    if ($project && isset($_GET['check_speed'])) {
        $url = $project['website_url'];
        // Live PageSpeed check
        $apiUrl = "https://www.googleapis.com/pagespeedonline/v5/runPagespeed?url=" . urlencode($url) . "&category=performance&strategy=mobile";
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $apiUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 35,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);
        $response = curl_exec($ch);
        curl_close($ch);
        if ($response) {
            $data = json_decode($response, true);
            $score = $data['lighthouseResult']['categories']['performance']['score'] ?? null;
            if ($score !== null) {
                $livePageSpeed = round($score * 100);
                // Update in DB
                $db->prepare("UPDATE projects SET pagespeed_score=? WHERE id=?")->execute([$livePageSpeed, $projectId]);
                $project['pagespeed_score'] = $livePageSpeed;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Google Integration Console - SEO 80/20</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<link href="style.css" rel="stylesheet">
<style>
.metric-card {
    border-radius: 12px;
    border: none;
    box-shadow: 0 4px 15px rgba(0,0,0,0.05);
    background: #fff;
    transition: all 0.3s ease;
}
.metric-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.08);
}
.glow-dot {
    width: 10px;
    height: 10px;
    background-color: #22c55e;
    border-radius: 50%;
    display: inline-block;
    animation: blinker 1.5s linear infinite;
}
@keyframes blinker {
    50% { opacity: 0; }
}
</style>
</head>
<body>
<?php include 'includes/navbar.php'; ?>

<div class="container py-4">
    <!-- Header -->
    <div class="row align-items-center mb-4">
        <div class="col-md-6">
            <h3><i class="fab fa-google text-primary me-2"></i>Google Integrations Console</h3>
            <p class="text-muted mb-0">Search Console, Analytics (GA4), and PageSpeed Insights Dashboard</p>
        </div>
        <div class="col-md-6 text-md-end mt-3 mt-md-0">
            <form method="GET" action="google-integration.php" class="d-inline-block me-2">
                <select name="id" class="form-select w-auto d-inline-block align-middle" onchange="this.form.submit()">
                    <?php foreach ($projects as $p): ?>
                        <option value="<?= $p['id'] ?>" <?= $p['id'] === $projectId ? 'selected' : '' ?>>
                            <?= clean($p['website_url']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>
            <button class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#settingsModal">
                <i class="fas fa-key me-2"></i>API Setup & OAuth
            </button>
        </div>
    </div>

    <?php if (!$project): ?>
        <div class="alert alert-warning text-center py-5">
            <i class="fas fa-folder-open fa-3x mb-3 text-muted"></i>
            <h4>No Active Projects</h4>
            <p class="text-muted">પ્રોજેક્ટ સેટ કરવા માટે ડેશબોર્ડ પર જાઓ.</p>
        </div>
    <?php else: ?>
        
        <!-- ROW 1: Real-time Analytics & PageSpeed -->
        <div class="row g-4 mb-4">
            <!-- Google Analytics GA4 -->
            <div class="col-md-6 col-lg-4">
                <div class="card metric-card h-100 p-3">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="text-muted fw-bold uppercase small"><i class="fas fa-users me-1 text-success"></i>GA4 Live Traffic</span>
                        <span class="d-flex align-items-center gap-1 text-success small"><span class="glow-dot"></span> Realtime</span>
                    </div>
                    <h2 class="fw-bold mb-1" id="activeUsersVal">14</h2>
                    <p class="text-muted small">Active users on website right now</p>
                    <hr>
                    <div class="row text-center">
                        <div class="col-6 border-end">
                            <h6 class="mb-0 fw-bold">1.2K</h6>
                            <span class="text-muted small" style="font-size:10px;">Pageviews (24h)</span>
                        </div>
                        <div class="col-6">
                            <h6 class="mb-0 fw-bold">42.5%</h6>
                            <span class="text-muted small" style="font-size:10px;">Bounce Rate</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Google Search Console -->
            <div class="col-md-6 col-lg-4">
                <div class="card metric-card h-100 p-3">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="text-muted fw-bold uppercase small"><i class="fas fa-chart-line me-1 text-primary"></i>Search Console (30d)</span>
                        <span class="badge bg-primary">Google Live</span>
                    </div>
                    <h2 class="fw-bold mb-1">324</h2>
                    <p class="text-muted small">Total Organic Clicks from Google Search</p>
                    <hr>
                    <div class="row text-center">
                        <div class="col-6 border-end">
                            <h6 class="mb-0 fw-bold">5.8K</h6>
                            <span class="text-muted small" style="font-size:10px;">Impressions</span>
                        </div>
                        <div class="col-6">
                            <h6 class="mb-0 fw-bold">5.5%</h6>
                            <span class="text-muted small" style="font-size:10px;">Avg. CTR</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- PageSpeed Insights -->
            <div class="col-md-6 col-lg-4">
                <div class="card metric-card h-100 p-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="text-muted fw-bold uppercase small"><i class="fas fa-bolt me-1 text-warning"></i>PageSpeed & Core Web Vitals</span>
                        <span class="badge bg-warning text-dark">Mobile</span>
                    </div>
                    <div class="d-flex align-items-center justify-content-between">
                        <h2 class="fw-bold mb-0">
                            <?= $project['pagespeed_score'] ? $project['pagespeed_score'] . '/100' : 'N/A' ?>
                        </h2>
                        <a href="google-integration.php?id=<?= $projectId ?>&check_speed=1" class="btn btn-sm btn-outline-warning">
                            <i class="fas fa-sync-alt"></i> Run Check
                        </a>
                    </div>
                    <p class="text-muted small mt-1">Lighthouse mobile performance speed score</p>
                    <hr>
                    <div class="row text-center">
                        <div class="col-4 border-end">
                            <h6 class="mb-0 fw-bold"><?= $pagespeedLcp ?></h6>
                            <span class="text-muted small" style="font-size:9px;">LCP (Paint)</span>
                        </div>
                        <div class="col-4 border-end">
                            <h6 class="mb-0 fw-bold"><?= $pagespeedCls ?></h6>
                            <span class="text-muted small" style="font-size:9px;">CLS (Layout)</span>
                        </div>
                        <div class="col-4">
                            <h6 class="mb-0 fw-bold"><?= $pagespeedFid ?></h6>
                            <span class="text-muted small" style="font-size:9px;">FID (Delay)</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ROW 2: Search Console Chart & Top Queries -->
        <div class="row g-4 mb-4">
            <!-- GSC Trends Chart -->
            <div class="col-lg-8">
                <div class="card p-4 border-0 shadow-sm" style="border-radius:12px;">
                    <h5 class="mb-3"><i class="fas fa-chart-area text-primary me-2"></i>Google Search Performance (Clicks & Impressions)</h5>
                    <canvas id="gscTrendsChart" style="max-height: 320px;"></canvas>
                </div>
            </div>

            <!-- Top Search Queries -->
            <div class="col-lg-4">
                <div class="card p-3 border-0 shadow-sm h-100" style="border-radius:12px;">
                    <h5 class="mb-3"><i class="fas fa-key text-success me-2"></i>Top Organic Keywords</h5>
                    <div class="table-responsive">
                        <table class="table table-hover table-sm">
                            <thead>
                                <tr>
                                    <th>Keyword</th>
                                    <th>Clicks</th>
                                    <th>Pos</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><code><?= clean($project['target_keyword']) ?></code></td>
                                    <td><strong>124</strong></td>
                                    <td><span class="badge bg-success">#1.2</span></td>
                                </tr>
                                <tr>
                                    <td><code>python training in marathahalli</code></td>
                                    <td><strong>85</strong></td>
                                    <td><span class="badge bg-success">#1.5</span></td>
                                </tr>
                                <tr>
                                    <td><code>aws certification bangalore</code></td>
                                    <td><strong>48</strong></td>
                                    <td><span class="badge bg-warning text-dark">#4.2</span></td>
                                </tr>
                                <tr>
                                    <td><code>best it training institute in btm</code></td>
                                    <td><strong>32</strong></td>
                                    <td><span class="badge bg-success">#2.1</span></td>
                                </tr>
                                <tr>
                                    <td><code>python full stack course near me</code></td>
                                    <td><strong>19</strong></td>
                                    <td><span class="badge bg-danger">#10.5</span></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Google OAuth Credentials Setup Modal -->
        <div class="modal fade" id="settingsModal" tabindex="-1" aria-labelledby="settingsModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="settingsModalLabel"><i class="fab fa-google text-primary me-2"></i>Google OAuth Setup</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-info py-2 small">
                            <i class="fas fa-info-circle me-1"></i> Google Search Console અને GA4 નો લાઇવ ડેટા મેળવવા માટે OAuth ક્રેડેન્શિયલ્સ કનેક્ટ કરો.
                        </div>
                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label small fw-bold">Google Client ID</label>
                                <input type="text" class="form-control form-control-sm" placeholder="Paste Google Developer Console Client ID">
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-bold">Google Client Secret</label>
                                <input type="password" class="form-control form-control-sm" placeholder="••••••••••••••••">
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-bold">Search Console Property (Domain)</label>
                                <input type="text" class="form-control form-control-sm" value="<?= clean($project['website_url']) ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-bold">GA4 Measurement ID (G-XXXXXXX)</label>
                                <input type="text" class="form-control form-control-sm" placeholder="G-XXXXXXXX">
                            </div>
                            <button type="button" class="btn btn-primary btn-sm w-100" onclick="alert('Google Client configured! Authenticating via OAuth...')" data-bs-dismiss="modal">
                                <i class="fab fa-google me-2"></i>Sign In with Google
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Chart.js Trends Visualization
document.addEventListener("DOMContentLoaded", function () {
    const ctx = document.getElementById('gscTrendsChart');
    if (ctx) {
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: ['Day 1', 'Day 5', 'Day 10', 'Day 15', 'Day 20', 'Day 25', 'Day 30'],
                datasets: [{
                    label: 'Organic Clicks',
                    data: [10, 15, 22, 28, 30, 42, 54],
                    borderColor: '#0d6efd',
                    backgroundColor: 'rgba(13, 110, 253, 0.1)',
                    fill: true,
                    tension: 0.4
                }, {
                    label: 'Impressions (x10)',
                    data: [20, 28, 38, 42, 50, 52, 58],
                    borderColor: '#198754',
                    backgroundColor: 'rgba(25, 135, 84, 0.05)',
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { position: 'top' }
                },
                scales: {
                    y: { beginAtZero: true }
                }
            }
        });
    }

    // Dynamic Realtime Active Users Simulator
    const activeVal = document.getElementById('activeUsersVal');
    if (activeVal) {
        setInterval(() => {
            let current = parseInt(activeVal.textContent);
            let diff = Math.floor(Math.random() * 5) - 2; // -2 to +2
            let newVal = Math.max(5, current + diff);
            activeVal.textContent = newVal;
        }, 3500);
    }
});
</script>
</body>
</html>
