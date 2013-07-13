<?php

function getBechdelInfo ( $imdb_id )
{
	$api_url = 'http://bechdeltest.com/api/v1/getMovieByImdbId?imdbid=' . str_pad ( $imdb_id, 7, '0', STR_PAD_LEFT );

	if ( false !== ( $res = file_get_contents ( $api_url ) ) )
	{
		$info = json_decode ( $res );

		if ( empty ( $info -> status ) )
		{
			return array (
				'id'      => (int) $info -> id,
				'rating'  => (int) $info -> rating,
				'dubious' =>       $info -> dubious ? 1 : 0
			);
		}
	}

	return false;
}

function getBechdelIDs()
{
	$api_url = 'http://bechdeltest.com/api/v1/getAllMovieIds';

	if ( false !== ( $res = file_get_contents ( $api_url ) ) )
	{
		$info = json_decode ( $res );

		$ids = array();

		foreach ( $info as $i )
			$ids [ (int) $i -> imdbid ] = (int) $i -> id;

		return $ids;
	}

	return false;
}