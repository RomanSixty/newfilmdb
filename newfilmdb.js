function initDashbar()
{
    $('#searchform').submit(function(){
        $.ajax({
            url: 'rpc.php',
            dataType: 'json',
            data: $('#searchform').serialize(),
            success: function(data) {
                $('#list').html(data.html);
                $('#num_results').html(data.count);
                initContent();
            }
        });
        return false;
    });

    $('.check').click(function(){
        $('#searchform').submit();
    });
}

function initContent()
{
    $('img.delay').lazyload({
        effect: 'fadeIn'
    });

    $('.movie').click(function(){
        var imdb_id = $(this).attr('data-imdbid');

        $.ajax({
            url: 'rpc.php',
            dataType: 'html',
            data: 'act=details&imdb_id=' + imdb_id,
            success: function(data) {
                $('#details').html(data);
                initDetails();
            }
        });
    });
}

function initForm()
{
    $('#edit_movie').submit(function(){
        $('#details').append('<div id="overlay"></div>');

        $.ajax({
            url: 'rpc.php',
            dataType: 'html',
            data: $('#edit_movie').serialize(),
            success: function(data) {
                $('#details').html(data);

                // da wir nicht wissen, ob hier eine Detailansicht
                // oder ein Formular zurückkommt, machen wir beides
                initDetails();
                initForm();
            }
        });
        return false;
    });

    $('#edit_movie .abort').click(function(){
        $('#details').append('<div id="overlay"></div>');

        var imdb_id = $(this).attr('data-imdbid');

        $.ajax({
            url: 'rpc.php',
            dataType: 'html',
            data: 'act=details&imdb_id=' + imdb_id,
            success: function(data) {
                $('#details').html(data);
                initDetails();
            }
        });
    });
}

function initDetails()
{
    $('#details .genre a').click(function(){
        timest = +new Date();

        filterid = 'genre' + timest;

        html  = '<input id="' + filterid + '" type="hidden" name="genre[]" value="' + $(this).html() + '" />';
        html += '<label class="filter" for="' + filterid + '">' + $(this).html() + '</label>';

        $('label[id=genre]').after(html);
        initFilter ( filterid );

        $('input#dir').remove();
        $('label[for=dir]').remove();
        $('#fulltext').val('');
        $('#searchform').submit();
    });

    $('#details ul.directors a').click(function(e){
        if ( !e.shiftKey )
        {
            $('#dashboard .act_filter').remove();
            $('#dir').remove();
            $('label[for=dir]').remove();
        }

        html  = '<input id="dir" type="hidden" name="director[]" value="' + $(this).html() + '" />';
        html += '<label class="filter" for="dir">' + $(this).html() + '</label>';

        $('label[id=director]').after(html);
        initFilter ( 'dir' );

        $('#fulltext').val('');
        $('#searchform').submit();

        return false;
    });

    $('#details ul.actors a').click(function(e){
        if ( !e.shiftKey )
        {
            $('#dashboard .act_filter').remove();
            $('#dir').remove();
            $('label[for=dir]').remove();
        }

        timest = +new Date();

        filterid = 'act' + timest;

        html  = '<input class="act_filter" id="' + filterid + '" type="hidden" name="cast[]" value="' + $(this).html() + '" />';
        html += '<label class="filter act_filter" for="' + filterid + '">' + $(this).html() + '</label>';

        $('label[id=cast]').after(html);
        initFilter ( filterid );

        $('#fulltext').val('');
        $('#searchform').submit();

        return false;
    });

    $('#details .editlink').click(function(){
        var imdb_id = $(this).attr('data-imdbid');

        $.ajax({
            url: 'rpc.php',
            dataType: 'html',
            data: 'act=edit&imdb_id=' + imdb_id,
            success: function(data) {
                $('#details .associated').html(data);
                initForm();
            }
        });

        return false;
    });

    $('#details .addlink').click(function(){
        var imdb_id = $(this).attr('data-imdbid');

        $.ajax({
            url: 'rpc.php',
            dataType: 'html',
            data: 'act=add&imdb_id=' + imdb_id,
            success: function(data) {
                $('#details').html(data);
                initForm();
            }
        });

        return false;
    });

    $('#details .updatelink').click(function(){
		$('#details').append('<div id="overlay"></div>');
		
        var imdb_id = $(this).attr('data-imdbid');

        $.ajax({
            url: 'rpc.php',
            dataType: 'html',
            data: 'act=update_imdb&imdb_id=' + imdb_id,
            success: function(data) {
                $('#details').html(data);
                initDetails();
            }
        });

        return false;
    });
}

function initFilter ( id )
{
    $('#dashboard label[for=' + id + ']').click(function(){
        $('input[id=' + id + ']').remove();
        $(this).remove();

        $('#searchform').submit();
    });
}

initDashbar();
initContent();
initDetails();