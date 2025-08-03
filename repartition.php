<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
session_regenerate_id(true);

require_once 'includes/auth.php';
require_once 'includes/db.php';

// Récupérer les données de répartition actuelles de l'utilisateur
$db = getDB();
$userId = $_SESSION['user_id'];

// Récupérer la dernière répartition de l'utilisateur
$currentRepartition = $db->fetchOne(
    "SELECT * FROM repartitions WHERE user_id = ? ORDER BY created_at DESC LIMIT 1",
    [$userId]
);

// Valeurs par défaut si aucune répartition n'existe
$defaultValues = [
    'logement' => 30,
    'alimentation' => 25,
    'transport' => 15,
    'sante' => 10,
    'education' => 5,
    'loisirs' => 5,
    'epargne' => 10
];

$repartition = $currentRepartition ? json_decode($currentRepartition['repartition'], true) : $defaultValues;
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Répartition du budget - TUNGO</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/repartition.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <?php include_once 'includes/header.php'; ?>

    <main class="main-content">
        <div class="container">
            <div class="page-header">
                <h1><i class="fas fa-chart-pie"></i> Répartition de votre budget</h1>
                <p>Ajustez manuellement la répartition de vos dépenses ou utilisez nos suggestions automatiques</p>
            </div>

            <div class="repartition-container">
                <!-- Section de répartition manuelle -->
                <div class="manual-repartition">
                    <h2>Répartition manuelle</h2>
                    <div class="sliders-container">
                        <div class="slider-group">
                            <label for="logement">
                                <i class="fas fa-home"></i> Logement
                                <span class="value-display" id="logement-value"><?= $repartition['logement'] ?>%</span>
                            </label>
                            <input type="range" id="logement" min="0" max="100" value="<?= $repartition['logement'] ?>" class="budget-slider">
                        </div>

                        <div class="slider-group">
                            <label for="alimentation">
                                <i class="fas fa-utensils"></i> Alimentation
                                <span class="value-display" id="alimentation-value"><?= $repartition['alimentation'] ?>%</span>
                            </label>
                            <input type="range" id="alimentation" min="0" max="100" value="<?= $repartition['alimentation'] ?>" class="budget-slider">
                        </div>

                        <div class="slider-group">
                            <label for="transport">
                                <i class="fas fa-car"></i> Transport
                                <span class="value-display" id="transport-value"><?= $repartition['transport'] ?>%</span>
                            </label>
                            <input type="range" id="transport" min="0" max="100" value="<?= $repartition['transport'] ?>" class="budget-slider">
                        </div>

                        <div class="slider-group">
                            <label for="sante">
                                <i class="fas fa-heartbeat"></i> Santé
                                <span class="value-display" id="sante-value"><?= $repartition['sante'] ?>%</span>
                            </label>
                            <input type="range" id="sante" min="0" max="100" value="<?= $repartition['sante'] ?>" class="budget-slider">
                        </div>

                        <div class="slider-group">
                            <label for="education">
                                <i class="fas fa-graduation-cap"></i> Éducation
                                <span class="value-display" id="education-value"><?= $repartition['education'] ?>%</span>
                            </label>
                            <input type="range" id="education" min="0" max="100" value="<?= $repartition['education'] ?>" class="budget-slider">
                        </div>

                        <div class="slider-group">
                            <label for="loisirs">
                                <i class="fas fa-gamepad"></i> Loisirs
                                <span class="value-display" id="loisirs-value"><?= $repartition['loisirs'] ?>%</span>
                            </label>
                            <input type="range" id="loisirs" min="0" max="100" value="<?= $repartition['loisirs'] ?>" class="budget-slider">
                        </div>

                        <div class="slider-group">
                            <label for="epargne">
                                <i class="fas fa-piggy-bank"></i> Épargne
                                <span class="value-display" id="epargne-value"><?= $repartition['epargne'] ?>%</span>
                            </label>
                            <input type="range" id="epargne" min="0" max="100" value="<?= $repartition['epargne'] ?>" class="budget-slider">
                        </div>
                    </div>

                    <div class="total-display">
                        <span class="total-label">Total :</span>
                        <span class="total-value" id="total-value">100%</span>
                        <span class="total-status" id="total-status">✓ Équilibré</span>
                    </div>

                    <div class="actions">
                        <button type="button" id="reset-btn" class="btn btn-secondary">
                            <i class="fas fa-undo"></i> Réinitialiser
                        </button>
                        <button type="button" id="save-btn" class="btn btn-primary">
                            <i class="fas fa-save"></i> Sauvegarder
                        </button>
                    </div>
                </div>

                <!-- Section de visualisation -->
                <div class="visualization-section">
                    <div class="chart-container">
                        <canvas id="repartitionChart"></canvas>
                    </div>
                    
                    <div class="suggestions">
                        <h3>Suggestions automatiques</h3>
                        <div class="suggestion-cards">
                            <div class="suggestion-card" data-profile="conservateur">
                                <h4>Profil conservateur</h4>
                                <p>Logement: 25% | Alimentation: 20% | Épargne: 20%</p>
                                <button class="btn-apply" data-profile="conservateur">Appliquer</button>
                            </div>
                            <div class="suggestion-card" data-profile="equilibre">
                                <h4>Profil équilibré</h4>
                                <p>Logement: 30% | Alimentation: 25% | Épargne: 15%</p>
                                <button class="btn-apply" data-profile="equilibre">Appliquer</button>
                            </div>
                            <div class="suggestion-card" data-profile="dynamique">
                                <h4>Profil dynamique</h4>
                                <p>Logement: 35% | Alimentation: 30% | Épargne: 10%</p>
                                <button class="btn-apply" data-profile="dynamique">Appliquer</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php include_once 'includes/footer.php'; ?>

    <script src="assets/js/repartition.js"></script>
</body>
</html> 