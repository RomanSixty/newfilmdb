<?php

require ( 'lib_mongofilmdb.php' );
require ( 'lib_html.php' );

switch ( $_REQUEST [ 'act' ] )
{
    case 'details':
        $html = getMovieDetails ( $_REQUEST [ 'imdb_id' ] );

        break;

    default:
        $html = rpc_filter ( $_REQUEST );
        break;
}

if ( !empty ( $html ) )
{
    header ( 'Content-Encoding: deflate' );
    echo substr ( gzcompress ( $html ), 2 );

    #echo $html;
}

function rpc_filter ( $p )
{
    $movies = getMovieList ( getFilters ( $p ) );

    $html = '';

    foreach ( $movies as $movie )
        $html .= getMovieSnippet ( $movie );

    return $html;
}

?>