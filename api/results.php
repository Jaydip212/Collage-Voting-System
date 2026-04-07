<?php
// api/results.php – Live vote count JSON endpoint
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

$electionId = (int)($_GET['election_id'] ?? 0);

if (!$electionId) {
    echo json_encode(['error' => 'Election ID required']);
    exit;
}

// Get election info
$stmt = $pdo->prepare("SELECT * FROM elections WHERE id=?");
$stmt->execute([$electionId]);
$election = $stmt->fetch();

if (!$election) {
    echo json_encode(['error' => 'Election not found']);
    exit;
}

// Get candidates with vote counts
$stmt = $pdo->prepare("
    SELECT c.id, c.name, c.photo, c.position, d.name as dept,
        COUNT(v.vote_id) as total_votes
    FROM candidates c
    LEFT JOIN votes v ON v.candidate_id=c.id AND v.election_id=c.election_id
    LEFT JOIN departments d ON c.department_id=d.id
    WHERE c.election_id=? AND c.status='approved'
    GROUP BY c.id
    ORDER BY total_votes DESC
");
$stmt->execute([$electionId]);
$candidates = $stmt->fetchAll();

// Format photo URLs
foreach ($candidates as &$c) {
    $c['photo'] = BASE_URL . '/uploads/candidates/' . $c['photo'];
}

$totalVotes = array_sum(array_column($candidates,'total_votes'));

echo json_encode([
    'election_id'   => $electionId,
    'election_title'=> $election['title'],
    'status'        => $election['status'],
    'total_votes'   => $totalVotes,
    'candidates'    => $candidates,
    'last_updated'  => date('Y-m-d H:i:s'),
]);
