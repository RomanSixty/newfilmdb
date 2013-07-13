<?php

/**
 * dieses Skript bildet die RPC-Schnittstelle,
 * über die die AJAX-Abfragen laufen
 */

require ( 'lib_mongofilmdb.php' );
require ( 'lib_bechdel.php' );
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

    return array ( 'count' => count ( $movies ),
                   'html'  => $html );
}

switch ( $_REQUEST [ 'act' ] )
{
    case 'add_movie':
        $imdb_id = intval ( $_REQUEST [ 'imdb_id' ] );

        if (    empty ( $imdb_id )
             || empty ( $_REQUEST [ 'custom' ][ 'languages' ] ) )
        {
            $return = getEditForm();
            break;
        }
        else
		{
			$movie = getMovie ( $imdb_id );

			// Bechdel-Daten ergänzen
			if ( false !== ( $bechdel_info = getBechdelInfo ( $imdb_id ) ) )
				$movie [ 'bechdel' ] = $bechdel_info;

            insertMovie ( $movie );
		}

        // kein break...

    case 'save_movie':
        $imdb_id = intval ( $_REQUEST [ 'imdb_id' ] );

        if ( !empty ( $imdb_id ) )
            updateMovie ( $imdb_id,
                          array ( 'custom' => $_REQUEST [ 'custom' ] ) );

        // kein break...

    case 'details':
        $return = getMovieDetails ( $_REQUEST [ 'imdb_id' ] );
        break;

    case 'add':
        $return = getEditForm ( $_REQUEST [ 'imdb_id' ], false );
        break;

    case 'edit':
        $return = getEditForm ( $_REQUEST [ 'imdb_id' ], true );
        break;

    default:
        $return = rpc_filter ( $_REQUEST );
        break;
}

if ( !empty ( $return ) )
{
    header ( 'Content-Encoding: deflate' );

    if ( is_array ( $return ) )
        echo substr ( gzcompress ( json_encode ( $return ) ), 2 );
    else
        echo substr ( gzcompress ( $return ), 2 );
}