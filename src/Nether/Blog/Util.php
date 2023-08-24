<?php

namespace Nether\Blog;

use Nether\Atlantis;
use Nether\Common;

class Util {

	static public function
	FetchSiteTags():
	Common\Datastore {

		$Output = new Common\Datastore;
		$Tags = Library::Get(Key::ConfBlogSiteTags);
		$Item = NULL;

		// empty setting bail.

		if(!$Tags)
		return $Output;

		// single tag reformat.

		if(is_int($Tags) || is_string($Tags))
		$Tags = [ $Tags ];

		// loop through tags.

		foreach($Tags as $Item) {
			$Tag = NULL;

			if(is_int($Item))
			$Tag = Atlantis\Tag\Entity::GetByID($Item);

			if(is_string($Item))
			$Tag = Atlantis\Tag\Entity::GetByField('Alias', $Item);

			if($Tag instanceof Atlantis\Tag\Entity)
			$Output->Push($Tag);
		}

		return $Output;
	}

};
