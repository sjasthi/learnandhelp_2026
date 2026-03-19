<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403); die('Forbidden');
}

require_once 'db_configuration.php';

$upload_dir = __DIR__ . '/uploads/assets/';
if (!is_dir($upload_dir)) { mkdir($upload_dir, 0755, true); }

// ── Photo upload helper ────────────────────────────────────────
function save_asset_photo($file, $asset_id, $old_path = null) {
    global $upload_dir;
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) return $old_path;
    $allowed = ['image/jpeg','image/png','image/gif','image/webp'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime  = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    if (!in_array($mime, $allowed)) return $old_path;
    if ($file['size'] > 5 * 1024 * 1024) return $old_path;
    if ($old_path && file_exists(__DIR__ . '/' . $old_path)) unlink(__DIR__ . '/' . $old_path);
    $ext      = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $filename = 'asset_' . $asset_id . '_' . time() . '.' . $ext;
    if (move_uploaded_file($file['tmp_name'], $upload_dir . $filename)) {
        return 'uploads/assets/' . $filename;
    }
    return $old_path;
}

$message      = '';
$message_type = '';
if (!empty($_SESSION['asset_flash'])) {
    $message      = $_SESSION['asset_flash']['msg'];
    $message_type = $_SESSION['asset_flash']['type'];
    unset($_SESSION['asset_flash']);
}
$today        = date('Y-m-d');
$changed_by   = trim(($_SESSION['first_name'] ?? '') . ' ' . ($_SESSION['last_name'] ?? '')) ?: ($_SESSION['email'] ?? 'Admin');

