<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
session_regenerate_id(true);

session_start();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Simulation - TUNGO</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/simulation.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="simulation-container">
        <div class="simulation-header">
            <h1>Simulation de votre budget</h1>
            <p>Découvrez comment optimiser la répartition de vos revenus</p>
        </div>

        <div class="simulation-progress">
            <div class="progress-bar">
                <div class="progress-step active" data-step="1">
                    <span class="step-number">1</span>
                    <span class="step-label">Revenus</span>
                </div>
                <div class="progress-step" data-step="2">
                    <span class="step-number">2</span>
                    <span class="step-label">Profil</span>
                </div>
                <div class="progress-step" data-step="3">
                    <span class="step-number">3</span>
                    <span class="step-label">Répartition</span>
                </div>
                <div class="progress-step" data-step="4">
                    <span class="step-number">4</span>
                    <span class="step-label">Résultats</span>
                </div>
            </div>
        </div>

        <form id="simulation-form" class="simulation-form">
            <!-- Étape 1: Revenus -->
            <div class="form-step active" data-step="1">
                <div class="step-content">
                    <h2>Quel est votre revenu mensuel ?</h2>
                    <p>Entrez le montant total de vos revenus mensuels</p>
                    
                    <div class="input-group">
                        <label for="revenu">Revenu mensuel (FCFA)</label>
                        <div class="input-wrapper">
                            <input type="number" id="revenu" name="revenu" required min="10000" step="1000" placeholder="Ex: 150000">
                            <span class="currency">FCFA</span>
                        </div>
                    </div>

                    <div class="input-group">
                        <label>Sources de revenus</label>
                        <div class="revenue-sources">
                            <div class="source-item">
                                <input type="checkbox" id="salaire" name="sources[]" value="salaire" checked>
                                <label for="salaire">Salaire</label>
                            </div>
                            <div class="source-item">
                                <input type="checkbox" id="freelance" name="sources[]" value="freelance">
                                <label for="freelance">Freelance</label>
                            </div>
                            <div class="source-item">
                                <input type="checkbox" id="commerce" name="sources[]" value="commerce">
                                <label for="commerce">Commerce</label>
                            </div>
                            <div class="source-item">
                                <input type="checkbox" id="investissement" name="sources[]" value="investissement">
                                <label for="investissement">Investissements</label>
                            </div>
                            <div class="source-item">
                                <input type="checkbox" id="autre" name="sources[]" value="autre">
                                <label for="autre">Autre</label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Étape 2: Profil -->
            <div class="form-step" data-step="2">
                <div class="step-content">
                    <h2>Quel est votre profil financier ?</h2>
                    <p>Sélectionnez le profil qui correspond le mieux à votre situation</p>
                    
                    <div class="profile-options">
                        <div class="profile-card" data-profile="regulier">
                            <div class="profile-icon">
                                <i class="fas fa-calendar-check"></i>
                            </div>
                            <h3>Régulier</h3>
                            <p>Revenus fixes et prévisibles chaque mois</p>
                            <ul>
                                <li>Salaire mensuel fixe</li>
                                <li>Dépenses régulières</li>
                                <li>Budget stable</li>
                            </ul>
                        </div>
                        
                        <div class="profile-card" data-profile="irregulier">
                            <div class="profile-icon">
                                <i class="fas fa-chart-line"></i>
                            </div>
                            <h3>Irrégulier</h3>
                            <p>Revenus variables selon les périodes</p>
                            <ul>
                                <li>Freelance ou commerce</li>
                                <li>Revenus saisonniers</li>
                                <li>Budget adaptatif</li>
                            </ul>
                        </div>
                        
                        <div class="profile-card" data-profile="mixte">
                            <div class="profile-icon">
                                <i class="fas fa-balance-scale"></i>
                            </div>
                            <h3>Mixte</h3>
                            <p>Combinaison de revenus fixes et variables</p>
                            <ul>
                                <li>Salaire + activités</li>
                                <li>Revenus complémentaires</li>
                                <li>Flexibilité maximale</li>
                            </ul>
                        </div>
                    </div>
                    
                    <input type="hidden" id="profil" name="profil" required>
                </div>
            </div>

            <!-- Étape 3: Répartition -->
            <div class="form-step" data-step="3">
                <div class="step-content">
                    <h2>Comment souhaitez-vous répartir votre budget ?</h2>
                    <p>Choisissez entre une répartition automatique ou manuelle</p>
                    
                    <div class="repartition-options">
                        <div class="option-card" data-mode="automatique">
                            <div class="option-icon">
                                <i class="fas fa-magic"></i>
                            </div>
                            <h3>Automatique</h3>
                            <p>TUNGO propose une répartition optimale basée sur votre profil</p>
                        </div>
                        
                        <div class="option-card" data-mode="manuel">
                            <div class="option-icon">
                                <i class="fas fa-sliders-h"></i>
                            </div>
                            <h3>Manuel</h3>
                            <p>Ajustez vous-même les pourcentages selon vos priorités</p>
                        </div>
                    </div>
                    
                    <input type="hidden" id="mode" name="mode" required>
                    
                    <!-- Répartition manuelle (cachée par défaut) -->
                    <div id="repartition-manuelle" class="repartition-manuelle" style="display: none;">
                        <h3>Ajustez vos pourcentages</h3>
                        <div class="sliders-container">
                            <div class="slider-group">
                                <label for="logement">Logement</label>
                                <div class="slider-wrapper">
                                    <input type="range" id="logement" name="logement" min="0" max="100" value="30" class="budget-slider">
                                    <span class="slider-value">30%</span>
                                </div>
                            </div>
                            
                            <div class="slider-group">
                                <label for="alimentation">Alimentation</label>
                                <div class="slider-wrapper">
                                    <input type="range" id="alimentation" name="alimentation" min="0" max="100" value="25" class="budget-slider">
                                    <span class="slider-value">25%</span>
                                </div>
                            </div>
                            
                            <div class="slider-group">
                                <label for="transport">Transport</label>
                                <div class="slider-wrapper">
                                    <input type="range" id="transport" name="transport" min="0" max="100" value="15" class="budget-slider">
                                    <span class="slider-value">15%</span>
                                </div>
                            </div>
                            
                            <div class="slider-group">
                                <label for="sante">Santé</label>
                                <div class="slider-wrapper">
                                    <input type="range" id="sante" name="sante" min="0" max="100" value="10" class="budget-slider">
                                    <span class="slider-value">10%</span>
                                </div>
                            </div>
                            
                            <div class="slider-group">
                                <label for="loisirs">Loisirs</label>
                                <div class="slider-wrapper">
                                    <input type="range" id="loisirs" name="loisirs" min="0" max="100" value="10" class="budget-slider">
                                    <span class="slider-value">10%</span>
                                </div>
                            </div>
                            
                            <div class="slider-group">
                                <label for="epargne">Épargne</label>
                                <div class="slider-wrapper">
                                    <input type="range" id="epargne" name="epargne" min="0" max="100" value="10" class="budget-slider">
                                    <span class="slider-value">10%</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="total-display">
                            <span>Total: <strong id="total-percentage">100%</strong></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Étape 4: Résultats -->
            <div class="form-step" data-step="4">
                <div class="step-content">
                    <h2>Votre répartition optimale</h2>
                    <p>Voici comment TUNGO recommande de répartir votre budget</p>
                    
                    <div class="results-container">
                        <div class="chart-container">
                            <canvas id="budget-chart"></canvas>
                        </div>
                        
                        <div class="results-details">
                            <div class="result-item">
                                <span class="category">Logement</span>
                                <span class="amount" id="logement-amount">0 FCFA</span>
                                <span class="percentage" id="logement-percent">0%</span>
                            </div>
                            <div class="result-item">
                                <span class="category">Alimentation</span>
                                <span class="amount" id="alimentation-amount">0 FCFA</span>
                                <span class="percentage" id="alimentation-percent">0%</span>
                            </div>
                            <div class="result-item">
                                <span class="category">Transport</span>
                                <span class="amount" id="transport-amount">0 FCFA</span>
                                <span class="percentage" id="transport-percent">0%</span>
                            </div>
                            <div class="result-item">
                                <span class="category">Santé</span>
                                <span class="amount" id="sante-amount">0 FCFA</span>
                                <span class="percentage" id="sante-percent">0%</span>
                            </div>
                            <div class="result-item">
                                <span class="category">Loisirs</span>
                                <span class="amount" id="loisirs-amount">0 FCFA</span>
                                <span class="percentage" id="loisirs-percent">0%</span>
                            </div>
                            <div class="result-item">
                                <span class="category">Épargne</span>
                                <span class="amount" id="epargne-amount">0 FCFA</span>
                                <span class="percentage" id="epargne-percent">0%</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="action-buttons">
                        <button type="button" id="save-simulation" class="btn btn-primary">
                            <i class="fas fa-save"></i> Sauvegarder
                        </button>
                        <button type="button" id="new-simulation" class="btn btn-secondary">
                            <i class="fas fa-redo"></i> Nouvelle simulation
                        </button>
                    </div>
                </div>
            </div>

            <!-- Navigation -->
            <div class="form-navigation">
                <button type="button" id="prev-btn" class="btn btn-outline" style="display: none;">
                    <i class="fas fa-arrow-left"></i> Précédent
                </button>
                <button type="button" id="next-btn" class="btn btn-primary">
                    Suivant <i class="fas fa-arrow-right"></i>
                </button>
            </div>
        </form>
    </div>

    <?php include 'includes/footer.php'; ?>
    
    <script src="assets/js/main.js"></script>
    <script src="assets/js/simulation.js"></script>
</body>
</html> 