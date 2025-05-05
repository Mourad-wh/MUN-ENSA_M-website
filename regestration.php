<?php
$servername = "localhost";
$username = "root"; // Change selon ton serveur
$password = ""; // Mets ton mot de passe MySQL si nécessaire
$dbname = "event_registration";

// Connexion à MySQL
$conn = new mysqli($servername, $username, $password, $dbname);

// Vérifier la connexion
if ($conn->connect_error) {
    die("Échec de la connexion : " . $conn->connect_error);
}

// Retrieve encryption key and IV from environment variables
$encryptionKey = 'dccd65c5d69e0c76c86ca4cf0d3f6fdd';
$iv = 'cc6e63b457fa1784';

// Enable error reporting for debugging
// error_reporting(E_ALL);
// ini_set('display_errors', 1);

// Vérifier si les données POST sont présentes
if (!isset($_POST['name'], $_POST['email'], $_POST['company'], $_POST['role'])) {
    http_response_code(400);
    exit;
}

// Récupérer les données POST
$name = $_POST['name'];
$email = $_POST['email'];
$company = $_POST['company'];
$role = $_POST['role'];
$days = isset($_POST['days']) ? $_POST['days'] : '';

// Encrypt the email using AES-256-CBC
$encryptedEmail = openssl_encrypt($email, 'AES-256-CBC', $encryptionKey, 0, $iv);

if ($encryptedEmail === false) {
    http_response_code(500);
    exit;
}

// Insérer les données dans la base de données
$stmt = $conn->prepare("INSERT INTO registrations (full_name, email, company, job) VALUES (?, ?, ?, ?)");
$stmt->bind_param("ssss", $name, $encryptedEmail, $company, $role);

if (!$stmt->execute()) {
    http_response_code(500);
    exit;
}

$stmt->close();
$conn->close();

// Charger le template du badge
$templatePath = __DIR__ . '/Images/registration/template.jpg';
$template = imagecreatefromjpeg($templatePath);

if (!$template) {
    http_response_code(500);
    exit;
}

// Définir les couleurs
$black = imagecolorallocate($template, 0, 0, 0);

// Définir la police (Assurez-vous que le fichier TTF existe)
$fontPath = __DIR__ . '/fonts/Poppins-Bold.ttf';

if (!file_exists($fontPath)) {
    http_response_code(500);
    exit;
}

// Ajouter le texte sur le badge
imagettftext($template, 50, 0, 100, 500, $black, $fontPath, $name);
imagettftext($template, 50, 0, 100, 650, $black, $fontPath, "$role at");
imagettftext($template, 50, 0, 100, 800, $black, $fontPath, $company);

if (!empty($days)) {
    $dayText = is_array($days) ? implode(", ", $days) : $days;
    imagettftext($template, 50, 0, 100, 950, $black, $fontPath, "Visiting Day(s): $dayText");
}

// Générer et envoyer l'image
header('Content-Type: image/png');
header('Content-Disposition: attachment; filename="My Badge.png"');
imagepng($template);
imagedestroy($template);
?>
