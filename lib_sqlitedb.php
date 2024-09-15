<?php

class sqlitedb extends SQLite3
{
    var $dbfile    = '/data/filmdb.sqlite';
    var $structure = '/data/filmdb.sql';

    function __construct()
    {
        $this -> open ( __DIR__ . $this -> dbfile );

        //$this -> updateDBSchema();
    }

    function updateDBSchema()
    {
        $schema_queries = explode ( ';', file_get_contents ( __DIR__ . $this -> structure ) );

        foreach ( $schema_queries as $query )
        {
            try {
                $this -> exec ( $query );
            } catch ( Exception $e ) { }
        }
    }

    /**
     * darf der Nutzer editieren?
     *
     * @todo konfigurierbar machen
     * @return bool
     */
    public function isAdmin()
    {
        return ( $_SERVER [ 'REMOTE_ADDR' ] == '192.168.0.2' );
    }

    /**
     * gibt die ID eines Cast-Eintrags zurück, entweder eine
     * existierende ID wenn es den Eintrag schon gibt, oder
     * eine neue ID, nachdem dieser angelegt wurde
     *
     * @param string $name vollständiger Name eines Cast-Mitglieds
     * @return int ID
     */
    private function getCastId ( $name )
    {
        $query = $this -> prepare ( 'SELECT "cast_id" FROM "cast" WHERE "cast"=:name' );
        $query -> bindValue ( ':name', $name, SQLITE3_TEXT );

        $res = $query -> execute();

        if ( $data = $res -> fetchArray ( SQLITE3_ASSOC ) )
            return $data [ 'cast_id' ];
        else
        {
            $query = $this -> prepare ( 'INSERT INTO "cast"("cast") VALUES(:name)' );
            $query -> bindValue ( ':name', $name, SQLITE3_TEXT );

            $query -> execute();

            return $this -> lastInsertRowID();
        }
    }

    /**
     * speichert den aktuellen Cast eines Films, entfernt dazu ggf. den bestehenden
     *
     * @param string $table    cast2movie | director2movie
     * @param int    $imdb_id  IMDb-ID
     *
     * @param array $arr_cast Liste von Cast-IDs
     */
    private function saveCast ( $table, $imdb_id, $arr_cast )
    {
        $this -> exec ( 'DELETE FROM ' . $table . ' WHERE imdb_id=' . $imdb_id );

        $sql = 'INSERT INTO ' . $table . ' ( cast_id, imdb_id, sort ) VALUES ';

        $sort   = 0;
        $values = [];

        $arr_cast = array_unique ( $arr_cast );

        // damit die Statements nicht zu groß werden, nur 100 pro Schwung
        while ( $cast_id = array_shift ( $arr_cast ) )
        {
            $values[] = '(' . $cast_id . ', ' . $imdb_id . ', ' . $sort++ . ')';

            if ( count ( $values ) < 100 )
                continue;
            else
            {
                $this -> exec ( $sql . implode ( ',', $values ) );

                $values = [];
            }
        }

        // die restlichen
        $this -> exec ( $sql . implode ( ',', $values ) );
    }

