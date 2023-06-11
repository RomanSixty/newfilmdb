<!DOCTYPE html>
<html>
  <head>
    <title>LX' Filmdb</title>
    <meta charset="utf-8"/>
    <link rel="stylesheet" href="css/newfilmdb.css" type="text/css"/>
  </head>
  <body>
<?php

require ( 'lib_imdb.php' );
require ( 'lib_omdb.php' );
require ( 'lib_sqlitedb.php' );
require ( 'lib_html.php' );

$db = new sqlitedb();

$_REQUEST [ 'genre' ][] = -3; // -Short
$_REQUEST [ 'type'  ][] =  1; // Movie

$movies = $db -> getMovieList ( $db -> getFilters ( $_REQUEST ) );

echo getDashboard ( count ( $movies ) );

echo '<section id="list">';
foreach ( $movies as $movie )
    echo getMovieSnippet ( $movie );
echo '</section>';

$random = array_rand ( $movies );

echo '<aside id="details">';
echo getMovieDetails ( $movies [ $random ][ 'imdb_id' ] );
echo '</aside>';

?>

  <script src="js/jquery-1.7.min.js"></script>
  <script src="js/newfilmdb.js"></script>
  </body>
</html>
