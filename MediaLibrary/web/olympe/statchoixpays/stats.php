<?php
require_once '../../utils/auth.php';
require_once '../../utils/config.php';

// Démarrage de la session
session_start();

$username = $_SESSION['username'] ?? '';
$email = $_SESSION['email'] ?? '';
$loggedInUser = getLoggedInUser();

// Vérification si l'utilisateur est autorisé à accéder à la page
if ($username !== "Nathan" || $email !== "nathan.gregoar@yahoo.fr") {
    // Redirection vers la page d'accueil
    header("Location: ../accueil/index.php");
    exit();
}

// Connexion à la base de données (à adapter avec vos informations d'accès)
$host = 'db';
$dbuser = 'nathan';
$dbpassword = '444719';
$dbname = 'media_library';

$connection = new mysqli($host, $dbuser, $dbpassword, $dbname);

if ($connection->connect_error) {
    die('Erreur de connexion : ' . $connection->connect_error);
}

// Requête SQL pour compter le nombre d'enregistrements dans la table "olympe"
$query = "SELECT COUNT(id) AS total FROM olympe";
$result = $connection->query($query);

if ($result) {
    $row = $result->fetch_assoc();
    $totalGods = $row['total'];
} else {
    $totalGods = 0; // En cas d'erreur dans la requête
}

// Détermine si le texte doit être au singulier ou au pluriel
$text = ($totalGods == 1) ? "Dieu de l'Olympe a répondu" : "Dieux de l'Olympe ont répondu";

// Requête SQL pour récupérer les pays enregistrés dans le champ "pays_non" de tous les utilisateurs
$queryPaysNon = "SELECT pays_non FROM olympe WHERE pays_non IS NOT NULL";
$resultPaysNon = $connection->query($queryPaysNon);

$paysNonData = []; // Tableau pour stocker les données des pays non

if ($resultPaysNon) {
    while ($rowPaysNon = $resultPaysNon->fetch_assoc()) {
        $paysNonList = explode(',', $rowPaysNon['pays_non']); // Séparer les pays par des virgules
        foreach ($paysNonList as $paysNon) {
            $paysNon = trim($paysNon); // Supprimer les espaces autour du nom du pays
            if (!empty($paysNon)) {
                if (!isset($paysNonData[$paysNon])) {
                    $paysNonData[$paysNon] = 1;
                } else {
                    $paysNonData[$paysNon]++;
                }
            }
        }
    }
}

// Requête SQL pour récupérer les pays enregistrés dans le champ "pays_oui"
$queryPays = "SELECT pays_oui FROM olympe WHERE pays_oui IS NOT NULL";
$resultPays = $connection->query($queryPays);

$paysData = []; // Tableau pour stocker les données des pays

if ($resultPays) {
    while ($rowPays = $resultPays->fetch_assoc()) {
        $paysList = explode(',', $rowPays['pays_oui']); // Séparer les pays par des virgules
        foreach ($paysList as $pays) {
            $pays = trim($pays); // Supprimer les espaces autour du nom du pays
            if (!empty($pays) && !array_key_exists($pays, $paysNonData)) {
                if (!isset($paysData[$pays])) {
                    $paysData[$pays] = 1;
                } else {
                    $paysData[$pays]++;
                }
            }
        }
    }
}

// Récupère les budgets min et max
$queryBudgetMin = "SELECT MIN(budget_min) AS minBudget FROM olympe";
$queryBudgetMax = "SELECT MAX(budget_max) AS maxBudget FROM olympe";

$resultBudgetMin = $connection->query($queryBudgetMin);
$resultBudgetMax = $connection->query($queryBudgetMax);

$minBudget = 0;
$maxBudget = 0;

if ($resultBudgetMin && $resultBudgetMax) {
    $rowMin = $resultBudgetMin->fetch_assoc();
    $minBudget = $rowMin['minBudget'];

    $rowMax = $resultBudgetMax->fetch_assoc();
    $maxBudget = $rowMax['maxBudget'];
}

$averageBudget = ($minBudget + $maxBudget) / 2;

