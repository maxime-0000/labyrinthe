<!-- modification de la page  -->



<!DOCTYPE html>
<html lang = "fr" >
   <head> 
<meta charset="utf-8" >
<link rel="stylesheet" href="style.css" >

  
<?php 
/* vue pour mobile sans zoom */
echo '<meta name="viewport" content="width=device-width" >';
/* auteur : Patrick JAUD */
echo '<meta name="author" content="Maxime DIGNE">'; 




/* inclure les titres */ 
if (isset($_GET['pages']))  # isset signifie si la variable existe. 
{ 
      switch($_GET['pages']) # pour étudier les différentes valeurs
      {
    
      case '1':  # si menu vaut 1
      include('pages/page1-h.php'); # charge la page-h.php correspondant à l'en-tête
      break;

      case '2':  
      include('pages/page2-h.php');
      break;
 
              
      /* page d'accueil */
      default: #pour les autres valeurs possible de menu
      include('pages/page0-h.php');
      } 
     
}


else  # si menu n'a pas été définie comme variable 
/* page d'accueil */ 

{  include('pages/page0-h.php'); 
}

      

echo '</head>';
 
?>

<body>

<!-- appel nav et header -->
<?php include("include/header.php") ; 

      include("include/nav.php") ; 
    

      
/* les pages */


     
if (isset($_GET['pages']))
{
    
      switch($_GET['pages'])
      {

      case '1': 
      include("pages/page1.php");
      break;
      
      case '2':
      include("pages/page2.php");
      break;

      default:
      include("pages/page0.php") ;
      }
}

else 
{
include("pages/page0.php") ;

}?>



<?php   
include("include/footer.php") ;


/* appel fonction javascript  */
      include("include/javascript.php");  # on inclut la fonction javascript
      ?>
</body>
</html>
 





<?php
 
    //Documentation php pour sqlite : https://www.php.net/manual/en/book.sqlite3.php
   
    /* Paramètres */
    $bdd_fichier = 'labyrinthe.db'; //Fichier de la base de données
    $type = 'vide';         //Type de couloir à lister
   
 
    $sqlite = new SQLite3($bdd_fichier);        //On ouvre le fichier de la base de données
   
    /* Instruction SQL pour récupérer la liste des pieces adjacentes à la pièce paramétrée */
    $sql = 'SELECT couloir.id, couloir.type FROM couloir WHERE type=:type';
 
 
    $sql_depart = "SELECT id FROM couloir WHERE type = 'depart'";
    $res_depart = $sqlite->query($sql_depart);
    $row_depart = $res_depart->fetchArray(SQLITE3_ASSOC);
    $depart_id = $row_depart['id'];
 
 
    $sql_possible = 'SELECT * from passage';
   
 
 
 
    /* Préparation de la requete et de ses paramètres */
    $requete = $sqlite -> prepare($sql_possible);  
    $requete -> bindValue(':type', $type, SQLITE3_TEXT);
   
    $result = $requete -> execute();    //Execution de la requête et récupération du résultat
 
    /* On génère et on affiche notre page HTML avec la liste de nos films */
    echo "<!DOCTYPE html>\n";       //On demande un saut de ligne avec \n, seulement avec " et pas '
    echo "<html lang=\"fr\"><head><meta charset=\"UTF-8\">\n";  //Avec " on est obligé d'échapper les " a afficher avec \
    echo "<title>Liste des couloirs</title>\n";
    echo "</head>\n";
   
    echo "<body>\n";
    echo "<h1>Liste des couloirs</h1>\n";
    echo "<ul>";
    echo "<h2> Vous êtes dans la salle $depart_id </h2>";
    while($passage = $result -> fetchArray(SQLITE3_ASSOC)) {
        if ($passage['couloir1'] == $depart_id ){
       
       
            echo '<li>Le passage vers la salle '.$passage['couloir2'].'
             est disponible a la position '.$passage['position2'].'</li>';
       
       
        }
        if ($passage['couloir2'] == $depart_id){
       
       
            echo '<li>Le passage vers la salle '.$passage['couloir1'].' 
            est disponible a la position '.$passage['position1'].'</li>';
       
       
        }
    }
 
    echo "</ul>";
    echo "</body>\n";
    echo "</html>\n";
   
   
    $sqlite -> close();         //On ferme bien le fichier de la base de données avant de terminer!
   
?>

<button onclick="window.location.href='index.php'">bouton test</button>

