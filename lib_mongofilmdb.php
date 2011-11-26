<?php

/**
 * darf der Nutzer editieren?
 *
 * @todo konfigurierbar machen
 * @return Boolean
 */
function isAdmin()
{
    return ( $_SERVER [ 'REMOTE_ADDR' ] == '192.168.0.2' );
}

/**
 * dieses Skript stellt Funktionen zur Verfügung,
 * um die MongoDB abzufragen
 */

$connection = new Mongo();
$db = $connection -> selectDB ( 'filmdb' );
$collection = $db -> movie;

/**
 * Liste von Filmen
 *
 * @param Array $filter MongoDB-Filterkriterien
 * @return Array Filme
 * @todo Sortierungsmöglichkeiten
 */
function getMovieList ( $filter = array() )
{
    global $collection;

    $result = $collection -> find (
        $filter,
        array (
            'imdb.imdb_id'     => 1,
            'imdb.title_orig'  => 1,
            'imdb.photo'       => 1,
            'custom.rating'    => 1
        )
    );

    // Struktur verflachen
    $movies = array();

    foreach ( $result as $r )
    {
        $movies[] = array (
            'imdb_id'   => $r [ 'imdb'   ][ 'imdb_id'    ],
            'title'     => $r [ 'imdb'   ][ 'title_orig' ],
            'photo'     => $r [ 'imdb'   ][ 'photo'      ],
            'rating'    => $r [ 'custom' ][ 'rating'     ]
        );
    }

    shuffle ( $movies );

    return $movies;
}

/**
 * Detail-Informationen eines Films
 *
 * @param Integer $imdb_id IMDb-ID
 * @return Array Filmdaten
 */
function getSingleMovie ( $imdb_id )
{
    global $collection;

    $result = $collection -> findOne (
        array ( 'imdb.imdb_id' => intval ( $imdb_id ) )
    );

    return $result;
}

/**
 * Filter aus REQUEST erzeugen
 *
 * @param Array $form Formulardaten aus einem REQUEST
 * @return Array MongoDB-Filterkriterien
 */
function getFilters ( $form )
{
    $filter = array();

    // Volltextsuche
    if ( !empty ( $form [ 'fulltext' ] ) )
    {
        $terms = explode ( ' ', _transliterate ( $form [ 'fulltext' ] ) );

        $filter [ 'fulltext' ] = array ( '$all' => $terms );
    }

    // Sprachfilter (ODER)
    if ( is_array ( $form [ 'lang' ] ) && !empty ( $form [ 'lang' ] ) )
        $filter [ 'custom.languages' ] = array ( '$in' => $form [ 'lang' ] );

    // Regiefilter
    if ( is_array ( $form [ 'director' ] ) && !empty ( $form [ 'director' ] ) )
        $filter [ 'imdb.director' ] = array ( '$in' => $form [ 'director' ] );

    // Cast-Filter (UND)
    if ( is_array ( $form [ 'cast' ] ) && !empty ( $form [ 'cast' ] ) )
        $filter [ 'imdb.cast' ] = array ( '$all' => $form [ 'cast' ] );

    // Genre-Filter (UND)
    if ( is_array ( $form [ 'genre' ] ) && !empty ( $form [ 'genre' ] ) )
        $filter [ 'imdb.genres' ] = array ( '$all' => $form [ 'genre' ] );

    return $filter;
}

/**
 * fertige Filmdaten in die Datenbank schreiben
 *
 * @param Array $movie Filmdaten
 */
function insertMovie ( $movie )
{
    if ( !isAdmin() ) return;

    global $collection;

    updateFulltext ( $movie );

    $collection -> save ( $movie );
}

/**
 * Filmdaten aktualisieren (noch nicht eingesetzt)
 *
 * @param Integer $imdb_id IMDb-ID
 * @param Array $custom zu aktualisierende Filmdaten
 */
function updateMovie ( $imdb_id, $custom )
{
    if ( !isAdmin() ) return;

    global $collection;

    updateFulltext ( $movie );

    $collection -> update ( array ( 'imdb.imdb_id' => intval ( $imdb_id ) ),
                            array ( '$set' => $custom ) );
}

/**
 * Volltextindex eines Films erzeugen/aktualisieren
 *
 * @param Array $movie MongoDB-Filmdaten
 */
function updateFulltext ( &$movie )
{
    // alle zu indizierenden Felder zusammensuchen

    $fulltext = $movie [ 'imdb' ][ 'title_orig' ] . ' '
              . $movie [ 'imdb' ][ 'title_deu'  ] . ' '
              . implode ( ' ', $movie [ 'imdb' ][ 'director' ] ) . ' '
              . implode ( ' ', $movie [ 'imdb' ][ 'cast'     ] );

    $fulltext = _transliterate ( $fulltext );

    // jedes Wort nur einmal
    $fulltext = array_unique ( explode ( ' ', $fulltext ) );

    // array_values hier, damit die Elemente ohne Lücken durchnummeriert
    // sind, andernfalls kann die MongoDB darin nicht vernünftig suchen
    $movie [ 'fulltext' ] = array_values ( $fulltext );
}

/**
 * Zeichenkette transliterieren
 *
 * @param String $string Zeichenkette
 * @return String normalisierte Zeichenkette
 */
function _transliterate ( $string )
{
    setlocale ( 'LC_ALL', 'de_DE' );

    $string = iconv ( 'utf-8', 'ASCII//TRANSLIT', $string );

    $string = preg_replace ( '~[^\w ]~', '', $string );
    $string = preg_replace ( '~[\s]+~', ' ', $string );

    return strtolower ( $string );
}

/**
 * hat ein Regisseur neben dem genannten Film weitere in der DB vorhandene Filme gedreht?
 *
 * @param Integer $imdb_id IMDb-ID
 * @param String $director genauer Name des Regisseurs
 * @return Boolean hat er oder hat er nicht
 */
function directorHasOtherMovies ( $imdb_id, $director )
{
    global $collection;

    $other = $collection -> find (
        array ( 'imdb.imdb_id'  => array ( '$ne' => intval ( $imdb_id ) ),
                'imdb.director' => array ( '$in' => array ( $director ) ) )
    ) -> count();

    return $other;
}

/**
 * hat ein Schauspieler neben dem genannten Film bei weiteren in der DB vorhandenen Filmen mitgespielt?
 *
 * @param Integer $imdb_id IMDb-ID
 * @param String $director genauer Name des Schauspielers
 * @return Boolean hat er oder hat er nicht
 */
function actorHasOtherMovies ( $imdb_id, $actor )
{
    global $collection;

    $other = $collection -> find (
        array ( 'imdb.imdb_id'  => array ( '$ne' => intval ( $imdb_id ) ),
                'imdb.cast' => array ( '$in' => array ( $actor ) ) )
    ) -> count();

    return $other;
}

?>