<?php

/**
 * dieses Skript beinhaltet die Funktionen, mit denen
 * aus IMDBPHP die Filmdaten geladen und strukturiert werden
 */

require("imdbapi/imdb.class.php");
chdir ( 'imdbapi' );

/**
 * holt zu einer IMDB-ID alle gewünschten Filmdaten
 * und packt sie in die passende Struktur für die
 * MongoDB
 *
 * @param Integer $imdb_id
 * @return Array Filmdaten
 */
function getMovie ( $imdb_id )
{
    $movie = new imdb ( str_pad ( $imdb_id, 7, '0', STR_PAD_LEFT ) );

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
        'imdb' => array
        (
            'imdb_id'    => intval ( $imdb_id ),
            'title_orig' => _charsetPrepare ( $movie->title() ),
            'title_deu'  => _charsetPrepare ( $title_deu ),
            'year'       => $movie->year(),
            'runtime'    => $movie->runtime(),
            'rating'     => $movie->rating(),
            'genres'     => $movie->genres(),
            'director'   => $directors,
            'cast'       => $actors,
            'plot'       => _charsetPrepare ( $movie->plotoutline() ),
            'photo'      => $movie->photo_localurl()
        ),
        'custom' => array
        (
            'rating'     => 0,
            'languages'  => array(),
            'notes'      => '',
            'qualitaet'  => ''
        )
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

    $string = utf8_encode ( $string );

    return trim ( $string );
}
?>