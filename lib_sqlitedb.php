<?php

define ( 'DIR', dirname ( __FILE__ ) );

class sqlitedb extends SQLite3
{
	var $dbfile    = '/data/filmdb.sqlite';
	var $structure = '/data/filmdb.sql';

	function __construct()
	{
		$this -> open ( DIR . $this -> dbfile );

		$this -> exec ( file_get_contents ( DIR . $this -> structure ) );
	}

	/**
	 * darf der Nutzer editieren?
	 *
	 * @todo konfigurierbar machen
	 * @return Boolean
	 */
	function isAdmin()
	{
		return ( $_SERVER [ 'REMOTE_ADDR' ] == '192.168.0.2' );
	}

	/**
	 * gibt die ID eines Cast-Eintrags zurück, entweder eine
	 * existierende ID wenn es den Eintrag schon gibt, oder
	 * eine neue ID, nachdem dieser angelegt wurde
	 *
	 * @param String $name vollständiger Name eines Cast-Mitglieds
	 * @return Integer ID
	 */
	function getCastId ( $name )
	{
		$query = $this -> prepare ( 'SELECT "cast_id" FROM "cast" WHERE "cast"=:name;' );
		$query -> bindValue ( ':name', $name, SQLITE3_TEXT );

		$res = $query -> execute();

		if ( $data = $res -> fetchArray ( SQLITE3_ASSOC ) )
			return $data [ 'cast_id' ];
		else
		{
			$query = $this -> prepare ( 'INSERT INTO "cast"("cast") VALUES(:name);' );
			$query -> bindValue ( ':name', $name, SQLITE3_TEXT );

			$res = $query -> execute();

			return $this -> lastInsertRowID();
		}
	}

	function saveCast ( $table, $imdb_id, $arr_cast )
	{
		$this -> exec ( 'DELETE FROM ' . $table . ' WHERE imdb_id=' . $imdb_id );

		$sql = 'INSERT INTO ' . $table . ' ( cast_id, imdb_id ) VALUES ';

		$values = array();

		foreach ( $arr_cast as $cast_id )
			$values[] = '(' . $cast_id . ', ' . $imdb_id . ')';

		$sql .= implode ( ',', $values );

		$this -> exec ( $sql );
	}

	function saveGenre ( $imdb_id, $arr_genres )
	{
		$this -> exec ( 'DELETE FROM genre2movie WHERE imdb_id=' . $imdb_id );

		$sql = 'INSERT INTO genre2movie ( genre_id, imdb_id ) VALUES ';

		$values = array();

		foreach ( $arr_genres as $genre_id )
			$values[] = '(' . $genre_id . ', ' . $imdb_id . ')';

		$sql .= implode ( ',', $values );

		$this -> exec ( $sql );
	}

	/**
	 * gibt die ID eines Genre-Eintrags zurück, entweder eine
	 * existierende ID wenn es den Eintrag schon gibt, oder
	 * eine neue ID, nachdem dieser angelegt wurde
	 *
	 * @param String $name Name eines Genres
	 * @return Integer ID
	 */
	function getGenreId ( $name )
	{
		$query = $this -> prepare ( 'SELECT "genre_id" FROM "genre" WHERE "genre"=:name' );
		$query -> bindValue ( ':name', $name, SQLITE3_TEXT );

		$res = $query -> execute();

		if ( $data = $res -> fetchArray ( SQLITE3_ASSOC ) )
			return $data [ 'genre_id' ];
		else
		{
			$query = $this -> prepare ( 'INSERT INTO "genre"("genre") VALUES(:name)' );
			$query -> bindValue ( ':name', $name, SQLITE3_TEXT );

			$res = $query -> execute();

			return $this -> lastInsertRowID();
		}
	}

	function saveMovie ( $data )
	{
		$this -> results ( 'INSERT OR REPLACE INTO movie(
			imdb_id,
			imdb_photo,
			imdb_plot,
			imdb_rating,
			imdb_runtime,
			imdb_title_deu,
			imdb_title_orig,
			imdb_year,
			language_deu,
			language_eng,
			language_omu,
			custom_rating,
			custom_notes,
			custom_quality,
			bechdel_id,
			bechdel_rating,
			bechdel_dubious,
			fulltext
		) VALUES (
			:imdb_id,
			:imdb_photo,
			:imdb_plot,
			:imdb_rating,
			:imdb_runtime,
			:imdb_title_deu,
			:imdb_title_orig,
			:imdb_year,
			:language_deu,
			:language_eng,
			:language_omu,
			:custom_rating,
			:custom_notes,
			:custom_quality,
			:bechdel_id,
			:bechdel_rating,
			:bechdel_dubious,
			:fulltext
		)', $data );
	}

