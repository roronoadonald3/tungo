<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
session_regenerate_id(true);

require_once 'includes/auth.php';

// Vérifier que l'utilisateur est connecté
requireLogin();

$user = getCurrentUser();
$userId = getCurrentUserId();

$errors = [];
$success = false;

// Traitement des actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        switch ($action) {
            case 'add':
                $revenuData = [
                    'user_id' => $userId,
                    'montant' => floatval($_POST['montant']),
                    'type_revenu' => sanitizeInput($_POST['type_revenu']),
                    'date_revenu' => $_POST['date_revenu'],
                    'description' => sanitizeInput($_POST['description'] ?? '')
                ];
                
                // Validation
                if ($revenuData['montant'] <= 0) {
                    $errors[] = 'Le montant doit être supérieur à 0';
                }
                
                if (empty($revenuData['type_revenu'])) {
                    $errors[] = 'Le type de revenu est requis';
                }
                
                if (empty($revenuData['date_revenu'])) {
                    $errors[] = 'La date est requise';
                }
                
                if (empty($errors)) {
                    $db = getDB();
                    $revenuId = $db->insert('revenus', $revenuData);
                    
                    if ($revenuId) {
                        $success = true;
                        logAction($userId, 'add_revenu', "Ajout revenu: {$revenuData['montant']} {$revenuData['type_revenu']}");
                    }
                }
                break;
                
            case 'update':
                $revenuId = intval($_POST['revenu_id']);
                $revenuData = [
                    'montant' => floatval($_POST['montant']),
                    'type_revenu' => sanitizeInput($_POST['type_revenu']),
                    'date_revenu' => $_POST['date_revenu'],
                    'description' => sanitizeInput($_POST['description'] ?? '')
                ];
                
                // Validation
                if ($revenuData['montant'] <= 0) {
                    $errors[] = 'Le montant doit être supérieur à 0';
                }
                
                if (empty($errors)) {
                    $db = getDB();
                    $updated = $db->update('revenus', $revenuData, 'id = ? AND user_id = ?', [$revenuId, $userId]);
                    
                    if ($updated) {
                        $success = true;
                        logAction($userId, 'update_revenu', "Modification revenu ID: $revenuId");
                    }
                }
                break;
                
            case 'delete':
                $revenuId = intval($_POST['revenu_id']);
                
                $db = getDB();
                $deleted = $db->delete('revenus', 'id = ? AND user_id = ?', [$revenuId, $userId]);
                
                if ($deleted) {
                    $success = true;
                    logAction($userId, 'delete_revenu', "Suppression revenu ID: $revenuId");
                }
                break;
        }
        
    } catch (Exception $e) {
        $errors[] = 'Erreur lors du traitement: ' . $e->getMessage();
    }
}

// Récupérer les revenus de l'utilisateur
try {
    $db = getDB();
    $revenus = $db->fetchAll(
        "SELECT * FROM revenus WHERE user_id = ? ORDER BY date_revenu DESC",
        [$userId]
    );
    
    // Calculer les statistiques
    $totalRevenus = array_sum(array_column($revenus, 'montant'));
    $revenusCeMois = array_filter($revenus, function($revenu) {
        return date('Y-m', strtotime($revenu['date_revenu'])) === date('Y-m');
    });
    $totalCeMois = array_sum(array_column($revenusCeMois, 'montant'));
    
} catch (Exception $e) {
    error_log("Erreur lors du chargement des revenus: " . $e->getMessage());
    $revenus = [];
    $totalRevenus = 0;
    $totalCeMois = 0;
}

