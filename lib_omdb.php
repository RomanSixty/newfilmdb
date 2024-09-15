<?php

/**
 * holt zu einer IMDb-ID alle gewünschten Filmdaten
 * und packt sie in die passende Struktur
 *
 * @param int $imdb_id IMDb-ID
 * @return array Filmdaten
 */
function getOMDbRatings ( $imdb_id )
{
    $env = parse_ini_file ( '.env' );

    $url = sprintf ( 'https://www.omdbapi.com/?i=tt%s&apikey=%s',
                     str_pad ( $imdb_id, 8, '0', STR_PAD_LEFT ),
                     $env [ 'APIKEY_OMDB' ] );

    $ch = curl_init();

    // set url

    curl_setopt ( $ch, CURLOPT_URL, $url );
    curl_setopt ( $ch, CURLOPT_TIMEOUT, 5 );
    curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, 1 );

    $buffer = curl_exec ( $ch );

    curl_close ( $ch );

    $json = json_decode ( $buffer, true );

    if ( $json [ 'Response' ] == 'False' )
        return array();
    else
    {
        $ratings = [
            'metacritic' => intval ( $json [ 'Metascore' ] )
        ];

        foreach ( $json [ 'Ratings' ] as $omdb_rating )
            if ( $omdb_rating [ 'Source' ] == 'Rotten Tomatoes' )
                $ratings [ 'rottentomatoes' ] = intval ( $omdb_rating [ 'Value' ] );

        return $ratings;
    }
}