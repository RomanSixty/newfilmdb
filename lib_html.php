<?php

/**
 * dieses Skript enthält Funktionen, die HTML-Schnipsel erzeugen
 * diese werden sowohl beim initialen Aufruf der Seite benötigt
 * als auch von der RPC-Schnittstelle aus aufgerufen (vgl. rpc.php)
 */

/**
 * Platzhalter im Template ersetzen
 *
 * @param string $template HTML-Schnipsel
 * @param array $replacements Platzhalter => Wert
 * @return string HTML-Schnipsel
 */
function templateReplacements ( $template, $replacements )
{
    foreach ( $replacements as $key => $val )
    {
        if ( is_array ( $val ) )
        {
            if ( preg_match ( '~{{{LOOP_'.$key.'(.*)}}}~sU', $template, $matches ) !== false )
            {
                $rep = '';

                foreach ( $val as $value )
                {
                    $temp = $matches [ 1 ];

                    handleConditions ( $temp, $value );

                    if ( is_array ( $value ) )
                    {
                        $rep .= $temp;

                        foreach ( $value as $k => $v )
                            $rep = preg_replace ( '~%%%' . $key . '.' . $k . '%%%~sU', $v, $rep );

                        $rep = preg_replace ( '~%%%.*%%%~sU', '', $rep );
                    }
                    else
                        $rep .= preg_replace ( '~%%%' . $key . '%%%~sU', $value, $temp );
                }

                $template = str_replace ( $matches [ 0 ], $rep, $template );
            }
        }
        else
        {
            $template = str_replace ( '%%%'.$key.'%%%', $val,   $template );
        }
    }

    handleConditions ( $template, $replacements );

    return $template;
}

/**
 * IF-Ausdrücke im Template verarbeiten
 *
 * @param string $template HTML-Code
 * @param array $replacements Template-Ersetzungen
 */
function handleConditions ( &$template, $replacements )
{
    if ( preg_match_all ( '~{IF (.*)}(.*){ENDIF \1}~sU', $template, $matches ) !== false )
    {
        foreach ( $matches [ 1 ] as $k => $var )
        {
            $parts = explode ( '.', $var );

            if ( count ( $parts ) == 1 )
            {
                if ( empty ( $replacements [ $var ] ) )
                    $template = str_replace ( $matches [ 0 ][ $k ], '', $template );
                else
                    $template = str_replace ( $matches [ 0 ][ $k ], $matches [ 2 ][ $k ], $template );
            }
            else
                if ( empty ( $replacements [ $parts [ 0 ]][ $parts [ 1 ]] ) )
                    $template = str_replace ( $matches [ 0 ][ $k ], '', $template );
                else
                    $template = str_replace ( $matches [ 0 ][ $k ], $matches [ 2 ][ $k ], $template );
        }
    }
}

/**
 * benutzerspezifisches Bild zurückgeben, wenn vorhanden
 * sonst das Bild aus der IMDb
 *
 * @param array $movie Filmdaten
 * @return string Bildpfad
 */
function getBestImage ( $movie )
{
    if ( file_exists ( $photo = './images/own_' . str_pad ( $movie [ 'imdb_id' ], 7, '0', STR_PAD_LEFT ) . '.jpg' ) )
        return $photo;
    elseif ( !empty ( $movie [ 'imdb_photo' ] ) )
        return $movie [ 'imdb_photo' ];
    else
        return './fdb_img/bg.jpg';
}

/**
 * HTML-Code für das Dashboard im Seitenkopf mit Suchfiltern
 *
 * @param int $count Anzahl der ausgegebenen Filme
 * @return string HTML-Code
 */
function getDashboard ( $count = 0 )
{
    global $db;

    $template = file_get_contents ( dirname ( __FILE__ ) . '/templates/dashboard.html' );

    $replacements = [
        'COUNT'     => $count,
        'types'     => $db -> getTypeList(),
        'genres'    => $db -> getGenreList(),
        'directors' => $db -> getDirectorList(),
        'cast'      => $db -> getCastList()
    ];

    return templateReplacements ( $template, $replacements );
}

/**
 * HTML-Schnipsel eines einzelnen Films der Filmliste
 *
 * @param array $movie Filmdaten aus der MongoDB
 * @return string HTML-Code
 */
