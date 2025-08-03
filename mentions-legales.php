<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
session_regenerate_id(true);
 include_once 'includes/header.php'; ?>

<main class="main-content" style="padding: 4rem 2rem;">
    <div class="container">
        <div class="page-header">
            <h1>Mentions Légales</h1>
        </div>
        <div class="legal-content" style="background: #fff; padding: 2rem; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
            <h2>1. Éditeur du site</h2>
            <p><strong>Nom de l'entreprise :</strong> TUNGO Fintech</p>
            <p><strong>Adresse :</strong> Abidjan, Côte d'Ivoire</p>
            <p><strong>Email :</strong> contact@tungo.africa</p>

            <h2>2. Hébergement</h2>
            <p><strong>Hébergeur :</strong> [Nom de l'hébergeur]</p>
            <p><strong>Adresse :</strong> [Adresse de l'hébergeur]</p>
            <p><strong>Téléphone :</strong> [Téléphone de l'hébergeur]</p>

            <h2>3. Propriété intellectuelle</h2>
            <p>L'ensemble de ce site relève de la législation internationale sur le droit d'auteur et la propriété intellectuelle. Tous les droits de reproduction sont réservés, y compris pour les documents téléchargeables et les représentations iconographiques et photographiques.</p>

            <h2>4. Données personnelles</h2>
            <p>Les informations recueillies font l’objet d’un traitement informatique destiné à la gestion de votre compte utilisateur. Conformément à la loi, vous bénéficiez d’un droit d’accès et de rectification aux informations qui vous concernent, que vous pouvez exercer en vous adressant à notre service client.</p>
            
            <h2>5. Cookies</h2>
            <p>Le site TUNGO peut être amené à vous demander l’acceptation des cookies pour des besoins de statistiques et d'affichage. Un cookie est une information déposée sur votre disque dur par le serveur du site que vous visitez.</p>
        </div>
    </div>
</main>

<?php include_once 'includes/footer.php'; ?>
