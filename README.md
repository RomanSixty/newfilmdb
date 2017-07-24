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
* own information can be added
  * rating
  * notes
  * own movie poster (by adding an image with `own_` as prefix to the image folder)
* [Bechdel rating](https://en.wikipedia.org/wiki/Bechdel_test) pulled from [bechdeltest.com](http://bechdeltest.com) (if available)
* gallery view to browse the collection
* detailed view in sidebar
* fulltext search
* filters (by genres, actors, directors, language)
* internal links from detail view to lists filtered by genre, cast or directors
* quick filter for the 25 most referenced cast members and directors

## Requirements (contained in this repository)

  * [jQuery](http://jquery.com/) for some AJAX stuff
  * [jQuery Lazy Load Plugin](http://www.appelsiini.net/projects/lazyload) for
    loading movie posters only when in viewport

### Other requirements (not contained):

  * [IMDBPHP2](http://projects.izzysoft.de/trac/imdbphp/) as IMDb data scraper,
    configured as submodule in directory `imdbphp/`
    you can clone this repository: `git@github.com:tboothman/imdbphp.git`
  * [PECL::intl](https://pecl.php.net/package/intl) for correct utf82ascii transliterations


# Legal

This software is distributed under the GPL.
