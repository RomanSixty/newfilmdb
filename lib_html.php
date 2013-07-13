<?php

/**
 * dieses Skript enthält Funktionen, die HTML-Schnipsel erzeugen
 * diese werden sowohl beim initialen Aufruf der Seite benötigt
 * als auch von der RPC-Schnittstelle aus aufgerufen (vgl. rpc.php)
 */

/**
 * Platzhalter im Template ersetzen
 *
 * @param String $template HTML-Schnipsel
 * @param Array $replacements Platzhalter => Wert
 * @return String HTML-Schnipsel
 */
function templateReplacements ( $template, $replacements )
{
	foreach ( $replacements as $key => $val )
	{
		if ( is_array ( $val ) )
		{
			preg_match ( '~({{{LOOP_'.$key.'(.*%%%'.$key.'%%%.*)}}})~sU', $template, $matches );

			if ( count ( $matches ) )
			{
				$rep = '';

				foreach ( $val as $value )
					$rep .= str_replace ( '%%%' . $key . '%%%', $value, $matches [ 2 ] );

				$template = str_replace ( $matches [ 1 ], $rep, $template );
			}
			else
				$template = str_replace ( $matches [ 1 ], '', $template );
		}
		else
		{
			$template = str_replace ( '%%%'.$key.'%%%', $val,   $template );

			// Bedingungen
			if ( empty ( $val ) )
			{
				$template = preg_replace ( '~<!-- !!!'.$key.'_START!!! -->.*<!-- !!!'.$key.'_END!!! -->~sU', '', $template );
			}
		}
	}

	return $template;
}

/**
 * benutzerspezifisches Bild zurückgeben, wenn vorhanden
 * sonst das Bild aus der IMDb
 *
 * @param Array $movie Filmdaten
 * @return String Bildpfad
 */
function getBestImage ( $movie )
{
	if ( file_exists ( $photo = './images/own_' . str_pad ( $movie [ 'imdb_id' ], 7, '0', STR_PAD_LEFT ) . '.jpg' ) )
    	return $photo;
    elseif ( !empty ( $movie [ 'photo' ] ) )
    	return $movie [ 'photo' ];
    else
    	return './fdb_img/bg.jpg';
}

/**
 * HTML-Code für das Dashboard im Seitenkopf mit Suchfiltern
 *
 * @param Integer $count Anzahl der ausgegebenen Filme
 * @return String HTML-Code
 */
function getDashboard ( $count = 0 )
{
	$template = file_get_contents ( dirname ( __FILE__ ) . '/templates/dashboard.html' );

	$replacements = array ( 'COUNT' => $count );

    return templateReplacements ( $template, $replacements );
}

/**
 * HTML-Schnipsel eines einzelnen Films der Filmliste
 *
 * @param Array $movie Filmdaten aus der MongoDB
 * @return String HTML-Code
 */
function getMovieSnippet ( $movie )
{
	$template = file_get_contents ( dirname ( __FILE__ ) . '/templates/movie_snippet.html' );

	$replacements = $movie;

    $replacements [ 'PHOTO' ] = getBestImage ( $movie );

    // Rating bestimmen
	if ( $movie [ 'rating' ] > 0 )
		$replacements [ 'RATING' ] = $movie [ 'rating' ].'/10';
	else
		$replacements [ 'RATING' ] = 'noch keine Wertung';

    return templateReplacements ( $template, $replacements );
}

/**
 * HTML-Schnipsel der Film-Detailansicht für die Sidebar
 *
 * @param Integer $imdb_id IMDb-ID
 * @return String HTML-Code
 */
function getMovieDetails ( $imdb_id )
{
	$template = file_get_contents ( dirname ( __FILE__ ) . '/templates/movie_details.html' );

	$movie = getSingleMovie ( $imdb_id );

	$replacements = $movie [ 'imdb' ];

	foreach ( $movie [ 'custom' ] as $key => $val )
	{
		if ( is_array ( $val ) )
			$replacements [ 'custom_'.$key ] = $val;
		else
			$replacements [ 'custom_'.$key ] = nl2br ( $val );
	}

	$replacements [ 'IS_ADMIN'      ] = isAdmin();
	$replacements [ 'PHOTO'         ] = getBestImage ( $movie [ 'imdb' ] );
	$replacements [ 'IMDBID_PADDED' ] = str_pad ( $movie [ 'imdb' ][ 'imdb_id' ], 7, '0', STR_PAD_LEFT );
	$replacements [ 'TITLE_DIFF'    ] = ( $movie [ 'imdb' ][ 'title_orig' ] != $movie [ 'imdb' ][ 'title_deu' ] );

	if (    empty ( $movie [ 'custom' ][ 'notes'   ] )
         && empty ( $movie [ 'custom' ][ 'quality' ] ) )
		$replacements [ 'NOTES_QUALITY' ] = false;

	$directors = '';
    foreach ( $movie [ 'imdb' ][ 'director' ] as $director )
    {
        if ( $num = directorHasOtherMovies ( $movie [ 'imdb' ][ 'imdb_id' ], $director ) )
            $directors .= '<li data-count="' . ($num+1) . '"><a href="#">' . $director . '</a></li>';
        else
            $directors .= '<li>' . $director . '</li>';
    }
    $replacements [ 'DIRECTORS' ] = $directors;


    $actnum = $actshown = 0;
    $actors = '';
    foreach ( $movie [ 'imdb' ][ 'cast' ] as $actor )
    {
        if ( $actshown < 40 && $num = actorHasOtherMovies ( $movie [ 'imdb' ][ 'imdb_id' ], $actor ) )
        {
            $actors .= '<li data-count="' . ($num+1) . '"><a href="#">' . $actor . '</a></li>';
            $actshown++;
        }
        elseif ( $actnum < 5 )
            $actors .= '<li>' . $actor . '</li>';

        $actnum++;
    }
    $replacements [ 'ACTORS' ] = $actors;

    return templateReplacements ( $template, $replacements );
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