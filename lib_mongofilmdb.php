<?php

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
        $terms = explode ( ' ', strtolower ( $form [ 'fulltext' ] ) );

        $regex = '/(\b' . implode ( '\b|\b', $terms ) . '\b)/i';

        $ft_func = 'function() {
            if (    this.imdb.title_orig.search(' . $regex . ') != -1
                 || this.imdb.title_deu.search(' . $regex . ') != -1 )
                return true;

            if ( this.imdb.cast && this.imdb.cast.toString().search(' . $regex . ') != -1 )
                return true;

            if ( this.imdb.director && this.imdb.director.toString().search(' . $regex . ') != -1 )
                return true;

            return false;
        }';

        $filter [ '$where' ] = $ft_func;
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
    global $collection;

    $collection -> save ( $movie );
}

/**
 * Filmdaten aktualisieren (noch nicht eingesetzt)
 *
 * @param Integer $imdb_id IMDb-ID
 * @param Array $movie zu aktualisierende Filmdaten
 */
function updateMovie ( $imdb_id, $movie )
{
    global $collection;

    $collection -> update ( array ( 'imdb.imdb_id' => intval ( $imdb_id ) ),
                            $movie );
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

    $other = $collection -> findOne (
        array ( 'imdb.imdb_id'  => array ( '$ne' => intval ( $imdb_id ) ),
                'imdb.director' => array ( '$in' => array ( $director ) ) )
    );

    return ( !empty ( $other ) );
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

    $other = $collection -> findOne (
        array ( 'imdb.imdb_id'  => array ( '$ne' => intval ( $imdb_id ) ),
                'imdb.cast' => array ( '$in' => array ( $actor ) ) )
    );

    return ( !empty ( $other ) );
}

?>