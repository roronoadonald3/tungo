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

if ($action === 'mark_read') {
    $conseilId = $input['conseil_id'] ?? null;
    if (!$conseilId) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'ID du conseil manquant']);
        exit();
    }

    try {
        $db = getDB();
        $updated = $db->update('conseils', 
            ['lu' => true], 
            'id = ? AND user_id = ?', 
            [$conseilId, $userId]
        );

        if ($updated) {
            echo json_encode(['success' => true, 'message' => 'Conseil marqué comme lu']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Impossible de mettre à jour le conseil']);
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Erreur serveur']);
    }
} else {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Action non reconnue']);
}
?>
