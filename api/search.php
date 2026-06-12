<?php
// API endpoint for searching reports and items
require_once '../config/db.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'error' => 'Please login to search']);
    exit();
}

$query = isset($_GET['q']) ? $_GET['q'] : '';
$category = isset($_GET['category']) ? $_GET['category'] : 'all';
$location = isset($_GET['location']) ? $_GET['location'] : '';
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';

if (empty($query) && empty($location)) {
    echo json_encode(['success' => false, 'error' => 'Please enter a search term']);
    exit();
}

$searchQuery = "SELECT r.*, u.username, u.phone,
                MATCH(r.title, r.description) AGAINST(? IN BOOLEAN MODE) as relevance
                FROM reports r 
                LEFT JOIN users u ON r.user_id = u.id 
                WHERE (r.title LIKE ? OR r.description LIKE ? OR r.item_type LIKE ?)";

$params = [];
$searchTerm = "%$query%";
$params[] = $query;
$params[] = $searchTerm;
$params[] = $searchTerm;
$params[] = $searchTerm;
$types = "ssss";

if ($category !== 'all') {
    $searchQuery .= " AND r.report_type = ?";
    $params[] = $category;
    $types .= "s";
}

if (!empty($location)) {
    $searchQuery .= " AND (r.location LIKE ? OR r.city LIKE ? OR r.area LIKE ?)";
    $locationTerm = "%$location%";
    $params[] = $locationTerm;
    $params[] = $locationTerm;
    $params[] = $locationTerm;
    $types .= "sss";
}

if (!empty($date_from)) {
    $searchQuery .= " AND DATE(r.incident_date) >= ?";
    $params[] = $date_from;
    $types .= "s";
}

if (!empty($date_to)) {
    $searchQuery .= " AND DATE(r.incident_date) <= ?";
    $params[] = $date_to;
    $types .= "s";
}

$searchQuery .= " AND r.status = 'approved' ORDER BY relevance DESC, r.created_at DESC LIMIT 100";

$stmt = $conn->prepare($searchQuery);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

$results = [];
while ($row = $result->fetch_assoc()) {
    $results[] = $row;
}

// Also search shops if category is shops or all
$shops = [];
if ($category === 'all' || $category === 'shops') {
    $shopQuery = "SELECT id, shop_name, description, address, phone, email, verified 
                  FROM shops 
                  WHERE shop_name LIKE ? OR description LIKE ?";
    $shopStmt = $conn->prepare($shopQuery);
    $shopStmt->bind_param("ss", $searchTerm, $searchTerm);
    $shopStmt->execute();
    $shopResult = $shopStmt->get_result();
    while ($row = $shopResult->fetch_assoc()) {
        $row['type'] = 'shop';
        $shops[] = $row;
    }
}

echo json_encode([
    'success' => true,
    'data' => [
        'reports' => $results,
        'shops' => $shops,
        'total' => count($results) + count($shops)
    ],
    'search_term' => $query
]);
?>