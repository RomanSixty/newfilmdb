<?php

/**
 * dieses Skript enthält Funktionen, die HTML-Schnipsel erzeugen
 * diese werden sowohl beim initialen Aufruf der Seite benötigt
 * als auch von der RPC-Schnittstelle aus aufgerufen (vgl. rpc.php)
 */

/**
 * HTML-Code für das Dashboard im Seitenkopf mit Suchfiltern
 *
 * @return String HTML-Code
 */
function getDashboard()
{
    $snippet  = '<section id="dashboard">';
    $snippet .= '<form method="POST" action="./" id="searchform">';

    // Volltextsuche
    $snippet .= '<section class="filter">';
    $snippet .= '<label for="fulltext" class="section">Suche:</label>';
    $snippet .= '<input type="text"
                        value=""
                        name="fulltext"
                        id="fulltext" />';
    $snippet .= '</section>';

    // Sprachfilter
    $snippet .= '<section class="filter">';
    $snippet .= '<label class="section">Sprache:</label>';
    $snippet .= '<span class="checkbutton"><input type="checkbox"
                        class="check"
                        value="eng"
                        checked="checked"
                        name="lang[]"
                        id="lang_eng" /> <label for="lang_eng">eng</label></span>';
    $snippet .= '<span class="checkbutton"><input type="checkbox"
                        class="check"
                        value="deu"
                        checked="checked"
                        name="lang[]"
                        id="lang_deu" /> <label for="lang_deu">deu</label></span>';
    $snippet .= '</section>';

    $snippet .= '<section class="filter">';
    $snippet .= '<label id="genre" class="section">Genre:</label>';
    $snippet .= '</section>';

    $snippet .= '<section class="filter">';
    $snippet .= '<label id="director" class="section">Regie:</label>';
    $snippet .= '</section>';

    $snippet .= '<section class="filter">';
    $snippet .= '<label id="cast" class="section">Cast:</label>';
    $snippet .= '</section>';

    $snippet .= '</form>';
    $snippet .= '</section>';

    return $snippet;
}

/**
 * HTML-Schnipsel eines einzelnen Films der Filmliste
 *
 * @param Array $movie Filmdaten aus der MongoDB
 * @return String HTML-Code
 */
function getMovieSnippet ( $movie )
{
    $snippet  = '<section data-imdbid="' . $movie [ 'imdb_id' ] . '"
                          class="movie">';
    $snippet .= '  <div class="img">';
    $snippet .= '    <img class="delay poster"
                          data-original="' . $movie [ 'photo' ] . '"
                          src="./fdb_img/blank.gif"
                          alt="' . $movie [ 'title' ] . '" />';
    $snippet .= '  </div>';
    $snippet .= '  <section class="meta">';
    $snippet .= '    <h1>' . $movie [ 'title' ] . '</h1></header>';

    if ( $movie [ 'rating' ] > 0 )
        $snippet .= '    <p>' . $movie [ 'rating' ] . '/10</p>';
    else
        $snippet .= '    <p>noch keine Wertung</p>';

    $snippet .= '  </section>';
    $snippet .= '</section>';

    return $snippet;
}

/**
 * HTML-Schnipsel der Film-Detailansicht für die Sidebar
 *
 * @param Integer $imdb_id IMDb-ID
 * @return String HTML-Code
 */
function getMovieDetails ( $imdb_id )
{
    $movie = getSingleMovie ( $imdb_id );

    $snippet  = '<header><h1>' . $movie [ 'imdb' ][ 'title_orig' ] . '</h1></header>';

    if ( empty ( $movie [ 'imdb' ][ 'photo' ] ) )
        $movie [ 'imdb' ][ 'photo' ] = './fdb_img/bg.jpg';

    $snippet .= '<div class="img">';
    $snippet .= '  <a href="http://www.imdb.com/title/tt' . str_pad ( $movie [ 'imdb' ][ 'imdb_id' ], 7, '0', STR_PAD_LEFT ) . '"
                       target="_blank">' .
                   '<img src="' . $movie [ 'imdb' ][ 'photo' ] . '"
                         alt="" />' .
                   '</a>';
    $snippet .= '</div>';

    $snippet .= '<section class="main_details">';
    if ( $movie [ 'imdb' ][ 'title_orig' ] != $movie [ 'imdb' ][ 'title_deu' ] )
    $snippet .= '  <h2>' . $movie [ 'imdb' ][ 'title_deu' ] . '</h2>';

    $snippet .= '  <p>' . $movie [ 'imdb' ][ 'year' ] . ', ' . $movie [ 'imdb' ][ 'runtime' ] . ' Minuten</p>';
    $snippet .= '  <p>' . $movie [ 'imdb' ][ 'plot' ] . '</p>';

    $snippet .= '  <p>';
    foreach ( $movie [ 'imdb' ][ 'genres' ] as $genre )
        $snippet .= '<span class="genre"><a href="#">' . $genre . '</a></span>';
    $snippet .= '  </p>';

    $snippet .= '    <div class="star">' . $movie [ 'imdb'   ][ 'rating' ] . '</div>';
    if ( $movie [ 'custom' ][ 'rating' ] > 0 )
    $snippet .= '    <div class="star">' . $movie [ 'custom' ][ 'rating' ] . '</div>';

    $snippet .= '  <dl>';
    $snippet .= '    <dt>Sprachen:</dd><dd> ' . implode ( ', ', $movie [ 'custom' ][ 'languages' ] ) . '</dd>';
    $snippet .= '  </dl>';

    $snippet .= '</section>';

    $snippet .= '<section class="associated">';
    $snippet .= '  <label>Regie</label><ul class="directors">';

    foreach ( $movie [ 'imdb' ][ 'director' ] as $director )
    {
        if ( directorHasOtherMovies ( $movie [ 'imdb' ][ 'imdb_id' ], $director ) )
            $snippet .= '<li><a href="#">' . $director . '</a></li>';
        else
            $snippet .= '<li>' . $director . '</li>';
    }

    $snippet .= '  </ul>';

    $snippet .= '  <label>Cast</label><ul class="actors">';

    $actnum = 0;
    foreach ( $movie [ 'imdb' ][ 'cast' ] as $actor )
    {
        if ( actorHasOtherMovies ( $movie [ 'imdb' ][ 'imdb_id' ], $actor ) )
            $snippet .= '<li><a href="#">' . $actor . '</a></li>';
        elseif ( $actnum < 5 )
            $snippet .= '<li>' . $actor . '</li>';

        $actnum++;
    }

    $snippet .= '  </ul>';

    if (    !empty ( $movie [ 'custom' ][ 'notes'   ] )
         || !empty ( $movie [ 'custom' ][ 'quality' ] ) )
    {
        $snippet .= '  <label>Anmerkungen</label>';

        if ( !empty ( $movie [ 'custom' ][ 'notes' ] ) )
        $snippet .= '  <p>' . $movie [ 'custom' ][ 'notes' ] . '</p>';

        if ( !empty ( $movie [ 'custom' ][ 'quality' ] ) )
        $snippet .= '  <dl><dt>Qualität</dt><dd>' . $movie [ 'custom' ][ 'quality' ] . '</dd></dl>';
    }

    $snippet .= '</section>';

    $snippet .= '<!-- ' . print_r($movie, true) . ' -->';

    return $snippet;
}

?>