	private function results ( $sql, $placeholders = array() )
	{
		$query = $this -> prepare ( $sql );

		foreach ( $placeholders as $key => $value )
		{
			if ( empty ( $value ) )
				$type = SQLITE3_NULL;
			else switch ( $key{0} )
			{
				case '@': $type = SQLITE3_INTEGER; break;
				case '#': $type = SQLITE3_FLOAT;   break;
				case '$':
				default:  $type = SQLITE3_TEXT;    break;
			}

			$query -> bindValue ( ':'.substr ( $key, 1 ), $value, $type );
		}

		$res = $query -> execute();

		$data = array();

		if ( $res )
			while ( $ds = $res -> fetchArray ( SQLITE3_ASSOC ) )
				$data[] = $ds;

		return $data;
	}

	/**
	 * wieviele in der DB vorhandene Filme hat ein Regisseur gedreht?
	 *
	 * @param String $director_id Cast-ID des Regisseurs
	 * @return Boolean hat er oder hat er nicht
	 */
	function directorMovieCount ( $director_id )
	{
		$moviecount = $this -> results (
			'SELECT COUNT(cast_id) AS cnt
			FROM director2movie
			WHERE cast_id=:cast_id',
			array ( '@cast_id' => $director_id )
		);

		return $moviecount [ 0 ][ 'cnt' ];
	}

	/**
	 * wieviele in der DB vorhandene Filme hat ein Cast-Mitglied gedreht?
	 *
	 * @param String $cast_id ID des Cast-Mitglieds
	 * @return Boolean hat er oder hat er nicht
	 */
	function castMovieCount ( $cast_id )
	{
		$moviecount = $this -> results (
			'SELECT COUNT(cast_id) AS cnt
			FROM cast2movie
			WHERE cast_id=:cast_id',
			array ( '@cast_id' => $cast_id )
		);

		return $moviecount [ 0 ][ 'cnt' ];
	}

	/**
	 * Filter aus REQUEST erzeugen
	 *
	 * @param Array $form Formulardaten aus einem REQUEST
	 * @return Array MongoDB-Filterkriterien
	 */
	function getFilters ( $form )
	{
		$joins = array();
		$where = array();

		// Volltextsuche
		if ( !empty ( $form [ 'fulltext' ] ) )
		{
			$terms = explode ( ' ', $this -> _transliterate ( $form [ 'fulltext' ] ) );

			foreach ( $terms as $term )
				$where[] = 'm.fulltext LIKE "%' . $term . '%"';
		}

		// Sprachfilter (ODER)
		if ( !empty ( $form [ 'lang' ] ) && is_array ( $form [ 'lang' ] ) )
		{
			$lang_where = array();

			foreach ( $form [ 'lang' ] as $lang => $value )
				$lang_where[] = $lang .'=1';

			$where [ 'lang' ] = '(' . implode ( ' OR ', $lang_where ) . ')';
		}

		// Regiefilter
		if ( !empty ( $form [ 'director' ] ) && is_array ( $form [ 'director' ] ) )
		{
			foreach ( $form [ 'director' ] as $value )
				$dir_where[] = 'd2m.cast_id=' . intval ( $value );

			$joins[] = 'LEFT JOIN director2movie d2m ON d2m.imdb_id=m.imdb_id';
			$where[] = '(' . implode ( ' OR ', $dir_where ) . ')';
		}

		// Cast-Filter
		if ( !empty ( $form [ 'cast' ] ) && is_array ( $form [ 'cast' ] ) )
		{
			$idx = 0;
			foreach ( $form [ 'cast' ] as $value )
			{
				$joins[] = 'LEFT JOIN cast2movie a2m_'.$idx.' ON a2m_'.$idx.'.imdb_id=m.imdb_id';
				$where[] = 'a2m_'.$idx.'.cast_id=' . intval ( $value );

				$idx++;
			}
		}

		// Genre-Filter (UND)
		if ( !empty ( $form [ 'genre' ] ) && is_array ( $form [ 'genre' ] ) )
		{
			$idx = 0;
			foreach ( $form [ 'genre' ] as $value )
			{
				$joins[] = 'LEFT JOIN genre2movie g2m_'.$idx.' ON g2m_'.$idx.'.imdb_id=m.imdb_id';
				$where[] = 'g2m_'.$idx.'.genre_id=' . intval ( $value );

				$idx++;
			}
		}

		return array ( 'joins' => $joins, 'where' => $where );
	}

