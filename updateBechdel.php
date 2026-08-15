<?php

require ( __DIR__ . '/lib_sqlitedb.php' );

if ( PHP_SAPI == 'cli' )
    updateBechdelCache();

function updateBechdelCache()
{
    $api_url = 'https://bechdeltest.com/';

    $page = 0;

    $entries = [];

    $opts = stream_context_create ( [ 'http' => [ 'timeout' => 15 ]] );

    while ( true )
    {
        $url = ( $page > 0 ) ? $api_url . '/page/' . $page .'/' : $api_url;

        if ( false !== ( $res = file_get_contents ( $url, false, $opts ) ) )
        {
            preg_match_all ( '~<div class="movie">(.*)</div>~Us', $res, $movies );

            foreach ( $movies [ 1 ] as $movie )
            {
                //                                                             IMDB-ID            RATING                 BECHDEL-ID     TITLE
                if ( preg_match ( '~<a href="https://www.imdb.com/title/(?:tt)?([0-9]+)/".*alt="\[([0-3])]".*href="/view/([0-9]+)/[^>]+>(.+)</a>~Us', $movie, $matches ) )
                {
                    $entries[] = [
                        '@imdb_id'        => intval ( $matches [ 1 ] ),
                        '@bechdel_id'     => $matches [ 3 ],
                        '@bechdel_rating' => $matches [ 2 ],
                        '$title'          => $matches [ 4 ]
                    ];
                }
            }

            $page++;

            if ( !preg_match ( '~<nav class="pager".*<a href="/page/'.$page.'/~Us', $res ) )
                break;
        }
    }

    $db = new sqlitedb();
    $db -> saveBechdelCache ( $entries );
}