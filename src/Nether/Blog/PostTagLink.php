<?php

namespace Nether\Blog;

use Nether\Atlantis;
use Nether\Common;
use Nether\Database;

class PostTagLink
extends Atlantis\Media\TagLink {

	const
	LinkType = 'blogpost';

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	public Post
	$Post;

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	protected function
	OnReady(Common\Prototype\ConstructArgs $Args):
	void {
		parent::OnReady($Args);

		if($Args->InputHas('ENT_ID'))
		$this->Post = Post::FromPrefixedDataset($Args->Input, 'ENT_');

		return;
	}

	static protected function
	FindExtendTables(Database\Verse $SQL, Common\Datastore $Input):
	void {
		parent::FindExtendTables($SQL, $Input);

		Post::JoinMainTables($SQL, 'Main', 'EntityUUID', TAlias: 'ENT');
		Post::JoinMainFields($SQL, TAlias: 'ENT');

		return;
	}

	static protected function
	FindExtendSorts(Database\Verse $SQL, Common\Datastore $Input):
	void {
		parent::FindExtendSorts($SQL, $Input);

		return;
	}

}
