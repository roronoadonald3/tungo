<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
session_regenerate_id(true);

/**
 * API pour traiter les simulations TUNGO
 * Reçoit les données de simulation et les sauvegarde en base
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Gérer les requêtes OPTIONS (CORS)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Vérifier que c'est une requête POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit();
}

require_once '../includes/db.php';
require_once '../includes/auth.php';

// Démarrer la session
session_start();

try {
    // Récupérer les données POST
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    // Si pas de données JSON, essayer les données POST standard
    if (!$data) {
        $data = $_POST;
    }
    
    // Vérifier l'action
    $action = $data['action'] ?? '';
    
    switch ($action) {
        case 'save_simulation':
            handleSaveSimulation($data);
            break;
            
        case 'get_simulation':
            handleGetSimulation($data);
            break;
            
        case 'delete_simulation':
            handleDeleteSimulation($data);
            break;
            
        default:
            throw new Exception('Action non reconnue');
    }
    
} catch (Exception $e) {
    error_log("Erreur API simulation: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erreur serveur: ' . $e->getMessage()
    ]);
}

/**
 * Sauvegarde une simulation
 */
function handleSaveSimulation($data) {
    // Vérifier que l'utilisateur est connecté
    if (!isLoggedIn()) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'Utilisateur non connecté'
        ]);
        return;
    }
    
    // Valider les données de simulation
    $simulationData = validateSimulationData($data['data'] ?? []);
    
    // Sauvegarder en base
    $db = getDB();
    
    $simulationRecord = [
        'user_id' => getCurrentUserId(),
        'revenu' => $simulationData['revenu'],
        'profil_type' => $simulationData['profil'],
        'mode_repartition' => $simulationData['mode'],
        'repartition_data' => json_encode($simulationData['repartition'])
    ];
    
    $simulationId = $db->insert('simulations', $simulationRecord);
    
    // Logger l'action
    logAction(getCurrentUserId(), 'simulation_saved', "Simulation #$simulationId sauvegardée");
    
    echo json_encode([
        'success' => true,
        'message' => 'Simulation sauvegardée avec succès',
        'simulation_id' => $simulationId
    ]);
}

/**
 * Récupère une simulation
 */
function handleGetSimulation($data) {
    // Vérifier que l'utilisateur est connecté
    if (!isLoggedIn()) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'Utilisateur non connecté'
        ]);
        return;
    }
    
    $simulationId = $data['simulation_id'] ?? null;
    
    if (!$simulationId) {
        // Récupérer la dernière simulation de l'utilisateur
        $db = getDB();
        $sql = "SELECT * FROM simulations WHERE user_id = ? ORDER BY created_at DESC LIMIT 1";
        $simulation = $db->fetchOne($sql, [getCurrentUserId()]);
        
        if (!$simulation) {
            echo json_encode([
                'success' => false,
                'message' => 'Aucune simulation trouvée'
            ]);
            return;
        }
    } else {
        // Récupérer une simulation spécifique
        $db = getDB();
        $sql = "SELECT * FROM simulations WHERE id = ? AND user_id = ?";
        $simulation = $db->fetchOne($sql, [$simulationId, getCurrentUserId()]);
        
        if (!$simulation) {
            echo json_encode([
                'success' => false,
                'message' => 'Simulation non trouvée'
            ]);
            return;
        }
    }
    
    // Décoder les données de répartition
    $simulation['repartition_data'] = json_decode($simulation['repartition_data'], true);
    
    echo json_encode([
        'success' => true,
        'simulation' => $simulation
    ]);
}

/**
 * Supprime une simulation
 */
function handleDeleteSimulation($data) {
    // Vérifier que l'utilisateur est connecté
    if (!isLoggedIn()) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'Utilisateur non connecté'
        ]);
        return;
    }
    
    $simulationId = $data['simulation_id'] ?? null;
    
    if (!$simulationId) {
        echo json_encode([
            'success' => false,
            'message' => 'ID de simulation requis'
        ]);
        return;
    }
    
    $db = getDB();
    
    // Vérifier que la simulation appartient à l'utilisateur
    $simulation = $db->fetchOne(
        "SELECT id FROM simulations WHERE id = ? AND user_id = ?",
        [$simulationId, getCurrentUserId()]
    );
    
    if (!$simulation) {
        echo json_encode([
            'success' => false,
            'message' => 'Simulation non trouvée'
        ]);
        return;
    }
    
    // Supprimer la simulation
    $affected = $db->delete('simulations', 'id = ? AND user_id = ?', [$simulationId, getCurrentUserId()]);
    
    if ($affected > 0) {
        logAction(getCurrentUserId(), 'simulation_deleted', "Simulation #$simulationId supprimée");
        
        echo json_encode([
            'success' => true,
            'message' => 'Simulation supprimée avec succès'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Erreur lors de la suppression'
        ]);
    }
}

