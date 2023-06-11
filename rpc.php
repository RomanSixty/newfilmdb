<?php

/**
 * dieses Skript bildet die RPC-Schnittstelle,
 * über die die AJAX-Abfragen laufen
 */

require ( 'lib_sqlitedb.php' );
require ( 'lib_bechdel.php' );
require ( 'lib_html.php' );
require ( 'lib_imdb.php' );
require ( 'lib_omdb.php' );

$db = new sqlitedb();

if ( !empty ( $_REQUEST [ 'act' ] ) ) switch ( $_REQUEST [ 'act' ] )
{
	case 'add_movie':
		$imdb_id = intval ( $_REQUEST [ 'imdb_id' ] );

		if (    empty ( $imdb_id )
			 || (    empty ( $_REQUEST [ 'language_deu' ] )
				  && empty ( $_REQUEST [ 'language_eng' ] )
				  && empty ( $_REQUEST [ 'language_omu' ] ) ) )
		{
			$return = getEditForm();

			break;
		}
		else
		{
			$movie = array (
				'@language_deu'   => isset ( $_REQUEST [ 'language_deu'   ] ) ? $_REQUEST [ 'language_deu'   ] : '',
				'@language_eng'   => isset ( $_REQUEST [ 'language_eng'   ] ) ? $_REQUEST [ 'language_eng'   ] : '',
				'@language_omu'   => isset ( $_REQUEST [ 'language_omu'   ] ) ? $_REQUEST [ 'language_omu'   ] : '',
				'@custom_rating'  => $_REQUEST [ 'custom_rating'  ],
				'$custom_notes'   => $_REQUEST [ 'custom_notes'   ],
				'$custom_quality' => $_REQUEST [ 'custom_quality' ],
			);

			$movie = array_merge ( $movie, getIMDbMovie ( $imdb_id ) );

			// Bechdel-Daten und weitere Ratings ergänzen
			$movie = array_merge ( $movie, getBechdelInfo ( $imdb_id ), getOMDbRatings ( $imdb_id ) );

			$db -> saveMovie ( $movie );
		}

		$return = getMovieDetails ( $imdb_id );

		break;

	case 'save_movie':
		$imdb_id = intval ( $_REQUEST [ 'imdb_id' ] );

		if ( !empty ( $imdb_id ) )
			$db -> updateMovie ( $_REQUEST );

		$return = getMovieDetails ( $imdb_id );

		break;

	case 'update_imdb':

		$imdb_id = intval ( $_REQUEST [ 'imdb_id' ] );

		$movie = array_merge ( $db -> getSingleMovie ( $imdb_id ), getIMDbMovie ( $imdb_id ), getBechdelInfo ( $imdb_id ), getOMDbRatings ( $imdb_id ) );

		$movie [ '@language_deu'   ] = $movie [ 'language_deu'   ];
		$movie [ '@language_eng'   ] = $movie [ 'language_eng'   ];
		$movie [ '@language_omu'   ] = $movie [ 'language_omu'   ];

		$movie [ '@metacritic'     ] = $movie [ 'metacritic'     ];
		$movie [ '@rottentomatoes' ] = $movie [ 'rottentomatoes' ];

		$movie [ '@custom_rating'  ] = $movie [ 'custom_rating'  ];
		$movie [ '$custom_notes'   ] = $movie [ 'custom_notes'   ];
		$movie [ '$custom_quality' ] = $movie [ 'custom_quality' ];

		$db -> saveMovie ( $movie );

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
}
else
{
	$movies = $db -> getMovieList ( $db -> getFilters ( $_REQUEST ) );

	$html = '';

	foreach ( $movies as $movie )
		$html .= getMovieSnippet ( $movie );

	$return = array ( 'count' => count ( $movies ),
				      'html'  => $html );
}

if ( !empty ( $return ) )
{
	header ( 'Content-Encoding: deflate' );

	if ( is_array ( $return ) )
		echo substr ( gzcompress ( json_encode ( $return ) ), 2 );
	else
		echo substr ( gzcompress ( $return ), 2 );
}