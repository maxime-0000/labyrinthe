<title>Labyrinthe</title>
<link rel="stylesheet" href="style.css">

<?php
echo "<body>";

/* ÉCRAN D’ACCUEIL AVANT LE JEU */
if (!isset($_GET["run"])): ?>
    <div id="accueil">
        <h1 class="debutjeux">Jeu du Labyrinthe, règles du jeu</h1>
        <ul>
            🎮 Objectif : Explorer le labyrinthe et trouver la sortie.<br>
            Déplacements : Cliquez sur un couloir pour avancer.<br>
            Clés 🔑 : Ramassez-les pour ouvrir les passages verrouillés.<br>
            Passages verrouillés : Utilisez une clé pour les franchir.<br>
            Recommencer : Cliquez sur “Recommencer une partie” pour repartir du début.<br>
        </ul>
        <button id="btnStart">Lancer le jeu</button>
    </div>

    <script>
        document.getElementById("btnStart").addEventListener("click", function () {
            window.location.href = "?run=1";
        });
    </script>
<?php
    exit;
endif;


/* DÉBUT DU JEU */
session_start();
$db = new SQLite3("labyrinthe.db");

/* INVENTAIRE */
if (!isset($_SESSION["nbCle"])) $_SESSION["nbCle"] = 0;
if (!isset($_SESSION["cles_ramassees"])) $_SESSION["cles_ramassees"] = [];
if (!isset($_SESSION["cle"])) $_SESSION["cle"] = false;
if (!isset($_SESSION["pas"])) $_SESSION["pas"] = 0;

/* POSITION ACTUELLE */
if (isset($_GET["position"])) {
    $position = (int)$_GET["position"];

    if (isset($_GET["grille_ouverte"]) && $_SESSION["cle"] === true) {
        $_SESSION["cle"] = false;
        $_SESSION["nbCle"] -= 1;
    }

    if (!isset($_SESSION["position_precedente"]) || $_SESSION["position_precedente"] != $position) {
        $_SESSION["pas"] += 1;
        $_SESSION["position_precedente"] = $position;
    }

} else {
    // Départ
    $req = $db->query("SELECT id FROM couloir WHERE type = 'depart' LIMIT 1");
    $row = $req->fetchArray(SQLITE3_ASSOC);
    $position = $row["id"];
}

/* TYPE DE LA CASE */
$info = $db->query("SELECT type FROM couloir WHERE id = $position")->fetchArray(SQLITE3_ASSOC);
$type_actuel = $info["type"] ?? "inconnu";


/* ÉCRAN DE FIN AVANT TOUT AFFICHAGE DU JEU */
if ($type_actuel === "sortie") {

    echo "<div id='fin'>";
    echo "<h1>🎉 Bravo ! Vous avez terminé le labyrinthe 🎉</h1>";
    echo "<p>Vous avez trouvé la sortie du labyrinthe.</p>";
    echo "<p><b>Nombre total de pas : ".$_SESSION['pas']."</b></p>";
    echo "<p><b>Clés ramassées : ".$_SESSION['nbCle']."</b></p>";

    echo "<br><br>";

    /* BOUTON RESET */
    echo "
    <form method='post'>
        <button type='submit' name='reset_session'>Recommencer la partie</button>
    </form>
    ";

    if (isset($_POST["reset_session"])) {
        $_SESSION = [];
        session_destroy();
        header("Location: index.php");
        exit;
    }

    echo "</div>";
    echo "</body>";
    exit;
}


/* RAMASSAGE DE CLÉ */
if (strtolower($type_actuel) === "cle" && !in_array($position, $_SESSION["cles_ramassees"])) {
    $_SESSION["nbCle"] += 1;
    $_SESSION["cles_ramassees"][] = $position;
    $_SESSION["cle"] = true;
    echo "<p><b>Vous avez ramassé une clé ! 🔑</b></p>";
}


/* OUTILS */
function normaliserDirection($dir) {
    $dir = strtoupper(trim($dir));
    return in_array($dir, ["N", "S", "E", "O"]) ? $dir : "Secret";
}
function directionFull($d) {
    return [
        "N" => "NORD",
        "S" => "SUD",
        "E" => "EST",
        "O" => "OUEST"
    ][$d] ?? "SECRET";
}


/* PASSAGES POSSIBLES */
$sql = "
SELECT
    CASE WHEN couloir1 = :pos THEN couloir2 ELSE couloir1 END AS couloir_dispo,
    CASE WHEN couloir1 = :pos THEN position2 ELSE position1 END AS direction,
    type AS type_passage
FROM passage
WHERE couloir1 = :pos OR couloir2 = :pos
";
$stmt = $db->prepare($sql);
$stmt->bindValue(":pos", $position, SQLITE3_INTEGER);
$result = $stmt->execute();


/* AFFICHAGE DU JEU */
echo "<h1>Position : Couloir $position (type : $type_actuel)</h1>";

echo $_SESSION["nbCle"] > 0
    ? "<p><b>Inventaire : {$_SESSION['nbCle']} clé(s) 🔑</b></p>"
    : "<p><b>Inventaire : aucune clé</b></p>";

echo "<p><b>Nombre de pas effectués : ".$_SESSION['pas']."</b></p>";

echo "<h2>Déplacements possibles :</h2>";


/* LISTE DES DÉPLACEMENTS — SANS <li>, AVEC <br> */
while ($row = $result->fetchArray(SQLITE3_ASSOC)) {

    $couloir_dispo  = $row["couloir_dispo"];
    $direction      = normaliserDirection($row["direction"]);
    $direction_text = directionFull($direction);
    $type_passage   = $row["type_passage"];

    // Passage bloqué
    if ($type_passage === "grille" && $_SESSION["cle"] === false) {
        echo "🚫 Couloir $couloir_dispo bloqué (grille, pas de clé)<br>";
        continue;
    }

    // Passage verrouillé, clé disponible
    if ($type_passage === "grille" && $_SESSION["cle"] === true) {
        echo "🔒 Couloir $couloir_dispo verrouillé ($direction_text) — utiliser la clé ?
              <a class='btn-action' href='?position=$couloir_dispo&grille_ouverte=1&run=1'>Oui</a><br>";
        continue;
    }

    // Passage libre
    echo "➡ Couloir $couloir_dispo disponible —
          <a class='btn-move' href='?position=$couloir_dispo&run=1'>Aller</a> ($direction_text)<br>";
}


/* BOUTON RESET */
echo "
<form method='post'>
    <button type='submit' name='reset_session'>Recommencer la partie</button>
</form>
";

if (isset($_POST["reset_session"])) {
    $_SESSION = [];
    session_destroy();
    header("Location: index.php");
    exit;
}

echo "</body>";
?>