/**
 * Valide les données de simulation
 */
function validateSimulationData($data) {
    $errors = [];
    
    // Vérifier le revenu
    if (!isset($data['revenu']) || !is_numeric($data['revenu']) || $data['revenu'] < 10000) {
        $errors[] = 'Revenu invalide (minimum 10 000 FCFA)';
    }
    
    // Vérifier le profil
    $profilsValides = ['regulier', 'irregulier', 'mixte'];
    if (!isset($data['profil']) || !in_array($data['profil'], $profilsValides)) {
        $errors[] = 'Profil invalide';
    }
    
    // Vérifier le mode de répartition
    $modesValides = ['automatique', 'manuel'];
    if (!isset($data['mode']) || !in_array($data['mode'], $modesValides)) {
        $errors[] = 'Mode de répartition invalide';
    }
    
    // Vérifier la répartition
    if (!isset($data['repartition']) || !is_array($data['repartition'])) {
        $errors[] = 'Données de répartition invalides';
    } else {
        $categories = ['logement', 'alimentation', 'transport', 'sante', 'loisirs', 'epargne'];
        $total = 0;
        
        foreach ($categories as $category) {
            if (!isset($data['repartition'][$category]) || !is_numeric($data['repartition'][$category])) {
                $errors[] = "Pourcentage invalide pour $category";
            } else {
                $total += $data['repartition'][$category];
            }
        }
        
        if ($total !== 100) {
            $errors[] = 'Le total des pourcentages doit être égal à 100%';
        }
    }
    
    if (!empty($errors)) {
        throw new Exception('Données invalides: ' . implode(', ', $errors));
    }
    
    return [
        'revenu' => (float) $data['revenu'],
        'profil' => $data['profil'],
        'mode' => $data['mode'],
        'repartition' => array_map('floatval', $data['repartition'])
    ];
}

/**
 * Calcule des statistiques de simulation
 */
function calculateSimulationStats($userId) {
    $db = getDB();
    
    // Nombre total de simulations
    $totalSimulations = $db->fetchOne(
        "SELECT COUNT(*) as total FROM simulations WHERE user_id = ?",
        [$userId]
    )['total'];
    
    // Dernière simulation
    $lastSimulation = $db->fetchOne(
        "SELECT * FROM simulations WHERE user_id = ? ORDER BY created_at DESC LIMIT 1",
        [$userId]
    );
    
    // Répartition moyenne
    $avgRepartition = $db->fetchOne(
        "SELECT 
            AVG((repartition_data->>'logement')::numeric) as logement,
            AVG((repartition_data->>'alimentation')::numeric) as alimentation,
            AVG((repartition_data->>'transport')::numeric) as transport,
            AVG((repartition_data->>'sante')::numeric) as sante,
            AVG((repartition_data->>'loisirs')::numeric) as loisirs,
            AVG((repartition_data->>'epargne')::numeric) as epargne
         FROM simulations 
         WHERE user_id = ?",
        [$userId]
    );
    
    return [
        'total_simulations' => $totalSimulations,
        'last_simulation' => $lastSimulation,
        'average_repartition' => $avgRepartition
    ];
}

/**
 * Génère des conseils basés sur la simulation
 */
function generateAdvice($simulationData) {
    $advice = [];
    
    // Conseils basés sur le revenu
    if ($simulationData['revenu'] < 50000) {
        $advice[] = [
            'type' => 'revenu',
            'message' => 'Votre revenu est faible. Pensez à diversifier vos sources de revenus.',
            'priorite' => 2
        ];
    }
    
    // Conseils basés sur la répartition
    $repartition = $simulationData['repartition'];
    
    if ($repartition['epargne'] < 10) {
        $advice[] = [
            'type' => 'epargne',
            'message' => 'Votre taux d\'épargne est faible. Essayez d\'épargner au moins 10% de vos revenus.',
            'priorite' => 1
        ];
    }
    
    if ($repartition['logement'] > 40) {
        $advice[] = [
            'type' => 'logement',
            'message' => 'Vos dépenses de logement sont élevées. Cherchez des solutions pour réduire ces coûts.',
            'priorite' => 2
        ];
    }
    
    if ($repartition['loisirs'] > 20) {
        $advice[] = [
            'type' => 'loisirs',
            'message' => 'Vos dépenses de loisirs sont importantes. Équilibrez avec l\'épargne.',
            'priorite' => 3
        ];
    }
    
    return $advice;
}
?> 