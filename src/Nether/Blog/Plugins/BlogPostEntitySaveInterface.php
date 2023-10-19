<?php

namespace Nether\Blog\Plugins;

use Nether\Atlantis;
use Nether\Blog;
use Nether\Common;

interface BlogPostEntitySaveInterface {

	public function
	OnSave(Atlantis\Engine $App, Common\Datastore $PluginData, Blog\Post $Post):
	void;

};