    /**
     * speichert die aktuelle Liste der Genres zu einem Film, entfernt ggf. die bestehenden
     *
     * @param int $imdb_id IMDb-ID
     * @param array $arr_genres Liste von Genre-IDs
     */
    private function saveGenre ( $imdb_id, $arr_genres )
    {
        $this -> exec ( 'DELETE FROM genre2movie WHERE imdb_id=' . $imdb_id );

        $sql = 'INSERT INTO genre2movie ( genre_id, imdb_id ) VALUES ';

        $values = [];

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
     * @param string $name Name eines Genres
     * @return int ID
     */
    private function getGenreId ( $name )
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

            $query -> execute();

            return $this -> lastInsertRowID();
        }
    }

    /**
     * speichert einen Film in der Datenbank, mitsamt aller Zusatzinfos, Cast, Genres etc.
     *
     * @param array $data Film-Daten
     */
    public function saveMovie ( $data )
    {
        $this -> results ( 'REPLACE INTO movie(
            imdb_id,
            imdb_photo,
            imdb_plot,
            imdb_rating,
            imdb_top250,
            imdb_runtime,
            imdb_title_deu,
            imdb_title_eng,
            imdb_title_orig,
            imdb_year,
            imdb_type,
            language_deu,
            language_eng,
            language_omu,
            custom_rating,
            custom_notes,
            custom_quality,
            bechdel_id,
            bechdel_rating,
            bechdel_dubious,
            metacritic,
            rottentomatoes
        ) VALUES (
            :imdb_id,
            :imdb_photo,
            :imdb_plot,
            :imdb_rating,
            :imdb_top250,
            :imdb_runtime,
            :imdb_title_deu,
            :imdb_title_eng,
            :imdb_title_orig,
            :imdb_year,
            :imdb_type,
            :language_deu,
            :language_eng,
            :language_omu,
            :custom_rating,
            :custom_notes,
            :custom_quality,
            :bechdel_id,
            :bechdel_rating,
            :bechdel_dubious,
            :metacritic,
            :rottentomatoes
        )', $data );

        // Cast
        if ( isset ( $data [ 'cast' ] ) )
        {
            $actors = [];

            foreach ( $data [ 'cast' ] as $actor )
                $actors[] = $this -> getCastId ( $actor );

            $this -> saveCast ( 'cast2movie', $data ['@imdb_id' ], $actors );
        }

        // Director
        if ( isset ( $data [ 'director' ] ) )
        {
            $directors = [];

            foreach ( $data [ 'director' ] as $director )
                $directors[] = $this -> getCastId ( $director );

            $this -> saveCast ( 'director2movie', $data ['@imdb_id' ], $directors );
        }

        // Genres
        $genres = [];

        foreach ( $data [ 'genres' ] as $genre )
            $genres[] = $this -> getGenreId ( $genre );

        $this -> saveGenre ( $data [ '@imdb_id' ], $genres );

        $this -> updateFulltext ( $data [ '@imdb_id' ] );
    }

    /**
     * aktualisiert die IMDb-unabhängigen Filmdaten
     *
     * @param array $data Filmdaten aus dem Edit-Formular
     */
    public function updateMovie ( $data )
    {
        foreach ( [ 'deu', 'eng', 'omu' ] as $lang )
            if ( !isset ( $data [ 'language_'.$lang ] ) )
                $data [ 'language_'.$lang ] = '';

        $movie = [
            '@imdb_id'        => $data [ 'imdb_id'        ],
            '@language_deu'   => $data [ 'language_deu'   ],
            '@language_eng'   => $data [ 'language_eng'   ],
            '@language_omu'   => $data [ 'language_omu'   ],
            '@custom_rating'  => $data [ 'custom_rating'  ],
            '$custom_notes'   => $data [ 'custom_notes'   ],
            '$custom_quality' => $data [ 'custom_quality' ]
        ];

        // und aktualisieren
        $this -> results ( 'UPDATE movie
            SET
                language_deu   = :language_deu,
                language_eng   = :language_eng,
                language_omu   = :language_omu,
                custom_rating  = :custom_rating,
                custom_notes   = :custom_notes,
                custom_quality = :custom_quality
            WHERE imdb_id = :imdb_id', $movie );

        $this -> updateFulltext ( $data [ 'imdb_id' ] );
    }

    /**
     * wieviele in der DB vorhandene Filme hat ein Cast-Mitglied gedreht?
     *
     * @param string $cast_id ID des Cast-Mitglieds
     * @param string $table   director2movie | cast2movie
     *
     * @return int Anzahl Filme
     */
    public function castMovieCount ( $cast_id, $table )
    {
        $moviecount = $this -> results (
            'SELECT COUNT(cast_id) AS cnt
            FROM ' . $table . '
            WHERE cast_id=:cast_id',
            [ '@cast_id' => $cast_id ]
        );

        return $moviecount [ 0 ][ 'cnt' ];
    }

    /**
     * Filter aus REQUEST erzeugen
     *
     * @param array $form Formulardaten aus einem REQUEST
     * @return array JOINs und WHERE-Klausel (jeweils Arrays) für DB-Abfrage
     */
    public function getFilters ( $form )
    {
        $joins = [];
        $where = [];

        // Volltextsuche
        if ( !empty ( $form [ 'fulltext' ] ) )
        {
            if ( is_numeric ( $form [ 'fulltext' ] ) )
                $where[] = 'm.imdb_id=' . intval ( $form [ 'fulltext' ] );
            else
            {
                $terms = explode ( ' ', $this -> _transliterate ( $form [ 'fulltext' ] ) );

                foreach ( $terms as $term )
                    $where[] = 'm.fulltext LIKE "%' . $term . '%"';
            }
        }

        // Sprachfilter (ODER)
        if ( !empty ( $form [ 'lang' ] ) && is_array ( $form [ 'lang' ] ) )
        {
            $lang_where = [];

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
                if ( $value > 0 )
                {
                    $joins[] = 'LEFT JOIN genre2movie g2m_'.$idx.' ON g2m_'.$idx.'.imdb_id=m.imdb_id';
                    $where[] = 'g2m_'.$idx.'.genre_id=' . intval ( $value );
                }
                else
                {
                    $joins[] = 'LEFT JOIN genre2movie g2m_'.$idx.' ON g2m_'.$idx.'.imdb_id=m.imdb_id AND g2m_'.$idx.'.genre_id=' . abs ( intval ( $value ) );
                    $where[] = 'g2m_'.$idx.'.genre_id IS NULL';
                }

                $idx++;
            }
        }

        // Typ-Filter (ODER)
        if ( !empty ( $form [ 'type' ] ) && is_array ( $form [ 'type' ] ) )
        {
            $type_where = [];
            $types = $this -> getTypeList();

            foreach ( $form [ 'type' ] as $value )
            {
                if ( $value >= 0 )
                    $type_where[] = 'm.imdb_type="' . $types [ $value -1 ][ 'name' ] . '"';
                else
                    $where[] = 'm.imdb_type<>"' . $types [ abs ( $value ) -1 ][ 'name' ] . '"';
            }

            if ( !empty ( $type_where ) )
                $where[] = '(' . implode ( ' OR ', $type_where ) . ')';
        }

        return [ 'joins' => $joins, 'where' => $where ];
    }

    /**
     * Liste von Filmen
     * @todo Sortierungsmöglichkeiten
     *
     * @param array $filters REQUEST-Suchfilter
     * @return array Filme
     */
    function getMovieList ( $filters = null )
    {
        $sql = 'SELECT DISTINCT
                m.imdb_id,
                m.imdb_title_orig,
                m.imdb_photo,
                m.custom_rating,
                m.imdb_year
            FROM movie m
            %JOIN
            %WHERE
            %ORDER';

        $filters [ 'where' ][] = '1=1';

        if ( empty ( $filters [ 'joins' ] ) )
            $filters [ 'joins' ] = [];

        $sql = str_replace ( '%JOIN',  implode ( ' ', $filters [ 'joins' ] ),                $sql );
        $sql = str_replace ( '%WHERE', 'WHERE ' . implode ( ' AND ', $filters [ 'where' ] ), $sql );
        $sql = str_replace ( '%ORDER', 'ORDER BY imdb_year DESC, m.imdb_id DESC',            $sql );

        return $this -> results ( $sql );
    }

    /**
     * Detail-Informationen eines Films
     *
     * @param int $imdb_id IMDb-ID
     * @return array Filmdaten
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
            WHERE imdb_id=:imdb_id
            ORDER BY c2m.sort ASC',
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
     * besorgt eine Liste aller in der DB vorhandenen Genres
     * mit zugehöriger Anzahl der Filme dieses Genres
     *
     * @return array Genreliste
     */
    public function getGenreList()
    {
        return $this -> results ( 'SELECT
                g.genre_id,
                genre,
                COUNT(g2m.imdb_id) AS cnt
            FROM genre g
            LEFT JOIN genre2movie g2m ON g2m.genre_id=g.genre_id
            GROUP BY g2m.genre_id
            ORDER BY COUNT(g2m.imdb_id) DESC' );
    }

    /**
     * besorgt eine Liste aller in der DB vorhandenen Filmtypen (Doku, Kurzfilm etc.)
     * mit zugehöriger Anzahl der Filme dieses Typs
     *
     * @return array Typenliste
     */
    public function getTypeList()
    {
        $types = $this -> results ( 'SELECT
                imdb_type AS name,
                COUNT(imdb_id) AS cnt
            FROM movie
            GROUP BY imdb_type
            ORDER BY imdb_type' );

        foreach ( $types as $index => &$type )
            $type [ 'value' ] = $index + 1;

        return $types;
    }

    /**
     * besorgt eine Liste der 25 aktivsten Darsteller*innen
     * (Kurz- und Animationsfilme ausgenommen)
     * mit zugehöriger Anzahl der Filme
     *
     * @return array Darsteller-Liste
     */
    public function getCastList()
    {
        return $this -> results ( 'SELECT
                c.cast_id,
                "cast",
                COUNT(c2m.imdb_id) AS cnt
            FROM "cast" c
            LEFT JOIN cast2movie  c2m ON c2m.cast_id=c.cast_id
            LEFT JOIN genre2movie g2m_1 ON c2m.imdb_id=g2m_1.imdb_id AND g2m_1.genre_id = 3
            LEFT JOIN genre2movie g2m_2 ON c2m.imdb_id=g2m_2.imdb_id AND g2m_2.genre_id = 6
            WHERE g2m_1.genre_id IS NULL
              AND g2m_2.genre_id IS NULL
            GROUP BY c2m.cast_id
            ORDER BY
                COUNT(c2m.imdb_id) DESC,
                AVG(c2m.sort) ASC
            LIMIT 25' );
    }

    /**
     * besorgt eine Liste der 25 aktivsten Regisseur*innen
     * (Kurzfilme ausgenommen)
     * mit zugehöriger Anzahl der Filme
     *
     * @return array Cast-Liste
     */
    public function getDirectorList()
    {
        return $this -> results ( 'SELECT
                c.cast_id,
                "cast",
                COUNT(d2m.imdb_id) AS cnt
            FROM "cast" c
            LEFT JOIN director2movie d2m ON d2m.cast_id=c.cast_id
            LEFT JOIN genre2movie g2m ON d2m.imdb_id=g2m.imdb_id AND g2m.genre_id = 3
            WHERE g2m.genre_id IS NULL
            GROUP BY d2m.cast_id
            ORDER BY
                COUNT(d2m.imdb_id) DESC,
                AVG(d2m.sort) ASC
            LIMIT 25' );
    }

    /**
     * SQLite-Wrapper für Queries mit Parametern
     *
     * @param string $sql SQL-Query mit ggf. Platzhaltern
     * @param array  $placeholders assoziatives Array mit Platzhaltern und deren Datentypen
     * @return array Ergebnis der SQL-Abfrage
     */
    public function results ( $sql, $placeholders = array() )
    {
        $query = $this -> prepare ( $sql );

        foreach ( $placeholders as $key => $value )
        {
            if ( empty ( $value ) && $value != 0 )
                $type = SQLITE3_NULL;
            else switch ( $key[0] )
            {
                case '@': $type = SQLITE3_INTEGER; break;
                case '#': $type = SQLITE3_FLOAT;   break;
                case '$':
                default:  $type = SQLITE3_TEXT;    break;
            }

            $query -> bindValue ( ':'.substr ( $key, 1 ), $value, $type );
        }

        $res = $query -> execute();

        $data = [];

        if ( $res )
            while ( $ds = $res -> fetchArray ( SQLITE3_ASSOC ) )
                $data[] = $ds;

        return $data;
    }

    /**
     * aktualisiert die Volltextinformationen eines Films in der Datenbank
     *
     * @param int $imdb_id IMDb-ID
     */
    private function updateFulltext ( $imdb_id )
    {
        $movie = $this -> getSingleMovie ( $imdb_id );

        $fulltext = $this -> getFulltext ( $movie );

        $this -> results ( 'UPDATE movie SET fulltext = :fulltext WHERE imdb_id = :imdb_id',
            array (
                '$fulltext' => $fulltext,
                '@imdb_id'  => $imdb_id
            )
        );
    }

    /**
     * Volltextindex eines Films erzeugen
     *
     * @param array $movie Filmdaten
     */
    private function getFulltext ( &$movie )
    {
        // alle zu indizierenden Felder zusammensuchen

        $fulltext[] = $movie [ 'imdb_title_orig' ];
        $fulltext[] = $movie [ 'imdb_title_deu'  ];
        $fulltext[] = $movie [ 'imdb_title_eng'  ];
        $fulltext[] = $movie [ 'custom_notes'    ];

        // Genres
        foreach ( $movie [ 'genres' ] as $genre )
            $fulltext[] = $genre [ 'name' ];

        // Cast
        foreach ( $movie [ 'cast' ] as $cast )
            $fulltext[] = $cast [ 'name' ];

        // Director
        foreach ( $movie [ 'director' ] as $director )
            $fulltext[] = $director [ 'name' ];

        // jedes Wort nur einmal
        $fulltext = array_unique ( $fulltext );

        // Top250?
        if ( !empty ( $movie [ 'imdb_top250' ] ) )
            $fulltext[] = 'Top250';

        // Worte normalisieren
        $fulltext = $this -> _transliterate ( implode ( ' ', $fulltext ) );

        return $fulltext;
    }

    /**
     * Zeichenkette transliterieren
     *
     * @param string $string Zeichenkette
     * @return string normalisierte Zeichenkette
     */
    private function _transliterate ( $string )
    {
        setlocale ( LC_ALL, 'de_DE' );

        $string = iconv ( 'utf-8', 'ASCII//TRANSLIT', $string );

        $string = preg_replace ( '~[^\w ]~', '', $string );
        $string = preg_replace ( '~[\s]+~', ' ', $string );

        return strtolower ( $string );
    }
}
