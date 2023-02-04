<?php

namespace Nether\Blog\Routes;

use Nether\Atlantis;
use Nether\Blog;
use Nether\Common;

use Exception;

class BlogDashboard
extends Atlantis\ProtectedWeb {

	#[Atlantis\Meta\RouteHandler('/dashboard/blog/new')]
	#[Atlantis\Meta\RouteAccessTypeUser]
	public function
	BlogNew():
	void {

		($this->Surface)
		->Wrap('blog/dashboard/blog-new');

		return;
	}

	#[Atlantis\Meta\RouteHandler('/dashboard/blog/new', Verb: 'POST')]
	#[Atlantis\Meta\RouteAccessTypeUser]
	public function
	BlogNewOnSubmit():
	void {

		($this->Data)
		->Title(Common\Datafilters::TrimmedText(...))
		->Alias(Common\Datafilters::PathableKeySingle(...))
		->Tagline(Common\Datafilters::TrimmedText(...))
		->Details(Common\Datafilters::TrimmedText(...));

		$Blog = Blog\Blog::Insert([
			'UserID'  => $this->User->ID,
			'Title'   => $this->Data->Title,
			'Alias'   => $this->Data->Alias,
			'Tagline' => $this->Data->Tagline,
			'Details' => $this->Data->Details
		]);

		$BUser = Blog\BlogUser::Insert([
			'BlogID' => $Blog->ID,
			'UserID' => $this->User->ID
		]);

		$BUser->SetAsAdmin();

		return;
	}

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	#[Atlantis\Meta\RouteHandler('/dashboard/blog/list')]
	#[Atlantis\Meta\RouteAccessTypeUser]
	public function
	BlogList():
	void {

		$BlogUsers = Blog\BlogUser::Find([
			'UserID' => $this->User->ID
		]);

		$Scope = [
			'BlogUsers' => $BlogUsers
		];

		($this->Surface)
		->Wrap('blog/dashboard/blog-list', $Scope);

		return;
	}

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	#[Atlantis\Meta\RouteHandler('/dashboard/blog/write')]
	#[Atlantis\Meta\RouteAccessTypeUser]
	public function
	BlogWrite():
	void {

		$BlogUsers = Blog\BlogUser::Find([
			'UserID' => $this->User->ID,
			'Writer' => 1
		]);

		$Scope = [
			'BlogUsers' => $BlogUsers
		];

		($this->Surface)
		->Wrap('blog/dashboard/blog-write', $Scope);

		return;
	}

}
