<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
session_regenerate_id(true);

require_once 'includes/auth.php';
require_once 'includes/db.php';

$db = getDB();
$userId = $_SESSION['user_id'];

// Récupérer les données de l'utilisateur
$userData = $db->fetchOne("SELECT * FROM users WHERE id = ?", [$userId]);

// Récupérer la dernière répartition
$repartition = $db->fetchOne(
    "SELECT * FROM repartitions WHERE user_id = ? ORDER BY created_at DESC LIMIT 1",
    [$userId]
);

// Récupérer les revenus récents
$recentRevenues = $db->fetchAll(
    "SELECT * FROM revenus WHERE user_id = ? ORDER BY date DESC LIMIT 5",
    [$userId]
);

// Récupérer les objectifs
$objectifs = $db->fetchAll(
    "SELECT * FROM objectifs WHERE user_id = ? AND actif = true",
    [$userId]
);

// Analyser les données pour générer des conseils
$conseils = [];

if ($repartition) {
    $repartitionData = json_decode($repartition['repartition'], true);
    
    // Analyse de l'épargne
    if ($repartitionData['epargne'] < 10) {
        $conseils[] = [
            'type' => 'warning',
            'icon' => 'fas fa-exclamation-triangle',
            'titre' => 'Épargne insuffisante',
            'message' => 'Vous épargnez seulement ' . $repartitionData['epargne'] . '% de vos revenus. Il est recommandé d\'épargner au moins 10-20% pour votre sécurité financière.',
            'action' => 'Augmentez votre pourcentage d\'épargne dans la répartition'
        ];
    } elseif ($repartitionData['epargne'] >= 20) {
        $conseils[] = [
            'type' => 'success',
            'icon' => 'fas fa-thumbs-up',
            'titre' => 'Excellente épargne',
            'message' => 'Vous épargnez ' . $repartitionData['epargne'] . '% de vos revenus. C\'est un excellent comportement financier !',
            'action' => 'Continuez sur cette voie'
        ];
    }
    
    // Analyse du logement
    if ($repartitionData['logement'] > 40) {
        $conseils[] = [
            'type' => 'warning',
            'icon' => 'fas fa-home',
            'titre' => 'Logement trop coûteux',
            'message' => 'Votre logement représente ' . $repartitionData['logement'] . '% de vos revenus. C\'est au-dessus de la recommandation de 30%.',
            'action' => 'Envisagez de réduire vos coûts de logement'
        ];
    }
    
    // Analyse de l'alimentation
    if ($repartitionData['alimentation'] > 35) {
        $conseils[] = [
            'type' => 'warning',
            'icon' => 'fas fa-utensils',
            'titre' => 'Dépenses alimentaires élevées',
            'message' => 'L\'alimentation représente ' . $repartitionData['alimentation'] . '% de vos revenus. Vous pourriez optimiser vos dépenses alimentaires.',
            'action' => 'Planifiez vos repas et comparez les prix'
        ];
    }
    
    // Équilibre général
    $totalEssentiels = $repartitionData['logement'] + $repartitionData['alimentation'] + $repartitionData['transport'] + $repartitionData['sante'];
    if ($totalEssentiels > 80) {
        $conseils[] = [
            'type' => 'danger',
            'icon' => 'fas fa-exclamation-circle',
            'titre' => 'Dépenses essentielles trop élevées',
            'message' => 'Vos dépenses essentielles représentent ' . $totalEssentiels . '% de vos revenus. Cela laisse peu de marge pour l\'épargne et les loisirs.',
            'action' => 'Réduisez vos dépenses essentielles si possible'
        ];
    }
}

// Analyser les revenus
if (!empty($recentRevenues)) {
    $totalRevenus = array_sum(array_column($recentRevenues, 'montant'));
    $moyenneRevenus = $totalRevenus / count($recentRevenues);
    
    if ($moyenneRevenus < 100000) { // 100,000 FCFA
        $conseils[] = [
            'type' => 'info',
            'icon' => 'fas fa-lightbulb',
            'titre' => 'Développez vos sources de revenus',
            'message' => 'Vos revenus moyens sont de ' . number_format($moyenneRevenus, 0, ',', ' ') . ' FCFA. Envisagez des activités complémentaires.',
            'action' => 'Explorez des opportunités de revenus supplémentaires'
        ];
    }
}

// Analyser les objectifs
if (!empty($objectifs)) {
    $objectifsAtteints = 0;
    foreach ($objectifs as $objectif) {
        if ($objectif['progression'] >= 100) {
            $objectifsAtteints++;
        }
    }
    
    if ($objectifsAtteints == 0) {
        $conseils[] = [
            'type' => 'warning',
            'icon' => 'fas fa-target',
            'titre' => 'Objectifs non atteints',
            'message' => 'Aucun de vos objectifs financiers n\'est atteint. Revoyez vos priorités et ajustez vos objectifs.',
            'action' => 'Réévaluez vos objectifs et votre plan d\'action'
        ];
    } elseif ($objectifsAtteints == count($objectifs)) {
        $conseils[] = [
            'type' => 'success',
            'icon' => 'fas fa-trophy',
            'titre' => 'Tous vos objectifs sont atteints !',
            'message' => 'Félicitations ! Vous avez atteint tous vos objectifs financiers. Continuez sur cette voie.',
            'action' => 'Définissez de nouveaux objectifs plus ambitieux'
        ];
    }
}

