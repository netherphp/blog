<?php ##########################################################################
################################################################################

namespace Nether\Blog\Routes;

use Nether\Atlantis;
use Nether\Avenue;
use Nether\Blog;
use Nether\Common;

################################################################################
################################################################################

#[Common\Meta\Date('2024-06-26')]
class BlogUsersWeb
extends Atlantis\ProtectedWeb {

	static public string
	$BlogDashHome    = 'Blogs',
	$BlogDashHomeURL = '/dashboard/blog';

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	#[Atlantis\Meta\RouteHandler('/dashboard/blog/users/:UUID:')]
	#[Atlantis\Meta\RouteAccessTypeUser]
	#[Avenue\Meta\ConfirmWillAnswerRequest]
	#[Avenue\Meta\ExtraDataArgs]
	public function
	UserListGet(string $UUID, Blog\Blog $Blog):
	void {

		$Trail = new Common\Datastore([
			Atlantis\Struct\Item::New(Title: static::$BlogDashHome, URL: static::$BlogDashHomeURL),
			Atlantis\Struct\Item::New(Title: sprintf('Manage Users: %s', $Blog->Title))
		]);

		$Users = Blog\BlogUser::Find([ 'BlogID' => $Blog->ID ]);

		($this->Surface)
		->Set('Page.Title', 'Blog Users')
		->Area('blog/dashboard/blog-users', [
			'Trail' => $Trail,
			'Blog'  => $Blog,
			'Users' => $Users
		]);

		return;
	}

	protected function
	UserListGetWillAnswerRequest(string $UUID, Avenue\Struct\ExtraData $Data):
	int {

		$Blog = Blog\Blog::GetByUUID($UUID);

		if(!$Blog)
		return Avenue\Response::CodeNotFound;

		////////

		$BUsr = $Blog->GetUser($this->User->ID);

		if(!$BUsr || !$BUsr->CanAdmin())
		return Avenue\Response::CodeForbidden;

		////////

		$Data['Blog'] = $Blog;
		$Data['BUsr'] = $BUsr;

		return Avenue\Response::CodeOK;
	}

};
