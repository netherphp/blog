<?php ##########################################################################
################################################################################

namespace Nether\Blog\Routes;

use Nether\Atlantis;
use Nether\Avenue;
use Nether\Blog;
use Nether\Common;

################################################################################
################################################################################

#[Common\Meta\Date('2024-06-23')]
class PostEditorWeb
extends Atlantis\ProtectedWeb {

	static public string
	$BlogDashHome    = 'Blogs',
	$BlogDashHomeURL = '/dashboard/blog';

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	#[Atlantis\Meta\RouteHandler('/dashboard/blog')]
	#[Atlantis\Meta\RouteAccessTypeUser]
	#[Avenue\Meta\ConfirmWillAnswerRequest]
	#[Avenue\Meta\ExtraDataArgs]
	public function
	BlogListGet(Common\Datastore $Blogs):
	void {

		$Trail = new Common\Datastore([
			Atlantis\Struct\Item::New(Title: static::$BlogDashHome, URL: static::$BlogDashHomeURL)
		]);

		($this->Surface)
		->Set('Page.Title', 'Blogs')
		->Area('blog/dashboard/blog-list', [
			'Trail' => $Trail,
			'Blogs' => $Blogs
		]);

		return;
	}

	protected function
	BlogListGetWillAnswerRequest(Avenue\Struct\ExtraData $Data):
	int {

		$BlogUsers = Blog\BlogUser::Find([
			'UserID' => $this->User->ID,
			'Admin'  => 1
		]);

		$Blogs = Blog\Blog::Find([
			'BlogID'=> $BlogUsers->Export()
		]);

		$Data['Blogs'] = $Blogs;

		return Avenue\Response::CodeOK;
	}

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	#[Atlantis\Meta\RouteHandler('/dashboard/blog/editor/:BlogUUID:')]
	#[Atlantis\Meta\RouteAccessTypeUser]
	#[Avenue\Meta\ConfirmWillAnswerRequest]
	#[Avenue\Meta\ExtraDataArgs]
	public function
	EditorGet(string $BlogUUID, Blog\Blog $Blog, Blog\BlogUser $BUsr, ?Blog\Post $Post):
	void {

		$Title = match(TRUE) {
			($Post instanceof Blog\Post)
			=> sprintf('Edit Post #%d', $Post->ID),

			default
			=> 'New Post'
		};

		$Trail = new Common\Datastore([
			Atlantis\Struct\Item::New(Title: static::$BlogDashHome, URL: static::$BlogDashHomeURL),
			Atlantis\Struct\Item::New(Title: $Title)
		]);

		($this->Surface)
		->Set('Page.Title', sprintf('%s - %s', $Title, $Blog->Title))
		->Area('blog/dashboard/post-editor', [
			'BlogUUID' => $BlogUUID,
			'Trail'    => $Trail,
			'Blog'     => $Blog,
			'BUsr'     => $BUsr,
			'Post'     => $Post
		]);

		return;
	}

	protected function
	EditorGetWillAnswerRequest(string $BlogUUID, Avenue\Struct\ExtraData $Data):
	int {

		$UUID = Common\Filters\Text::UUID($BlogUUID);
		$Blog = NULL;
		$Post = NULL;

		////////

		if(!$UUID)
		return Avenue\Response::CodeNotFound;

		////////

		// check to see if this uuid belongs to a post. if it does we
		// want to edit that post and backfill from its info.

		$Blog = Blog\Blog::GetByUUID($UUID);

		if(!$Blog) {
			$Post = Blog\Post::GetByUUID($UUID);

			if(!$Post)
			return Avenue\Response::CodeNotFound;

			$Blog = $Post->Blog;
		}

		////////

		$BUsr = $Blog->GetUser($this->User->ID);

		if(!$BUsr || !$BUsr->CanWrite())
		return Avenue\Response::CodeForbidden;

		////////

		$Data['Blog'] = $Blog;
		$Data['BUsr'] = $BUsr;
		$Data['Post'] = $Post;

		return Avenue\Response::CodeOK;
	}

}
