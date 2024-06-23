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
	$BlogDashHome    = 'Blog',
	$BlogDashHomeURL = '/dashboard/blog',
	$BlogDashEditor  = 'Block Editor';

	#[Atlantis\Meta\RouteHandler('/dashboard/blog/editor/:BlogUUID:')]
	#[Atlantis\Meta\RouteAccessTypeUser]
	#[Avenue\Meta\ConfirmWillAnswerRequest]
	#[Avenue\Meta\ExtraDataArgs]
	public function
	EditorGet(string $BlogUUID, Blog\Blog $Blog, Blog\BlogUser $BUsr, ?Blog\Post $Post):
	void {

		$Trail = new Common\Datastore([
			Atlantis\Struct\Item::New(Title: static::$BlogDashHome, URL: static::$BlogDashHomeURL),
			Atlantis\Struct\Item::New(Title: $Blog->Title),
			Atlantis\Struct\Item::New(Title: static::$BlogDashEditor)
		]);

		($this->Surface)
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
