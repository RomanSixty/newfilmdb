<!DOCTYPE html>
<html>
  <head>
    <title>LX' Filmdb</title>
    <meta charset="utf-8"/>
  </head>
  <link rel="stylesheet" href="./newfilmdb.css"/>
  <body>
<?php


require ( 'lib_imdb.php' );
require ( 'lib_mongofilmdb.php' );
require ( 'lib_html.php' );

echo getDashboard();

$movies = getMovieList ( getFilters ( $_POST ) );

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