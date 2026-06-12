<?php
// API endpoint to get reports with filters
require_once '../config/db.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'error' => 'Please login to view reports']);
    exit();
}

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'] ?? 'user';

$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
$offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
$status = isset($_GET['status']) ? $_GET['status'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';
$report_type = isset($_GET['type']) ? $_GET['type'] : '';

$query = "SELECT r.*, u.username, u.email, 
          (SELECT COUNT(*) FROM claims c WHERE c.report_id = r.id) as claim_count,
          (SELECT COUNT(*) FROM report_views v WHERE v.report_id = r.id) as view_count
          FROM reports r 
          LEFT JOIN users u ON r.user_id = u.id 
          WHERE 1=1";

$params = [];
$types = "";

if ($user_role !== 'admin' && $user_role !== 'moderator') {
    $query .= " AND r.status = 'approved'";
}

if (!empty($status)) {
    $query .= " AND r.status = ?";
    $params[] = $status;
    $types .= "s";
}

if (!empty($search)) {
    $query .= " AND (r.title LIKE ? OR r.description LIKE ? OR r.item_type LIKE ?)";
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $types .= "sss";
}

if (!empty($report_type)) {
    $query .= " AND r.report_type = ?";
    $params[] = $report_type;
    $types .= "s";
}

$query .= " ORDER BY r.created_at DESC LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;
$types .= "ii";

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

$reports = [];
while ($row = $result->fetch_assoc()) {
    $reports[] = $row;
}

// Get total count
$countQuery = "SELECT COUNT(*) as total FROM reports r WHERE 1=1";
if ($user_role !== 'admin' && $user_role !== 'moderator') {
    $countQuery .= " AND r.status = 'approved'";
}
$countResult = $conn->query($countQuery);
$total = $countResult->fetch_assoc()['total'];

echo json_encode([
    'success' => true,
    'data' => $reports,
    'total' => $total,
    'limit' => $limit,
    'offset' => $offset
]);
?>