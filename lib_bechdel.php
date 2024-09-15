<?php

/**
 * Bechdel-Info via Schnittstelle laden
 *
 * @param int $imdb_id IMDb-ID
 *
 * @return array Bechdel-Rating
 */
function getBechdelInfo ( $imdb_id )
{
    $api_url = 'http://bechdeltest.com/api/v1/getMovieByImdbId?imdbid=' . str_pad ( $imdb_id, 7, '0', STR_PAD_LEFT );

    if ( false !== ( $res = file_get_contents ( $api_url ) ) )
    {
        $info = json_decode ( $res );

        if ( empty ( $info -> status ) )
        {
            return [
                '@bechdel_id'      => (int) $info -> id,
                '@bechdel_rating'  => (int) $info -> rating,
                '@bechdel_dubious' =>       $info -> dubious ? 1 : 0
            ];
        }
    }

    return [
        '@bechdel_id'      => '',
        '@bechdel_rating'  => '',
        '@bechdel_dubious' => ''
    ];
}

/**
 * Übersetzung Bechdel- und IMDb-IDs
 * @return array|false
 */
function getBechdelIDs()
{
    $api_url = 'http://bechdeltest.com/api/v1/getAllMovieIds';

    if ( false !== ( $res = file_get_contents ( $api_url ) ) )
    {
        $info = json_decode ( $res );

        $ids = [];

        foreach ( $info as $i )
            $ids [ (int) $i -> imdbid ] = (int) $i -> id;

        return $ids;
    }

    return false;
}