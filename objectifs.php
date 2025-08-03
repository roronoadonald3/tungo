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
                $objectifData = [
                    'user_id' => $userId,
                    'nom' => sanitizeInput($_POST['nom']),
                    'montant_cible' => !empty($_POST['montant_cible']) ? floatval($_POST['montant_cible']) : null,
                    'pourcentage_cible' => !empty($_POST['pourcentage_cible']) ? floatval($_POST['pourcentage_cible']) : null,
                    'type_objectif' => sanitizeInput($_POST['type_objectif']),
                    'date_limite' => !empty($_POST['date_limite']) ? $_POST['date_limite'] : null,
                    'statut' => 'actif'
                ];
                
                // Validation
                if (empty($objectifData['nom'])) {
                    $errors[] = 'Le nom de l\'objectif est requis';
                }
                
                if (empty($objectifData['montant_cible']) && empty($objectifData['pourcentage_cible'])) {
                    $errors[] = 'Vous devez définir soit un montant cible, soit un pourcentage cible';
                }
                
                if (empty($objectifData['type_objectif'])) {
                    $errors[] = 'Le type d\'objectif est requis';
                }
                
                if (empty($errors)) {
                    $db = getDB();
                    $objectifId = $db->insert('objectifs', $objectifData);
                    
                    if ($objectifId) {
                        $success = true;
                        logAction($userId, 'add_objectif', "Ajout objectif: {$objectifData['nom']}");
                    }
                }
                break;
                
            case 'update':
                $objectifId = intval($_POST['objectif_id']);
                $objectifData = [
                    'nom' => sanitizeInput($_POST['nom']),
                    'montant_cible' => !empty($_POST['montant_cible']) ? floatval($_POST['montant_cible']) : null,
                    'pourcentage_cible' => !empty($_POST['pourcentage_cible']) ? floatval($_POST['pourcentage_cible']) : null,
                    'type_objectif' => sanitizeInput($_POST['type_objectif']),
                    'date_limite' => !empty($_POST['date_limite']) ? $_POST['date_limite'] : null
                ];
                
                // Validation
                if (empty($objectifData['nom'])) {
                    $errors[] = 'Le nom de l\'objectif est requis';
                }
                
                if (empty($errors)) {
                    $db = getDB();
                    $updated = $db->update('objectifs', $objectifData, 'id = ? AND user_id = ?', [$objectifId, $userId]);
                    
                    if ($updated) {
                        $success = true;
                        logAction($userId, 'update_objectif', "Modification objectif ID: $objectifId");
                    }
                }
                break;
                
            case 'update_progress':
                $objectifId = intval($_POST['objectif_id']);
                $montantActuel = floatval($_POST['montant_actuel']);
                
                $db = getDB();
                $updated = $db->update('objectifs', 
                    ['montant_actuel' => $montantActuel], 
                    'id = ? AND user_id = ?', 
                    [$objectifId, $userId]
                );
                
                if ($updated) {
                    $success = true;
                    logAction($userId, 'update_progress', "Mise à jour progression objectif ID: $objectifId");
                }
                break;
                
            case 'delete':
                $objectifId = intval($_POST['objectif_id']);
                
                $db = getDB();
                $deleted = $db->delete('objectifs', 'id = ? AND user_id = ?', [$objectifId, $userId]);
                
                if ($deleted) {
                    $success = true;
                    logAction($userId, 'delete_objectif', "Suppression objectif ID: $objectifId");
                }
                break;
                
            case 'complete':
                $objectifId = intval($_POST['objectif_id']);
                
                $db = getDB();
                $completed = $db->update('objectifs', 
                    ['statut' => 'atteint'], 
                    'id = ? AND user_id = ?', 
                    [$objectifId, $userId]
                );
                
                if ($completed) {
                    $success = true;
                    logAction($userId, 'complete_objectif', "Objectif atteint ID: $objectifId");
                }
                break;
        }
        
    } catch (Exception $e) {
        $errors[] = 'Erreur lors du traitement: ' . $e->getMessage();
    }
}

// Récupérer les objectifs de l'utilisateur
try {
    $db = getDB();
    $objectifs = $db->fetchAll(
        "SELECT * FROM objectifs WHERE user_id = ? ORDER BY created_at DESC",
        [$userId]
    );
    
    // Calculer les statistiques
    $totalObjectifs = count($objectifs);
    $objectifsActifs = array_filter($objectifs, function($obj) {
        return $obj['statut'] === 'actif';
    });
    $objectifsAtteints = array_filter($objectifs, function($obj) {
        return $obj['statut'] === 'atteint';
    });
    
    $totalMontantCible = array_sum(array_column($objectifsActifs, 'montant_cible'));
    $totalMontantActuel = array_sum(array_column($objectifsActifs, 'montant_actuel'));
    $pourcentageGlobal = $totalMontantCible > 0 ? ($totalMontantActuel / $totalMontantCible) * 100 : 0;
    
} catch (Exception $e) {
    error_log("Erreur lors du chargement des objectifs: " . $e->getMessage());
    $objectifs = [];
    $totalObjectifs = 0;
    $objectifsActifs = [];
    $objectifsAtteints = [];
    $totalMontantCible = 0;
    $totalMontantActuel = 0;
    $pourcentageGlobal = 0;
}

