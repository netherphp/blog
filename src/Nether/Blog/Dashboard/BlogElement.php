<?php

namespace Nether\Blog\Dashboard;

use Nether\Atlantis;
use Nether\Blog;
use Nether\Common;
use Nether\Database;

class BlogElement
extends Atlantis\Dashboard\Element {

	public Database\ResultSet
	$Blogs;

	public Common\Datastore
	$Hits;

	public Common\Datastore
	$Visitors;

	public bool
	$UserCanAdmin;

	public function
	__Construct(Atlantis\Engine $App) {

		parent::__Construct(
			$App,
			'Blogs',
			'blog/dashboard/element/main'
		);

		return;
	}

	protected function
	OnReady():
	void {

		$this->Blogs = Blog\BlogUser::Find([
			'UserID' => $this->App->User->ID,
			'Limit'  => 0
		]);

		$this->UserCanAdmin = (TRUE
			&& $this->App->User
			&& $this->App->User->HasAccessType(Blog\Library::AccessBlogCreate)
		);

		$this->Blogs->Remap(function(Blog\BlogUser $BU) {
			$Since = new Common\Date('-24 hour');

			return new Blog\Struct\DashboardBlogInfo(
				$BU,
				Atlantis\Struct\TrafficRow::FindCount([ 'PathStart'=> "/{$BU->Blog->Alias}", 'Since'=> $Since->GetUnixtime() ]),
				Atlantis\Struct\TrafficRow::FindCount([ 'PathStart'=> "/{$BU->Blog->Alias}", 'Since'=> $Since->GetUnixtime(), 'Group'=> 'visitor' ])
			);
		});

		$this->Columns = 'half';

		return;
	}

}
