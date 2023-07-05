<?php

namespace Nether\Blog\Dashboard;

use Nether\Atlantis;
use Nether\Blog;
use Nether\Database;

class BlogElement
extends Atlantis\Dashboard\Element {

	public Database\Struct\PrototypeFindResult
	$Blogs;

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
			'UserID' => 1,
			'Limit'  => 0
		]);

		$this->UserCanAdmin = (TRUE
			&& $this->App->User
			&& $this->App->User->HasAccessType(Blog\Library::AccessBlogCreate)
		);

		//$this->Columns = min(3, $this->Blogs->Count());

		//if($this->UserCanAdmin)
		//$this->Columns = min(3, ($this->Columns + 1));

		$this->Columns = 'full';

		return;
	}

}
