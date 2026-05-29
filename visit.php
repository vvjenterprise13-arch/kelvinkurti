<?php
// DB connection
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
try {
    // તમારા ડેટાબેઝ કનેક્શનની વિગતો અહીં બદલો
    $mysqli = new mysqli("localhost", "u531962676_makasur", "Jay@5228", "u531962676_makasur");
    $mysqli->set_charset("utf8mb4");
    
    // કનેક્શન માટે ટાઇમઝોન IST (+05:30) સેટ કરો
    $mysqli->query("SET time_zone = '+05:30'");
    
} catch (mysqli_sql_exception $e) {
    die("Connection failed: Unable to connect to the database.");
}

// -- ડિલીટ ઓલ લોગ્સ માટેનો કોડ --
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_all') {
    $mysqli->query("TRUNCATE TABLE visitors_log");
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// -- ક્વેરીમાં 'timestamp' ને બદલે 'created_at' નો ઉપયોગ કરો --
$query = "SELECT *, COUNT(ip) OVER (PARTITION BY ip) as ip_count 
          FROM visitors_log 
          ORDER BY created_at DESC";
$result = $mysqli->query($query);

// બધો ડેટા PHP એરેમાં લો અને ગણતરી માટે કાઉન્ટર્સ તૈયાર કરો
$logs = [];
if ($result) {
    $logs = $result->fetch_all(MYSQLI_ASSOC);
}

$total_logs = count($logs);

// બધા કાઉન્ટર્સને 0 થી શરૂ કરો
$repeated_ip_yes = 0;
$repeated_ip_no = 0;
$redirected_yes = 0;
$redirected_no = 0;
$device_desktop = 0;
$device_mobile = 0;
$location_india = 0;
$location_outside = 0;
$indian_regions_count = [];
$foreign_regions_count = [];
$ip_counts = []; // <-- નવો ફેરફાર #1: IP ની ગણતરી સ્ટોર કરવા માટે એરે

// એક જ લૂપમાં બધી ગણતરી કરો
foreach ($logs as $log) {
    if ($log['ip_count'] > 1) { $repeated_ip_yes++; } else { $repeated_ip_no++; }
    if ($log['redirected']) { $redirected_yes++; } else { $redirected_no++; }
    if (strtolower($log['device']) === 'desktop') { $device_desktop++; } else { $device_mobile++; }

    $region = !empty($log['regionname']) ? $log['regionname'] : 'Unknown';
    $country = !empty($log['country']) ? $log['country'] : 'Unknown Country';

    if (strtolower($log['country']) === 'india') {
        $location_india++;
        $indian_regions_count[$region] = ($indian_regions_count[$region] ?? 0) + 1;
    } else {
        $location_outside++;
        $display_key = ($region === 'Unknown' || $region === $country) ? $country : "$region, $country";
        $foreign_regions_count[$display_key] = ($foreign_regions_count[$display_key] ?? 0) + 1;
    }
    
    // <-- નવો ફેરફાર #2: દરેક IP અને તેની ગણતરી સ્ટોર કરો
    if ($log['ip_count'] > 1) {
        $ip_counts[$log['ip']] = $log['ip_count'];
    }
}

arsort($indian_regions_count);
arsort($foreign_regions_count);
arsort($ip_counts); // <-- નવો ફેરફાર #3: IP ગણતરીને ઉતરતા ક્રમમાં સૉર્ટ કરો
$top_5_ips = array_slice($ip_counts, 0, 5, true); // ટોપ 5 IP લો

