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

    if ( isAdmin() )
        $snippet .= '<a href="#" class="editlink" data-imdbid="' . $movie [ 'imdb' ][ 'imdb_id' ] . '"><img src="./fdb_img/edit.png" alt="edit"/></a>';

    if ( empty ( $movie [ 'imdb' ][ 'photo' ] ) )
        $movie [ 'imdb' ][ 'photo' ] = './fdb_img/bg.jpg';

    $snippet .= '<section class="card">';

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

    $snippet .= '  <p class="year_runtime">' . $movie [ 'imdb' ][ 'year' ] . ', ' . $movie [ 'imdb' ][ 'runtime' ] . ' Minuten</p>';
    $snippet .= '  <p class="plot"><span>' . $movie [ 'imdb' ][ 'plot' ] . '</span></p>';

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

    $snippet .= '</section>'; // class="main_details"
    $snippet .= '</section>'; // class="card"

    $snippet .= '<section class="associated">';
    $snippet .= '  <label>Regie</label><ul class="directors">';

    foreach ( $movie [ 'imdb' ][ 'director' ] as $director )
    {
        if ( $num = directorHasOtherMovies ( $movie [ 'imdb' ][ 'imdb_id' ], $director ) )
            $snippet .= '<li data-count="' . ($num+1) . '"><a href="#">' . $director . '</a></li>';
        else
            $snippet .= '<li>' . $director . '</li>';
    }

    $snippet .= '  </ul>';

    $snippet .= '  <label>Cast</label><ul class="actors">';

    $actnum = $actshown = 0;
    foreach ( $movie [ 'imdb' ][ 'cast' ] as $actor )
    {
        if ( $actshown < 40 && $num = actorHasOtherMovies ( $movie [ 'imdb' ][ 'imdb_id' ], $actor ) )
        {
            $snippet .= '<li data-count="' . ($num+1) . '"><a href="#">' . $actor . '</a></li>';
            $actshown++;
        }
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

    if ( isAdmin() )
        $snippet .= '<a href="#" class="addlink" data-imdbid="' . $movie [ 'imdb' ][ 'imdb_id' ] . '"><img src="./fdb_img/add.png" alt="add"/></a>';

    $snippet .= '</section>';

    $snippet .= '<!-- ' . print_r($movie, true) . ' -->';

    return $snippet;
}

/**
 * Formular zum Bearbeiten oder Anlegen eines Films
 *
 * @param Integer $imdb_id IMDb-ID des zu bearbeitenden oder des zuletzt gesehenen Films
 * @param Boolean $edit Bearbeiten: true, Anlegen: false
 * @return String HTML-Code
 */
function getEditForm ( $imdb_id, $edit = true )
{
    if ( $edit )
    {
        $movie = getSingleMovie ( $imdb_id );

        $snippet = '<h2>Film bearbeiten</h2>';
    }
    else
        $snippet = '<h2>Neuen Film anlegen</h2>';

    $snippet .= '<form method="POST" action="./" id="edit_movie">';

    if ( $edit )
    {
        $snippet .= '<input type="hidden" name="act" value="save_movie" />';
        $snippet .= '<input type="hidden" name="imdb_id" value="' . $movie [ 'imdb' ][ 'imdb_id' ] . '" />';
    }
    else
    {
        $snippet .= '<input type="hidden" name="act" value="add_movie" />';

        $snippet .= '<fieldset>';
        $snippet .= '<legend><label for="imdbid">IMDb-ID*:</label></legend>';
        $snippet .= '<input type="text" maxlength="7" size="7" id="imdbid" name="imdb_id" value="" />';
        $snippet .= '</fieldset>';
    }

    // Sprache

    $snippet .= '<fieldset>';
    $snippet .= '<legend><label>Sprache*:</label></legend>';

    $languages = array ( 'deu' => 'deutsch',
                         'eng' => 'englisch',
                         'OmU' => 'Untertitel' );

    foreach ( $languages as $lang => $label )
    {
        $checked = ( in_array ( $lang, $movie [ 'custom' ][ 'languages' ] ) )
                 ? ' checked="checked"'
                 : '';

        $snippet .= '<input type="checkbox" id="' . $lang . '" name="custom[languages][]" value="' . $lang . '"' . $checked . '/>';
        $snippet .= '<label for="' . $lang . '">' . $label . '</label>';
    }

    $snippet .= '</fieldset>';

    // Wertung

    $snippet .= '<fieldset>';
    $snippet .= '<legend><label>Wertung:</label></legend>';

    foreach ( array ( 0,1,2,3,4,5,6,7,8,9,10 ) as $rating )
    {
        $checked = ( $movie [ 'custom' ][ 'rating' ] == $rating )
                 ? ' checked="checked"'
                 : '';

        $snippet .= '<input type="radio" id="r' . $rating . '" name="custom[rating]" value="' . $rating . '"' . $checked . '/>';
        $snippet .= '<label for="r' . $rating . '">' . $rating . '</label>';
    }

    $snippet .= '</fieldset>';

    // Bemerkungen

    $snippet .= '<fieldset>';
    $snippet .= '<legend><label for="notes">Bemerkungen:</label></legend>';
    $snippet .= '<textarea id="notes" name="custom[notes]">' . $movie [ 'custom' ][ 'notes' ] . '</textarea>';
    $snippet .= '</fieldset>';

    // Qualität

    $snippet .= '<fieldset>';
    $snippet .= '<legend><label for="quality">Qualität:</label></legend>';
    $snippet .= '<textarea id="quality" name="custom[quality]">' . $movie [ 'custom' ][ 'quality' ] . '</textarea>';
    $snippet .= '</fieldset>';

    $snippet .= '<input type="button" class="button abort" value="abbrechen" data-imdbid="' . $imdb_id . '" />';
    $snippet .= '<input type="submit" class="button submit" value="speichern" />';

    $snippet .= '</form>';

    return $snippet;
}

?>