	/**
	 * Liste von Filmen
	 *
	 * @param Array $filters REQUEST-Suchfilter
	 * @return Array Filme
	 * @todo Sortierungsmöglichkeiten
	 */
	function getMovieList ( $filters = null )
	{
		$sql = 'SELECT DISTINCT
				m.imdb_id,
				m.imdb_title_orig,
				m.imdb_photo,
				m.custom_rating
			FROM movie m
			%JOIN
			%WHERE
			%ORDER';

		$joins   = array();
		$where[] = '1=1';

		$filters [ 'where' ][] = '1=1';

		$sql = str_replace ( '%JOIN',  implode ( ' ', $filters [ 'joins' ] ),                $sql );
		$sql = str_replace ( '%WHERE', 'WHERE ' . implode ( ' AND ', $filters [ 'where' ] ), $sql );
		$sql = str_replace ( '%ORDER', 'ORDER BY imdb_title_orig ASC',                       $sql );

		return $this -> results ( $sql );
	}

	/**
	 * Detail-Informationen eines Films
	 *
	 * @param Integer $imdb_id IMDb-ID
	 * @return Array Filmdaten
	 */
	function getSingleMovie ( $imdb_id )
	{
		$movies = $this -> results (
			'SELECT * FROM movie WHERE imdb_id=:imdb_id',
			array ( '@imdb_id' => $imdb_id )
		);

		$movie = $movies [ 0 ];

		// Directors
		$movie [ 'director' ] = $this -> results (
			'SELECT
				c.cast_id AS id,
				"cast"    AS "name"
			FROM "cast" c
			LEFT JOIN director2movie d2m
				ON d2m.cast_id=c.cast_id
			WHERE imdb_id=:imdb_id',
			array ( '@imdb_id' => $imdb_id )
		);

		// Cast
		$movie [ 'cast' ] = $this -> results (
			'SELECT
				c.cast_id AS id,
				"cast"    AS "name"
			FROM "cast" c
			LEFT JOIN cast2movie c2m
				ON c2m.cast_id=c.cast_id
			WHERE imdb_id=:imdb_id',
			array ( '@imdb_id' => $imdb_id )
		);

		// Genres
		$movie [ 'genres' ] = $this -> results (
			'SELECT
				g.genre_id AS id,
				genre      AS "name"
			FROM genre g
			LEFT JOIN genre2movie g2m
				ON g2m.genre_id=g.genre_id
			WHERE imdb_id=:imdb_id',
			array ( '@imdb_id' => $imdb_id )
		);

		return $movie;
	}

	/**
	 * fertige Filmdaten in die Datenbank schreiben
	 *
	 * @param Array $movie Filmdaten
	 */
	function insertMovie ( $movie )
	{
		if ( !isAdmin() ) return;

		global $collection;

		updateFulltext ( $movie );

		$collection -> save ( $movie );
	}

	/**
	 * Filmdaten aktualisieren
	 *
	 * @param Integer $imdb_id IMDb-ID
	 * @param Array $custom zu aktualisierende Filmdaten
	 */
	function updateMovie ( $imdb_id, $custom )
	{
		if ( !isAdmin() ) return;

		global $collection;

		$imdb_id = intval ( $imdb_id );

		$collection -> update ( array ( 'imdb.imdb_id' => $imdb_id ),
								array ( '$set' => $custom ) );

		// Volltextindex aktualisieren
		$movie = getSingleMovie ( $imdb_id );

		updateFulltext ( $movie );

		$collection -> update ( array ( 'imdb.imdb_id' => $imdb_id ),
								array ( '$set' => array ( 'fulltext' => $movie [ 'fulltext' ] ) ) );
	}

	/**
	 * Volltextindex eines Films erzeugen/aktualisieren
	 *
	 * @param Array $movie MongoDB-Filmdaten
	 */
	function updateFulltext ( &$movie )
	{
		// alle zu indizierenden Felder zusammensuchen

		$fulltext = $movie [ 'imdb' ][ 'title_orig' ] . ' '
				  . $movie [ 'imdb' ][ 'title_deu'  ] . ' '
				  . implode ( ' ', $movie [ 'imdb' ][ 'director' ] ) . ' '
				  . implode ( ' ', $movie [ 'imdb' ][ 'cast'     ] ) . ' '
				  . $movie [ 'custom' ][ 'notes' ];

		$fulltext = _transliterate ( $fulltext );

		// jedes Wort nur einmal
		$fulltext = array_unique ( explode ( ' ', $fulltext ) );

		// array_values hier, damit die Elemente ohne Lücken durchnummeriert
		// sind, andernfalls kann die MongoDB darin nicht vernünftig suchen
		$movie [ 'fulltext' ] = array_values ( $fulltext );
	}

	/**
	 * Zeichenkette transliterieren
	 *
	 * @param String $string Zeichenkette
	 * @return String normalisierte Zeichenkette
	 */
	function _transliterate ( $string )
	{
		setlocale ( 'LC_ALL', 'de_DE' );

		$string = iconv ( 'utf-8', 'ASCII//TRANSLIT', $string );

		$string = preg_replace ( '~[^\w ]~', '', $string );
		$string = preg_replace ( '~[\s]+~', ' ', $string );

		return strtolower ( $string );
	}
}