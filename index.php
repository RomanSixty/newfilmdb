<!DOCTYPE html>
<html>
  <head>
    <title>LX' Filmdb</title>
    <meta charset="utf-8"/>
    <link rel="stylesheet" href="http://fonts.googleapis.com/css?family=Lusitana:400,700" type="text/css"/>
    <link rel="stylesheet" href="./newfilmdb.css" type="text/css"/>
  </head>
  <body>
<?php

require ( 'lib_imdb.php' );
require ( 'lib_mongofilmdb.php' );
require ( 'lib_html.php' );

$movies = getMovieList();

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

  <script src="./jquery-1.7.min.js"></script>
  <script src="./jquery.lazyload.min.js"></script>
  <script src="./newfilmdb.js"></script>
  </body>
</html>