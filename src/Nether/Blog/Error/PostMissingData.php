<?php

namespace Nether\Blog\Error;

use Exception;

class PostMissingData
extends Exception {

	public function
	__Construct(string $What) {
		parent::__Construct("Post missing data: {$What}");
		return;
	}

}