// ── Audit log helper ──────────────────────────────────────────
function log_asset($db, $asset_id, $asset_name, $action, $details = null) {
    global $changed_by;
    $stmt = $db->prepare("INSERT INTO asset_logs (asset_id, asset_name, action, changed_by, details) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("issss", $asset_id, $asset_name, $action, $changed_by, $details);
    $stmt->execute();
    $stmt->close();
}

// ── DELETE (GET) ──────────────────────────────────────────────
if (isset($_GET['delete_asset'])) {
    $del_id = intval($_GET['delete_asset']);
    $stmt   = $db->prepare("SELECT name, photo_path FROM assets WHERE id = ?");
    $stmt->bind_param("i", $del_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if ($row) {
        if (!empty($row['photo_path']) && file_exists(__DIR__ . '/' . $row['photo_path'])) {
            unlink(__DIR__ . '/' . $row['photo_path']);
        }
        $stmt = $db->prepare("DELETE FROM assets WHERE id = ?");
        $stmt->bind_param("i", $del_id);
        $stmt->execute();
        $stmt->close();
        log_asset($db, $del_id, $row['name'], 'Deleted');
        $message      = '"' . htmlspecialchars($row['name']) . '" deleted.';
        $message_type = 'success';
    }
}

// ── DUPLICATE (GET) ───────────────────────────────────────────
if (isset($_GET['duplicate'])) {
    $did  = intval($_GET['duplicate']);
    $stmt = $db->prepare("SELECT * FROM assets WHERE id = ?");
    $stmt->bind_param("i", $did);
    $stmt->execute();
    $orig = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if ($orig) {
        $new_name = $orig['name'] . ' (Copy)';
        $stmt = $db->prepare("
            INSERT INTO assets (name, asset_type, category, description, serial_number, purchase_date, purchase_price, condition_status, location, assigned_to, warranty_expiry, status, notes)
            VALUES (?, ?, ?, ?, NULL, ?, ?, ?, ?, NULL, ?, ?, ?)
        ");
        $p_serial = null;
        $stmt->bind_param("sssssdsssss",
            $new_name, $orig['asset_type'], $orig['category'], $orig['description'],
            $orig['purchase_date'], $orig['purchase_price'], $orig['condition_status'],
            $orig['location'], $orig['warranty_expiry'], $orig['status'], $orig['notes']
        );
        $stmt->execute();
        $new_id = $db->insert_id;
        $stmt->close();
        log_asset($db, $new_id, $new_name, 'Duplicated', 'Copied from "' . $orig['name'] . '" (ID ' . $did . ')');
        $message      = '"' . htmlspecialchars($orig['name']) . '" duplicated. Review and update the copy below.';
        $message_type = 'success';
        // Pre-fill form with copy
        $edit_asset_prefill = array_merge($orig, ['id' => null, 'name' => $new_name, 'serial_number' => '']);
    }
}

// ── BULK STATUS UPDATE ────────────────────────────────────────
if (isset($_POST['action']) && $_POST['action'] === 'bulk_status') {
    $new_status = in_array($_POST['bulk_new_status'] ?? '', ['Active','Inactive','Disposed']) ? $_POST['bulk_new_status'] : '';
    $ids        = array_map('intval', (array)($_POST['bulk_ids'] ?? []));
    if ($new_status && $ids) {
        $in = implode(',', $ids);
        $db->query("UPDATE assets SET status='$new_status' WHERE id IN ($in)");
        $names_res = $db->query("SELECT id, name FROM assets WHERE id IN ($in)");
        while ($nr = $names_res->fetch_assoc()) {
            log_asset($db, $nr['id'], $nr['name'], 'Updated', 'Bulk status → ' . $new_status);
        }
        $message      = count($ids) . ' asset(s) updated to ' . $new_status . '.';
        $message_type = 'success';
    }
}

// ── BULK CONDITION UPDATE ─────────────────────────────────────
if (isset($_POST['action']) && $_POST['action'] === 'bulk_condition') {
    $new_cond = in_array($_POST['bulk_new_condition'] ?? '', ['New','Good','Fair','Poor','Retired']) ? $_POST['bulk_new_condition'] : '';
    $ids      = array_map('intval', (array)($_POST['bulk_ids'] ?? []));
    if ($new_cond && $ids) {
        $in = implode(',', $ids);
        $db->query("UPDATE assets SET condition_status='$new_cond' WHERE id IN ($in)");
        $names_res = $db->query("SELECT id, name FROM assets WHERE id IN ($in)");
        while ($nr = $names_res->fetch_assoc()) {
            log_asset($db, $nr['id'], $nr['name'], 'Updated', 'Bulk condition → ' . $new_cond);
        }
        $message      = count($ids) . ' asset(s) condition set to ' . $new_cond . '.';
        $message_type = 'success';
    }
}

// ── BULK DELETE ───────────────────────────────────────────────
if (isset($_POST['action']) && $_POST['action'] === 'bulk_delete') {
    $ids = array_map('intval', (array)($_POST['bulk_ids'] ?? []));
    if ($ids) {
        $in = implode(',', $ids);
        // Fetch names before deleting for the log
        $names_res = $db->query("SELECT id, name FROM assets WHERE id IN ($in)");
        $to_log = $names_res->fetch_all(MYSQLI_ASSOC);
        $db->query("DELETE FROM assets WHERE id IN ($in)");
        foreach ($to_log as $nr) {
            log_asset($db, $nr['id'], $nr['name'], 'Deleted', 'Bulk delete');
        }
        $message      = count($to_log) . ' asset(s) deleted.';
        $message_type = 'success';
    }
}

// ── ADD ──────────────────────────────────────────────────────
if (isset($_POST['action']) && $_POST['action'] === 'add') {
    $name             = trim($_POST['name']);
    $asset_type       = in_array($_POST['asset_type'], ['Physical','Digital']) ? $_POST['asset_type'] : 'Physical';
    $category         = trim($_POST['category'])      ?: null;
    $description      = trim($_POST['description'])   ?: null;
    $serial_number    = trim($_POST['serial_number']) ?: null;
    $purchase_date    = trim($_POST['purchase_date']) ?: null;
    $purchase_price   = is_numeric($_POST['purchase_price']) ? (float)$_POST['purchase_price'] : null;
    $condition_status = in_array($_POST['condition_status'], ['New','Good','Fair','Poor','Retired']) ? $_POST['condition_status'] : 'Good';
    $location         = trim($_POST['location'])        ?: null;
    $assigned_to      = trim($_POST['assigned_to'])     ?: null;
    $warranty_expiry  = trim($_POST['warranty_expiry']) ?: null;
    $status           = in_array($_POST['status'], ['Active','Inactive','Disposed']) ? $_POST['status'] : 'Active';
    $notes            = trim($_POST['notes'])           ?: null;

    if ($name === '') {
        $message = "Asset name is required."; $message_type = "error";
    } else {
        $stmt = $db->prepare("
            INSERT INTO assets (name, asset_type, category, description, serial_number, purchase_date, purchase_price, condition_status, location, assigned_to, warranty_expiry, status, notes)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("ssssssdssssss", $name, $asset_type, $category, $description, $serial_number, $purchase_date, $purchase_price, $condition_status, $location, $assigned_to, $warranty_expiry, $status, $notes);
        $stmt->execute();
        $new_id = $db->insert_id;
        $stmt->close();
        log_asset($db, $new_id, $name, 'Added');
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $photo_path = save_asset_photo($_FILES['photo'], $new_id);
            if ($photo_path) {
                $stmt2 = $db->prepare("UPDATE assets SET photo_path = ? WHERE id = ?");
                $stmt2->bind_param("si", $photo_path, $new_id);
                $stmt2->execute(); $stmt2->close();
            }
        }
        $_SESSION['asset_flash'] = ['msg' => 'Asset added successfully.', 'type' => 'success'];
        header('Location: admin_assets.php#asset-list');
        exit;
    }
}

// ── EDIT (save) ──────────────────────────────────────────────
if (isset($_POST['action']) && $_POST['action'] === 'edit') {
    $edit_id          = intval($_POST['asset_id']);
    $name             = trim($_POST['name']);
    $asset_type       = in_array($_POST['asset_type'], ['Physical','Digital']) ? $_POST['asset_type'] : 'Physical';
    $category         = trim($_POST['category'])      ?: null;
    $description      = trim($_POST['description'])   ?: null;
    $serial_number    = trim($_POST['serial_number']) ?: null;
    $purchase_date    = trim($_POST['purchase_date']) ?: null;
    $purchase_price   = is_numeric($_POST['purchase_price']) ? (float)$_POST['purchase_price'] : null;
    $condition_status = in_array($_POST['condition_status'], ['New','Good','Fair','Poor','Retired']) ? $_POST['condition_status'] : 'Good';
    $location         = trim($_POST['location'])        ?: null;
    $assigned_to      = trim($_POST['assigned_to'])     ?: null;
    $warranty_expiry  = trim($_POST['warranty_expiry']) ?: null;
    $status           = in_array($_POST['status'], ['Active','Inactive','Disposed']) ? $_POST['status'] : 'Active';
    $notes            = trim($_POST['notes'])           ?: null;

    if ($name === '') {
        $message = "Asset name is required."; $message_type = "error";
    } else {
        $stmt = $db->prepare("
            UPDATE assets SET name=?, asset_type=?, category=?, description=?, serial_number=?,
                purchase_date=?, purchase_price=?, condition_status=?, location=?, assigned_to=?,
                warranty_expiry=?, status=?, notes=?
            WHERE id=?
        ");
        $stmt->bind_param("ssssssdssssssi", $name, $asset_type, $category, $description, $serial_number, $purchase_date, $purchase_price, $condition_status, $location, $assigned_to, $warranty_expiry, $status, $notes, $edit_id);
        $stmt->execute();
        $stmt->close();
        log_asset($db, $edit_id, $name, 'Updated');
        // Handle photo
        $ph_stmt = $db->prepare("SELECT photo_path FROM assets WHERE id = ?");
        $ph_stmt->bind_param("i", $edit_id);
        $ph_stmt->execute();
        $ph_row   = $ph_stmt->get_result()->fetch_assoc();
        $ph_stmt->close();
        $old_photo = $ph_row['photo_path'] ?? null;
        $new_photo = $old_photo;
        if (!empty($_POST['remove_photo'])) {
            if ($old_photo && file_exists(__DIR__ . '/' . $old_photo)) unlink(__DIR__ . '/' . $old_photo);
            $new_photo = null;
        }
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $new_photo = save_asset_photo($_FILES['photo'], $edit_id, $old_photo);
        }
        if ($new_photo !== $old_photo) {
            $ph_upd = $db->prepare("UPDATE assets SET photo_path = ? WHERE id = ?");
            $ph_upd->bind_param("si", $new_photo, $edit_id);
            $ph_upd->execute(); $ph_upd->close();
        }
        $_SESSION['asset_flash'] = ['msg' => 'Asset updated successfully.', 'type' => 'success'];
        header('Location: admin_assets.php#asset-list');
        exit;
    }
}

// ── FETCH edit target ────────────────────────────────────────
$edit_asset = $edit_asset_prefill ?? null;
if (!$edit_asset && isset($_GET['edit'])) {
    $eid  = intval($_GET['edit']);
    $stmt = $db->prepare("SELECT * FROM assets WHERE id = ?");
    $stmt->bind_param("i", $eid);
    $stmt->execute();
    $edit_asset = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

// ── CSV EXPORT ───────────────────────────────────────────────
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $all = $db->query("SELECT * FROM assets ORDER BY name ASC")->fetch_all(MYSQLI_ASSOC);
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="assets_' . date('Y-m-d') . '.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['ID','Name','Type','Category','Description','Serial #','Purchase Date','Purchase Price','Condition','Location','Assigned To','Warranty Expiry','Status','Notes','Date Created']);
    foreach ($all as $row) {
        fputcsv($out, [
            $row['id'], $row['name'], $row['asset_type'], $row['category'],
            $row['description'], $row['serial_number'], $row['purchase_date'],
            $row['purchase_price'], $row['condition_status'], $row['location'],
            $row['assigned_to'], $row['warranty_expiry'],
            $row['status'], $row['notes'], $row['date_created'],
        ]);
    }
    fclose($out);
    exit;
}

if (isset($_GET['export']) && $_GET['export'] === 'log_csv') {
    $logs = $db->query("SELECT * FROM asset_logs ORDER BY created_at DESC")->fetch_all(MYSQLI_ASSOC);
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="asset_log_' . date('Y-m-d') . '.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['ID','Asset ID','Asset Name','Action','Changed By','Details','Date/Time']);
    foreach ($logs as $row) {
        fputcsv($out, [$row['id'], $row['asset_id'], $row['asset_name'], $row['action'], $row['changed_by'], $row['details'], $row['created_at']]);
    }
    fclose($out);
    exit;
}

// ── FILTERS ──────────────────────────────────────────────────
$filter_type      = isset($_GET['filter_type'])      ? $_GET['filter_type']      : '';
$filter_status    = isset($_GET['filter_status'])    ? $_GET['filter_status']    : '';
$filter_condition = isset($_GET['filter_condition']) ? $_GET['filter_condition'] : '';
if (!in_array($filter_type,      ['','Physical','Digital']))                     $filter_type      = '';
if (!in_array($filter_status,    ['','Active','Inactive','Disposed']))           $filter_status    = '';
if (!in_array($filter_condition, ['','New','Good','Fair','Poor','Retired']))     $filter_condition = '';
$filter_assigned  = isset($_GET['filter_assigned']) ? trim($_GET['filter_assigned']) : '';

// ── SEARCH / LIST ─────────────────────────────────────────────
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$sort   = isset($_GET['sort'])   ? $_GET['sort']         : 'id';
$dir    = isset($_GET['dir'])    ? $_GET['dir']          : 'ASC';

$allowed_sort = ['id','name','asset_type','category','serial_number','purchase_date','purchase_price','condition_status','location','assigned_to','warranty_expiry','status','date_created','photo_path'];
$allowed_dir  = ['ASC','DESC'];
if (!in_array($sort, $allowed_sort)) $sort = 'id';
if (!in_array($dir,  $allowed_dir))  $dir  = 'DESC';

$where_clauses = []; $bind_params = []; $bind_types = '';
if ($search !== '') {
    $like = "%$search%";
    $where_clauses[] = "(name LIKE ? OR category LIKE ? OR asset_type LIKE ? OR location LIKE ? OR serial_number LIKE ? OR assigned_to LIKE ?)";
    array_push($bind_params, $like, $like, $like, $like, $like, $like);
    $bind_types .= 'ssssss';
}
if ($filter_type      !== '') { $where_clauses[] = "asset_type = ?";       $bind_params[] = $filter_type;      $bind_types .= 's'; }
if ($filter_status    !== '') { $where_clauses[] = "status = ?";            $bind_params[] = $filter_status;    $bind_types .= 's'; }
if ($filter_condition !== '') { $where_clauses[] = "condition_status = ?";  $bind_params[] = $filter_condition; $bind_types .= 's'; }
if ($filter_assigned !== '') { $where_clauses[] = "assigned_to = ?"; $bind_params[] = $filter_assigned; $bind_types .= 's'; }

$sql = "SELECT * FROM assets";
if ($where_clauses) $sql .= " WHERE " . implode(" AND ", $where_clauses);
$sql .= " ORDER BY $sort $dir";
$stmt = $db->prepare($sql);
if ($bind_params) $stmt->bind_param($bind_types, ...$bind_params);
$stmt->execute();
$assets = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$total_value    = array_sum(array_column($assets, 'purchase_price'));
$active_filters = ($filter_type !== '' || $filter_status !== '' || $filter_condition !== '' || $filter_assigned !== '' || $search !== '');

// ── Warranty alerts (across ALL active assets) ────────────────
$warranty_expired  = 0;
$warranty_expiring = 0;
$w_res = $db->query("SELECT warranty_expiry FROM assets WHERE warranty_expiry IS NOT NULL AND status = 'Active'");
while ($w = $w_res->fetch_assoc()) {
    $days = (strtotime($w['warranty_expiry']) - strtotime($today)) / 86400;
    if ($days < 0)        $warranty_expired++;
    elseif ($days <= 90)  $warranty_expiring++;
}

// ── Recent audit log ──────────────────────────────────────────
$recent_logs = $db->query("SELECT * FROM asset_logs ORDER BY created_at DESC LIMIT 10")->fetch_all(MYSQLI_ASSOC);

// ── Datalist / filter data ────────────────────────────────────
$cat_options      = $db->query("SELECT DISTINCT category FROM assets WHERE category IS NOT NULL AND category != '' ORDER BY category")->fetch_all(MYSQLI_ASSOC);
$user_names       = $db->query("SELECT CONCAT(First_Name, ' ', Last_Name) AS full_name FROM users WHERE First_Name IS NOT NULL AND First_Name != '' ORDER BY First_Name, Last_Name")->fetch_all(MYSQLI_ASSOC);
$assigned_options = $db->query("SELECT DISTINCT assigned_to FROM assets WHERE assigned_to IS NOT NULL AND assigned_to != '' ORDER BY assigned_to")->fetch_all(MYSQLI_ASSOC);

// ── Helpers ───────────────────────────────────────────────────
function sort_link($col, $label, $sort, $dir, $search, $filters = []) {
    $new_dir = ($sort === $col && $dir === 'ASC') ? 'DESC' : 'ASC';
    $arrow   = ($sort === $col) ? ($dir === 'ASC' ? ' ▲' : ' ▼') : ' ↕';
    $q       = $search ? '&search=' . urlencode($search) : '';
    foreach ($filters as $k => $v) { if ($v !== '') $q .= '&' . urlencode($k) . '=' . urlencode($v); }
    return '<a href="admin_assets.php?sort=' . $col . '&dir=' . $new_dir . $q . '#asset-list" style="color:#274606;text-decoration:none;white-space:nowrap;">' . htmlspecialchars($label) . $arrow . '</a>';
}
$filters_arr = ['filter_type' => $filter_type, 'filter_status' => $filter_status, 'filter_condition' => $filter_condition, 'filter_assigned' => $filter_assigned];

$condition_colours = [
    'New'     => ['bg' => '#e8f5e9', 'color' => '#2e7d32'],
    'Good'    => ['bg' => '#f1f8e9', 'color' => '#558b2f'],
    'Fair'    => ['bg' => '#fff8e1', 'color' => '#f57f17'],
    'Poor'    => ['bg' => '#fff3e0', 'color' => '#e65100'],
    'Retired' => ['bg' => '#fce4ec', 'color' => '#c62828'],
];
$status_colours = [
    'Active'   => ['bg' => '#e8f5e9', 'color' => '#2e7d32'],
    'Inactive' => ['bg' => '#f3f3f3', 'color' => '#757575'],
    'Disposed' => ['bg' => '#fce4ec', 'color' => '#c62828'],
];
$log_colours = [
    'Added'      => ['bg' => '#e8f5e9', 'color' => '#2e7d32'],
    'Updated'    => ['bg' => '#e3f2fd', 'color' => '#1565c0'],
    'Deleted'    => ['bg' => '#fce4ec', 'color' => '#c62828'],
    'Duplicated' => ['bg' => '#fff8e1', 'color' => '#f57f17'],
];
?>
<!DOCTYPE html>
<html lang="en-us">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="images/icon_logo.png" type="image/icon type">
    <title>Assets – Administration</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;700;900&display=swap" rel="stylesheet">
    <link href="css/main.css?v=2025-08-22a" rel="stylesheet">
    <style>
        body { background: #f8f8f8; margin: 0; font-family: 'Roboto', Arial, sans-serif; }
        .banner-wrapper { width:100vw; left:50%; margin-left:-50vw; height:220px; background:#fff; overflow:hidden; box-shadow:0 4px 24px rgba(0,0,0,.08); position:relative; }
        .banner-wrapper img { position:absolute; top:0; left:0; width:100%; height:100%; object-fit:cover; }
        .banner-title { position:absolute; top:50%; left:50%; transform:translate(-50%,-50%); margin:0; font-family:'Roboto',sans-serif; font-size:3em; font-weight:900; color:#99d930; text-shadow:0 2px 16px rgba(0,0,0,.44); letter-spacing:1px; z-index:2; white-space:nowrap; }
        .page-wrap { max-width:1900px; margin:36px auto 60px; padding:0 18px; }
        .flash { padding:13px 20px; border-radius:9px; margin-bottom:22px; font-weight:700; font-size:1em; }
        .flash.success { background:#edfae5; color:#2a6006; border:1.5px solid #99d930; }
        .flash.error   { background:#fff0f0; color:#a00;    border:1.5px solid #f88; }
        .card { background:#fff; border-radius:16px; box-shadow:0 6px 32px rgba(80,120,180,.09); border:2px solid #99d930; padding:20px 24px; margin-bottom:32px; }
        .card h2 { margin:0; font-size:1.35em; color:#274606; font-weight:900; letter-spacing:.3px; }
        /* Collapsible form header */
        .form-card-header { cursor:pointer; display:flex; justify-content:space-between; align-items:center; user-select:none; padding:4px 0; }
        .form-card-header:hover h2 { color:#85c220; }
        .form-card-header .chevron { font-size:.85em; color:#99d930; transition:transform .3s; flex-shrink:0; }
        .form-collapse { overflow:hidden; max-height:0; transition:max-height .4s ease; }
        .form-collapse.open { max-height:1400px; }
        .form-collapse-inner { padding-top:18px; }
        .form-grid { display:grid; grid-template-columns:1fr 1fr 1fr; gap:10px 16px; }
        .form-group { display:flex; flex-direction:column; gap:3px; }
        .form-group.full-width { grid-column:1/-1; }
        .form-group label { font-size:.78em; font-weight:700; color:#274606; text-transform:uppercase; letter-spacing:.5px; }
        .form-group input, .form-group select, .form-group textarea { padding:7px 11px; border:1.5px solid #cde8a0; border-radius:8px; font-size:.95em; font-family:'Roboto',sans-serif; background:#f8fbe9; color:#222; transition:border-color .18s,box-shadow .18s; outline:none; }
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus { border-color:#99d930; box-shadow:0 0 0 3px rgba(153,217,48,.15); }
        .form-group textarea { resize:vertical; min-height:52px; }
        .btn { display:inline-flex; align-items:center; gap:7px; padding:10px 22px; border-radius:8px; font-size:.97em; font-weight:700; font-family:'Roboto',sans-serif; border:none; cursor:pointer; transition:background .18s,transform .12s; text-decoration:none; }
        .btn-green   { background:#99d930; color:#274606; box-shadow:0 2px 8px rgba(153,217,48,.25); }
        .btn-green:hover   { background:#85c220; transform:translateY(-1px); }
        .btn-outline { background:#fff; color:#274606; border:2px solid #99d930; }
        .btn-outline:hover { background:#f0fad8; }
        .btn-danger  { background:#fff0f0; color:#c00; border:1.5px solid #f99; padding:6px 10px; font-size:.82em; }
        .btn-danger:hover  { background:#ffe0e0; }
        .btn-edit    { background:#f0fad8; color:#274606; border:1.5px solid #99d930; padding:6px 12px; font-size:.82em; }
        .btn-edit:hover    { background:#e2f5b2; }
        .btn-copy    { background:#fff8e1; color:#f57f17; border:1.5px solid #ffcc02; padding:6px 10px; font-size:.82em; }
        .btn-copy:hover    { background:#fff3cd; }
        .form-actions { display:flex; gap:12px; margin-top:20px; flex-wrap:wrap; }
        /* Search+filter bar */
        .search-filter-bar { display:flex; gap:10px; margin-bottom:18px; align-items:center; flex-wrap:nowrap; }
        .search-filter-bar input[type=text] { flex:1; min-width:0; padding:10px 14px; border:1.5px solid #cde8a0; border-radius:8px; font-size:.95em; font-family:'Roboto',sans-serif; background:#f8fbe9; outline:none; transition:border-color .18s; }
        .search-filter-bar input[type=text]:focus { border-color:#99d930; }
        .search-filter-bar select { flex:0 0 auto; width:150px; padding:10px 10px; border:1.5px solid #cde8a0; border-radius:8px; font-size:.9em; font-family:'Roboto',sans-serif; background:#f8fbe9; color:#333; outline:none; cursor:pointer; transition:border-color .18s; }
        .search-filter-bar select:focus { border-color:#99d930; }
        .search-filter-bar select.active-filter { border-color:#99d930; background:#edfae5; font-weight:700; color:#274606; }
        @media(max-width:900px){ .search-filter-bar { flex-wrap:wrap; } }
        /* Warranty alert */
        .warranty-alert { display:flex; gap:12px; margin-bottom:18px; flex-wrap:wrap; }
        .warranty-tile { display:flex; align-items:center; gap:10px; padding:12px 18px; border-radius:10px; font-weight:700; font-size:.95em; }
        .warranty-tile.expired  { background:#fce4ec; color:#c62828; border:1.5px solid #ef9a9a; }
        .warranty-tile.expiring { background:#fff3e0; color:#e65100; border:1.5px solid #ffcc02; }
        /* Bulk bar */
        .bulk-bar { display:flex; align-items:center; gap:10px; padding:10px 14px; background:#f8fbe9; border:1.5px solid #cde8a0; border-radius:10px; margin-bottom:14px; flex-wrap:wrap; }
        .bulk-bar select { padding:7px 10px; border:1.5px solid #cde8a0; border-radius:7px; font-size:.9em; font-family:'Roboto',sans-serif; background:#fff; outline:none; }
        /* Table */
        .table-wrap { overflow-x:auto; border-radius:10px; }
        table { width:100%; border-collapse:collapse; font-size:.93em; }
        thead tr { background:#99d930; color:#274606; }
        thead th { padding:11px 13px; text-align:left; font-weight:900; font-size:.83em; text-transform:uppercase; letter-spacing:.4px; white-space:nowrap; }
        thead th.cb { width:32px; text-align:center; }
        tbody tr { border-bottom:1px solid #e8f5c8; transition:background .13s; }
        tbody tr:hover { background:#f4fce6; }
        tbody tr.selected-row { background:#edfae5; }
        tbody tr:last-child { border-bottom:none; }
        td { padding:9px 13px; color:#333; vertical-align:middle; }
        td.cb { text-align:center; }
        td.actions { white-space:nowrap; display:flex; gap:5px; align-items:center; }
        tfoot td { padding:11px 13px; font-weight:900; background:#f4fce6; border-top:2px solid #99d930; color:#274606; }
        .badge { display:inline-block; padding:3px 10px; border-radius:20px; font-size:.8em; font-weight:700; }
        .badge-count { background:#99d930; color:#274606; border-radius:20px; padding:2px 12px; font-size:.85em; font-weight:700; margin-left:10px; }
        .badge-expired  { background:#fce4ec; color:#c62828; }
        .badge-expiring { background:#fff3e0; color:#e65100; }
        .empty-state { text-align:center; color:#888; padding:38px 0; font-size:1.05em; }
        .back-link { display:inline-flex; align-items:center; gap:6px; margin-bottom:18px; font-weight:700; color:#274606; text-decoration:none; font-size:.97em; }
        .back-link:hover { color:#99d930; }
        /* Log table */
        .log-table { width:100%; border-collapse:collapse; font-size:.88em; }
        .log-table th { background:#1a1a1a; color:#fff; padding:8px 12px; text-align:left; font-size:.82em; text-transform:uppercase; letter-spacing:.4px; }
        .log-table td { padding:8px 12px; border-bottom:1px solid #f0f0f0; vertical-align:middle; }
        .log-table tr:last-child td { border-bottom:none; }
        /* Asset name link */
        .asset-name-link { color:#274606; font-weight:700; text-decoration:none; cursor:pointer; border-bottom:1.5px dashed #99d930; }
        .asset-name-link:hover { color:#85c220; }
        /* View button */
        .btn-view { background:#f0f4ff; color:#1565c0; border:1.5px solid #90caf9; padding:6px 10px; font-size:.82em; }
        .btn-view:hover { background:#e3eeff; }
        /* Modal */
        .modal-overlay { display:none; position:fixed; inset:0; background:rgba(0,0,0,.45); z-index:9000; align-items:center; justify-content:center; padding:16px; }
        .modal-overlay.open { display:flex; }
        .modal-box { background:#fff; border-radius:18px; box-shadow:0 12px 60px rgba(0,0,0,.22); max-width:720px; width:100%; max-height:90vh; overflow-y:auto; border:2px solid #99d930; }
        .modal-header { display:flex; align-items:center; justify-content:space-between; padding:20px 26px 14px; border-bottom:1.5px solid #e8f5c8; }
        .modal-header h3 { margin:0; font-size:1.25em; color:#274606; font-weight:900; }
        .modal-close { background:none; border:none; font-size:1.5em; cursor:pointer; color:#888; line-height:1; padding:0 4px; }
        .modal-close:hover { color:#c00; }
        .modal-body { padding:22px 26px; }
        .modal-photo { width:100%; max-height:220px; object-fit:contain; border-radius:10px; border:1.5px solid #cde8a0; margin-bottom:18px; }
        .modal-grid { display:grid; grid-template-columns:1fr 1fr; gap:12px 24px; margin-bottom:18px; }
        .modal-field { display:flex; flex-direction:column; gap:3px; }
        .modal-field label { font-size:.75em; font-weight:700; color:#274606; text-transform:uppercase; letter-spacing:.5px; }
        .modal-field span { font-size:.95em; color:#222; }
        .modal-text-block { margin-bottom:14px; }
        .modal-text-block label { display:block; font-size:.75em; font-weight:700; color:#274606; text-transform:uppercase; letter-spacing:.5px; margin-bottom:5px; }
        .modal-text-block p { margin:0; font-size:.95em; color:#444; line-height:1.55; background:#f8fbe9; border:1.5px solid #e8f5c8; border-radius:8px; padding:10px 13px; white-space:pre-wrap; }
        .modal-actions { display:flex; gap:10px; padding:14px 26px 20px; border-top:1.5px solid #e8f5c8; flex-wrap:wrap; }
        @media(max-width:720px){ .form-grid{grid-template-columns:1fr;} .card{padding:18px 14px;} .banner-title{font-size:2em;} .modal-grid{grid-template-columns:1fr;} }
    </style>
</head>
<body>
<?php include 'show-navbar.php'; show_navbar(); ?>

<div class="banner-wrapper">
    <img src="images/banner_images/Admin/block-pattern.jpg" alt="Admin banner">
    <h1 class="banner-title">Assets</h1>
</div>

<div class="page-wrap">

    <a href="administration.php" class="back-link">&#8592; Back to Administration</a>

    <?php if ($message): ?>
        <div class="flash <?= htmlspecialchars($message_type) ?>" style="display:flex;align-items:center;justify-content:space-between;gap:16px;">
            <span><?= htmlspecialchars($message) ?></span>
            <a href="admin_assets.php#asset-list" class="btn btn-green" style="white-space:nowrap;padding:7px 18px;font-size:.88em;">&#8592; Back to Asset List</a>
        </div>
    <?php endif; ?>


    <!-- ── ADD / EDIT FORM ── -->
    <div class="card">
        <div class="form-card-header" onclick="toggleForm()">
            <h2><?= ($edit_asset && !empty($edit_asset['id'])) ? '✏️ Edit Asset' : ($edit_asset ? '📋 Duplicate Asset — Review &amp; Save' : '➕ Add New Asset') ?></h2>
            <span class="chevron" id="form-chevron">▼</span>
        </div>
        <div class="form-collapse" id="form-collapse"><div class="form-collapse-inner">
        <form method="POST" action="admin_assets.php<?= ($edit_asset && !empty($edit_asset['id'])) ? '?edit=' . intval($edit_asset['id']) : '' ?>" enctype="multipart/form-data">
            <input type="hidden" name="action" value="<?= ($edit_asset && !empty($edit_asset['id'])) ? 'edit' : 'add' ?>">
            <?php if ($edit_asset && !empty($edit_asset['id'])): ?>
                <input type="hidden" name="asset_id" value="<?= intval($edit_asset['id']) ?>">
            <?php endif; ?>
            <div class="form-grid">
                <div class="form-group">
                    <label>Asset Name <span style="color:#c00">*</span></label>
                    <input type="text" name="name" required maxlength="150" value="<?= htmlspecialchars($edit_asset['name'] ?? '') ?>" placeholder="e.g. MacBook Pro 14&quot;">
                </div>
                <div class="form-group">
                    <label>Type <span style="color:#c00">*</span></label>
                    <select name="asset_type">
                        <option value="Physical" <?= ($edit_asset['asset_type'] ?? 'Physical') === 'Physical' ? 'selected' : '' ?>>Physical</option>
                        <option value="Digital"  <?= ($edit_asset['asset_type'] ?? '') === 'Digital' ? 'selected' : '' ?>>Digital</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Category</label>
                    <input type="text" name="category" maxlength="100" list="category-list" value="<?= htmlspecialchars($edit_asset['category'] ?? '') ?>" placeholder="e.g. Computer, Furniture, Software License">
                </div>
                <div class="form-group">
                    <label>Serial / License #</label>
                    <input type="text" name="serial_number" maxlength="100" value="<?= htmlspecialchars($edit_asset['serial_number'] ?? '') ?>" placeholder="e.g. SN-XYZ12345">
                </div>
                <div class="form-group">
                    <label>Purchase Date</label>
                    <input type="date" name="purchase_date" value="<?= htmlspecialchars($edit_asset['purchase_date'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>Purchase Price ($)</label>
                    <input type="number" name="purchase_price" min="0" step="0.01" value="<?= htmlspecialchars($edit_asset['purchase_price'] ?? '') ?>" placeholder="0.00">
                </div>
                <div class="form-group">
                    <label>Condition</label>
                    <select name="condition_status">
                        <?php foreach (['New','Good','Fair','Poor','Retired'] as $c): ?>
                        <option value="<?= $c ?>" <?= ($edit_asset['condition_status'] ?? 'Good') === $c ? 'selected' : '' ?>><?= $c ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Location</label>
                    <input type="text" name="location" maxlength="150" value="<?= htmlspecialchars($edit_asset['location'] ?? '') ?>" placeholder="e.g. Main Office, Storage Room">
                </div>
                <div class="form-group">
                    <label>Assigned To</label>
                    <select name="assigned_to">
                        <option value="">— Unassigned —</option>
                        <?php foreach ($user_names as $un):
                            $uname = htmlspecialchars($un['full_name']);
                            $sel   = ($edit_asset['assigned_to'] ?? '') === $un['full_name'] ? 'selected' : '';
                        ?>
                        <option value="<?= $uname ?>" <?= $sel ?>><?= $uname ?></option>
                        <?php endforeach; ?>
                        <?php
                        // If the current value is not in the users list, show it as an option
                        $cur = $edit_asset['assigned_to'] ?? '';
                        $in_list = $cur === '' || in_array($cur, array_column($user_names, 'full_name'));
                        if (!$in_list): ?>
                        <option value="<?= htmlspecialchars($cur) ?>" selected><?= htmlspecialchars($cur) ?></option>
                        <?php endif; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Warranty Expiry</label>
                    <input type="date" name="warranty_expiry" value="<?= htmlspecialchars($edit_asset['warranty_expiry'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>Status</label>
                    <select name="status">
                        <?php foreach (['Active','Inactive','Disposed'] as $s): ?>
                        <option value="<?= $s ?>" <?= ($edit_asset['status'] ?? 'Active') === $s ? 'selected' : '' ?>><?= $s ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group full-width">
                    <label>Description</label>
                    <textarea name="description" placeholder="Brief description…"><?= htmlspecialchars($edit_asset['description'] ?? '') ?></textarea>
                </div>
                <div class="form-group full-width">
                    <label>Notes</label>
                    <textarea name="notes" placeholder="Any additional notes…"><?= htmlspecialchars($edit_asset['notes'] ?? '') ?></textarea>
                </div>
                <div class="form-group full-width">
                    <label>Photo / Image</label>
                    <?php if (!empty($edit_asset['photo_path'])): ?>
                    <div style="margin-bottom:8px;display:flex;align-items:center;gap:14px;flex-wrap:wrap;">
                        <img src="<?= htmlspecialchars($edit_asset['photo_path']) ?>" alt="Asset photo" style="max-height:110px;max-width:180px;border-radius:8px;border:1.5px solid #cde8a0;object-fit:cover;">
                        <label style="display:inline-flex;align-items:center;gap:6px;font-weight:400;text-transform:none;letter-spacing:0;font-size:.9em;cursor:pointer;color:#c62828;">
                            <input type="checkbox" name="remove_photo" value="1"> Remove current photo
                        </label>
                    </div>
                    <?php endif; ?>
                    <input type="file" name="photo" accept="image/*" style="padding:6px;">
                    <small style="color:#888;font-size:.82em;">JPEG, PNG, GIF or WebP · max 5 MB · Leave blank to keep existing photo.</small>
                </div>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-green">
                    <?= ($edit_asset && !empty($edit_asset['id'])) ? '💾 Save Changes' : '➕ Add Asset' ?>
                </button>
                <?php if ($edit_asset): ?>
                    <a href="admin_assets.php#asset-list" class="btn btn-outline">✕ Cancel</a>
                <?php endif; ?>
            </div>
        </form>
        </div></div><!-- /form-collapse -->
    </div>

    <!-- ── ASSET LIST ── -->
    <div class="card" id="asset-list">
        <h2 style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;">
            <span>All Assets <span class="badge-count"><?= count($assets) ?></span></span>
            <span style="display:flex;gap:10px;flex-wrap:wrap;">
                <a href="admin_assets_report.php" class="btn btn-outline" style="font-size:.88em;padding:7px 16px;">📊 View Report</a>
                <a href="admin_assets.php?export=csv" class="btn btn-green" style="font-size:.88em;padding:7px 16px;">⬇ Export CSV</a>
                <a href="admin_assets.php?export=log_csv" class="btn btn-outline" style="font-size:.88em;padding:7px 16px;">📋 Export Log</a>
            </span>
        </h2>

        <!-- Warranty alert banner -->
        <?php if ($warranty_expired > 0 || $warranty_expiring > 0): ?>
        <div class="warranty-alert">
            <?php if ($warranty_expired > 0): ?>
            <div class="warranty-tile expired">
                ⚠️ <?= $warranty_expired ?> asset<?= $warranty_expired > 1 ? 's' : '' ?> with <strong>expired</strong> warranty
            </div>
            <?php endif; ?>
            <?php if ($warranty_expiring > 0): ?>
            <div class="warranty-tile expiring">
                🕐 <?= $warranty_expiring ?> asset<?= $warranty_expiring > 1 ? 's' : '' ?> warranty expiring <strong>within 90 days</strong>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- Search + Filters -->
        <form method="GET" action="admin_assets.php#asset-list" id="filter-form">
            <div class="search-filter-bar">
                <input type="text" name="search" placeholder="Search name, category, location, serial #, assigned to…" value="<?= htmlspecialchars($search) ?>">
                <select name="filter_type" onchange="this.form.submit()" class="<?= $filter_type ? 'active-filter' : '' ?>">
                    <option value="">All Types</option>
                    <option value="Physical" <?= $filter_type === 'Physical' ? 'selected' : '' ?>>Physical</option>
                    <option value="Digital"  <?= $filter_type === 'Digital'  ? 'selected' : '' ?>>Digital</option>
                </select>
                <select name="filter_status" onchange="this.form.submit()" class="<?= $filter_status ? 'active-filter' : '' ?>">
                    <option value="">All Statuses</option>
                    <?php foreach (['Active','Inactive','Disposed'] as $s): ?>
                    <option value="<?= $s ?>" <?= $filter_status === $s ? 'selected' : '' ?>><?= $s ?></option>
                    <?php endforeach; ?>
                </select>
                <select name="filter_condition" onchange="this.form.submit()" class="<?= $filter_condition ? 'active-filter' : '' ?>">
                    <option value="">All Conditions</option>
                    <?php foreach (['New','Good','Fair','Poor','Retired'] as $c): ?>
                    <option value="<?= $c ?>" <?= $filter_condition === $c ? 'selected' : '' ?>><?= $c ?></option>
                    <?php endforeach; ?>
                </select>
                <select name="filter_assigned" onchange="this.form.submit()" class="<?= $filter_assigned ? 'active-filter' : '' ?>">
                    <option value="">All Assignees</option>
                    <?php foreach ($assigned_options as $ao): ?>
                    <option value="<?= htmlspecialchars($ao['assigned_to']) ?>" <?= $filter_assigned === $ao['assigned_to'] ? 'selected' : '' ?>><?= htmlspecialchars($ao['assigned_to']) ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="btn btn-green">🔍 Search</button>
                <?php if ($active_filters): ?>
                    <a href="admin_assets.php#asset-list" class="btn btn-outline">✕ Clear</a>
                    <span style="font-size:.85em;color:#888;align-self:center;"><?= count($assets) ?> result<?= count($assets) !== 1 ? 's' : '' ?></span>
                <?php endif; ?>
                <?php if ($sort !== 'id' || $dir !== 'ASC'): ?>
                    <input type="hidden" name="sort" value="<?= htmlspecialchars($sort) ?>">
                    <input type="hidden" name="dir"  value="<?= htmlspecialchars($dir) ?>">
                <?php endif; ?>
            </div>
        </form>

        <!-- Bulk action bar -->
        <form method="POST" action="admin_assets.php#asset-list" id="bulk-form">
            <input type="hidden" name="action" id="bulk-action-input" value="">
            <div class="bulk-bar">
                <strong style="font-size:.9em;color:#274606;">Bulk Action:</strong>
                <select id="bulk-action-select" onchange="updateBulkUI()">
                    <option value="">— Choose Action —</option>
                    <option value="bulk_status">Set Status</option>
                    <option value="bulk_condition">Set Condition</option>
                    <option value="bulk_delete">&#128465; Delete Selected</option>
                </select>
                <select name="bulk_new_status" id="bulk-status-select" style="display:none;">
                    <option value="">— Choose Status —</option>
                    <option value="Active">Active</option>
                    <option value="Inactive">Inactive</option>
                    <option value="Disposed">Disposed</option>
                </select>
                <select name="bulk_new_condition" id="bulk-condition-select" style="display:none;">
                    <option value="">— Choose Condition —</option>
                    <option value="New">New</option>
                    <option value="Good">Good</option>
                    <option value="Fair">Fair</option>
                    <option value="Poor">Poor</option>
                    <option value="Retired">Retired</option>
                </select>
                <button type="submit" class="btn btn-green" id="bulk-apply-btn" style="padding:7px 18px;font-size:.88em;display:none;"
                        onclick="return collectBulkIds()">Apply to Selected</button>
                <span id="selected-count" style="font-size:.85em;color:#888;"></span>
                <a id="clear-selection-btn" onclick="clearSelection()" style="display:none;font-size:.85em;color:#888;cursor:pointer;text-decoration:underline;">✕ Clear selected</a>
            </div>

            <div class="table-wrap">
                <?php if (empty($assets)): ?>
                    <div class="empty-state">No assets found<?= $active_filters ? ' matching your filters' : '' ?>.</div>
                <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th class="cb"><input type="checkbox" id="check-all" title="Select all"></th>
                            <th><?= sort_link('id','#',$sort,$dir,$search,$filters_arr) ?></th>
                            <th><?= sort_link('photo_path','Photo',$sort,$dir,$search,$filters_arr) ?></th>
                            <th><?= sort_link('name','Name',$sort,$dir,$search,$filters_arr) ?></th>
                            <th><?= sort_link('asset_type','Type',$sort,$dir,$search,$filters_arr) ?></th>
                            <th><?= sort_link('category','Category',$sort,$dir,$search,$filters_arr) ?></th>
                            <th><?= sort_link('serial_number','Serial #',$sort,$dir,$search,$filters_arr) ?></th>
                            <th><?= sort_link('purchase_date','Purchase Date',$sort,$dir,$search,$filters_arr) ?></th>
                            <th><?= sort_link('purchase_price','Price',$sort,$dir,$search,$filters_arr) ?></th>
                            <th><?= sort_link('condition_status','Condition',$sort,$dir,$search,$filters_arr) ?></th>
                            <th><?= sort_link('location','Location',$sort,$dir,$search,$filters_arr) ?></th>
                            <th><?= sort_link('assigned_to','Assigned To',$sort,$dir,$search,$filters_arr) ?></th>
                            <th><?= sort_link('warranty_expiry','Warranty',$sort,$dir,$search,$filters_arr) ?></th>
                            <th><?= sort_link('status','Status',$sort,$dir,$search,$filters_arr) ?></th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($assets as $a):
                        $cc = $condition_colours[$a['condition_status']] ?? ['bg'=>'#eee','color'=>'#333'];
                        $sc = $status_colours[$a['status']]             ?? ['bg'=>'#eee','color'=>'#333'];
                        // Warranty
                        $w_html = '<span style="color:#bbb;">—</span>';
                        if (!empty($a['warranty_expiry'])) {
                            $days  = (strtotime($a['warranty_expiry']) - strtotime($today)) / 86400;
                            $efmt  = date('M j, Y', strtotime($a['warranty_expiry']));
                            if      ($days < 0)    $w_html = '<span class="badge badge-expired">Exp. ' . $efmt . '</span>';
                            elseif  ($days <= 90)  $w_html = '<span class="badge badge-expiring">Exp. ' . $efmt . '</span>';
                            else                   $w_html = '<span style="font-size:.85em;color:#555;">' . $efmt . '</span>';
                        }
                    ?>
                        <tr id="row-<?= $a['id'] ?>" data-asset="<?= htmlspecialchars(json_encode($a), ENT_QUOTES) ?>">
                            <td class="cb"><input type="checkbox" class="row-cb" value="<?= $a['id'] ?>"></td>
                            <td><?= intval($a['id']) ?></td>
                            <td>
                                <?php if (!empty($a['photo_path'])): ?>
                                <a href="<?= htmlspecialchars($a['photo_path']) ?>" target="_blank" title="View photo">
                                    <img src="<?= htmlspecialchars($a['photo_path']) ?>" alt="Photo" style="height:38px;width:38px;object-fit:cover;border-radius:6px;border:1px solid #cde8a0;display:block;">
                                </a>
                                <?php else: ?><span style="color:#ccc;">—</span><?php endif; ?>
                            </td>
                            <td><a class="asset-name-link" onclick="openAssetModal(this)" title="View details"><?= htmlspecialchars($a['name']) ?></a></td>
                            <td><?= htmlspecialchars($a['asset_type']) ?></td>
                            <td><?= $a['category'] ? htmlspecialchars($a['category']) : '<span style="color:#bbb;">—</span>' ?></td>
                            <td style="font-family:monospace;font-size:.85em;"><?= $a['serial_number'] ? htmlspecialchars($a['serial_number']) : '<span style="color:#bbb;">—</span>' ?></td>
                            <td style="white-space:nowrap;"><?= $a['purchase_date'] ? date('M j, Y', strtotime($a['purchase_date'])) : '<span style="color:#bbb;">—</span>' ?></td>
                            <td style="white-space:nowrap;font-weight:700;"><?= $a['purchase_price'] !== null ? '$' . number_format($a['purchase_price'], 2) : '<span style="color:#bbb;">—</span>' ?></td>
                            <td><span class="badge" style="background:<?= $cc['bg'] ?>;color:<?= $cc['color'] ?>;"><?= htmlspecialchars($a['condition_status']) ?></span></td>
                            <td><?= $a['location']    ? htmlspecialchars($a['location'])    : '<span style="color:#bbb;">—</span>' ?></td>
                            <td><?= $a['assigned_to'] ? htmlspecialchars($a['assigned_to']) : '<span style="color:#bbb;">—</span>' ?></td>
                            <td><?= $w_html ?></td>
                            <td><span class="badge" style="background:<?= $sc['bg'] ?>;color:<?= $sc['color'] ?>;"><?= htmlspecialchars($a['status']) ?></span></td>
                            <td class="actions">
                                <a class="btn btn-view" onclick="openAssetModal(this)" title="View details">👁</a>
                                <a href="admin_assets.php?edit=<?= $a['id'] ?>" class="btn btn-edit">✏️</a>
                                <a href="admin_assets.php?duplicate=<?= $a['id'] ?>#asset-list"
                                   class="btn btn-copy" title="Duplicate">⧉</a>
                                <a href="admin_assets.php?delete_asset=<?= $a['id'] ?>#asset-list"
                                   class="btn btn-danger" title="Delete"
                                   onclick="return confirm('Delete \'<?= htmlspecialchars(addslashes($a['name'])) ?>\'? This cannot be undone.')">🗑</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="8" style="text-align:right;">Total (<?= count($assets) ?> asset<?= count($assets) !== 1 ? 's' : '' ?>):</td>
                            <td style="white-space:nowrap;">$<?= number_format($total_value, 2) ?></td>
                            <td colspan="6"></td>
                        </tr>
                    </tfoot>
                </table>
                <?php endif; ?>
            </div>
        </form><!-- /bulk-form -->
    </div>

    <!-- ── RECENT ACTIVITY LOG ── -->
    <?php if (!empty($recent_logs)): ?>
    <div class="card">
        <h2 style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;">
            <span>Recent Activity</span>
            <a href="admin_assets.php?export=log_csv" class="btn btn-outline" style="font-size:.85em;padding:6px 14px;">📋 Export Full Log</a>
        </h2>
        <div class="table-wrap">
            <table class="log-table">
                <thead><tr><th>Date / Time</th><th>Action</th><th>Asset</th><th>Changed By</th><th>Details</th></tr></thead>
                <tbody>
                <?php foreach ($recent_logs as $log):
                    $lc = $log_colours[$log['action']] ?? ['bg'=>'#eee','color'=>'#333'];
                ?>
                <tr>
                    <td style="white-space:nowrap;color:#888;font-size:.85em;"><?= date('M j, Y g:i a', strtotime($log['created_at'])) ?></td>
                    <td><span class="badge" style="background:<?= $lc['bg'] ?>;color:<?= $lc['color'] ?>;"><?= htmlspecialchars($log['action']) ?></span></td>
                    <td><strong><?= htmlspecialchars($log['asset_name']) ?></strong></td>
                    <td><?= htmlspecialchars($log['changed_by'] ?? '—') ?></td>
                    <td style="color:#666;font-size:.88em;"><?= htmlspecialchars($log['details'] ?? '—') ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

</div>

<script>
var _formOpen = <?= ($edit_asset || $message_type === 'error') ? 'true' : 'false' ?>;

// ── Collapsible form ────────────────────────────────────────────
function toggleForm(forceOpen) {
    const col  = document.getElementById('form-collapse');
    const chev = document.getElementById('form-chevron');
    const open = forceOpen !== undefined ? forceOpen : !col.classList.contains('open');
    col.classList.toggle('open', open);
    chev.style.transform = open ? 'rotate(180deg)' : '';
}
if (_formOpen) toggleForm(true);

// Select-all checkbox
document.getElementById('check-all').addEventListener('change', function() {
    document.querySelectorAll('.row-cb').forEach(cb => {
        cb.checked = this.checked;
        cb.closest('tr').classList.toggle('selected-row', this.checked);
    });
    updateCount();
});
document.querySelectorAll('.row-cb').forEach(cb => {
    cb.addEventListener('change', function() {
        this.closest('tr').classList.toggle('selected-row', this.checked);
        updateCount();
    });
});
function updateCount() {
    const n = document.querySelectorAll('.row-cb:checked').length;
    document.getElementById('selected-count').textContent = n > 0 ? n + ' selected' : '';
    document.getElementById('clear-selection-btn').style.display = n > 0 ? 'inline' : 'none';
}
function clearSelection() {
    document.getElementById('check-all').checked = false;
    document.querySelectorAll('.row-cb').forEach(cb => { cb.checked = false; cb.closest('tr').classList.remove('selected-row'); });
    updateCount();
}
function updateBulkUI() {
    const action = document.getElementById('bulk-action-select').value;
    document.getElementById('bulk-status-select').style.display    = action === 'bulk_status'    ? '' : 'none';
    document.getElementById('bulk-condition-select').style.display = action === 'bulk_condition' ? '' : 'none';
    document.getElementById('bulk-apply-btn').style.display        = action ? '' : 'none';
    if (action === 'bulk_delete') {
        document.getElementById('bulk-apply-btn').textContent = 'Delete Selected';
        document.getElementById('bulk-apply-btn').style.background = '#e53935';
        document.getElementById('bulk-apply-btn').style.color = '#fff';
    } else {
        document.getElementById('bulk-apply-btn').textContent = 'Apply to Selected';
        document.getElementById('bulk-apply-btn').style.background = '';
        document.getElementById('bulk-apply-btn').style.color = '';
    }
}
function collectBulkIds() {
    const ids = [...document.querySelectorAll('.row-cb:checked')].map(cb => cb.value);
    if (!ids.length) { alert('Please select at least one asset.'); return false; }
    const action = document.getElementById('bulk-action-select').value;
    if (!action) { alert('Please choose a bulk action.'); return false; }
    if (action === 'bulk_status' && !document.getElementById('bulk-status-select').value) {
        alert('Please choose a status.'); return false;
    }
    if (action === 'bulk_condition' && !document.getElementById('bulk-condition-select').value) {
        alert('Please choose a condition.'); return false;
    }
    if (action === 'bulk_delete') {
        if (!confirm('Delete ' + ids.length + ' selected asset(s)? This cannot be undone.')) return false;
    }
    document.getElementById('bulk-action-input').value = action;
    ids.forEach(id => {
        const inp = document.createElement('input');
        inp.type = 'hidden'; inp.name = 'bulk_ids[]'; inp.value = id;
        document.getElementById('bulk-form').appendChild(inp);
    });
    return true;
}

// ── Asset Detail Modal ──────────────────────────────────────────
function openAssetModal(el) {
    const row  = el.closest('tr');
    const a    = JSON.parse(row.dataset.asset);
    const dash = '—';

    document.getElementById('modal-title').textContent = a.name;

    // Photo
    const photo = document.getElementById('modal-photo');
    if (a.photo_path) { photo.src = a.photo_path; photo.style.display = 'block'; }
    else              { photo.style.display = 'none'; }

    // Fields
    document.getElementById('modal-type').textContent        = a.asset_type || dash;
    document.getElementById('modal-category').textContent    = a.category    || dash;
    document.getElementById('modal-serial').textContent      = a.serial_number || dash;
    document.getElementById('modal-purchase-date').textContent = a.purchase_date ? new Date(a.purchase_date + 'T00:00:00').toLocaleDateString('en-US',{year:'numeric',month:'short',day:'numeric'}) : dash;
    document.getElementById('modal-price').textContent       = a.purchase_price !== null && a.purchase_price !== '' ? '$' + parseFloat(a.purchase_price).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g,',') : dash;
    document.getElementById('modal-condition').textContent   = a.condition_status || dash;
    document.getElementById('modal-location').textContent    = a.location    || dash;
    document.getElementById('modal-assigned').textContent    = a.assigned_to || dash;
    document.getElementById('modal-warranty').textContent    = a.warranty_expiry ? new Date(a.warranty_expiry + 'T00:00:00').toLocaleDateString('en-US',{year:'numeric',month:'short',day:'numeric'}) : dash;
    document.getElementById('modal-status').textContent      = a.status      || dash;
    document.getElementById('modal-date-created').textContent = a.date_created ? new Date(a.date_created).toLocaleDateString('en-US',{year:'numeric',month:'short',day:'numeric'}) : dash;

    // Description & Notes (hide block if empty)
    const descBlock = document.getElementById('modal-desc-block');
    if (a.description && a.description.trim()) {
        document.getElementById('modal-description').textContent = a.description;
        descBlock.style.display = 'block';
    } else { descBlock.style.display = 'none'; }

    const notesBlock = document.getElementById('modal-notes-block');
    if (a.notes && a.notes.trim()) {
        document.getElementById('modal-notes').textContent = a.notes;
        notesBlock.style.display = 'block';
    } else { notesBlock.style.display = 'none'; }

    document.getElementById('modal-edit-btn').href = 'admin_assets.php?edit=' + a.id;
    document.getElementById('asset-modal').classList.add('open');
    document.body.style.overflow = 'hidden';
}
function closeAssetModal() {
    document.getElementById('asset-modal').classList.remove('open');
    document.body.style.overflow = '';
}
document.addEventListener('keydown', e => { if (e.key === 'Escape') closeAssetModal(); });

</script>

<!-- ── ASSET DETAIL MODAL ── -->
<div class="modal-overlay" id="asset-modal" onclick="if(event.target===this)closeAssetModal()">
    <div class="modal-box">
        <div class="modal-header">
            <h3 id="modal-title">Asset Details</h3>
            <button class="modal-close" onclick="closeAssetModal()" title="Close">&times;</button>
        </div>
        <div class="modal-body">
            <img id="modal-photo" class="modal-photo" src="" alt="Asset photo" style="display:none;">
            <div class="modal-grid">
                <div class="modal-field"><label>Type</label><span id="modal-type"></span></div>
                <div class="modal-field"><label>Category</label><span id="modal-category"></span></div>
                <div class="modal-field"><label>Serial / License #</label><span id="modal-serial"></span></div>
                <div class="modal-field"><label>Purchase Date</label><span id="modal-purchase-date"></span></div>
                <div class="modal-field"><label>Purchase Price</label><span id="modal-price"></span></div>
                <div class="modal-field"><label>Condition</label><span id="modal-condition"></span></div>
                <div class="modal-field"><label>Location</label><span id="modal-location"></span></div>
                <div class="modal-field"><label>Assigned To</label><span id="modal-assigned"></span></div>
                <div class="modal-field"><label>Warranty Expiry</label><span id="modal-warranty"></span></div>
                <div class="modal-field"><label>Status</label><span id="modal-status"></span></div>
                <div class="modal-field"><label>Date Added</label><span id="modal-date-created"></span></div>
            </div>
            <div class="modal-text-block" id="modal-desc-block">
                <label>Description</label>
                <p id="modal-description"></p>
            </div>
            <div class="modal-text-block" id="modal-notes-block">
                <label>Notes</label>
                <p id="modal-notes"></p>
            </div>
        </div>
        <div class="modal-actions">
            <a id="modal-edit-btn" href="#" class="btn btn-edit">✏️ Edit</a>
            <button class="btn btn-outline" onclick="closeAssetModal()">Close</button>
        </div>
    </div>
</div>

<!-- Datalists for form autocomplete -->
<datalist id="category-list">
    <?php foreach ($cat_options as $co): ?>
    <option value="<?= htmlspecialchars($co['category']) ?>">
    <?php endforeach; ?>
</datalist>
<datalist id="users-list">
    <?php foreach ($user_names as $un): ?>
    <option value="<?= htmlspecialchars($un['full_name']) ?>">
    <?php endforeach; ?>
</datalist>

<?php include 'footer.php'; ?>
</body>
</html>