// Conseils généraux si peu de données
if (empty($conseils)) {
    $conseils[] = [
        'type' => 'info',
        'icon' => 'fas fa-info-circle',
        'titre' => 'Bienvenue sur TUNGO !',
        'message' => 'Commencez par ajouter vos revenus et définir vos objectifs pour recevoir des conseils personnalisés.',
        'action' => 'Complétez votre profil financier'
    ];
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Conseils personnalisés - TUNGO</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/conseils.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <?php include_once 'includes/header.php'; ?>

    <main class="main-content">
        <div class="container">
            <div class="page-header">
                <h1><i class="fas fa-lightbulb"></i> Conseils personnalisés</h1>
                <p>Des recommandations adaptées à votre situation financière</p>
            </div>

            <div class="conseils-container">
                <!-- Résumé de la situation -->
                <div class="situation-summary">
                    <h2>Votre situation actuelle</h2>
                    <div class="summary-cards">
                        <?php if ($repartition): ?>
                        <div class="summary-card">
                            <div class="summary-icon">
                                <i class="fas fa-chart-pie"></i>
                            </div>
                            <div class="summary-content">
                                <h3>Répartition actuelle</h3>
                                <p>Dernière mise à jour : <?= date('d/m/Y', strtotime($repartition['created_at'])) ?></p>
                            </div>
                        </div>
                        <?php endif; ?>

                        <div class="summary-card">
                            <div class="summary-icon">
                                <i class="fas fa-money-bill-wave"></i>
                            </div>
                            <div class="summary-content">
                                <h3>Revenus récents</h3>
                                <p><?= count($recentRevenues) ?> entrées enregistrées</p>
                            </div>
                        </div>

                        <div class="summary-card">
                            <div class="summary-icon">
                                <i class="fas fa-target"></i>
                            </div>
                            <div class="summary-content">
                                <h3>Objectifs actifs</h3>
                                <p><?= count($objectifs) ?> objectifs en cours</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Liste des conseils -->
                <div class="conseils-list">
                    <h2>Nos recommandations</h2>
                    
                    <?php foreach ($conseils as $index => $conseil): ?>
                    <div class="conseil-card conseil-<?= $conseil['type'] ?>">
                        <div class="conseil-icon">
                            <i class="<?= $conseil['icon'] ?>"></i>
                        </div>
                        <div class="conseil-content">
                            <h3><?= $conseil['titre'] ?></h3>
                            <p class="conseil-message"><?= $conseil['message'] ?></p>
                            <div class="conseil-action">
                                <span class="action-label">Action recommandée :</span>
                                <span class="action-text"><?= $conseil['action'] ?></span>
                            </div>
                        </div>
                        <div class="conseil-actions">
                            <button class="btn btn-sm btn-primary" onclick="appliquerConseil(<?= $index ?>)">
                                <i class="fas fa-check"></i> Appliquer
                            </button>
                            <button class="btn btn-sm btn-secondary" onclick="ignorerConseil(<?= $index ?>)">
                                <i class="fas fa-times"></i> Ignorer
                            </button>
                        </div>
                    </div>
                    <?php endforeach; ?>

                    <?php if (empty($conseils)): ?>
                    <div class="empty-state">
                        <div class="empty-icon">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <h3>Aucun conseil disponible</h3>
                        <p>Complétez vos informations financières pour recevoir des conseils personnalisés.</p>
                        <div class="empty-actions">
                            <a href="revenus.php" class="btn btn-primary">
                                <i class="fas fa-plus"></i> Ajouter des revenus
                            </a>
                            <a href="objectifs.php" class="btn btn-secondary">
                                <i class="fas fa-target"></i> Définir des objectifs
                            </a>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Actions rapides -->
                <div class="quick-actions">
                    <h2>Actions rapides</h2>
                    <div class="actions-grid">
                        <a href="repartition.php" class="action-card">
                            <div class="action-icon">
                                <i class="fas fa-sliders-h"></i>
                            </div>
                            <h3>Ajuster la répartition</h3>
                            <p>Modifiez la répartition de votre budget</p>
                        </a>
                        
                        <a href="revenus.php" class="action-card">
                            <div class="action-icon">
                                <i class="fas fa-plus-circle"></i>
                            </div>
                            <h3>Ajouter un revenu</h3>
                            <p>Enregistrez une nouvelle source de revenus</p>
                        </a>
                        
                        <a href="objectifs.php" class="action-card">
                            <div class="action-icon">
                                <i class="fas fa-bullseye"></i>
                            </div>
                            <h3>Gérer les objectifs</h3>
                            <p>Définissez et suivez vos objectifs financiers</p>
                        </a>
                        
                        <a href="simulation.php" class="action-card">
                            <div class="action-icon">
                                <i class="fas fa-calculator"></i>
                            </div>
                            <h3>Nouvelle simulation</h3>
                            <p>Testez différents scénarios budgétaires</p>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php include_once 'includes/footer.php'; ?>

    <script src="assets/js/conseils.js"></script>
</body>
</html> 