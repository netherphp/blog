<?php

namespace Nether\Blog\Plugins;

use Nether\Atlantis;
use Nether\Blog;
use Nether\Common;

interface BlogPostEditorValuesInterface {

	public function
	GetValues(Atlantis\Engine $App, Common\Datastore $PluginData, ?Blog\Post $Post=NULL):
	Common\Datastore;

};
