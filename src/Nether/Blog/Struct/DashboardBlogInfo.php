<?php

namespace Nether\Blog\Struct;

use Nether\Blog;

class DashboardBlogInfo {

	public Blog\BlogUser
	$BlogUser;

	public int
	$Hits;

	public int
	$Visitors;

	public function
	__Construct(Blog\BlogUser $BU, int $Hits, int $Visitors) {

		$this->BlogUser = $BU;
		$this->Hits = $Hits;
		$this->Visitors = $Visitors;

		return;
	}

}
