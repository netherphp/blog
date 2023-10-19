<?php

namespace Nether\Blog\Plugins;

use Nether\Atlantis;
use Nether\Blog;
use Nether\Common;

interface BlogPostEditorValuesInterface {

	public function
	GetValues(Atlantis\Engine $App, Common\Datastore $VData, ?Blog\Post $Post=NULL):
	Common\Datastore;

};
