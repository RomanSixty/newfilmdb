<?php

/**
 * dieses Skript bildet die RPC-Schnittstelle,
 * über die die AJAX-Abfragen laufen
 */

require ( 'lib_mongofilmdb.php' );
require ( 'lib_html.php' );
require ( 'lib_imdb.php' );

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

switch ( $_REQUEST [ 'act' ] )
{
    case 'add_movie':
        $imdb_id = intval ( $_REQUEST [ 'imdb_id' ] );

        if (    empty ( $imdb_id )
             || empty ( $_REQUEST [ 'custom' ][ 'languages' ] ) )
        {
            $html = getEditForm();
            break;
        }
        else
            insertMovie ( getMovie ( $imdb_id ) );

        // kein break...

    case 'save_movie':
        $imdb_id = intval ( $_REQUEST [ 'imdb_id' ] );

        if ( !empty ( $imdb_id ) )
            updateMovie ( $imdb_id,
                          array ( 'custom' => $_REQUEST [ 'custom' ] ) );

        // kein break...

    case 'details':
        $html = getMovieDetails ( $_REQUEST [ 'imdb_id' ] );
        break;

    case 'add':
        $html = getEditForm ( $_REQUEST [ 'imdb_id' ], false );
        break;

    case 'edit':
        $html = getEditForm ( $_REQUEST [ 'imdb_id' ], true );
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