?>
<!DOCTYPE html>
<html lang="gu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Visitor Logs - Filter & Sort View</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body { background-color: #f4f7f9; font-size: 14px; }
        .container-fluid { padding: 20px; }
        h2 { font-size: 1.5rem; font-weight: 600; margin-bottom: 20px; color: #333; }
        .stats-container { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 25px; }
        .stat-card { background-color: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.07); border-left: 5px solid #0d6efd; display: flex; flex-direction: column; }
        .stat-card h5 { font-size: 1rem; font-weight: 600; color: #495057; margin-bottom: 15px; }
        .stat-card .stat-item { display: flex; justify-content: space-between; font-size: 14px; padding: 5px 0; }
        .stat-card .stat-label { color: #6c757d; }
        .stat-card .stat-value { font-weight: 600; color: #343a40; }
        .stat-card.warning { border-left-color: #ffc107; }
        .stat-card.success { border-left-color: #198754; }
        .stat-card.info { border-left-color: #0dcaf0; }
        .stat-card.danger { border-left-color: #dc3545; }
        .stat-card.primary { border-left-color: #0d6efd; }
        .table-wrapper { box-shadow: 0 2px 8px rgba(0,0,0,0.07); border-radius: 8px; overflow: hidden; background: #fff; }
        .table th, .table td { padding: 0.6rem 0.75rem; vertical-align: middle; font-size: 13px; }
        .table thead th { background-color: #e9ecef; color: #495057; font-weight: 600; }
        .sortable-col { cursor: pointer; }
        .sortable-col .sort-arrow { display: inline-block; width: 1em; height: 1em; margin-left: 5px; opacity: 0.4; }
        .sortable-col.sort-asc .sort-arrow::before { content: '▲'; opacity: 1; }
        .sortable-col.sort-desc .sort-arrow::before { content: '▼'; opacity: 1; }
    </style>
</head>
<body>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <h2 class="mb-0">📊 Visitor Logs</h2>
        <div>
            <span class="badge bg-primary align-middle fs-6 me-3"><?= $total_logs ?> Total</span>
            <button id="deleteAllBtn" class="btn btn-danger btn-sm">
                <i class="bi bi-trash-fill"></i> બધા લોગ ડિલીટ કરો
            </button>
        </div>
    </div>
    
    <form id="deleteForm" method="POST" action="">
        <input type="hidden" name="action" value="delete_all">
    </form>


    <div class="stats-container">
        <!-- નવો ફેરફાર #4: સૌથી વધુ વારંવાર IP માટે નવું કાર્ડ -->
        <div class="stat-card info">
            <h5>સૌથી વધુ વારંવાર IP (Top 5)</h5>
            <?php if (!empty($top_5_ips)): ?>
                <?php foreach ($top_5_ips as $ip => $count): ?>
                <div class="stat-item">
                    <span class="stat-label"><?= htmlspecialchars($ip) ?></span>
                    <span class="stat-value"><?= $count ?> વખત</span>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="stat-item"><span class="stat-label">કોઈ રિપીટ IP મળ્યા નથી.</span></div>
            <?php endif; ?>
        </div>
        <div class="stat-card info"><h5>ભારતીય પ્રદેશ મુજબ (Indian Regions)</h5><?php if (!empty($indian_regions_count)): foreach ($indian_regions_count as $region => $count): ?><div class="stat-item"><span class="stat-label"><?= htmlspecialchars($region) ?></span><span class="stat-value"><?= $count ?></span></div><?php endforeach; else: ?><div class="stat-item"><span class="stat-label">No Indian region data</span></div><?php endif; ?></div>
        <div class="stat-card danger"><h5>વિદેશી પ્રદેશ મુજબ (Foreign Regions)</h5><?php if (!empty($foreign_regions_count)): foreach ($foreign_regions_count as $region => $count): ?><div class="stat-item"><span class="stat-label"><?= htmlspecialchars($region) ?></span><span class="stat-value"><?= $count ?></span></div><?php endforeach; else: ?><div class="stat-item"><span class="stat-label">No foreign region data</span></div><?php endif; ?></div>
        <div class="stat-card warning"><h5>વારંવાર IP (Repeated IP)</h5><div class="stat-item"><span class="stat-label">Yes (હા)</span><span class="stat-value"><?= $repeated_ip_yes ?></span></div><div class="stat-item"><span class="stat-label">No (ના)</span><span class="stat-value"><?= $repeated_ip_no ?></span></div></div>
        <div class="stat-card success"><h5>રીડાયરેક્ટ (Redirected)</h5><div class="stat-item"><span class="stat-label">Yes (હા)</span><span class="stat-value"><?= $redirected_yes ?></span></div><div class="stat-item"><span class="stat-label">No (ના)</span><span class="stat-value"><?= $redirected_no ?></span></div></div>
        <div class="stat-card primary"><h5>ઉપકરણ (Device)</h5><div class="stat-item"><span class="stat-label">Desktop</span><span class="stat-value"><?= $device_desktop ?></span></div><div class="stat-item"><span class="stat-label">Mobile / Other</span><span class="stat-value"><?= $device_mobile ?></span></div></div>
        <div class="stat-card danger"><h5>સ્થાન (Location)</h5><div class="stat-item"><span class="stat-label">India</span><span class="stat-value"><?= $location_india ?></span></div><div class="stat-item"><span class="stat-label">Outside India</span><span class="stat-value"><?= $location_outside ?></span></div></div>
    </div>

    <div class="mb-3">
        <input type="text" id="filterInput" class="form-control" placeholder="ફિલ્ટર કરવા માટે અહીં ટાઇપ કરો (દા.ત. IP, શહેર, દેશ, ISP...)">
    </div>

    <div class="table-wrapper">
        <div class="table-responsive">
            <table class="table table-striped table-hover table-bordered">
                <thead>
                    <tr>
                        <th class="sortable-col" data-col-index="0">#<span class="sort-arrow"></span></th>
                        <th class="sortable-col" data-col-index="1">IP<span class="sort-arrow"></span></th>
                        <th class="sortable-col" data-col-index="2">વારંવાર IP<span class="sort-arrow"></span></th>
                        <th class="sortable-col" data-col-index="3">ISP<span class="sort-arrow"></span></th>
                        <th class="sortable-col" data-col-index="4">City<span class="sort-arrow"></span></th>
                        <th class="sortable-col" data-col-index="5">Region<span class="sort-arrow"></span></th>
                        <th class="sortable-col" data-col-index="6">Country<span class="sort-arrow"></span></th>
                        <th class="sortable-col" data-col-index="7">Device<span class="sort-arrow"></span></th>
                        <th>User-Agent</th>
                        <th>Redirected</th>
                        <th class="sortable-col sort-desc" data-col-index="10">Created At<span class="sort-arrow"></span></th>
                    </tr>
                </thead>
                <tbody id="logTableBody">
                <?php if (!empty($logs)): ?>
                    <?php $i = 1; foreach ($logs as $row): ?>
                    <tr>
                        <td><?= $i++ ?></td>
                        <td class="ip-col"><?= htmlspecialchars($row['ip']) ?></td>
                        <!-- નવો ફેરફાર #5: IP ની ગણતરી બતાવો -->
                        <td>
                            <?php if ($row['ip_count'] > 1): ?>
                                <span class="badge bg-warning">Yes (<?= $row['ip_count'] ?>)</span>
                            <?php else: ?>
                                <span class="badge bg-secondary">No</span>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($row['isp']) ?></td>
                        <td><?= htmlspecialchars($row['city']) ?></td>
                        <td><?= htmlspecialchars($row['regionname']) ?></td>
                        <td><?php $country = htmlspecialchars($row['country']); if (strtolower($country) !== 'india' && !empty($country)) { echo '<span class="badge bg-danger">' . $country . '</span>'; } else { echo $country; } ?></td>
                        <td><?php $device = htmlspecialchars($row['device']); if (strtolower($device) === 'desktop') { echo '<span class="badge bg-info">' . $device . '</span>'; } else { echo '<span class="badge bg-primary">' . $device . '</span>'; } ?></td>
                        <td class="user-agent-col"><?= htmlspecialchars($row['user_agent']) ?></td>
                        <td><?= $row['redirected'] ? '<span class="badge bg-success">Yes</span>' : '<span class="badge bg-secondary">No</span>' ?></td>
                        <td><?= htmlspecialchars($row['created_at']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr id="noDataRow"><td colspan="11" class="text-center p-4">ડેટાબેઝમાં કોઈ લોગ મળ્યા નથી.</td></tr>
                <?php endif; ?>
                <?php $mysqli->close(); ?>
                <tr id="noResultsRow" style="display: none;"><td colspan="11" class="text-center text-muted p-4">તમારી શોધ સાથે મેળ ખાતું કોઈ પરિણામ મળ્યું નથી.</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
// આ JavaScript કોડમાં કોઈ ફેરફારની જરૂર નથી
document.addEventListener('DOMContentLoaded', function() {
    const deleteAllBtn = document.getElementById('deleteAllBtn');
    if(deleteAllBtn) {
        deleteAllBtn.addEventListener('click', function(e) {
            e.preventDefault();
            const confirmation = confirm('શું તમે ખરેખર બધા લોગ ડિલીટ કરવા માંગો છો?\nઆ ક્રિયાને પૂર્વવત્ કરી શકાશે નહીં.');
            if (confirmation) {
                document.getElementById('deleteForm').submit();
            }
        });
    }

    const filterInput = document.getElementById('filterInput');
    const tableBody = document.getElementById('logTableBody');
    const noResultsRow = document.getElementById('noResultsRow');
    
    filterInput.addEventListener('keyup', applyFilter);

    function applyFilter() {
        const filterText = filterInput.value.toLowerCase();
        const allRows = tableBody.querySelectorAll('tr');
        let visibleRows = 0;
        for (let i = 0; i < allRows.length; i++) {
            if (allRows[i].id === 'noResultsRow' || allRows[i].id === 'noDataRow') { continue; }
            const rowText = allRows[i].textContent.toLowerCase();
            if (rowText.includes(filterText)) {
                allRows[i].style.display = '';
                visibleRows++;
            } else {
                allRows[i].style.display = 'none';
            }
        }
        noResultsRow.style.display = (visibleRows === 0 && filterText !== '') ? 'table-row' : 'none';
    }

    document.querySelectorAll('.sortable-col').forEach(headerCell => {
        headerCell.addEventListener('click', () => {
            const columnIndex = headerCell.dataset.colIndex;
            if(!columnIndex) return;
            const currentIsAsc = headerCell.classList.contains('sort-asc');
            const newSortDir = currentIsAsc ? 'desc' : 'asc';
            
            sortTableByColumn(columnIndex, newSortDir);

            document.querySelectorAll('.sortable-col').forEach(th => { th.classList.remove('sort-asc', 'sort-desc'); });
            headerCell.classList.add(newSortDir === 'asc' ? 'sort-asc' : 'sort-desc');
        });
    });

    function sortTableByColumn(columnIndex, direction) {
        const rows = Array.from(tableBody.querySelectorAll('tr:not(#noResultsRow):not(#noDataRow)'));
        const sortedRows = rows.sort((a, b) => {
            const aText = a.querySelector(`td:nth-child(${parseInt(columnIndex) + 1})`).textContent.trim();
            const bText = b.querySelector(`td:nth-child(${parseInt(columnIndex) + 1})`).textContent.trim();
            const isNumericColumn = columnIndex === '0';
            const isDateColumn = columnIndex === '10';
            let valA, valB;

            if (isNumericColumn) {
                valA = parseInt(aText, 10) || 0;
                valB = parseInt(bText, 10) || 0;
            } else if (isDateColumn) {
                valA = new Date(aText);
                valB = new Date(bText);
            } else {
                valA = aText.toLowerCase();
                valB = bText.toLowerCase();
            }

            if (valA < valB) return direction === 'asc' ? -1 : 1;
            if (valA > valB) return direction === 'asc' ? 1 : -1;
            return 0;
        });
        
        rows.forEach(row => tableBody.removeChild(row));
        sortedRows.forEach(row => tableBody.appendChild(row));
    }
});
</script>

</body>
</html>