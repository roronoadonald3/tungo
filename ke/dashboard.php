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

// Récupérer les données du tableau de bord
try {
    $db = getDB();
    
    // Dernière simulation
    $lastSimulation = $db->fetchOne(
        "SELECT * FROM simulations WHERE user_id = ? ORDER BY created_at DESC LIMIT 1",
        [$userId]
    );
    
    // Revenus récents (3 derniers mois)
    $recentRevenus = $db->fetchAll(
        "SELECT * FROM revenus WHERE user_id = ? AND date_revenu >= CURRENT_DATE - INTERVAL '3 months' ORDER BY date_revenu DESC",
        [$userId]
    );
    
    // Objectifs actifs
    $activeObjectifs = $db->fetchAll(
        "SELECT * FROM objectifs WHERE user_id = ? AND statut = 'actif' ORDER BY created_at DESC",
        [$userId]
    );
    
    // Conseils non lus
    $unreadConseils = $db->fetchAll(
        "SELECT * FROM conseils WHERE user_id = ? AND lu = FALSE ORDER BY priorite DESC, created_at DESC LIMIT 5",
        [$userId]
    );
    
    // Statistiques rapides
    $totalRevenus = $db->fetchOne(
        "SELECT COALESCE(SUM(montant), 0) as total FROM revenus WHERE user_id = ? AND date_revenu >= CURRENT_DATE - INTERVAL '1 month'",
        [$userId]
    )['total'];
    
    $totalObjectifs = $db->fetchOne(
        "SELECT COUNT(*) as total FROM objectifs WHERE user_id = ? AND statut = 'actif'",
        [$userId]
    )['total'];
    
    $completedObjectifs = $db->fetchOne(
        "SELECT COUNT(*) as total FROM objectifs WHERE user_id = ? AND statut = 'atteint'",
        [$userId]
    )['total'];
    
} catch (Exception $e) {
    error_log("Erreur lors du chargement du tableau de bord: " . $e->getMessage());
    $lastSimulation = null;
    $recentRevenus = [];
    $activeObjectifs = [];
    $unreadConseils = [];
    $totalRevenus = 0;
    $totalObjectifs = 0;
    $completedObjectifs = 0;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de bord - TUNGO</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="dashboard-container">
        <div class="dashboard-header">
            <div class="welcome-section">
                <h1>Bonjour, <?php echo htmlspecialchars($user['nom']); ?> !</h1>
                <p>Voici un aperçu de vos finances ce mois-ci</p>
            </div>
            <div class="quick-actions">
                <a href="simulation.php" class="btn btn-primary">
                    <i class="fas fa-calculator"></i> Nouvelle simulation
                </a>
                <a href="revenus.php" class="btn btn-secondary">
                    <i class="fas fa-plus"></i> Ajouter un revenu
                </a>
            </div>
        </div>

        <!-- Statistiques rapides -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-money-bill-wave"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo formatCurrency($totalRevenus); ?></h3>
                    <p>Revenus ce mois</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-target"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo $totalObjectifs; ?></h3>
                    <p>Objectifs actifs</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo $completedObjectifs; ?></h3>
                    <p>Objectifs atteints</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-lightbulb"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo count($unreadConseils); ?></h3>
                    <p>Conseils non lus</p>
                </div>
            </div>
        </div>

        <div class="dashboard-content">
            <!-- Dernière simulation -->
            <div class="dashboard-section">
                <div class="section-header">
                    <h2>Dernière simulation</h2>
                    <a href="simulation.php" class="btn btn-outline">Nouvelle simulation</a>
                </div>
                
                <?php if ($lastSimulation): ?>
                    <div class="simulation-card">
                        <div class="simulation-info">
                            <h3>Simulation du <?php echo date('d/m/Y', strtotime($lastSimulation['created_at'])); ?></h3>
                            <p><strong>Revenu:</strong> <?php echo formatCurrency($lastSimulation['revenu']); ?></p>
                            <p><strong>Profil:</strong> <?php echo ucfirst($lastSimulation['profil_type']); ?></p>
                            <p><strong>Mode:</strong> <?php echo ucfirst($lastSimulation['mode_repartition']); ?></p>
                        </div>
                        <div class="simulation-chart">
                            <canvas id="last-simulation-chart"></canvas>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-calculator"></i>
                        <h3>Aucune simulation</h3>
                        <p>Créez votre première simulation pour commencer à gérer votre budget</p>
                        <a href="simulation.php" class="btn btn-primary">Créer une simulation</a>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Revenus récents -->
            <div class="dashboard-section">
                <div class="section-header">
                    <h2>Revenus récents</h2>
                    <a href="revenus.php" class="btn btn-outline">Gérer les revenus</a>
                </div>
                
                <?php if (!empty($recentRevenus)): ?>
                    <div class="revenue-list">
                        <?php foreach (array_slice($recentRevenus, 0, 5) as $revenu): ?>
                            <div class="revenue-item">
                                <div class="revenue-icon">
                                    <i class="fas fa-money-bill-wave"></i>
                                </div>
                                <div class="revenue-details">
                                    <h4><?php echo ucfirst($revenu['type_revenu']); ?></h4>
                                    <p><?php echo date('d/m/Y', strtotime($revenu['date_revenu'])); ?></p>
                                </div>
                                <div class="revenue-amount">
                                    <?php echo formatCurrency($revenu['montant']); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-money-bill-wave"></i>
                        <h3>Aucun revenu enregistré</h3>
                        <p>Ajoutez vos premiers revenus pour commencer le suivi</p>
                        <a href="revenus.php" class="btn btn-primary">Ajouter un revenu</a>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Objectifs actifs -->
            <div class="dashboard-section">
                <div class="section-header">
                    <h2>Objectifs actifs</h2>
                    <a href="objectifs.php" class="btn btn-outline">Gérer les objectifs</a>
                </div>
                
                <?php if (!empty($activeObjectifs)): ?>
                    <div class="objectifs-grid">
                        <?php foreach (array_slice($activeObjectifs, 0, 4) as $objectif): ?>
                            <div class="objectif-card">
                                <div class="objectif-header">
                                    <h4><?php echo htmlspecialchars($objectif['nom']); ?></h4>
                                    <span class="objectif-type"><?php echo ucfirst($objectif['type_objectif']); ?></span>
                                </div>
                                
                                <?php if ($objectif['montant_cible']): ?>
                                    <div class="objectif-progress">
                                        <div class="progress-bar">
                                            <?php 
                                            $percentage = $objectif['montant_cible'] > 0 ? 
                                                min(100, ($objectif['montant_actuel'] / $objectif['montant_cible']) * 100) : 0;
                                            ?>
                                            <div class="progress-fill" style="width: <?php echo $percentage; ?>%"></div>
                                        </div>
                                        <div class="progress-text">
                                            <?php echo formatCurrency($objectif['montant_actuel']); ?> / 
                                            <?php echo formatCurrency($objectif['montant_cible']); ?>
                                            (<?php echo round($percentage); ?>%)
                                        </div>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if ($objectif['date_limite']): ?>
                                    <div class="objectif-deadline">
                                        <i class="fas fa-calendar"></i>
                                        Échéance: <?php echo date('d/m/Y', strtotime($objectif['date_limite'])); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-target"></i>
                        <h3>Aucun objectif défini</h3>
                        <p>Créez vos premiers objectifs financiers</p>
                        <a href="objectifs.php" class="btn btn-primary">Créer un objectif</a>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Conseils personnalisés -->
            <div class="dashboard-section">
                <div class="section-header">
                    <h2>Conseils personnalisés</h2>
                    <a href="conseils.php" class="btn btn-outline">Voir tous les conseils</a>
                </div>
                
                <?php if (!empty($unreadConseils)): ?>
                    <div class="conseils-list">
                        <?php foreach ($unreadConseils as $conseil): ?>
                            <div class="conseil-card" data-conseil-id="<?php echo $conseil['id']; ?>">
                                <div class="conseil-icon">
                                    <i class="fas fa-lightbulb"></i>
                                </div>
                                <div class="conseil-content">
                                    <p><?php echo htmlspecialchars($conseil['message']); ?></p>
                                    <small><?php echo date('d/m/Y', strtotime($conseil['created_at'])); ?></small>
                                </div>
                                <button class="conseil-mark-read" title="Marquer comme lu">
                                    <i class="fas fa-check"></i>
                                </button>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-lightbulb"></i>
                        <h3>Aucun nouveau conseil</h3>
                        <p>Continuez à utiliser TUNGO pour recevoir des conseils personnalisés</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Liens rapides -->
        <div class="quick-links">
            <h3>Accès rapides</h3>
            <div class="links-grid">
                <a href="revenus.php" class="quick-link">
                    <i class="fas fa-money-bill-wave"></i>
                    <span>Revenus</span>
                </a>
                <a href="objectifs.php" class="quick-link">
                    <i class="fas fa-target"></i>
                    <span>Objectifs</span>
                </a>
                <a href="repartition.php" class="quick-link">
                    <i class="fas fa-chart-pie"></i>
                    <span>Répartition</span>
                </a>
                <a href="conseils.php" class="quick-link">
                    <i class="fas fa-lightbulb"></i>
                    <span>Conseils</span>
                </a>
                <a href="profil.php" class="quick-link">
                    <i class="fas fa-user"></i>
                    <span>Mon profil</span>
                </a>
                <a href="simulation.php" class="quick-link">
                    <i class="fas fa-calculator"></i>
                    <span>Simulation</span>
                </a>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>
    
    <script src="assets/js/main.js"></script>
    <script src="assets/js/dashboard.js"></script>
    
    <?php if ($lastSimulation): ?>
    <script>
        // Données de la dernière simulation pour le graphique
        const simulationData = <?php echo $lastSimulation['repartition_data']; ?>;
    </script>
    <?php endif; ?>
</body>
</html> 