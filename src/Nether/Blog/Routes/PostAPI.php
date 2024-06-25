<?php ##########################################################################
################################################################################

namespace Nether\Blog\Routes;

use Nether\Atlantis;
use Nether\Blog;
use Nether\Common;

use Exception;

################################################################################
################################################################################

class PostAPI
extends Atlantis\ProtectedAPI {

	const
	QuitMsg = [
		1 => 'post not found',
		2 => 'user cannot write to that blog',
		3 => 'blog not found'
	];

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	protected function
	RequireBlogByID(int $ID):
	Blog\Blog {

		$Blog = Blog\Blog::GetByID($ID);

		if(!$Blog)
		$this->Quit(3);

		////////

		return $Blog;
	}

	protected function
	RequirePostByID(int $ID):
	Blog\Post {

		$Post = Blog\Post::GetByID($ID);

		if(!$Post)
		$this->Quit(1);

		////////

		return $Post;
	}

	protected function
	RequireUserCanWriteBlog(Blog\Blog $Blog):
	Blog\BlogUser {

		$BUsr = $Blog->GetUser($this->User->ID);

		if(!$BUsr->CanWrite())
		$this->Quit(2);

		return $BUsr;
	}

	protected function
	RequireUserCanWritePost(Blog\Post $Post):
	Blog\BlogUser {

		$BUsr = $Post->Blog->GetUser($this->User->ID);

		if(!$BUsr->CanWrite())
		$this->Quit(2);

		return $BUsr;
	}

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	#[Atlantis\Meta\RouteHandler('/api/blogpost/entity', Verb: 'GET')]
	#[Atlantis\Meta\RouteAccessTypeUser]
	public function
	EntityGet():
	void {

		($this->Data)
		->ID(Common\Filters\Numbers::IntType(...));

		$Post = $this->RequirePostByID($this->Data->ID);

		////////

		$this->SetPayload($Post->DescribeForPublicAPI());

		return;
	}

	#[Atlantis\Meta\RouteHandler('/api/blogpost/entity', Verb: 'POST')]
	#[Atlantis\Meta\RouteAccessTypeUser]
	public function
	EntityPost():
	void {

		($this->Data)
		->BlogID(Common\Filters\Numbers::IntType(...))
		->Title(Common\Filters\Text::TrimmedNullable(...))
		->Editor(Common\Filters\Text::TrimmedNullable(...))
		->Content(Common\Filters\Text::TrimmedNullable(...))
		->Enabled(Common\Filters\Numbers::IntType(...));

		////////

		$Blog = $this->RequireBlogByID($this->Data->BlogID);
		$BUsr = $this->RequireUserCanWriteBlog($Blog);
		$Post = NULL;
		$Err = NULL;

		$Data = [
			'UserID'  => $this->User->ID,
			'BlogID'  => $this->Data->BlogID,
			'Editor'  => $this->Data->Editor,
			'Enabled' => $this->Data->Enabled,
			'Title'   => $this->Data->Title,
			'Content' => $this->Data->Content
		];

		////////

		try {
			$Post = Blog\Post::Insert($Data);
		}

		catch(Exception $Err) {
			$this->Quit(-1, $Err->GetMessage());
		}

		$Post->UpdateHTML();

		////////

		$this->SetGoto($Post->GetPageURL());
		$this->SetPayload($Post->DescribeForPublicAPI());

		return;
	}

	#[Atlantis\Meta\RouteHandler('/api/blogpost/entity', Verb: 'PATCH')]
	#[Atlantis\Meta\RouteAccessTypeUser]
	public function
	EntityPatch():
	void {

		($this->Data)
		->ID(Common\Filters\Numbers::IntType(...));

		////////

		$Post = $this->RequirePostByID($this->Data->ID);
		$BUsr = $this->RequireUserCanWritePost($Post);
		$Data = $Post->Patch($this->Data);

		if(count($Data)) {
			$Post->Update($Data);
			$Post->UpdateHTML();
		}

		////////

		$Post = $this->RequirePostByID($this->Data->ID);
		$this->SetPayload($Post->DescribeForPublicAPI());

		return;
	}

	#[Atlantis\Meta\RouteHandler('/api/blogpost/entity', Verb: 'DELETE')]
	#[Atlantis\Meta\RouteAccessTypeUser]
	public function
	EntityDelete():
	void {

		$this->SetPayload([ 'delete' ]);

		return;
	}

}
