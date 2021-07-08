$(function(){

	/* Dashbar */

	$('#searchform')
		.on('submit', function(){
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
		})
        .on('click', '.list_type a', function(e){
            typeFilter( e, $(this) );

            return false;
        })
		.on('click', '.list_genre a', function(e){
			genreFilter( e, $(this) );

			return false;
		})
		.on('click', '.list_director a', function(e){
			directorFilter ( e, $(this) );

			return false;
		})
		.on('click', '.list_actor a', function(e){
			actorFilter ( e, $(this) );

			return false;
		});

	$('.checkbutton .check').on('click', function(){
		$('#searchform').submit();
	});

	/* Details */

	$('#details')
		.on('click', '.genre a', function(e){
			genreFilter( e, $(this) );

			return false;
		})
		.on('click', 'ul.directors a', function(e){
			directorFilter ( e, $(this) );

			return false;
		})
		.on('click', 'ul.actors a', function(e){
			actorFilter ( e, $(this) );

			return false;
		})
		.on('click', '.editlink', function(){
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
		})
		.on('click', '.addlink', function(){
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
		})
		.on('click', '.updatelink', function(){
			$('#details').append('<div id="overlay"></div>');

			var imdb_id = $(this).attr('data-imdbid');

			$.ajax({
				url: 'rpc.php',
				dataType: 'html',
				data: 'act=update_imdb&imdb_id=' + imdb_id,
				success: function(data) {
					$('#details').html(data);
				}
			});

			return false;
		});

	$('#list').on('click', '.movie', function(){
		var imdb_id = $(this).attr('data-imdbid');

		$.ajax({
			url: 'rpc.php',
			dataType: 'html',
			data: 'act=details&imdb_id=' + imdb_id,
			success: function(data) {
				$('#details').html(data);
			}
		});
	});
});

function initContent()
{
	$('.filter input').each(function(){
		initFilter ( $(this).attr('id') );
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
			}
		});
	});
}

function initFilter ( id )
{
	$('#dashboard label.filter[for=' + id + ']').unbind('click').click(function(){
		$('input[id=' + id + ']').remove();
		$(this).remove();

		$('#searchform').submit();
	});
}

function typeFilter ( e, $el )
{
    var type_value= $el.data('value');
    var type_name = $el.html();

    if ( e.shiftKey )
    {
        type_value = - type_value;
        type_name  = '-' + type_name;
    }

    timest = +new Date();

    filterid = 'type' + timest;

    html  = '<input id="' + filterid + '" type="hidden" name="type[]" value="' + type_value + '" />';
    html += '<label class="filter" for="' + filterid + '">' + type_name + '</label>';

    $('label[id=type]').after(html);
    initFilter ( filterid );

    $('#fulltext').val('');
    $('#searchform').submit();
}

function genreFilter ( e, $el )
{
	var genre_id   = $el.data('id');
	var genre_name = $el.html();

	if ( e.shiftKey )
	{
		genre_id   = - genre_id;
		genre_name = '-' + genre_name;
	}

	timest = +new Date();

	filterid = 'genre' + timest;

	html  = '<input id="' + filterid + '" type="hidden" name="genre[]" value="' + genre_id + '" />';
	html += '<label class="filter" for="' + filterid + '">' + genre_name + '</label>';

	$('label[id=genre]').after(html);
	initFilter ( filterid );

	$('#fulltext').val('');
	$('#searchform').submit();
}

function directorFilter ( e, $el )
{
	if ( !e.shiftKey )
		$('#dashboard .dir_filter').remove();

	timest = +new Date();

	filterid = 'dir' + timest;

	html  = '<input class="dir_filter" id="' + filterid + '" type="hidden" name="director[]" value="' + $el.data('id') + '" />';
	html += '<label class="filter" for="' + filterid + '">' + $el.html() + '</label>';

	$('label[id=director]').after(html);
	initFilter ( filterid );

	$('#fulltext').val('');
	$('#searchform').submit();
}

function actorFilter ( e, $el )
{
	if ( !e.shiftKey )
		$('#dashboard .act_filter').remove();

	timest = +new Date();

	filterid = 'act' + timest;

	html  = '<input class="act_filter" id="' + filterid + '" type="hidden" name="cast[]" value="' + $el.data('id') + '" />';
	html += '<label class="filter act_filter" for="' + filterid + '">' + $el.html() + '</label>';

	$('label[id=cast]').after(html);
	initFilter ( filterid );

	$('#fulltext').val('');
	$('#searchform').submit();
}

initContent();