function getMovieSnippet ( $movie )
{
    $template = file_get_contents ( dirname ( __FILE__ ) . '/templates/movie_snippet.html' );

    $replacements = $movie;

    $replacements [ 'PHOTO' ] = getBestImage ( $movie );

    // Rating bestimmen
    if ( !empty ( $movie [ 'custom_rating' ] ) )
        $replacements [ 'RATING' ] = $movie [ 'custom_rating' ].'/10';
    else
        $replacements [ 'RATING' ] = 'noch keine Wertung';

    return templateReplacements ( $template, $replacements );
}

/**
 * HTML-Schnipsel der Film-Detailansicht für die Sidebar
 *
 * @param int $imdb_id IMDb-ID
 * @return string HTML-Code
 */
function getMovieDetails ( $imdb_id )
{
    global $db;

    $template = file_get_contents ( dirname ( __FILE__ ) . '/templates/movie_details.html' );

    $movie = $db -> getSingleMovie ( $imdb_id );

    $movie [ 'custom_notes'   ] = nl2br ( $movie [ 'custom_notes'   ] );
    $movie [ 'custom_quality' ] = nl2br ( $movie [ 'custom_quality' ] );

    $movie [ 'IS_ADMIN'       ] = $db -> isAdmin();
    $movie [ 'PHOTO'          ] = getBestImage ( $movie );
    $movie [ 'IMDBID_PADDED'  ] = str_pad ( $movie [ 'imdb_id' ], 7, '0', STR_PAD_LEFT );
    $movie [ 'TITLE_DIFF'     ] = (    stripos ( $movie [ 'imdb_title_deu' ], $movie [ 'imdb_title_orig' ] ) === false
                                    && stripos ( $movie [ 'imdb_title_deu' ], $movie [ 'imdb_title_eng'  ] ) === false );
    $movie [ 'TITLE_DIFF_ENG' ] = ( stripos ( $movie [ 'imdb_title_eng' ], $movie [ 'imdb_title_orig' ] ) === false );

    if (    empty ( $movie [ 'custom_notes'   ] )
         && empty ( $movie [ 'custom_quality' ] ) )
        $movie [ 'NOTES_QUALITY' ] = false;
    else
        $movie [ 'NOTES_QUALITY' ] = true;

    if ( $movie [ 'metacritic' ] < 40 )
        $movie [ 'metacritic_color' ] = 'red';
    elseif ( $movie [ 'metacritic' ] > 60 )
        $movie [ 'metacritic_color' ] = 'green';
    else
        $movie [ 'metacritic_color' ] = 'yellow';

    if ( $movie [ 'rottentomatoes' ] > 60 )
        $movie [ 'tomatometer' ] = 'fresh';
    else
        $movie [ 'tomatometer' ] = 'rotten';

    // wir zeigen maximal 5 Regisseure,
    // davon maximal 3 ohne weitere Filme in der Datenbank
    $directnum = $directshown = 0;
    foreach ( $movie [ 'director' ] as $key => $director )
    {
        if ( $directshown < 5 && ( $num = $db -> castMovieCount ( $director [ 'id' ], 'director2movie' ) ) > 1 )
        {
            $movie [ 'director' ][ $key ][ 'moviecount' ] = $num;
            $directshown++;
        }
        elseif ( $directnum >= 3 )
            unset ( $movie [ 'director' ][ $key ] );

        $directnum++;
    }

    // wir zeigen maximal 30 Cast-Mitglieder,
    // davon maximal 5 ohne weitere Filme in der Datenbank
    $actnum = $actshown = 0;
    foreach ( $movie [ 'cast' ] as $key => $actor )
    {
        if ( $actshown < 30 && ( $num = $db -> castMovieCount ( $actor [ 'id' ], 'cast2movie' ) ) > 1 )
        {
            $movie [ 'cast' ][ $key ][ 'moviecount' ] = $num;
            $actshown++;
        }
        elseif ( $actnum >= 5 )
            unset ( $movie [ 'cast' ][ $key ] );
        else
        {
            $actnum++;
            $actshown++;
        }
    }

    if ( !empty ( $movie [ 'bechdel_id' ] ) )
        $movie [ 'BECHDEL' ] = getBechdelImage ( $movie );
    else
        $movie [ 'BECHDEL' ] = '';

    return templateReplacements ( $template, $movie );
}

/**
 * Formular zum Bearbeiten oder Anlegen eines Films
 *
 * @param int $imdb_id IMDb-ID des zu bearbeitenden oder des zuletzt gesehenen Films
 * @param bool $edit Bearbeiten: true, Anlegen: false
 *
 * @return string HTML-Code
 */
