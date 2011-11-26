<?php

/**
 * dieses Skript bildet die RPC-Schnittstelle,
 * über die die AJAX-Abfragen laufen
 */

require ( 'lib_mongofilmdb.php' );
require ( 'lib_html.php' );

/**
 * Filmliste basierend auf REQUEST-Parametern zurückgeben
 *
 * @param Array $p REQUEST-Parameter
 * @return String HTML-Code der Filmliste
 */
function rpc_filter ( $p )
{
    $movies = getMovieList ( getFilters ( $p ) );

    $html = '';

    foreach ( $movies as $movie )
        $html .= getMovieSnippet ( $movie );

    return $html;
}

function save_movie ( $form )
{
    $imdb_id = intval ( $form [ 'imdb_id' ] );

    if ( !empty ( $imdb_id ) )
        updateMovie ( $imdb_id, array ( 'custom' => $form [ 'custom' ] ) );
}

switch ( $_REQUEST [ 'act' ] )
{
    case 'save_movie':
        save_movie ( $_REQUEST );
        // ja, kein break...

    case 'details':
        $html = getMovieDetails ( $_REQUEST [ 'imdb_id' ] );
        break;

    case 'edit':
        $html = getEditForm ( $_REQUEST [ 'imdb_id' ] );
        break;

    default:
        $html = rpc_filter ( $_REQUEST );
        break;
}

if ( !empty ( $html ) )
{
    header ( 'Content-Encoding: deflate' );
    echo substr ( gzcompress ( $html ), 2 );
}

?>