// Types d'objectifs disponibles
$typesObjectifs = [
    'epargne' => 'Épargne',
    'logement' => 'Logement',
    'transport' => 'Transport',
    'sante' => 'Santé',
    'education' => 'Éducation',
    'loisirs' => 'Loisirs',
    'urgence' => 'Fonds d\'urgence',
    'investissement' => 'Investissement',
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
    <title>Objectifs financiers - TUNGO</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/objectifs.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="objectifs-container">
        <div class="page-header">
            <div class="header-content">
                <h1>Objectifs financiers</h1>
                <p>Définissez et suivez vos objectifs financiers</p>
            </div>
            <button class="btn btn-primary" id="add-objectif-btn">
                <i class="fas fa-plus"></i> Nouvel objectif
            </button>
        </div>

        <!-- Statistiques -->
        <div class="stats-section">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-target"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo $totalObjectifs; ?></h3>
                    <p>Total des objectifs</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo count($objectifsAtteints); ?></h3>
                    <p>Objectifs atteints</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-chart-line"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo round($pourcentageGlobal, 1); ?>%</h3>
                    <p>Progression globale</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-money-bill-wave"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo formatCurrency($totalMontantActuel); ?></h3>
                    <p>Montant épargné</p>
                </div>
            </div>
        </div>

        <!-- Progression globale -->
        <div class="global-progress-section">
            <div class="progress-card">
                <h3>Progression globale</h3>
                <div class="progress-bar-large">
                    <div class="progress-fill-large" style="width: <?php echo $pourcentageGlobal; ?>%"></div>
                </div>
                <div class="progress-stats">
                    <span><?php echo formatCurrency($totalMontantActuel); ?> / <?php echo formatCurrency($totalMontantCible); ?></span>
                    <span><?php echo round($pourcentageGlobal, 1); ?>%</span>
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
        <div class="form-section" id="objectif-form-section" style="display: none;">
            <div class="form-card">
                <div class="form-header">
                    <h2 id="form-title">Nouvel objectif</h2>
                    <button class="close-btn" id="close-form">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                
                <form method="POST" id="objectif-form">
                    <input type="hidden" name="action" id="form-action" value="add">
                    <input type="hidden" name="objectif_id" id="objectif-id" value="">
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="nom">Nom de l'objectif *</label>
                            <input type="text" id="nom" name="nom" required 
                                   placeholder="Ex: Achat d'une voiture">
                        </div>
                        
                        <div class="form-group">
                            <label for="type_objectif">Type d'objectif *</label>
                            <select id="type_objectif" name="type_objectif" required>
                                <option value="">Sélectionner un type</option>
                                <?php foreach ($typesObjectifs as $value => $label): ?>
                                    <option value="<?php echo $value; ?>"><?php echo $label; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="montant_cible">Montant cible (XOF)</label>
                            <input type="number" id="montant_cible" name="montant_cible" 
                                   step="0.01" min="0" placeholder="0">
                        </div>
                        
                        <div class="form-group">
                            <label for="pourcentage_cible">Pourcentage cible (%)</label>
                            <input type="number" id="pourcentage_cible" name="pourcentage_cible" 
                                   step="0.01" min="0" max="100" placeholder="0">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="date_limite">Date limite</label>
                            <input type="date" id="date_limite" name="date_limite">
                        </div>
                        
                        <div class="form-group">
                            <label>Type de cible</label>
                            <div class="target-type-info">
                                <p>Définissez soit un montant cible, soit un pourcentage de vos revenus</p>
                            </div>
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

        <!-- Liste des objectifs -->
        <div class="objectifs-section">
            <div class="section-header">
                <h2>Mes objectifs</h2>
                <div class="filters">
                    <select id="status-filter">
                        <option value="">Tous les statuts</option>
                        <option value="actif">Actifs</option>
                        <option value="atteint">Atteints</option>
                    </select>
                    <select id="type-filter">
                        <option value="">Tous les types</option>
                        <?php foreach ($typesObjectifs as $value => $label): ?>
                            <option value="<?php echo $value; ?>"><?php echo $label; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <?php if (!empty($objectifs)): ?>
                <div class="objectifs-grid" id="objectifs-grid">
                    <?php foreach ($objectifs as $objectif): ?>
                        <div class="objectif-card" data-status="<?php echo $objectif['statut']; ?>" 
                             data-type="<?php echo $objectif['type_objectif']; ?>">
                            <div class="objectif-header">
                                <div class="objectif-icon">
                                    <i class="fas fa-target"></i>
                                </div>
                                <div class="objectif-status">
                                    <?php if ($objectif['statut'] === 'atteint'): ?>
                                        <span class="status-badge completed">
                                            <i class="fas fa-check"></i> Atteint
                                        </span>
                                    <?php else: ?>
                                        <span class="status-badge active">
                                            <i class="fas fa-clock"></i> Actif
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="objectif-content">
                                <h3><?php echo htmlspecialchars($objectif['nom']); ?></h3>
                                <p class="objectif-type"><?php echo $typesObjectifs[$objectif['type_objectif']] ?? ucfirst($objectif['type_objectif']); ?></p>
                                
                                <?php if ($objectif['montant_cible']): ?>
                                    <div class="objectif-target">
                                        <span class="target-label">Montant cible:</span>
                                        <span class="target-value"><?php echo formatCurrency($objectif['montant_cible']); ?></span>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if ($objectif['pourcentage_cible']): ?>
                                    <div class="objectif-target">
                                        <span class="target-label">Pourcentage cible:</span>
                                        <span class="target-value"><?php echo $objectif['pourcentage_cible']; ?>%</span>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="objectif-progress">
                                    <?php 
                                    $percentage = 0;
                                    if ($objectif['montant_cible'] && $objectif['montant_cible'] > 0) {
                                        $percentage = min(100, ($objectif['montant_actuel'] / $objectif['montant_cible']) * 100);
                                    }
                                    ?>
                                    <div class="progress-bar">
                                        <div class="progress-fill" style="width: <?php echo $percentage; ?>%"></div>
                                    </div>
                                    <div class="progress-text">
                                        <?php echo formatCurrency($objectif['montant_actuel']); ?> 
                                        <?php if ($objectif['montant_cible']): ?>
                                            / <?php echo formatCurrency($objectif['montant_cible']); ?>
                                        <?php endif; ?>
                                        (<?php echo round($percentage, 1); ?>%)
                                    </div>
                                </div>
                                
                                <?php if ($objectif['date_limite']): ?>
                                    <div class="objectif-deadline">
                                        <i class="fas fa-calendar"></i>
                                        Échéance: <?php echo date('d/m/Y', strtotime($objectif['date_limite'])); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="objectif-actions">
                                <?php if ($objectif['statut'] === 'actif'): ?>
                                    <button class="action-btn progress-btn" data-objectif-id="<?php echo $objectif['id']; ?>" title="Mettre à jour la progression">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="action-btn complete-btn" data-objectif-id="<?php echo $objectif['id']; ?>" title="Marquer comme atteint">
                                        <i class="fas fa-check"></i>
                                    </button>
                                <?php endif; ?>
                                <button class="action-btn edit-btn" data-objectif='<?php echo json_encode($objectif); ?>' title="Modifier">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="action-btn delete-btn" data-objectif-id="<?php echo $objectif['id']; ?>" title="Supprimer">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-target"></i>
                    <h3>Aucun objectif défini</h3>
                    <p>Commencez par créer votre premier objectif financier</p>
                    <button class="btn btn-primary" id="add-first-objectif">
                        <i class="fas fa-plus"></i> Créer un objectif
                    </button>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Modal de mise à jour de progression -->
    <div class="modal" id="progress-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Mettre à jour la progression</h3>
                <button class="close-btn" id="close-progress-modal">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <form method="POST" id="progress-form">
                    <input type="hidden" name="action" value="update_progress">
                    <input type="hidden" name="objectif_id" id="progress-objectif-id" value="">
                    
                    <div class="form-group">
                        <label for="montant_actuel">Montant actuel (XOF)</label>
                        <input type="number" id="montant_actuel" name="montant_actuel" 
                               step="0.01" min="0" required>
                    </div>
                    
                    <div class="progress-preview">
                        <h4>Prévisualisation de la progression</h4>
                        <div class="progress-bar-preview">
                            <div class="progress-fill-preview" id="progress-preview-fill"></div>
                        </div>
                        <div class="progress-text-preview" id="progress-preview-text"></div>
                    </div>
                </form>
            </div>
            <div class="modal-actions">
                <button class="btn btn-secondary" id="cancel-progress">Annuler</button>
                <button type="submit" form="progress-form" class="btn btn-primary">
                    <i class="fas fa-save"></i> Mettre à jour
                </button>
            </div>
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
                <p>Êtes-vous sûr de vouloir supprimer cet objectif ?</p>
                <p class="objectif-info" id="objectif-to-delete"></p>
            </div>
            <div class="modal-actions">
                <button class="btn btn-secondary" id="cancel-delete">Annuler</button>
                <form method="POST" id="delete-form" style="display: inline;">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="objectif_id" id="delete-objectif-id" value="">
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash"></i> Supprimer
                    </button>
                </form>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>
    
    <script src="assets/js/main.js"></script>
    <script src="assets/js/objectifs.js"></script>
</body>
</html> 