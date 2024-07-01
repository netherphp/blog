<?php ##########################################################################
################################################################################

namespace Nether\Blog\Plugins\Dashboard;

use Nether\Atlantis;
use Nether\Blog;
use Nether\Common;
use Nether\Database;

################################################################################
################################################################################

class ContentInfoWidget
extends Atlantis\Plugin
implements Atlantis\Plugin\Interfaces\Dashboard\InfoWidgetInterface {

	#[Common\Meta\Info('Populated by Allow()')]
	protected Database\ResultSet
	$Blogs;

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	public function
	GetSorting():
	int {

		return 50;
	}

	public function
	GetColumnSizes():
	string {

		return 'col-12';
	}

	public function
	Allow():
	bool {

		$this->Blogs = Blog\BlogUser::Find([
			'UserID'    => $this->App->User->ID,
			'Write'     => TRUE,
			'Remappers' => [ Blog\BlogUser::MapToBlog(...) ]
		]);

		$HasBlogs = $this->Blogs->Count() > 0;

		$CouldHaveBlogs = $this->App->User->HasAccessType(
			Blog\Library::AccessBlogCreate
		);

		////////

		return (FALSE
			|| $HasBlogs
			|| $CouldHaveBlogs
		);
	}

	public function
	Render():
	string {

		$PostCount = Blog\Post::FindCount([
			'BlogID'=> (
				($this->Blogs)
				->Map(Atlantis\Prototype::MapToID(...))
				->Export()
			)
		]);

		$Output = (
			($this->App->Surface)
			->GetArea('blog/dashboard/infowidget/content', [
				'Blogs'     => $this->Blogs,
				'PostCount' => $PostCount
			])
		);

		return $Output;
	}

};
