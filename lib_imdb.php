<?php

/**
 * dieses Skript beinhaltet die Funktionen, mit denen
 * aus imdbGraphQLPHP die Filmdaten geladen und strukturiert werden
 */

require ( 'vendor/duck7000/imdb-graphql-php/bootstrap.php' );


/**
 * holt zu einer IMDB-ID alle gewünschten Filmdaten
 * und packt sie in die passende Struktur
 *
 * @param int $imdb_id IMDb-ID
 * @return array Filmdaten
 */
function getIMDbMovie ( $imdb_id )
{
    $config = new \Imdb\Config();

    $config->photoroot = __DIR__ . '/images/';

    $movie = new \Imdb\Title ( str_pad ( $imdb_id, 7, '0', STR_PAD_LEFT ), $config );

    $title_orig = $title_eng = $title_deu = $movie -> originalTitle();

    $directors = $actors = array();

    if ( empty ( $title_orig ) )
        $title_orig = $title_eng = $title_deu = $movie -> title();

    $deu_found = $eng_found = false;

    foreach ( $movie -> alsoknow() as $aka )
    {
        if ( $aka [ 'comment' ] == 'Working Title' )
            continue;

        if (    $deu_found === false
             && (    $aka [ 'countryId' ] == 'DE'
                  || $aka [ 'countryId' ] == 'XWG' ) )
        {
            $title_deu = $aka [ 'title' ];
            $deu_found = true;
        }
        elseif (    $deu_found === false
                 && $aka [ 'country' ] == 'International' )
        {
            $title_deu = $aka [ 'title' ];
        }
        elseif (    $eng_found === false
                 && $aka [ 'countryId' ] == 'XWW' )
        {
            $title_eng = $aka [ 'title' ];
            $eng_found = true;
        }
        elseif (    $eng_found === false
                 && $aka [ 'countryId' ] == 'US' )
        {
            $title_eng = $aka [ 'title' ];
            $eng_found = true;
        }
    }

    // Regisseur
    foreach ( $movie -> director() as $d )
        $directors[] = _charsetPrepare ( $d [ 'name' ] );

    // Schauspieler
    foreach ( (array) $movie -> cast() as $c )
        $actors[] = _charsetPrepare ( $c [ 'name' ] );

    // Typ übersetzen wir, da die Aufschlüsselung der IMDb zu detailliert ist
    $type = $movie->movietype();

    switch ( $type )
    {
        case 'Video':
        case 'TV Movie':
            $type = 'Movie';
            break;

        case 'TV Series':
        case 'TV Mini Series':
            $type = 'Series';
            break;

        case 'TV Special':
            $type = 'Special';
            break;

        case 'TV Short':
        case 'Short':
            $type = 'Short';
            break;
    }

    // Genres in einfaches Array übersetzen
    $genre_raw = $movie->genre();

    $genres = [];

    foreach ( $genre_raw as $genre )
        if ( !empty ( $genre [ 'mainGenre' ] ) )
            $genres[] = $genre [ 'mainGenre' ];

    $runtime = $movie->runtime();

    return [
        '@imdb_id'         => intval ( $imdb_id ),
        '$imdb_photo'      => $movie->photoLocalurl(),
        '$imdb_plot'       => _charsetPrepare ( $movie->plotoutline() ),
        '$imdb_rating'     => $movie->rating(),
        '@imdb_top250'     => intval ( $movie->top250() ),
        '@imdb_runtime'    => intval ( $runtime [ 0 ][ 'time' ] ),
        '$imdb_title_deu'  => _charsetPrepare ( $title_deu  ),
        '$imdb_title_orig' => _charsetPrepare ( $title_orig ),
        '$imdb_title_eng'  => _charsetPrepare ( $title_eng  ),
        '@imdb_year'       => $movie->year(),
        '$imdb_type'       => $type,
        '$fulltext'        => '',

        'genres'     => $genres,
        'director'   => $directors,
        'cast'       => $actors
   ];
}

/**
 * Helper-Funktion um gescrapete Daten in passenden Zeichensatz zu bringen
 *
 * @param string $string Zeichenkette
 * @return string normalisierte UTF8-Zeichenkette
 */
function _charsetPrepare ( $string )
{
    $string = strip_tags ( $string );
    $string = str_replace ( 'See full summary&nbsp;&raquo;', '', $string );

    $string = html_entity_decode ( $string );
    $string = str_replace ( '&#x27;', "'", $string );

    return trim ( $string );
}