# NewFilmDb

## About

This project is a custom built database for my movie collection. I started out using *MongoDB* as database backend but soon realized that using a technology I don't use in everyday work didn't help my motivation in implementing features. Therefore I switched to *SQLite*.

## Features

![Screenshot NewFilmDb](/screenshot.jpg?raw=true)

* movie data is pulled from [IMDb](http://www.imdb.com "Internet Movie Database"), containing:
  * movie title (original title, international title, German title)
  * genres
  * IMDb rating
  * cast and directors
  * plot outline
  * movie poster
  * duration
  * year of publication
  * top250 ranking (if applicable)
  * movie type (Movie, TV Series etc.)
* additional movie ratings from [OMDb-API](https://www.omdbapi.com/)
  * Metacritic
  * Rotten Tomatoes
* own information can be added
  * rating
  * notes
  * own movie poster (by adding an image with `own_` as prefix to the image folder)
* [Bechdel rating](https://en.wikipedia.org/wiki/Bechdel_test) pulled from [bechdeltest.com](http://bechdeltest.com) (if available)
* gallery view to browse the collection
* detailed view in sidebar
* fulltext search
* filters (by types, genres, actors, directors, language)
* internal links from detail view to lists filtered by genre, cast or directors
* quick filter for the 25 most referenced cast members and directors

## Requirements (contained in this repository)

  * [jQuery](http://jquery.com/) for some AJAX stuff

### Other requirements (not contained):

  * [imdbGraphQLPHP](https://github.com/duck7000/imdbGraphQLPHP) as IMDb data scraper,
    you can get it via `composer install`
  * [PECL::intl](https://pecl.php.net/package/intl) for correct utf82ascii transliterations
  * API key for OMDb-API (you can get one from their [website](https://www.omdbapi.com/))


# Legal

This software is distributed under the GPL.
