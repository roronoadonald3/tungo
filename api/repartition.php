<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
session_regenerate_id(true);

header('Content-Type: application/json');
require_once '../includes/db.php';
require_once '../includes/auth.php';

session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit();
}

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Utilisateur non connecté']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';
$userId = getCurrentUserId();

if ($action === 'save_repartition') {
    $data = $input['data'] ?? null;
    if (!$data || !is_array($data)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Données de répartition manquantes']);
        exit();
    }

    // Validation
    $total = array_sum($data);
    if ($total !== 100) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'La somme des pourcentages doit être 100']);
        exit();
    }

    try {
        $db = getDB();
        $repartitionData = [
            'user_id' => $userId,
            'logement' => $data['logement'],
            'alimentation' => $data['alimentation'],
            'transport' => $data['transport'],
            'sante' => $data['sante'],
            'education' => $data['education'] ?? 0,
            'loisirs' => $data['loisirs'],
            'epargne' => $data['epargne'],
        ];

        // Check if a repartition already exists to decide between INSERT and UPDATE
        $existing = $db->fetchOne("SELECT id FROM repartitions WHERE user_id = ?", [$userId]);

        if ($existing) {
            $db->update('repartitions', $repartitionData, 'user_id = ?', [$userId]);
        } else {
            $db->insert('repartitions', $repartitionData);
        }

        echo json_encode(['success' => true, 'message' => 'Répartition sauvegardée avec succès']);

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Erreur serveur']);
    }
} else {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Action non reconnue']);
}
?>
