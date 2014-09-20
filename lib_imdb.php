<?php

/**
 * dieses Skript beinhaltet die Funktionen, mit denen
 * aus IMDBPHP die Filmdaten geladen und strukturiert werden
 */

require("imdbphp/imdb.class.php");
chdir ( 'imdbphp' );

/**
 * holt zu einer IMDB-ID alle gewünschten Filmdaten
 * und packt sie in die passende Struktur für die
 * MongoDB
 *
 * @param Integer $imdb_id
 * @return Array Filmdaten
 */
function getIMDbMovie ( $imdb_id )
{
	$movie = new imdb ( str_pad ( $imdb_id, 7, '0', STR_PAD_LEFT ) );

	$title_orig = $movie->orig_title();

	if ( empty ( $title_orig ) )
		$title_orig = $movie->title();

	// deutscher Titel
	$title_deu = $movie -> title();

	foreach ( (array) $movie -> alsoknow() as $aka )
		if (    $aka [ 'country' ] == 'Germany'
			 || $aka [ 'country' ] == 'West Germany' )
		{
			$title_deu = $aka [ 'title' ];
			break;
		}
		elseif ( $aka [ 'country' ] == 'International' )
			$title_deu = $aka [ 'title' ];

	// Regisseur
	foreach ( (array) $movie -> director() as $d )
		$directors[] = _charsetPrepare ( $d [ 'name' ] );

	// Schauspieler
	foreach ( (array) $movie->cast() as $c )
		$actors[] = _charsetPrepare ( $c [ 'name' ] );

	return array
	(
		'@imdb_id'    => intval ( $imdb_id ),
		'$imdb_photo'      => $movie->photo_localurl(),
		'$imdb_plot'       => _charsetPrepare ( $movie->plotoutline() ),
		'$imdb_rating'     => $movie->rating(),
		'@imdb_runtime'    => intval ( $movie->runtime() ),
		'$imdb_title_deu'  => _charsetPrepare ( $title_deu ),
		'$imdb_title_orig' => _charsetPrepare ( $title_orig ),
		'@imdb_year'       => $movie->year(),
		'$fulltext'        => '',

		'genres'     => $movie->genres(),
		'director'   => $directors,
		'cast'       => $actors
   );
}

/**
 * Helper-Funktion um gescrapete Daten in passenden Zeichensatz zu bringen
 *
 * @param String $string Zeichenkette
 * @return String normalisierte UTF8-Zeichenkette
 */
function _charsetPrepare ( $string )
{
	$string = strip_tags ( $string );
	$string = str_replace ( 'See full summary&nbsp;&raquo;', '', $string );

	$string = html_entity_decode ( $string );
	$string = str_replace ( '&#x27;', "'", $string );

	// offenbar nicht mehr nötig...
	//$string = utf8_encode ( $string );

	return trim ( $string );
}