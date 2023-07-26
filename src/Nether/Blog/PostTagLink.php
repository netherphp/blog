<?php

namespace Nether\Blog;

use Nether\Atlantis;
use Nether\Common;
use Nether\Database;

class PostTagLink
extends Atlantis\Tag\EntityLink {

	#[Atlantis\Meta\TagEntityProperty('blogpost')]
	#[Database\Meta\TableJoin('EntityUUID', Extend: TRUE)]
	public Post
	$Post;

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	static protected function
	FindExtendOptions(Common\Datastore $Input):
	void {

		$Input['LinkClass'] = static::class;
		parent::FindExtendOptions($Input);

		$Input
		->Define('BlogID', NULL)
		->Define('GroupByTag', NULL);

		return;
	}

	static protected function
	FindExtendFilters(Database\Verse $SQL, Common\Datastore $Input):
	void {

		$Input['LinkClass'] = static::class;
		parent::FindExtendFilters($SQL, $Input);

		$PostTable = Post::GetTableInfo();
		$PostField = $PostTable->GetAliasedField('BlogID');

		if($Input['BlogID'] !== NULL)
		$SQL->Where("{$PostField}=:BlogID");

		if($Input['GroupByTag'] !== NULL)
		$SQL->Group('Main.TagID');

		//Common\Dump::Var($SQL, TRUE);

		return;
	}

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
