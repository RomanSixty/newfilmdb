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
		.on('click', '.list_genre a', function(){
			genreFilter( $(this) );

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
		.on('click', '.genre a', function(){
			genreFilter( $(this) );

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
					initDetails();
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
				initDetails();
			}
		});
	});
});

function initContent()
{
	$('img.delay').lazyload({
		effect: 'fadeIn'
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
			}
		});
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

function genreFilter ( $el )
{
	timest = +new Date();

	filterid = 'genre' + timest;

	html  = '<input id="' + filterid + '" type="hidden" name="genre[]" value="' + $el.data('id') + '" />';
	html += '<label class="filter" for="' + filterid + '">' + $el.html() + '</label>';

	$('label[id=genre]').after(html);
	initFilter ( filterid );

	$('input#dir').remove();
	$('label[for=dir]').remove();
	$('#fulltext').val('');
	$('#searchform').submit();
}

function directorFilter ( e, $el )
{
	if ( !e.shiftKey )
	{
		$('#dashboard .act_filter').remove();
		$('#dir').remove();
		$('label[for=dir]').remove();
	}

	timest = +new Date();

	filterid = 'dir' + timest;

	html  = '<input id="' + filterid + '" type="hidden" name="director[]" value="' + $el.data('id') + '" />';
	html += '<label class="filter" for="' + filterid + '">' + $el.html() + '</label>';

	$('label[id=director]').after(html);
	initFilter ( filterid );

	$('#fulltext').val('');
	$('#searchform').submit();
}

function actorFilter ( e, $el )
{
	if ( !e.shiftKey )
	{
		$('#dashboard .act_filter').remove();
		$('#dir').remove();
		$('label[for=dir]').remove();
	}

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