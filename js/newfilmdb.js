window.onload = function(){

    /* Dashbar */

    document.getElementById('searchform').addEventListener('submit', function(e){
        e.preventDefault();

        let formData = new FormData(this);

        fetch('rpc.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                document.getElementById('list').innerHTML = data.html;
                document.getElementById('num_results').innerHTML = data.count;
                initContent();
            });
    });

    document.querySelectorAll('.list_type a').forEach(function(item) {
        item.addEventListener('click', function(e) {
            e.preventDefault();
            makeFilter(e, this, 'type');
        });
    });

    document.querySelectorAll('.list_genre a').forEach(function(item) {
        item.addEventListener('click', function(e) {
            e.preventDefault();
            makeFilter(e, this, 'genre');
        });
    });

    document.querySelectorAll('.list_director a').forEach(function(item) {
        item.addEventListener('click', function(e) {
            e.preventDefault();
            makeFilter(e, this, 'director');
        });
    });

    document.querySelectorAll('.list_actor a').forEach(function(item) {
        item.addEventListener('click', function(e) {
            e.preventDefault();
            makeFilter(e, this, 'cast');
        });
    });

    // Sprachfilter-Checkboxen
    document.querySelectorAll('.checkbutton .check').forEach(function(button) {
        button.addEventListener('click', function() {
            document.getElementById('searchform').requestSubmit();
        });
    });


    /* Details */

    document.getElementById('details').addEventListener('click', function(e) {
        if (e.target.matches('.genre a')) {
            e.preventDefault();
            makeFilter(e, e.target, 'genre');
        } else if (e.target.matches('.type a')) {
            e.preventDefault();
            makeFilter(e, e.target, 'type');
        } else if (e.target.matches('ul.directors a')) {
            e.preventDefault();
            makeFilter(e, e.target, 'director');
        } else if (e.target.matches('ul.actors a')) {
            e.preventDefault();
            makeFilter(e, e.target, 'cast');
        } else if (e.target.matches('.editlink')) {
            e.preventDefault();
            let imdb_id = e.target.getAttribute('data-imdbid');
            fetch('rpc.php?act=edit&imdb_id=' + imdb_id)
                .then(response => response.text())
                .then(data => {
                    document.querySelector('#details .associated').innerHTML = data;
                    initForm();
                });
        } else if (e.target.matches('.addlink')) {
            e.preventDefault();
            let imdb_id = e.target.getAttribute('data-imdbid');
            fetch('rpc.php?act=add&imdb_id=' + imdb_id)
                .then(response => response.text())
                .then(data => {
                    document.getElementById('details').innerHTML = data;
                    initForm();
                });
        } else if (e.target.matches('.updatelink')) {
            e.preventDefault();
            document.getElementById('details').insertAdjacentHTML('beforeend', '<div id="overlay"></div>');
            let imdb_id = e.target.getAttribute('data-imdbid');
            fetch('rpc.php?act=update_imdb&imdb_id=' + imdb_id)
                .then(response => response.text())
                .then(data => {
                    document.getElementById('details').innerHTML = data;
                });
        }
    });

    document.getElementById('list').addEventListener('click', function(e) {
        if (e.target.matches('.movie')) {
            let imdb_id = e.target.getAttribute('data-imdbid');
            fetch('rpc.php?act=details&imdb_id=' + imdb_id)
                .then(response => response.text())
                .then(data => {
                    document.getElementById('details').innerHTML = data;
                });
        }
    });
};

/**
 * Filter initialisieren beim Neuladen
 */
function initContent() {
    document.querySelectorAll('.filter input[type="hidden"]').forEach(el => initFilter(el.id));
}

/**
 * Formular zum Bearbeiten oder Hinzufügen
 */
function initForm() {
    document.getElementById('edit_movie').addEventListener('submit', function(e){
        e.preventDefault();

        document.getElementById('details').insertAdjacentHTML('beforeend', '<div id="overlay"></div>');

        let formData = new FormData(this);

        fetch('rpc.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(data => {
                document.getElementById('details').innerHTML = data;
                initForm();
            });
    });

    document.querySelector('#edit_movie .abort').addEventListener('click', function(e){
        e.preventDefault();

        document.getElementById('details').insertAdjacentHTML('beforeend', '<div id="overlay"></div>');

        let imdb_id = this.getAttribute('data-imdbid');

        fetch('rpc.php?act=details&imdb_id=' + imdb_id, {
                method: 'POST'
            })
            .then(response => response.text())
            .then(data => {
                document.getElementById('details').innerHTML = data;
            });
    });
}

/**
 * Klick auf Filter soll diesen wieder entfernen
 * @param {String} id Filter-ID
 */
function initFilter(id){
    document.querySelector('#dashboard label.filter[for=' + id + ']').addEventListener('click', function(e){
        e.preventDefault();

        document.getElementById(id).remove();
        e.target.remove();

        document.getElementById('searchform').requestSubmit();
    });
}

/**
 * Filter erzeugen
 * @param {Event} e Click-Event
 * @param {Element} el angeklickte Kategorie
 * @param {String} which welche Kategorie ('genre'/'type'/'director'/'cast')
 */
function makeFilter(e, el, which){
    let value= el.getAttribute('data-id');
    let name = el.innerText;

    let filterid = which + '_' + value.toString();

    // wenn der Filter schon existiert, dann entfernen
    if ( document.getElementById(filterid) ) {
        document.getElementById(filterid).remove();
        document.querySelector('[for="'+filterid+'"]').remove();
    }

    // Kategorien können negativ gefiltert werden
    // (z.B. Filme *außer* Thriller)
    if (which === 'genre' || which === 'type') {
        if ( e.shiftKey ) {
            value = - value;
            name  = '-' + name;
        }
    }

    let html = '<input id="'+filterid+'" type="hidden" name="'+which+'[]" value="'+value+'" /><label class="filter" for="'+filterid+'">'+name+'</label>';

    document.getElementById(which).insertAdjacentHTML('afterend', html);
    initFilter(filterid);

    document.getElementById('fulltext').value = '';
    document.getElementById('searchform').requestSubmit();
}

initContent();