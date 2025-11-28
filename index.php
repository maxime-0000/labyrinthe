
<title>Labyrinthe</title>

<?php
echo "<body>";
/***************** ÉCRAN D’ACCUEIL AVANT JEU *****************/
if (!isset($_GET["run"])): ?>
    <div id="accueil">
        <h1 class="debutjeux">Jeu du Labyrinthe, règles du jeu</h1>
        <ul>
            <li>🎮 Objectif : Explorer le labyrinthe et trouver la sortie.</li>
            <li>Déplacements : Cliquez sur un couloir pour avancer.</li>
            <li>Clés 🔑 : Ramassez-les pour ouvrir les passages verrouillés.</li>
            <li>Passages verrouillés : Utilisez une clé pour les franchir.</li>
            <li>Recommencer : Cliquez sur “Recommencer une partie” pour repartir du début.</li>
        </ul>
        <button id="btnStart">Lancer le jeu</button>
    </div>
    <script>
    document.getElementById("btnStart").addEventListener("click", function () {
        window.location.href = "?run=1"; // Lance la partie
    });
    </script>
<?php
    exit;
endif;
/***************** DÉBUT DU JEU *****************/
session_start();
$db = new SQLite3("labyrinthe.db");
/***************** INVENTAIRE *****************/
if (!isset($_SESSION["nbCle"])) {
    $_SESSION["nbCle"] = 0; // commence à 0
}
if (!isset($_SESSION["cles_ramassees"])) {
    $_SESSION["cles_ramassees"] = [];
}
if (!isset($_SESSION["cle"])) {
    $_SESSION["cle"] = false; // pas de clé au départ
}
/***************** POSITION ACTUELLE *****************/
if (isset($_GET["position"])) {
    $position = (int)$_GET["position"];
    if (isset($_GET["grille_ouverte"]) && $_SESSION["cle"] === true) {
        $_SESSION["cle"] = false; // consomme une clé
        $_SESSION["nbCle"] -= 1;
    }
} else {
    // case de départ
    $req = $db->query("SELECT id FROM couloir WHERE type = 'depart' LIMIT 1");
    $row = $req->fetchArray(SQLITE3_ASSOC);
    $position = $row["id"];
}
/***************** TYPE DE LA CASE *****************/
$info = $db->query("SELECT type FROM couloir WHERE id = $position")->fetchArray(SQLITE3_ASSOC);
$type_actuel = $info["type"] ?? "inconnu";
/***************** RAMASSAGE CLÉ *****************/
if (strtolower($type_actuel) === "cle" && !in_array($position, $_SESSION["cles_ramassees"])) {
    $_SESSION["nbCle"] += 1;
    $_SESSION["cles_ramassees"][] = $position;
    $_SESSION["cle"] = true; // joueur possède au moins une clé
    echo "<p><b>Vous avez ramassé une clé ! 🔑</b></p>";
}
/***************** OUTILS *****************/
function normaliserDirection($dir) {
    $dir = strtoupper(trim($dir));
    return in_array($dir, ["N","S","E","O"]) ? $dir : "Secret";
}
function directionFull($d) {
    return [
        "N" => "NORD",
        "S" => "SUD",
        "E" => "EST",
        "O" => "OUEST"
    ][$d] ?? "SECRET";
}
/***************** PASSAGES POSSIBLES *****************/
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
/***************** AFFICHAGE DU JEU *****************/
echo "<h1>Position : Couloir $position (type : $type_actuel)</h1>";
echo $_SESSION["nbCle"] > 0
    ? "<p><b>Inventaire : {$_SESSION['nbCle']} clé(s) disponible(s) 🔑</b></p>"
    : "<p><b>Inventaire : aucune clé</b></p>";
echo "<h2>Déplacements possibles :</h2><ul>";
while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $couloir_dispo  = $row["couloir_dispo"];
    $direction      = normaliserDirection($row["direction"]);
    $direction_text = directionFull($direction);
    $type_passage   = $row["type_passage"];
    // Passage bloqué si pas de clé
    if ($type_passage === "grille" && $_SESSION["cle"] === false) {
        echo "<li>🚫 Couloir $couloir_dispo bloqué (grille, pas de clé)</li>";
        continue;
    }
    // Passage avec grille et clé disponible
    if ($type_passage === "grille" && $_SESSION["cle"] === true) {
        echo "<li>🔒 Couloir $couloir_dispo verrouillé ($direction_text) — utiliser la clé ?
              <a href='?position=$couloir_dispo&grille_ouverte=1&run=1'>Oui</a>
              </li>";
        continue;
    }
    // Passage libre
    echo "<li>➡ Couloir $couloir_dispo disponible —
          <a href='?position=$couloir_dispo&run=1'>Aller</a> ($direction_text)
          </li>";
}
echo "</ul>";
/***************** BOUTONS RESET *****************/
echo "
<form method='post'>
    <button type='submit' name='reset_session'>Recommencer la partie</button>
</form>
";
/***************** RESET SESSION *****************/
if (isset($_POST["reset_session"])) {
    $_SESSION["nbCle"] = 0;
    $_SESSION["cles_ramassees"] = [];
    $_SESSION["cle"] = false;
    echo "<p><b>Inventaire réinitialisé !</b></p>";
    echo "<script>window.location.href = window.location.href;</script>";
    session_destroy();
    header("Location: ".$_SERVER["PHP_SELF"]);
    exit;
}
/***************** RESET : RETOUR ÉCRAN ACCUEIL *****************/
echo "</body>";
?>
