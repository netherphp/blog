<?php

namespace Nether\Blog\Dashboard;

use Nether\Atlantis;

class BlogAdminGroup
extends Atlantis\Dashboard\SidebarGroup {

	public int
	$Priority = -10;

	public function
	__Construct() {
		parent::__Construct('Blog Admin');

		($this->Items)
		->Push(new Atlantis\Dashboard\SidebarGroupItem(
			'Manage Blogs',
			'/ops/blogs/list',
			'mdi-account-group'
		));

		return;
	}

}