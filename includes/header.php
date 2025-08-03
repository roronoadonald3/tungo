<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Démarrer la session si ce n’est pas déjà fait
if (session_status() === PHP_SESSION_NONE) {
}

// Vérifier si l'utilisateur est connecté
$isLoggedIn = isset($_SESSION['user_id']);
?>
<header class="header">
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <nav class="navbar">
        <div class="nav-container">
            <div class="nav-logo">
                <a href="index.php">
                    <h2>TUNGO</h2>
                </a>
            </div>
            <ul class="nav-menu">
                <?php if ($isLoggedIn): ?>
                    <!-- Menu pour utilisateurs connectés -->
                    <li><a href="dashboard.php" class="nav-link">Mon tableau de bord</a></li>
                    <li><a href="revenus.php" class="nav-link">Revenus</a></li>
                    <li><a href="objectifs.php" class="nav-link">Objectifs</a></li>
                    <li><a href="repartition.php" class="nav-link">Répartition</a></li>
                    <li><a href="conseils.php" class="nav-link">Conseils</a></li>
                    <li class="dropdown">
                        <a href="#" class="nav-link dropdown-toggle">
                            <i class="fas fa-user"></i> Mon profil
                        </a>
                        <ul class="dropdown-menu">
                            <li><a href="profil.php">Modifier mon profil</a></li>
                            <li><a href="simulation.php">Nouvelle simulation</a></li>
                            <li><hr></li>
                            <li><a href="logout.php" class="logout-link">Déconnexion</a></li>
                        </ul>
                    </li>
                <?php else: ?>
                    <!-- Menu pour visiteurs -->
                    <li><a href="index.php" class="nav-link">Accueil</a></li>
                    <li><a href="simulation.php" class="nav-link">Simulation</a></li>
                    <li><a href="login.php" class="nav-link">Connexion</a></li>
                    <li><a href="signup.php" class="nav-btn">S'inscrire</a></li>
                <?php endif; ?>
            </ul>
            <div class="hamburger">
                <span></span>
                <span></span>
                <span></span>
            </div>
        </div>
    </nav>
</header>

<style>
.nav-logo a {
    text-decoration: none;
    color: inherit;
}

.dropdown {
    position: relative;
}

.dropdown-toggle {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.dropdown-menu {
    position: absolute;
    top: 100%;
    right: 0;
    background: var(--white);
    border-radius: 8px;
    box-shadow: var(--shadow-lg);
    min-width: 200px;
    opacity: 0;
    visibility: hidden;
    transform: translateY(-10px);
    transition: all 0.3s ease;
    z-index: 1000;
}

.dropdown:hover .dropdown-menu {
    opacity: 1;
    visibility: visible;
    transform: translateY(0);
}

.dropdown-menu li {
    list-style: none;
}

.dropdown-menu a {
    display: block;
    padding: 0.75rem 1rem;
    color: var(--text-dark);
    text-decoration: none;
    transition: background 0.3s ease;
}

.dropdown-menu a:hover {
    background: #f8fafc;
    color: var(--primary-color);
}

.dropdown-menu hr {
    margin: 0.5rem 0;
    border: none;
    border-top: 1px solid #e5e7eb;
}

.logout-link {
    color: var(--secondary-color) !important;
}

.logout-link:hover {
    background: #fef2f2 !important;
}

@media (max-width: 768px) {
    .dropdown-menu {
        position: static;
        opacity: 1;
        visibility: visible;
        transform: none;
        box-shadow: none;
        background: transparent;
        margin-left: 1rem;
    }

    .dropdown-menu a {
        padding: 0.5rem 0;
        color: var(--text-light);
    }
}
</style>
