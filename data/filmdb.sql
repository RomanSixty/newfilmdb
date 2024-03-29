CREATE TABLE IF NOT EXISTS "movie" (
	"imdb_id" INTEGER PRIMARY KEY NOT NULL UNIQUE,
	"date_update" DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
	"imdb_photo" VARCHAR DEFAULT NULL,
	"imdb_plot" TEXT NOT NULL DEFAULT "",
	"imdb_rating" FLOAT NOT NULL DEFAULT 0,
	"imdb_runtime" INTEGER NOT NULL DEFAULT 0,
	"imdb_title_deu" VARCHAR NOT NULL DEFAULT "",
	"imdb_title_orig" VARCHAR NOT NULL DEFAULT "",
	"imdb_year" INTEGER NOT NULL DEFAULT 1900,
	"language_deu" BOOL DEFAULT NULL,
	"language_eng" BOOL DEFAULT NULL,
	"language_omu" BOOL DEFAULT NULL,
	"custom_rating" INTEGER,
	"custom_notes" TEXT,
	"custom_quality" TEXT,
	"bechdel_id" INTEGER,
	"bechdel_rating" INTEGER,
	"bechdel_dubious" INTEGER,
	"fulltext" TEXT
);

CREATE TABLE IF NOT EXISTS "cast" (
	"cast_id" INTEGER PRIMARY KEY  NOT NULL,
	"cast" VARCHAR NOT NULL UNIQUE
);

CREATE TABLE IF NOT EXISTS "cast2movie" (
	"cast_id" INTEGER NOT NULL,
	"imdb_id" INTEGER NOT NULL,
	"sort" INTEGER NOT NULL DEFAULT 0
);
CREATE        INDEX IF NOT EXISTS "actor_id"      ON "cast2movie" ("cast_id" ASC);
CREATE        INDEX IF NOT EXISTS "actor_imdb_id" ON "cast2movie" ("imdb_id" ASC);
CREATE UNIQUE INDEX IF NOT EXISTS "actor_both"    ON "cast2movie" ("cast_id" ASC, "imdb_id" ASC);

CREATE TABLE IF NOT EXISTS "director2movie" (
	"cast_id" INTEGER NOT NULL,
	"imdb_id" INTEGER NOT NULL,
	"sort" INTEGER NOT NULL DEFAULT 0
);
CREATE        INDEX IF NOT EXISTS "director_id"      ON "director2movie" ("cast_id" ASC);
CREATE        INDEX IF NOT EXISTS "director_imdb_id" ON "director2movie" ("imdb_id" ASC);
CREATE UNIQUE INDEX IF NOT EXISTS "director_both"    ON "director2movie" ("cast_id" ASC, "imdb_id" ASC);

CREATE TABLE IF NOT EXISTS "genre" (
	"genre_id" INTEGER PRIMARY KEY  AUTOINCREMENT  NOT NULL  UNIQUE,
	"genre" VARCHAR NOT NULL UNIQUE
);

CREATE TABLE IF NOT EXISTS "genre2movie" (
	"genre_id" INTEGER NOT NULL,
	"imdb_id" INTEGER NOT NULL
);
CREATE INDEX IF NOT EXISTS "genre_imdb_id" ON "genre2movie" ("imdb_id" ASC);


-- Update 2015-02-08: Top250 position of a movie
ALTER TABLE "movie" ADD COLUMN "imdb_top250" INTEGER;

-- Update 2015-04-19: English title of a movie
ALTER TABLE "movie" ADD COLUMN "imdb_title_eng" VARCHAR NOT NULL DEFAULT "";

-- Update 2020-02-16: Type (Movie, TV-Series, etc.)
ALTER TABLE "movie" ADD COLUMN "imdb_type" VARCHAR NOT NULL DEFAULT "Movie";

-- Update 2023-06-10: Metacritic and RottenTomatoes ratings
ALTER TABLE "movie" ADD COLUMN "metacritic" INTEGER;
ALTER TABLE "movie" ADD COLUMN "rottentomatoes" INTEGER;