function getEditForm ( $imdb_id = null, $edit = true )
{
    global $db;

    if ( $edit )
    {
        $movie = $db -> getSingleMovie ( $imdb_id );

        $snippet = '<h2>Film bearbeiten</h2>';
    }
    else
        $snippet = '<h2>Neuen Film anlegen</h2>';

    $snippet .= '<form method="POST" action="./" id="edit_movie">';

    if ( $edit )
    {
        $snippet .= '<input type="hidden" name="act" value="save_movie" />';
        $snippet .= '<input type="hidden" name="imdb_id" value="' . $movie [ 'imdb_id' ] . '" />';
    }
    else
    {
        $snippet .= '<input type="hidden" name="act" value="add_movie" />';

        $snippet .= '<fieldset>';
        $snippet .= '<legend><label for="imdbid">IMDb-ID*:</label></legend>';
        $snippet .= '<input type="text" maxlength="8" size="8" id="imdbid" name="imdb_id" value="" />';
        $snippet .= '</fieldset>';
    }

    // Sprache

    $snippet .= '<fieldset>';
    $snippet .= '<legend><label>Sprache*:</label></legend>';

    $languages = [
        'deu' => 'deutsch',
        'eng' => 'englisch',
        'omu' => 'Untertitel'
    ];

    foreach ( $languages as $lang => $label )
    {
        $checked = ( !empty ( $movie [ 'language_' . $lang ] ) )
                 ? ' checked="checked"'
                 : '';

        $snippet .= '<input type="checkbox" id="' . $lang . '" name="language_' . $lang . '" value="1"' . $checked . '/>';
        $snippet .= '<label for="' . $lang . '">' . $label . '</label>';
    }

    $snippet .= '</fieldset>';

    // Wertung

    $snippet .= '<fieldset>';
    $snippet .= '<legend><label>Wertung:</label></legend>';

    foreach ( [ 0,1,2,3,4,5,6,7,8,9,10 ] as $rating )
    {
        $checked = ( $edit && $movie [ 'custom_rating' ] == $rating )
                 ? ' checked="checked"'
                 : '';

        $snippet .= '<input type="radio" id="r' . $rating . '" name="custom_rating" value="' . $rating . '"' . $checked . '/>';
        $snippet .= '<label for="r' . $rating . '">' . $rating . '</label>';
    }

    $snippet .= '</fieldset>';

    // Bemerkungen

    $snippet .= '<fieldset>';
    $snippet .= '<legend><label for="notes">Bemerkungen:</label></legend>';
    $snippet .= '<textarea id="notes" name="custom_notes">';
    if ( $edit ) $snippet .= $movie [ 'custom_notes' ];
    $snippet .= '</textarea>';
    $snippet .= '</fieldset>';

    // Qualität

    $snippet .= '<fieldset>';
    $snippet .= '<legend><label for="quality">Qualität:</label></legend>';
    $snippet .= '<textarea id="quality" name="custom_quality">';
    if ( $edit ) $snippet .= $movie [ 'custom_quality' ];
    $snippet .= '</textarea>';
    $snippet .= '</fieldset>';

    $snippet .= '<input type="button" class="button abort" value="abbrechen" data-imdbid="' . $imdb_id . '" />';
    $snippet .= '<input type="submit" class="button submit" value="speichern" />';

    $snippet .= '</form>';

    return $snippet;
}

/**
 * Bechdel-Info ausgeben (Icon mit beschreibendem title-Text)
 *
 * @param array $movie Film-Infos
 *
 * @return string
 */
function getBechdelImage ( $movie )
{
    $src = 'fdb_img/bechdel/' . $movie [ 'bechdel_rating' ] . $movie [ 'bechdel_dubious' ] . '.png';

    switch ( $movie [ 'bechdel_rating' ] )
    {
        case 0:
            $text = 'Weniger als zwei Frauen im Film';
            break;
        case 1:
            $text = 'Zwei oder mehr Frauen im Film, die aber nicht miteinander reden';
            break;
        case 2:
            $text = 'Zwei oder mehr Frauen im Film, die aber nur über Männer reden';
            break;
        case 3:
            $text = 'Zwei oder mehr Frauen im Film, die über etwas anderes als Männer reden';
            break;
    }

    if ( $movie [ 'bechdel_dubious' ] )
        $text .= ', (uneindeutig)';

    return '<dt>Bechdel:</dt><dd><a href="http://bechdeltest.com/view/' . $movie [ 'bechdel_id' ] . '" target="_blank"><img src="' . $src . '" alt="' . $text  . '" title="' . $text  . '" /></a></dd>';
}