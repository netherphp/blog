<?php

namespace Nether\Blog\Plugins;

use Nether\Atlantis;
use Nether\Blog;

class BlogPostWriterMenu
implements BlogPostWriterMenuInterface {

	public function
	GetActionItems(Atlantis\Engine $App, Blog\Post $Post):
	array {

		return [];
	}

};
