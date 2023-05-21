<!DOCTYPE html>
<html>
<head>
    <title>Ma Collection de Films</title>
    <style>
        .alert {
            padding: 10px;
            margin-bottom: 10px;
            border-radius: 4px;
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>
<body>
    <h1>Ma Collection de Films</h1>

    <?php
    $host = 'db';
    $user = 'nathan';
    $password = '444719';
    $database = 'movie_collection';

    $connection = new mysqli($host, $user, $password, $database);

    if ($connection->connect_error) {
        die('Erreur de connexion : ' . $connection->connect_error);
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $title = trim($_POST['title']);
        $director = trim($_POST['director']);
        $releaseYear = trim($_POST['release_year']);

        if (empty($title) || empty($director) || empty($releaseYear)) {
            echo '<div class="alert alert-error">Veuillez remplir tous les champs.</div>';
        } else {
            $title = $connection->real_escape_string($title);
            $director = $connection->real_escape_string($director);
            $releaseYear = intval($releaseYear);

            $checkDuplicateSql = "SELECT * FROM films WHERE title = '$title' AND director = '$director' AND release_year = $releaseYear";
            $duplicateResult = $connection->query($checkDuplicateSql);

            if ($duplicateResult->num_rows > 0) {
                echo '<div class="alert alert-error">Ce film existe déjà dans la collection.</div>';
            } else {
                $insertSql = "INSERT INTO films (title, director, release_year) VALUES ('$title', '$director', $releaseYear)";

                if ($connection->query($insertSql) === TRUE) {
                    echo '<div class="alert alert-success">Film ajouté avec succès !</div>';
                } else {
                    echo '<div class="alert alert-error">Erreur lors de l\'ajout du film : ' . $connection->error . '</div>';
                }
            }
        }
    }

    if (isset($_GET['delete'])) {
        $filmId = $_GET['delete'];

        $deleteSql = "DELETE FROM films WHERE id = $filmId";

        if ($connection->query($deleteSql) === TRUE) {
            echo '<div class="alert alert-success">Film supprimé avec succès !</div>';
        } else {
            echo '<div class="alert alert-error">Erreur lors de la suppression du film : ' . $connection->error . '</div>';
        }
    }
    ?>

    <h2>Ajouter un Film</h2>
    <form method="POST" action="<?php echo $_SERVER['PHP_SELF']; ?>">
        <label for="title">Titre :</label>
        <input type="text" name="title" required><br>

        <label for="director">Réalisateur :</label>
        <input type="text" name="director" required><br>

        <label for="release_year">Année de sortie :</label>
        <input type="number" name="release_year" required><br>

        <input type="submit" value="Ajouter le Film">
    </form>

    <h2>Mes Films</h2>

    <?php
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';

    $searchSql = "SELECT * FROM films";
    if (!empty($search)) {
        $search = $connection->real_escape_string($search);
        $searchSql .= " WHERE title LIKE '%$search%'";
    }

    $result = $connection->query($searchSql);

    if ($result->num_rows > 0) {
        echo '<ul>';

        while ($row = $result->fetch_assoc()) {
            echo '<li>' . $row['title'] . ' (' . $row['director'] . ', ' . $row['release_year'] . ') ';
            echo '<a href="?delete=' . $row['id'] . '">Supprimer</a></li>';
        }

        echo '</ul>';
    } else {
        echo '<p>Aucun film trouvé.</p>';
    }

    $connection->close();
    ?>
</body>
</html>
