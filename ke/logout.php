<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
session_regenerate_id(true);

require_once 'includes/auth.php';

// Déconnecter l'utilisateur
logoutUser();

// Rediriger vers la page d'accueil avec un message de succès
header('Location: index.php?logout=1');
exit();
?> 