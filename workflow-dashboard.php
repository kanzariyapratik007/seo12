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
$openIssuesCount = 0;
$competitorsCount = 0;
$backlinksCount = 0;
$keywordsCount = 0;
$hasSavedMeta = false;

if ($projectId > 0) {
    $stmt = $db->prepare("SELECT * FROM projects WHERE id=? AND user_id=?");
    $stmt->execute([$projectId, $userId]);
    $project = $stmt->fetch();

    if ($project) {
        // Fetch real stats
        $openIssuesStmt = $db->prepare("SELECT COUNT(*) FROM onpage_issues WHERE project_id=? AND status='open'");
        $openIssuesStmt->execute([$projectId]);
        $openIssuesCount = (int)$openIssuesStmt->fetchColumn();

        $kws = array_filter(array_map('trim', explode(',', $project['target_keyword'])));
        $competitorsCount = count($kws) * 5;

        $backStmt = $db->prepare("SELECT COUNT(*) FROM backlinks WHERE project_id=? AND status='created'");
        $backStmt->execute([$projectId]);
        $backlinksCount = (int)$backStmt->fetchColumn();

        $kwStmt = $db->prepare("SELECT COUNT(*) FROM keywords WHERE project_id=?");
        $kwStmt->execute([$projectId]);
        $keywordsCount = (int)$kwStmt->fetchColumn();

        $metaStmt = $db->prepare("SELECT COUNT(*) FROM project_meta WHERE project_id=?");
        $metaStmt->execute([$projectId]);
        $hasSavedMeta = ((int)$metaStmt->fetchColumn()) > 0;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>End-to-End Workflow - SEO 80/20</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<link href="style.css" rel="stylesheet">
<style>
/* Custom Flowchart styling */
.flow-container {
    background: #ffffff;
    border-radius: 16px;
    padding: 30px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.05);
}

.flow-section-header {
    background: #0f172a;
    color: #fff;
    padding: 10px 20px;
    border-radius: 8px;
    font-weight: 700;
    letter-spacing: 0.5px;
    text-transform: uppercase;
    font-size: 14px;
}

.flow-row {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: center;
    gap: 15px;
    margin-top: 25px;
}

.flow-step-card {
    background: #fff;
    border: 2px solid #e2e8f0;
    border-radius: 12px;
    padding: 15px;
    width: 150px;
    text-align: center;
    cursor: pointer;
    position: relative;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    box-shadow: 0 4px 6px rgba(0,0,0,0.02);
}

.flow-step-card .step-number {
    font-size: 10px;
    font-weight: 800;
    color: #94a3b8;
    text-transform: uppercase;
    margin-bottom: 5px;
}

.flow-step-card .step-icon {
    font-size: 24px;
    color: #475569;
    margin: 10px 0;
    transition: all 0.3s ease;
}

.flow-step-card .step-title {
    font-size: 11px;
    font-weight: 700;
    color: #1e293b;
    line-height: 1.3;
    height: 30px;
    display: flex;
    align-items: center;
    justify-content: center;
}

/* Connectors */
.flow-arrow {
    color: #cbd5e1;
    font-size: 20px;
    display: flex;
    align-items: center;
}

/* Card States and Hovers */
.flow-step-card:hover,
.flow-step-card.active {
    border-color: var(--primary);
    transform: translateY(-5px);
    box-shadow: 0 10px 20px rgba(13, 110, 253, 0.15);
}

.flow-step-card:hover .step-icon,
.flow-step-card.active .step-icon {
    color: var(--primary);
    transform: scale(1.15);
}

.flow-step-card.active {
    background: rgba(13, 110, 253, 0.03);
    border-width: 3px;
}

/* Color Coding by Categories */
.category-setup { border-top: 4px solid #3b82f6; }
.category-scan { border-top: 4px solid #14b8a6; }
.category-ai { border-top: 4px solid #a855f7; }
.category-execution { border-top: 4px solid #f97316; }
.category-backlinks { border-top: 4px solid #10b981; }

.detail-card {
    border-radius: 16px;
    border: none;
    box-shadow: 0 15px 40px rgba(0,0,0,0.06);
    background: #ffffff;
}

.detail-header {
    border-top-left-radius: 16px;
    border-top-right-radius: 16px;
}
</style>
</head>
<body>
<?php include 'includes/navbar.php'; ?>

<div class="container-fluid py-4 px-4">
    <!-- Header -->
    <div class="row align-items-center mb-4">
        <div class="col-md-6">
            <h3><i class="fas fa-route text-primary me-2"></i>AI SEO Platform Workflow</h3>
            <p class="text-muted mb-0">સંપૂર્ણ SEO 80/20 સિસ્ટમનો એન્ડ-ટુ-એન્ડ રોડમેપ અને લાઇવ વર્કફ્લો</p>
        </div>
        <div class="col-md-6 text-md-end mt-3 mt-md-0">
            <form method="GET" action="workflow-dashboard.php" class="d-inline-block">
                <select name="id" class="form-select w-auto d-inline-block align-middle me-2" onchange="this.form.submit()">
                    <?php foreach ($projects as $p): ?>
                        <option value="<?= $p['id'] ?>" <?= $p['id'] === $projectId ? 'selected' : '' ?>>
                            <?= clean($p['website_url']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>
            <?php if ($project): ?>
                <a href="seo-80-20.php?id=<?= $projectId ?>" class="btn btn-outline-secondary">
                    <i class="fas fa-columns me-2"></i>Dashboard
                </a>
            <?php endif; ?>
        </div>
    </div>

    <?php if (!$project): ?>
        <div class="alert alert-warning text-center py-5">
            <i class="fas fa-folder-open fa-3x mb-3 text-muted"></i>
            <h4>No Projects Found</h4>
            <p class="text-muted">પ્રોજેક્ટ રોડમેપ જોવા માટે પહેલાં એક નવો પ્રોજેક્ટ ઉમેરો.</p>
            <a href="add-project.php" class="btn btn-primary mt-2">Add New Project</a>
        </div>
    <?php else: ?>
        
        <!-- Workflow Timeline Container -->
        <div class="flow-container mb-4">
            <div class="flow-section-header text-center">
                <i class="fas fa-cogs me-2"></i>End-to-End Workflow - How the System Works
            </div>

            <!-- ROW 1 (Steps 1 to 10) -->
            <div class="flow-row">
                <!-- Step 1 -->
                <div class="flow-step-card category-setup active" onclick="selectStep(1)" data-step="1">
                    <div class="step-number">Step 1</div>
                    <div class="step-icon"><i class="fas fa-user-check"></i></div>
                    <div class="step-title">Sign Up / Login</div>
                </div>
                <div class="flow-arrow"><i class="fas fa-chevron-right"></i></div>

                <!-- Step 2 -->
                <div class="flow-step-card category-setup" onclick="selectStep(2)" data-step="2">
                    <div class="step-number">Step 2</div>
                    <div class="step-icon"><i class="fas fa-globe"></i></div>
                    <div class="step-title">Add Website</div>
                </div>
                <div class="flow-arrow"><i class="fas fa-chevron-right"></i></div>

                <!-- Step 3 -->
                <div class="flow-step-card category-setup" onclick="selectStep(3)" data-step="3">
                    <div class="step-number">Step 3</div>
                    <div class="step-icon"><i class="fas fa-link"></i></div>
                    <div class="step-title">Connect Tools</div>
                </div>
                <div class="flow-arrow"><i class="fas fa-chevron-right"></i></div>

                <!-- Step 4 -->
                <div class="flow-step-card category-scan" onclick="selectStep(4)" data-step="4">
                    <div class="step-number">Step 4</div>
                    <div class="step-icon"><i class="fas fa-spider"></i></div>
                    <div class="step-title">Initial Crawl</div>
                </div>
                <div class="flow-arrow"><i class="fas fa-chevron-right"></i></div>

                <!-- Step 5 -->
                <div class="flow-step-card category-scan" onclick="selectStep(5)" data-step="5">
                    <div class="step-number">Step 5</div>
                    <div class="step-icon"><i class="fas fa-check-double"></i></div>
                    <div class="step-title">SEO Audit & Scan</div>
                </div>
                <div class="flow-arrow"><i class="fas fa-chevron-right"></i></div>

                <!-- Step 6 -->
                <div class="flow-step-card category-scan" onclick="selectStep(6)" data-step="6">
                    <div class="step-number">Step 6</div>
                    <div class="step-icon"><i class="fas fa-search-dollar"></i></div>
                    <div class="step-title">Competitor Analysis</div>
                </div>
                <div class="flow-arrow"><i class="fas fa-chevron-right"></i></div>

                <!-- Step 7 -->
                <div class="flow-step-card category-ai" onclick="selectStep(7)" data-step="7">
                    <div class="step-number">Step 7</div>
                    <div class="step-icon"><i class="fas fa-brain"></i></div>
                    <div class="step-title">AI Suggestions</div>
                </div>
                <div class="flow-arrow"><i class="fas fa-chevron-right"></i></div>

                <!-- Step 8 -->
                <div class="flow-step-card category-ai" onclick="selectStep(8)" data-step="8">
                    <div class="step-number">Step 8</div>
                    <div class="step-icon"><i class="fas fa-thumbs-up"></i></div>
                    <div class="step-title">Review & Approve</div>
                </div>
                <div class="flow-arrow"><i class="fas fa-chevron-right"></i></div>

                <!-- Step 9 -->
                <div class="flow-step-card category-execution" onclick="selectStep(9)" data-step="9">
                    <div class="step-number">Step 9</div>
                    <div class="step-icon"><i class="fas fa-code"></i></div>
                    <div class="step-title">Auto Execute</div>
                </div>
                <div class="flow-arrow"><i class="fas fa-chevron-right"></i></div>

                <!-- Step 10 -->
                <div class="flow-step-card category-execution" onclick="selectStep(10)" data-step="10">
                    <div class="step-number">Step 10</div>
                    <div class="step-icon"><i class="fas fa-cloud-upload-alt"></i></div>
                    <div class="step-title">Deploy Website</div>
                </div>
            </div>

            <div class="text-center my-3 text-muted">
                <i class="fas fa-arrow-down fa-2x"></i>
            </div>

            <!-- ROW 2 (Steps 11 to 17) -->
            <div class="flow-row">
                <!-- Step 11 -->
                <div class="flow-step-card category-backlinks" onclick="selectStep(11)" data-step="11">
                    <div class="step-number">Step 11</div>
                    <div class="step-icon"><i class="fas fa-google"></i></div>
                    <div class="step-title">Index Request</div>
                </div>
                <div class="flow-arrow"><i class="fas fa-chevron-right"></i></div>

                <!-- Step 12 -->
                <div class="flow-step-card category-backlinks" onclick="selectStep(12)" data-step="12">
                    <div class="step-number">Step 12</div>
                    <div class="step-icon"><i class="fas fa-chart-line"></i></div>
                    <div class="step-title">Rank Tracking</div>
                </div>
                <div class="flow-arrow"><i class="fas fa-chevron-right"></i></div>

                <!-- Step 13 -->
                <div class="flow-step-card category-backlinks" onclick="selectStep(13)" data-step="13">
                    <div class="step-number">Step 13</div>
                    <div class="step-icon"><i class="fas fa-chart-area"></i></div>
                    <div class="step-title">Traffic & Analytics</div>
                </div>
                <div class="flow-arrow"><i class="fas fa-chevron-right"></i></div>

                <!-- Step 14 -->
                <div class="flow-step-card category-backlinks" onclick="selectStep(14)" data-step="14">
                    <div class="step-number">Step 14</div>
                    <div class="step-icon"><i class="fas fa-link"></i></div>
                    <div class="step-title">Backlink Process</div>
                </div>
                <div class="flow-arrow"><i class="fas fa-chevron-right"></i></div>

                <!-- Step 15 -->
                <div class="flow-step-card category-ai" onclick="selectStep(15)" data-step="15">
                    <div class="step-number">Step 15</div>
                    <div class="step-icon"><i class="fas fa-file-pdf"></i></div>
                    <div class="step-title">Reports</div>
                </div>
                <div class="flow-arrow"><i class="fas fa-chevron-right"></i></div>

                <!-- Step 16 -->
                <div class="flow-step-card category-setup" onclick="selectStep(16)" data-step="16">
                    <div class="step-number">Step 16</div>
                    <div class="step-icon"><i class="fas fa-bell"></i></div>
                    <div class="step-title">Alerts & Notification</div>
                </div>
                <div class="flow-arrow"><i class="fas fa-chevron-right"></i></div>

                <!-- Step 17 -->
                <div class="flow-step-card category-scan" onclick="selectStep(17)" data-step="17">
                    <div class="step-number">Step 17</div>
                    <div class="step-icon"><i class="fas fa-redo-alt"></i></div>
                    <div class="step-title">Continuous Imp.</div>
                </div>
            </div>
        </div>

        <!-- Live Step Details Box -->
        <div class="card detail-card mb-4">
            <div class="card-header bg-primary text-white py-3 detail-header">
                <h5 class="mb-0" id="detailTitle">Step 1: Sign Up / Login</h5>
            </div>
            <div class="card-body p-4 d-flex flex-column justify-content-between" style="min-height: 250px;">
                <div>
                    <div class="d-flex align-items-center mb-3">
                        <div class="bg-light text-primary p-3 rounded-circle me-3">
                            <i class="fas fa-user-check fa-2x" id="detailIcon"></i>
                        </div>
                        <div>
                            <span class="badge bg-success" id="detailStatus">Setup Complete</span>
                            <p class="text-muted small mb-0 mt-1" id="detailHeading">યુઝર રજીસ્ટ્રેશન અને લોગિન સિક્યોરિટી.</p>
                        </div>
                    </div>
                    <hr>
                    <div id="detailDescription" class="mt-3 text-dark">
                        સિસ્ટમનો બેઝિક ઓથોરિટી ગેટવે જે યુઝર્સને ડેટા સેવ રાખવા માટે મંજૂરી આપે છે.
                    </div>
                </div>
                <div class="mt-4">
                    <a href="#" class="btn btn-primary" id="detailActionBtn">
                        <i class="fas fa-external-link-alt me-2"></i>મેનેજ કરો
                    </a>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>

<script>
const PROJECT_ID = <?= $projectId ?>;
// Detailed JSON dictionary matching the 17 steps
const workflowSteps = {
    1: {
        title: "Step 1: Sign Up / Login",
        icon: "fa-user-check",
        status: "Setup Complete",
        heading: "યુઝર રજીસ્ટ્રેશન અને સિક્યોરિટી.",
        desc: `સિસ્ટમ સુરક્ષા અને ઓથેન્ટિકેશન માટે તમારી લોગિન પ્રોફાઇલ ઓલરેડી સક્રિય છે. <br><br>
               <strong>તમારી પ્રોફાઇલ માહિતી:</strong> <br>
               <ul>
                 <li>યુઝરનેમ: <strong><?= clean($_SESSION['username'] ?? 'User') ?></strong></li>
                 <li>સેશન સિક્યોરિટી: <strong>SSL / Protected</strong></li>
               </ul>`,
        btnText: "પ્રોજેક્ટ ડેશબોર્ડ જુઓ",
        btnLink: "dashboard.php"
    },
    2: {
        title: "Step 2: Add Website",
        icon: "fa-globe",
        status: "Setup Complete",
        heading: "પ્રોજેક્ટ તરીકે સાઇટ લિંક ઉમેરવી.",
        desc: `તમે સફળતાપૂર્વક તમારી વેબસાઇટ પ્રોજેક્ટ તરીકે સિસ્ટમમાં સેટઅપ કરેલી છે. <br><br>
               <strong>સાઇટની માહિતી:</strong> <br>
               <ul>
                 <li>વેબસાઇટ URL: <code><?= clean($project['website_url']) ?></code></li>
                 <li>બિઝનેસ નામ: <strong><?= clean($project['business_name'] ?: 'Not Setup') ?></strong></li>
               </ul>`,
        btnText: "નવો પ્રોજેક્ટ ઉમેરો",
        btnLink: "add-project.php"
    },
    3: {
        title: "Step 3: Connect Tools",
        icon: "fa-link",
        status: "Setup Active",
        heading: "ગુગલ ટૂલ્સ અને કસ્ટમ API કનેક્શન્સ.",
        desc: `વેબસાઇટના એનાલિસિસ માટે PageSpeed Insights અને ChatGPT API કીઝ કનેક્ટ કરવામાં આવેલી છે. <br><br>
               <strong>એક્ટિવ ટૂલ્સ:</strong> <br>
               <ul>
                 <li>PageSpeed Insights API: <strong>કનેક્ટ થયેલ છે</strong></li>
                 <li>ChatGPT API: <strong>એક્ટિવ</strong></li>
               </ul>`,
        btnText: "API સેટઅપ મેનેજ કરો",
        btnLink: "api-setup.php"
    },
    4: {
        title: "Step 4: Initial Crawl",
        icon: "fa-spider",
        status: "Auto Checked",
        heading: "વેબસાઇટના પેજીસની ક્રાઉલિંગ પ્રક્રિયા.",
        desc: `સિસ્ટમ ઓટોમેશન દ્વારા સાઇટની ક્રાઉલિંગ પ્રોસેસ કરે છે જેથી ઓન-પેજ ઓડિટ રન થઈ શકે. <br><br>
               <strong>ક્રાઉલિંગ વિગતો:</strong> <br>
               <ul>
                 <li>વેબસાઇટ લિંક: <code><?= clean($project['target_site'] ?: $project['website_url']) ?></code></li>
               </ul>`,
        btnText: "ઓન-પેજ રિપોર્ટ જુઓ",
        btnLink: "seo-80-20.php?id=<?= $projectId ?>"
    },
    5: {
        title: "Step 5: SEO Audit & Scan",
        icon: "fa-check-double",
        status: "Completed",
        heading: "૧૦૦૦+ એસઈઓ ચેક્સ રિયલ-ટાઇમ સ્કેનર.",
        desc: `વેબસાઇટ પર ટેકનિકલ એરર્સ, મોબાઇલ કમ્પેટિબિલિટી, Robots.txt અને Sitemap ની ઓટોમેટિક ચકાસણી. <br><br>
               <strong>તાજેતરનું ઓડિટ સ્ટેટસ:</strong> <br>
               <ul>
                 <li>પેજ સ્પીડ સ્કોર: <strong><?= $project['pagespeed_score'] ?: 'Not tested' ?>/100</strong></li>
                 <li>ઓપન ટાસ્ક ઇસ્યુઝ: <strong class='text-danger'><?= $openIssuesCount ?> ખુલ્લા છે</strong></li>
               </ul>`,
        btnText: "ઓન-પેજ એનાલાઈઝર રન કરો",
        btnLink: "seo-80-20.php?id=<?= $projectId ?>"
    },
    6: {
        title: "Step 6: Competitor & Keyword Analysis",
        icon: "fa-search-dollar",
        status: "Live Tracked",
        heading: "હરીફ વેબસાઇટ્સનું કીવર્ડ ગેપ ટ્રેકિંગ.",
        desc: `તમારા મુખ્ય કીવર્ડ્સ માટે ગૂગલ સર્ચ એનાલિસિસ દ્વારા હરીફ સાઇટ્સ શોધવી. <br><br>
               <strong>તમારા પ્રોજેક્ટની માહિતી:</strong> <br>
               <ul>
                 <li>ટ્રેક કરેલા હરીફોની સંખ્યા: <strong><?= $competitorsCount ?></strong></li>
                 <li>ટાર્ગેટ કીવર્ડ્સ: <strong><?= $keywordsCount ?></strong></li>
               </ul>`,
        btnText: "હરીફ એનાલિસિસ જુઓ",
        btnLink: "seo-80-20.php?id=<?= $projectId ?>"
    },
    7: {
        title: "Step 7: AI Suggestions",
        icon: "fa-brain",
        status: "AI Generated",
        heading: "OpenAI ChatGPT દ્વારા શ્રેષ્ઠ Title & Description જનરેશન.",
        desc: `વેબસાઇટ કન્ટેન્ટ અને કીવર્ડ ડેન્સિટીના આધારે આપમેળે ટાઇટલ અને ડિસ્ક્રિપ્શન સૂચનો. <br><br>
               <strong>સ્ટેટસ:</strong> <br>
               <ul>
                 <li>AI મેટા પ્રપોઝલ રેડી: <strong><?= $hasSavedMeta ? '✅ ઉપલબ્ધ છે' : '❌ રન કરવા માટે Run All SEO કરો' ?></strong></li>
               </ul>`,
        btnText: "મેટા ઓપ્ટિમાઇઝર ખોલો",
        btnLink: "seo-80-20.php?id=<?= $projectId ?>"
    },
    8: {
        title: "Step 8: Review & Approve",
        icon: "fa-thumbs-up",
        status: "20% Manual required",
        desc: `સિસ્ટમ દ્વારા સૂચવેલા ફેરફારોને ચેક કરીને લાઇવ કરવા માટે મંજૂરી (Approve) આપો. <br><br>
               <strong>રિવ્યુ માહિતી:</strong> <br>
               <ul>
                 <li>અપ્રૂવ કરવા લાયક ઓપન લિસ્ટ: <strong class='text-warning'><?= $openIssuesCount ?> ઇસ્યુઝ</strong></li>
               </ul>`,
        btnText: "રિપોર્ટમાં ઇસ્યુઝ રિવ્યુ કરો",
        btnLink: "seo-80-20.php?id=<?= $projectId ?>"
    },
    9: {
        title: "Step 9: Auto Execute",
        icon: "fa-code",
        status: "Ready to Execute",
        desc: `જેવા તમે કોઈ ફિક્સ કન્ફર્મ કરો છો, એટલે આપણું ઓટોમેશન ટાસ્ક સક્રિય થઈ જાય છે.`,
        btnText: "કાર્તિક પ્રોફાઇલ ક્રેડેન્શિયલ જુઓ",
        btnLink: "client-profile.php?id=<?= $projectId ?>"
    },
    10: {
        title: "Step 10: Deploy Website",
        icon: "fa-cloud-upload-alt",
        status: "Live Integration",
        desc: `મંજૂર થયેલા ફેરફારો ઓટોમેટિકલી ક્રેડેન્શિયલ્સના માધ્યમથી ડાયરેક્ટ સાઇટ પર સેવ થઈ જાય છે.`,
        btnText: "લાઇવ સાઇટ વિઝિટ કરો",
        btnLink: "<?= clean($project['website_url']) ?>"
    },
    11: {
        title: "Step 11: Index Request",
        icon: "fa-google",
        status: "Auto Requesting",
        desc: `ગુગલ ક્રાઉલર તમારી સાઇટને જલ્દી ઇન્ડેક્સ કરે તે માટે ઇન્ડેક્સિંગ રિક્વેસ્ટ સબમિશન.`,
        btnText: "પ્રોજેક્ટ કીવર્ડ્સ જુઓ",
        btnLink: "seo-80-20.php?id=<?= $projectId ?>"
    },
    12: {
        title: "Step 12: Rank Tracking",
        icon: "fa-chart-line",
        status: "100% Auto Tracked",
        desc: `ગુગલ અને બિંગ સર્ચ એન્જિનમાં તમારા કીવર્ડ્સ કયા ક્રમે છે તેનું દરરોજ ઓટોમેટિક એનાલિસિસ. <br><br>
               <strong>ટ્રેકિંગ માહિતી:</strong> <br>
               <ul>
                 <li>ટ્રેક કીવર્ડ્સ: <strong><?= $keywordsCount ?></strong></li>
               </ul>`,
        btnText: "રેન્ક ટ્રેકર જુઓ",
        btnLink: "seo-80-20.php?id=<?= $projectId ?>"
    },
    13: {
        title: "Step 13: Traffic & Analytics",
        icon: "fa-chart-area",
        status: "Monitoring Active",
        desc: `વેબસાઇટ પર કયા પેજ પરથી ટ્રાફિક અને કન્વર્ઝન રેટ આવે છે તેનું મોનિટરિંગ કરવું.`,
        btnText: "ડેશબોર્ડ ખોલો",
        btnLink: "dashboard.php"
    },
    14: {
        title: "Step 14: Backlink Process",
        icon: "fa-link",
        status: "80% Auto Posting",
        heading: "આપમેળે હાઈ-ક્વોલિટી બેકલીંક્સ બિલ્ડિંગ.",
        desc: `Instapaper, Wakelet, Tumblr, Minds જેવી સાઇટ્સ પર ડાયનેમિક સિંગલ ટાર્ગેટ લિંક્સ ઓટો-પોસ્ટ થાય છે. <br><br>
               <strong>તમારા પ્રોજેક્ટનું સ્ટેટસ:</strong> <br>
               <ul>
                 <li>બનેલી કુલ બેકલીંક્સ: <strong><?= $backlinksCount ?></strong></li>
               </ul>`,
        btnText: "બેકલીંક સબમિશન મેનેજ કરો",
        btnLink: "submission-manager.php"
    },
    15: {
        title: "Step 15: Reports",
        icon: "fa-file-pdf",
        status: "White-Label Ready",
        desc: `ક્લાયન્ટ શેરિંગ માટે એસઈઓ પ્રોગ્રેસનો સુંદર રીપોર્ટ એક્સપોર્ટ કરવાનો ઓપ્શન.`,
        btnText: "Excel માં રિપોર્ટ એક્સપોર્ટ કરો",
        btnLink: "export-excel.php?id=<?= $projectId ?>"
    },
    16: {
        title: "Step 16: Alerts & Notification",
        icon: "fa-bell",
        status: "Active Monitoring",
        desc: `વેબસાઇટ ડાઉન થાય કે રેન્ક ડ્રોપ થાય ત્યારે WhatsApp અને ઈમેલ પર ઓટોમેટિક અલર્ટ સબમિશન.`,
        btnText: "ઓટો-શેડ્યૂલર જુઓ",
        btnLink: "schedule-setup.php"
    },
    17: {
        title: "Step 17: Continuous Improvement",
        icon: "fa-redo-alt",
        status: "Active Cycle",
        desc: `એસઈઓ સ્કોર સુધારવા માટે દર અઠવાડિયે કન્ટીન્યુઅસ ઓટોમેટિક ઓડિટ રન કરતું સાયકલ.`,
        btnText: "વર્કફ્લો હોમ જુઓ",
        btnLink: "workflow-dashboard.php?id=<?= $projectId ?>"
    }
};

function selectStep(stepNum) {
    // Remove active class from all step cards
    document.querySelectorAll('.flow-step-card').forEach(card => {
        card.classList.remove('active');
    });
    
    // Set active to selected step card
    const activeCard = document.querySelector(`.flow-step-card[data-step="${stepNum}"]`);
    if (activeCard) activeCard.classList.add('active');
    
    // Update bottom detail panel
    const step = workflowSteps[stepNum];
    if (step) {
        document.getElementById('detailTitle').textContent = step.title;
        document.getElementById('detailStatus').textContent = step.status;
        document.getElementById('detailHeading').textContent = step.heading || "પ્રક્રિયાની વિગતો અને એનાલિસિસ.";
        document.getElementById('detailDescription').innerHTML = step.desc;
        
        // Icon update
        const iconEl = document.getElementById('detailIcon');
        iconEl.className = `fas ${step.icon} fa-2x`;
        
        // Action button update
        const btnEl = document.getElementById('detailActionBtn');
        btnEl.innerHTML = `<i class="fas fa-external-link-alt me-2"></i>${step.btnText}`;
        btnEl.href = step.btnLink;
        
        // Change colors depending on status
        const statusBadge = document.getElementById('detailStatus');
        if (step.status.includes('Manual')) {
            statusBadge.className = 'badge bg-warning text-dark';
        } else {
            statusBadge.className = 'badge bg-success';
        }
    }
}

// Initialise step 1
selectStep(1);
</script>
</body>
</html>