$connection->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>L'Olympe - Stats choix de destination</title>
    <link rel="stylesheet" type="text/css" href="./stats.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="navbar">
        <a href="../../accueil/index.php">Accueil</a>
        <a href="../../olympe/olympe.php">L'Olympe</a>
        <a href="../../olympe/statchoixpays/stats.php" style="color: #D7EBF3;">Stats</a>
        <a href="../../ecollyday/ecollyday.php">Ecollyday</a>        
    </div>
    <h1>Bienvenue dans l'Olympe <?php echo $username;?> - Stats choix de la destination Summer 2024</h1>
    <h2><?php echo $totalGods . " " . $text; ?> au formulaire !</h2>

    <div style="max-width: 20%;">
        <canvas id="barChartBudget" aria-label="Diagramme des budgets min, moyenne et max"></canvas>
    </div>

    <div style="max-width: 20%;">
        <canvas id="pieChartPaysOui" aria-label="Diagramme des pays où l'Olympe veut partir"></canvas>
    </div>

    <div style="max-width: 20%;">
        <canvas id="pieChartPaysNon" aria-label="Diagramme des pays où l'Olympe ne veut pas partir"></canvas>
    </div>

    <div style="max-width: 20%;">
        <canvas id="barChartTransport" aria-label="Diagramme des moyens de transport"></canvas>
    </div>

    <?php
    require_once '../../utils/auth.php';
    require_once '../../utils/config.php';

    // Connexion à la base de données
    $connection = new mysqli($host, $dbuser, $dbpassword, $dbname);

    if ($connection->connect_error) {
        die('Erreur de connexion : ' . $connection->connect_error);
    }

    // Récupération des utilisateurs ayant des enregistrements dans la table olympe
    $queryUsers = "SELECT DISTINCT added_by FROM olympe";
    $resultUsers = $connection->query($queryUsers);

    // Récupération des moyens de transport
    $transportOptions = ['Avion', 'Train', 'Bus', 'Bateau'];

    // Création du tableau
    echo '<table>';
    echo '<thead><tr><th>Pseudos</th>';
    foreach ($transportOptions as $transport) {
        echo '<th>' . $transport . '</th>';
    }
    echo '</tr></thead>';
        
    echo '<tbody>';
    while ($rowUser = $resultUsers->fetch_assoc()) {
        $userId = $rowUser['added_by'];

        $queryTransport = "SELECT transport FROM olympe WHERE added_by = $userId";
        $resultTransport = $connection->query($queryTransport);
        $transportChoices = [];

        while ($rowTransport = $resultTransport->fetch_assoc()) {
            $transportChoices = explode(',', $rowTransport['transport']);
        }

        echo '<tr>';
        echo '<td>' . getUserName($userId) . '</td>';

        foreach ($transportOptions as $transport) {
            $cellColor = in_array($transport, $transportChoices) ? 'green' : 'white';
            echo '<td style="background-color: ' . $cellColor . ';"></td>';
        }

        echo '</tr>';
    }
    echo '</tbody>';

    echo '</table>';

    // Fonction pour récupérer le nom d'utilisateur à partir de l'ID
    function getUserName($userId) {
        global $connection; // Assurez-vous que la connexion à la base de données est accessible ici

        $query = "SELECT username FROM users WHERE id = $userId";
        $result = $connection->query($query);

        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            return $row['username'];
        } else {
            return "Utilisateur inconnu";
        }
    }

    $connection->close();
    ?>

    <!-- Diagramme camembert pays -->
    <script>
    // Récupération du contexte du canvas pour le diagramme des pays oui
    var pieChartPaysOui = document.getElementById('pieChartPaysOui').getContext('2d');

    // Configuration des données pour le graphique des pays oui
    var chartDataPaysOui = {
        datasets: [{
            data: [<?php echo implode(",", array_values($paysData)); ?>],
            backgroundColor: ['#FF6384', '#36A2EB', '#FFCE56', '#8E44AD', '#16A085'], // Ajoutez plus de couleurs si nécessaire
        }],
        labels: <?php echo json_encode(array_keys($paysData)); ?>,
    };

    // Configuration du graphique camembert pour les pays oui
    var pieConfigPaysOui = {
        type: 'pie',
        data: chartDataPaysOui,
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                title: {
                    display: true,
                    text: "Diagramme des pays où l'Olympe veut partir",
                },
            },
            legend: {
                position: 'bottom',
            },
        },
    };

    // Création du graphique camembert pour les pays oui
    var myPieChartPaysOui = new Chart(pieChartPaysOui, pieConfigPaysOui);

    // Récupération du contexte du canvas pour le diagramme des pays non
    var pieChartPaysNon = document.getElementById('pieChartPaysNon').getContext('2d');

    // Configuration des données pour le graphique des pays non
    var chartDataPaysNon = {
        datasets: [{
            data: [<?php echo implode(",", array_values($paysNonData)); ?>],
            backgroundColor: ['#FF6384', '#36A2EB', '#FFCE56', '#8E44AD', '#16A085'], // Ajoutez plus de couleurs si nécessaire
        }],
        labels: <?php echo json_encode(array_keys($paysNonData)); ?>,
    };

    // Configuration du graphique camembert pour les pays non
    var pieConfigPaysNon = {
        type: 'pie',
        data: chartDataPaysNon,
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                title: {
                    display: true,
                    text: "Diagramme des pays où l'Olympe ne veut pas partir",
                },
            },
            legend: {
                position: 'bottom',
            },
        },
    };

    // Création du graphique camembert pour les pays non
    var myPieChartPaysNon = new Chart(pieChartPaysNon, pieConfigPaysNon);
    </script>

    <!-- Budget min et max -->
    <script>
    var barChartBudget = document.getElementById('barChartBudget').getContext('2d');

    var chartDataBudget = {
        labels: ['Budget Min', 'Moyenne', 'Budget Max'],
        datasets: [{
            label: 'Budget Min',
            data: [<?php echo $minBudget; ?>, 0, 0], // Notez l'utilisation de 0 pour les autres valeurs
            backgroundColor: 'rgba(255, 99, 132, 0.7)', // Couleur pour le budget min
            borderWidth: 1
        }, {
            label: 'Moyenne',
            data: [0, <?php echo $averageBudget; ?>, 0], // Notez l'utilisation de 0 pour les autres valeurs
            backgroundColor: 'rgba(54, 162, 235, 0.7)', // Couleur pour la moyenne
            borderWidth: 1
        }, {
            label: 'Budget Max',
            data: [0, 0, <?php echo $maxBudget; ?>], // Notez l'utilisation de 0 pour les autres valeurs
            backgroundColor: 'rgba(255, 206, 86, 0.7)', // Couleur pour le budget max
            borderWidth: 1
        }]
    };

    var barConfigBudget = {
        type: 'bar',
        data: chartDataBudget,
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                title: {
                    display: true,
                    text: 'Diagramme des budgets min, moyenne et max'
                }
            },
            scales: {
                x: {
                    stacked: true // Les barres seront empilées horizontalement
                },
                y: {
                    beginAtZero: true
                }
            },
            plugins: {
                legend: {
                    position: 'top', // Vous pouvez ajuster la position ici
                    labels: {
                        font: {
                            size: 10 // Vous pouvez ajuster la taille de la police ici
                        }
                    }
                }
            }
        }
    };

    var myBarChartBudget = new Chart(barChartBudget, barConfigBudget);
    </script>

</body>
</html>
