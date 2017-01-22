# Installation

The following routine installs NewFilmDb on your system. Go to the folder you want it to be installed to and execute the following statements:

```bash
#
# clone and prepare newfilmdb
#
git clone git@github.com:RomanSixty/newfilmdb.git
cd newfilmdb
chown www-data data
#
# initialize and prepare imdbphp
#
git submodule update --init imdbphp
cd imdbphp
mkdir images
chown www-data images
chown www-data cache
```

If your webserver process runs as a different user than `www-data`, you have to replace that with the user you need.

