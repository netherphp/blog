<?php

namespace Nether\Blog\Plugins;

use Nether\Atlantis;
use Nether\Blog;

class AccessTypeDefine
implements Atlantis\Plugins\AccessTypeDefineInterface {

	public function
	Get():
	iterable {

		$Output = [
			new Atlantis\User\AccessTypeDef(
				Blog\Library::AccessBlogCreate, 1,
				'Allows user to create new blogs.'
			),
			new Atlantis\User\AccessTypeDef(
				Blog\Library::AccessBlogCreate, 0,
				'Prevent user from creating new blogs.'
			)
		];

		return $Output;
	}

}
