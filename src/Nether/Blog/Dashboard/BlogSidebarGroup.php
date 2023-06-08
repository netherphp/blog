<?php

namespace Nether\Blog\Dashboard;

use Nether\Atlantis;
use Nether\Blog;

class BlogSidebarGroup
extends Atlantis\Dashboard\SidebarGroup {

	public function
	__Construct(Atlantis\Engine $App) {
		parent::__Construct('Blog');

		$this->SetupSidebarItems($App);

		return;
	}

	protected function
	SetupSidebarItems(Atlantis\Engine $App):
	void {

		$UserAccess = $App->User->GetAccessTypes();
		$UserCanCreate = FALSE;
		$UserHasBlogs = TRUE;

		if($UserAccess[Blog\Library::AccessBlogCreate]) {
			if($UserAccess[Blog\Library::AccessBlogCreate]->IsGT(0))
			$UserCanCreate = TRUE;
		}

		// @todo 2023-01-07
		// count how many blogs the user even has access to.

		// @todo 2023-01-07
		// library config setting if user has less than n blogs then
		// allow them to create up to n even without the access type.

		////////

		if($UserHasBlogs) {
			($this->Items)
			->Push(new Atlantis\Dashboard\SidebarGroupItem(
				'Write New Post',
				'/dashboard/blog/write',
				'mdi-file-document-edit'
			))
			->Push(new Atlantis\Dashboard\SidebarGroupItem(
				'Manage Blogs',
				'/dashboard/blog/list',
				'mdi-format-list-text'
			));
		}

		////////

		if($UserCanCreate) {
			($this->Items)
			->Push(new Atlantis\Dashboard\SidebarGroupItem(
				'Start New Blog',
				'/dashboard/blog/new',
				'mdi-plus'
			));
		}

		return;
	}

}
