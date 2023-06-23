<?php

namespace Nether\Blog;

use Nether\Atlantis;
use Nether\Common;
use Nether\Database;

use Exception;

class PostTagLink
extends Atlantis\Tag\EntityLink {

	#[Atlantis\Meta\TagEntityProperty('blogpost')]
	public Post
	$Post;

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	static public function
	JoinExtendTables(Database\Verse $SQL, string $JAlias='Main', ?string $TPre=NULL):
	void {

		parent::JoinExtendTables($SQL, $JAlias, $TPre);

		$Table = static::GetTableInfo();
		$TPre = $Table->GetPrefixedAlias($TPre);
		$JAlias = $Table->GetPrefixedAlias($JAlias);

		Post::JoinMainTables($SQL, $JAlias, 'EntityUUID', $TPre);
		Post::JoinExtendTables($SQL, $TPre, $TPre);

		return;
	}

	static public function
	JoinExtendFields(Database\Verse $SQL, ?string $TPre=NULL):
	void {

		parent::JoinExtendFields($SQL, $TPre);

		$Table = static::GetTableInfo();
		$TPre = $Table->GetPrefixedAlias($TPre);

		Post::JoinMainFields($SQL, $TPre);
		Post::JoinExtendFields($SQL, $TPre);

		return;
	}

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	static protected function
	FindExtendSorts(Database\Verse $SQL, Common\Datastore $Input):
	void {

		parent::FindExtendSorts($SQL, $Input);

		switch($Input['Sort']) {
			case 'post-newest':
				$SQL
				->Sort('BP.TimeCreated', $SQL::SortDesc)
				->Group('Main.EntityUUID');
			break;
			case 'post-oldest':
				$SQL
				->Sort('BP.TimeCreated', $SQL::SortAsc)
				->Group('Main.EntityUUID');
			break;
		}

		return;
	}

}