// Types de revenus disponibles
$typesRevenus = [
    'salaire' => 'Salaire',
    'freelance' => 'Freelance',
    'commerce' => 'Commerce',
    'investissement' => 'Investissement',
    'bourse' => 'Bourse',
    'vente' => 'Vente',
    'don' => 'Don/Cadeau',
    'autre' => 'Autre'
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des revenus - TUNGO</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/revenus.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="revenus-container">
        <div class="page-header">
            <div class="header-content">
                <h1>Gestion des revenus</h1>
                <p>Suivez et gérez vos sources de revenus</p>
            </div>
            <button class="btn btn-primary" id="add-revenu-btn">
                <i class="fas fa-plus"></i> Ajouter un revenu
            </button>
        </div>

        <!-- Statistiques -->
        <div class="stats-section">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-money-bill-wave"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo formatCurrency($totalRevenus); ?></h3>
                    <p>Total des revenus</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-calendar"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo formatCurrency($totalCeMois); ?></h3>
                    <p>Revenus ce mois</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-list"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo count($revenus); ?></h3>
                    <p>Nombre d'entrées</p>
                </div>
            </div>
        </div>

        <!-- Messages -->
        <?php if ($success): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                Opération réussie !
            </div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-error">
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <!-- Formulaire d'ajout/modification -->
        <div class="form-section" id="revenu-form-section" style="display: none;">
            <div class="form-card">
                <div class="form-header">
                    <h2 id="form-title">Ajouter un revenu</h2>
                    <button class="close-btn" id="close-form">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                
                <form method="POST" id="revenu-form">
                    <input type="hidden" name="action" id="form-action" value="add">
                    <input type="hidden" name="revenu_id" id="revenu-id" value="">
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="montant">Montant *</label>
                            <div class="input-group">
                                <input type="number" id="montant" name="montant" required 
                                       step="0.01" min="0" placeholder="0">
                                <span class="currency">XOF</span>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="type_revenu">Type de revenu *</label>
                            <select id="type_revenu" name="type_revenu" required>
                                <option value="">Sélectionner un type</option>
                                <?php foreach ($typesRevenus as $value => $label): ?>
                                    <option value="<?php echo $value; ?>"><?php echo $label; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="date_revenu">Date *</label>
                            <input type="date" id="date_revenu" name="date_revenu" required 
                                   value="<?php echo date('Y-m-d'); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="description">Description</label>
                            <input type="text" id="description" name="description" 
                                   placeholder="Description optionnelle">
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="button" class="btn btn-secondary" id="cancel-btn">Annuler</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Enregistrer
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Liste des revenus -->
        <div class="revenus-section">
            <div class="section-header">
                <h2>Mes revenus</h2>
                <div class="filters">
                    <select id="type-filter">
                        <option value="">Tous les types</option>
                        <?php foreach ($typesRevenus as $value => $label): ?>
                            <option value="<?php echo $value; ?>"><?php echo $label; ?></option>
                        <?php endforeach; ?>
                    </select>
                    <input type="month" id="date-filter" value="<?php echo date('Y-m'); ?>">
                </div>
            </div>
            
            <?php if (!empty($revenus)): ?>
                <div class="revenus-list" id="revenus-list">
                    <?php foreach ($revenus as $revenu): ?>
                        <div class="revenu-card" data-type="<?php echo $revenu['type_revenu']; ?>" 
                             data-date="<?php echo date('Y-m', strtotime($revenu['date_revenu'])); ?>">
                            <div class="revenu-icon">
                                <i class="fas fa-money-bill-wave"></i>
                            </div>
                            
                            <div class="revenu-details">
                                <h4><?php echo $typesRevenus[$revenu['type_revenu']] ?? ucfirst($revenu['type_revenu']); ?></h4>
                                <p class="revenu-date">
                                    <i class="fas fa-calendar"></i>
                                    <?php echo date('d/m/Y', strtotime($revenu['date_revenu'])); ?>
                                </p>
                                <?php if (!empty($revenu['description'])): ?>
                                    <p class="revenu-description"><?php echo htmlspecialchars($revenu['description']); ?></p>
                                <?php endif; ?>
                            </div>
                            
                            <div class="revenu-amount">
                                <?php echo formatCurrency($revenu['montant']); ?>
                            </div>
                            
                            <div class="revenu-actions">
                                <button class="action-btn edit-btn" data-revenu='<?php echo json_encode($revenu); ?>' title="Modifier">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="action-btn delete-btn" data-revenu-id="<?php echo $revenu['id']; ?>" title="Supprimer">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-money-bill-wave"></i>
                    <h3>Aucun revenu enregistré</h3>
                    <p>Commencez par ajouter votre premier revenu</p>
                    <button class="btn btn-primary" id="add-first-revenu">
                        <i class="fas fa-plus"></i> Ajouter un revenu
                    </button>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Modal de confirmation de suppression -->
    <div class="modal" id="delete-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Confirmer la suppression</h3>
                <button class="close-btn" id="close-modal">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <p>Êtes-vous sûr de vouloir supprimer ce revenu ?</p>
                <p class="revenu-info" id="revenu-to-delete"></p>
            </div>
            <div class="modal-actions">
                <button class="btn btn-secondary" id="cancel-delete">Annuler</button>
                <form method="POST" id="delete-form" style="display: inline;">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="revenu_id" id="delete-revenu-id" value="">
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash"></i> Supprimer
                    </button>
                </form>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>
    
    <script src="assets/js/main.js"></script>
    <script src="assets/js/revenus.js"></script>
</body>
</html> 