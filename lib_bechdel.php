<?php

/**
 * Bechdel-Info ermitteln
 * @param int $imdb_id IMDb-ID
 * @return array Bechdel-Rating
 */
function getBechdelInfo ( $imdb_id )
{
    $db = new sqlitedb();

    if ( $bdata = $db -> getBechdelRating ( $imdb_id ) )
    {
        return [
            '@bechdel_id'      => (int) $bdata [ 'bechdel_id' ],
            '@bechdel_rating'  => (int) $bdata [ 'bechdel_rating' ],
            '@bechdel_dubious' =>       0
        ];
    }

    return [
        '@bechdel_id'      => '',
        '@bechdel_rating'  => '',
        '@bechdel_dubious' => ''
    ];
}