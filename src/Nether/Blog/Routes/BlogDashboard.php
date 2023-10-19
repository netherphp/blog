<?php

namespace Nether\Blog\Routes;

use Nether\Atlantis;
use Nether\Avenue;
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
	#[Avenue\Meta\ConfirmWillAnswerRequest]
	public function
	BlogWrite():
	void {

		($this->Data)
		->ID(Common\Filters\Numbers::IntNullable(...))
		->Editor([
			Common\Filters\Text::SlottableKey(...),
			Common\Filters\Text::TrimmedNullable(...)
		])
		->Plugins([
			Common\Filters\Text::Base64Decode(...),
			Common\Filters\Text::DatastoreFromJSON(...)
		]);

		////////

		$Plugins = $this->Data->Plugins;
		$Values = new Common\Datastore;

		//Common\Dump::Var($Plugins, TRUE);

		if($Plugins['Values'])
		$Values->MergeRight($this->GetValuesFromPlugins(
			$Plugins['Values']
		));

		////////

		$Blogs = Blog\BlogUser::Find([
			'UserID'    => $this->User->ID,
			'Writer'    => 1,
			'Remappers' => [ fn(Blog\BlogUser $BU)=> $BU->Blog ]
		]);

		($this->Surface)
		->Wrap('blog/dashboard/blog-write', [
			'Post'        => NULL,
			'Values'      => $Values,
			'Editor'      => $this->Data->Editor,
			'Blogs'       => $Blogs
		]);

		return;
	}

	protected function
	BlogWriteWillAnswerRequest():
	int {

		// if a specific blog was asked for but the user cannot write
		// to that blog then it needs to bail.

		if($this->Data->ID) {
			$BlogUser = Blog\BlogUser::GetByPair(
				(int)$this->Data->ID,
				$this->User->ID
			);

			if(!$BlogUser)
			return Avenue\Response::CodeForbidden;

			if(!$BlogUser->CanWrite())
			return Avenue\Response::CodeForbidden;
		}

		return Avenue\Response::CodeOK;
	}

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	#[Atlantis\Meta\RouteHandler('/dashboard/blog/edit')]
	#[Atlantis\Meta\RouteAccessTypeUser]
	#[Avenue\Meta\ConfirmWillAnswerRequest]
	public function
	BlogEdit(Blog\Post $Post):
	void {

		($this->Data)
		->ID(Common\Filters\Numbers::IntNullable(...))
		->Plugins([
			Common\Filters\Text::Base64Decode(...),
			Common\Filters\Text::DatastoreFromJSON(...)
		]);

		////////

		$Values = new Common\Datastore;

		////////

		$Blogs = Blog\BlogUser::Find([
			'UserID'    => $this->User->ID,
			'Writer'    => 1,
			'Remappers' => [ fn(Blog\BlogUser $BU)=> $BU->Blog ]
		]);

		($this->Surface)
		->Wrap('blog/dashboard/blog-write', [
			'Post'   => $Post,
			'Values' => $Values,
			'Blogs'  => $Blogs,
			'Editor' => $Post->Editor
		]);

		return;
	}

	protected function
	BlogEditWillAnswerRequest(Avenue\Struct\ExtraData $ExtraData):
	int {

		// bail if the post could not even be found.

		if(!$this->Data->ID)
		return Avenue\Response::CodeNotFound;

		$Post = Blog\Post::GetByID((int)$this->Data->ID);

		if(!$Post)
		return Avenue\Response::CodeNotFound;

		// bail if the user has no blog access or privs.

		$BlogUser = Blog\BlogUser::GetByPair(
			$Post->BlogID,
			$this->User->ID
		);

		if(!$BlogUser)
		return Avenue\Response::CodeForbidden;

		if(!$Post->CanUserEdit($BlogUser))
		return Avenue\Response::CodeForbidden;

		////////

		$ExtraData['Post'] = $Post;
		$ExtraData['BlogUser'] = $BlogUser;

		return Avenue\Response::CodeOK;
	}

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	#[Atlantis\Meta\RouteHandler('/dashboard/blog/settings')]
	#[Atlantis\Meta\RouteAccessTypeUser]
	#[Avenue\Meta\ConfirmWillAnswerRequest]
	public function
	BlogSettings(Blog\Blog $Blog):
	void {

		($this->Surface)
		->Wrap('blog/dashboard/blog-settings', [
			'Blog'  => $Blog
		]);

		return;
	}

	protected function
	BlogSettingsWillAnswerRequest(Avenue\Struct\ExtraData $ExtraData):
	int {

		// bail if the blog could not be found.

		if(!$this->Data->ID)
		return Avenue\Response::CodeNotFound;

		$Blog = Blog\Blog::GetByID($this->Data->ID);

		if(!$Blog)
		return Avenue\Response::CodeNotFound;

		// bail if the user has no blog access or privs.

		$BlogUser = Blog\BlogUser::GetByPair(
			$Blog->ID,
			$this->User->ID
		);

		if(!$BlogUser || !$BlogUser->CanAdmin())
		return Avenue\Response::CodeForbidden;

		////////

		$ExtraData['Blog'] = $Blog;

		return Avenue\Response::CodeOK;
	}

	////////////////////////////////////////////////////////////////
	// PLUGIN HELPERS //////////////////////////////////////////////

	#[Common\Meta\Info('Given Key-Value list of Plugin => Data.')]
	protected function
	GetValuesFromPlugins(iterable $Plugins):
	Common\Datastore {

		$Output = new Common\Datastore;

		////////

		Common\Datastore::FromArray($Plugins)
		->Each(function(mixed $VData, mixed $Class) use($Output) {

			($this->App->Plugins)
			->Get(Blog\Plugins\BlogPostEditorValuesInterface::class)
			->Filter(fn(string $C)=> $C === $Class)
			->Map(fn(string $C)=> new $C)
			->Each(
				fn(Blog\Plugins\BlogPostEditorValuesInterface $Plugin)
				=> $Output->MergeRight($Plugin->GetValues(
					$this->App,
					Common\Datastore::FromArray($VData ?: []),
					NULL
				))
			);

			return;
		});

		////////

		return $Output;
	}

}
