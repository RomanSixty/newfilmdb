function initDashbar()
{
    $('#searchform').submit(function(){
        $.ajax({
            url: 'rpc.php',
            dataType: 'html',
            data: $('#searchform').serialize(),
            success: function(data) {
                $('#list').html(data);
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

    $('#details ul.directors a').click(function(){
        $('#dir').remove();
        $('label[for=dir]').remove();

        html  = '<input id="dir" type="hidden" name="director[]" value="' + $(this).html() + '" />';
        html += '<label class="filter" for="dir">' + $(this).html() + '</label>';

        $('label[id=director]').after(html);
        initFilter ( 'dir' );

        $('#fulltext').val('');
        $('#searchform').submit();
    });

    $('#details ul.actors a').click(function(){
        timest = +new Date();

        filterid = 'act' + timest;

        html  = '<input id="' + filterid + '" type="hidden" name="cast[]" value="' + $(this).html() + '" />';
        html += '<label class="filter" for="' + filterid + '">' + $(this).html() + '</label>';

        $('label[id=cast]').after(html);
        initFilter ( filterid );

        $('#fulltext').val('');
        $('#searchform').submit();
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