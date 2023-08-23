<?php

namespace Nether\Blog;

use Nether\Atlantis;
use Nether\Common;
use Nether\Database;

use Exception;

class BlogTagLink
extends Atlantis\Tag\EntityLink {

	#[Atlantis\Meta\TagEntityProperty('blog')]
	#[Database\Meta\TableJoin('EntityUUID')]
	public Blog
	$Blog;

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	static public function
	_JoinExtendTables(Database\Verse $SQL, string $JAlias='Main', ?string $TPre=NULL):
	void {

		parent::JoinExtendTables($SQL, $JAlias, $TPre);

		$Table = static::GetTableInfo();
		$TPre = $Table->GetPrefixedAlias($TPre);
		$JAlias = $Table->GetPrefixedAlias($JAlias);

		Blog::JoinMainTables($SQL, $JAlias, 'EntityUUID', $TPre);
		Blog::JoinExtendTables($SQL, $TPre, $TPre);

		return;
	}

	static public function
	_JoinExtendFields(Database\Verse $SQL, ?string $TPre=NULL):
	void {

		parent::JoinExtendFields($SQL, $TPre);

		$Table = static::GetTableInfo();
		$TPre = $Table->GetPrefixedAlias($TPre);

		Blog::JoinMainFields($SQL, $TPre);
		Blog::JoinExtendFields($SQL, $TPre);

		return;
	}

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	static protected function
	FindExtendSorts(Database\Verse $SQL, Common\Datastore $Input):
	void {

		parent::FindExtendSorts($SQL, $Input);

		switch($Input['Sort']) {

		}

		return;
	}

}
