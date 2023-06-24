<?php

namespace Nether\Blog\Routes;

use Nether\Atlantis;
use Nether\Avenue;
use Nether\Blog;
use Nether\Common;

use Throwable;

class BlogAPI
extends Atlantis\ProtectedAPI {

	#[Atlantis\Meta\RouteHandler('/api/blog/entity', Verb: 'GET')]
	public function
	BlogEntityGet():
	void {

		($this->Data)
		->ID(Common\Datafilters::TypeInt(...));

		////////

		if(!$this->Data->ID)
		$this->Quit(1, 'no ID specified');

		$Blog = Blog\Blog::GetByID($this->Data->ID);

		if(!$Blog)
		$this->Quit(2, 'blog not found');

		////////

		$this->SetPayload($Blog->DescribeForPublicAPI());

		return;
	}

	#[Atlantis\Meta\RouteHandler('/api/blog/entity', Verb: 'POST')]
	#[Atlantis\Meta\RouteAccessTypeUser]
	public function
	BlogEntityPost():
	void {

		($this->Data)
		->Title(Common\Datafilters::TrimmedText(...))
		->Alias(Common\Datafilters::TrimmedText(...))
		->Tagline(Common\Datafilters::StrippedText(...))
		->Details(Common\Datafilters::TrimmedText(...))
		->CoverImageID(Common\Datafilters::TypeIntNullable(...));

		////////

		if(!$this->Data->Title)
		$this->Quit(1, 'no Title specified');

		if(!$this->Data->Alias)
		$this->Quit(1, 'no Alias specified');

		$Blog = Blog\Blog::Insert([
			'Title'   => $this->Data->Title,
			'Alias'   => $this->Data->Alias,
			'Tagline' => $this->Data->Tagline,
			'Details' => $this->Data->Details
		]);

		$BlogUser = Blog\BlogUser::Insert([
			'BlogID' => $Blog->ID,
			'UserID' => $this->User->ID
		]);

		$BlogUser->SetAsAdmin();

		$this->SetGoto($Blog->GetURL());

		return;
	}

	#[Atlantis\Meta\RouteHandler('/api/blog/entity', Verb: 'PATCH')]
	#[Atlantis\Meta\RouteAccessTypeUser]
	public function
	BlogEntityPatch():
	void {

		($this->Data)
		->ID(Common\Datafilters::TypeInt(...));

		////////

		if(!$this->Data->ID)
		$this->Quit(1, 'no ID specified');

		$Blog = Blog\Blog::GetByID($this->Data->ID);

		if(!$Blog)
		$this->Quit(2, 'blog not found');

		////////

		$BlogUser = Blog\BlogUser::GetByPair($Blog->ID, $this->User->ID);

		if(!$BlogUser || !$BlogUser->CanAdmin())
		$this->Quit(3, 'user cannot admin this blog');

		$Blog->Update($Blog->Patch($this->Data));

		$this->SetGoto($Blog->GetURL());

		return;
	}

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	#[Atlantis\Meta\RouteHandler('/api/blog/post', Verb: 'GET')]
	public function
	BlogPostGet():
	void {

		($this->Query)
		->ID(Common\Datafilters::TypeInt(...));

		////////

		if(!$this->Query->ID)
		$this->Quit(1, 'no ID specified');

		////////

		$Post = Blog\Post::GetByID($this->Query->ID);

		if(!$Post)
		$this->Quit(2, 'post not found');

		////////

		$this->SetPayload([
			'Post' => $Post
		]);

		return;
	}

	#[Atlantis\Meta\RouteHandler('/api/blog/post', Verb: 'POST')]
	#[Atlantis\Meta\RouteAccessTypeUser]
	public function
	BlogPostPost():
	void {

		($this->Data)
		->Editor(Common\Datafilters::TrimmedText(...))
		->BlogID(Common\Datafilters::TypeInt(...))
		->Title(Common\Datafilters::TrimmedText(...))
		->Alias(Common\Datafilters::TrimmedText(...))
		->Content(Common\Datafilters::TrimmedText(...))
		->CoverImageID(Common\Datafilters::TypeIntNullable(...));

		////////

		if(!$this->Data->BlogID)
		$this->Quit(1, 'no BlogID specified');

		////////

		$BlogUser = Blog\BlogUser::GetByPair(
			$this->Data->BlogID,
			$this->User->ID
		);

		if(!$BlogUser)
		$this->Quit(2, 'user does not have blog access');

		if(!$BlogUser->CanWrite())
		$this->Quit(3, 'user does not have blog write access');

		////////

		try {
			$Content = match($this->Data->Editor) {
				'link'
				=> Blog\Struct\EditorLink::New(
					$this->Data->Title,
					$this->Data->Date,
					$this->Data->URL,
					$this->Data->Excerpt,
					$this->Data->Content
				),

				default
				=> $this->Data->Content
			};

			$Post = Blog\Post::Insert([
				'BlogID'       => $BlogUser->BlogID,
				'UserID'       => $BlogUser->UserID,
				'Editor'       => $this->Data->Editor,
				'Title'        => $this->Data->Title,
				'Alias'        => $this->Data->Alias,
				'CoverImageID' => $this->Data->CoverImageID,
				'Content'      => $Content
			]);
		}

		catch(Blog\Error\PostMissingData $Err) {
			$this->Quit(4, $Err->GetMessage());
		}

		catch(Throwable $Err) {
			$this->Quit(PHP_INT_MAX, "WTF: {$Err->GetMessage()}");
		}

		$this->SetGoto($Post->GetURL());

		return;
	}

	#[Atlantis\Meta\RouteHandler('/api/blog/post', Verb: 'PATCH')]
	#[Atlantis\Meta\RouteAccessTypeUser]
	public function
	BlogPostPatch():
	void {

		($this->Data)
		->ID(Common\Datafilters::TypeInt(...));

		////////

		if(!$this->Data->ID)
		$this->Quit(1, 'no ID specified');

		$Post = Blog\Post::GetByID($this->Data->ID);

		if(!$Post)
		$this->Quit(2, 'post not found');

		////////

		$BlogUser = Blog\BlogUser::GetByPair(
			$Post->BlogID,
			$this->User->ID
		);

		if(!$BlogUser)
		$this->Quit(3, 'user does not have any blog access');

		if(!$Post->CanUserEdit($BlogUser))
		$this->Quit(3, 'user does not have blog edit access');

		////////

		$Post->Update($Post->Patch($this->Data));

		$this->SetGoto($Post->GetURL());

		return;
	}

	#[Atlantis\Meta\RouteHandler('/api/blog/post', Verb: 'DELETE')]
	#[Atlantis\Meta\RouteAccessTypeUser]
	public function
	BlogPostDelete():
	void {

		($this->Data)
		->ID(Common\Datafilters::TypeInt(...));

		////////

		if(!$this->Data->ID)
		$this->Quit(1, 'no ID specified');

		$Post = Blog\Post::GetByID($this->Data->ID);

		if(!$Post)
		$this->Quit(2, 'post not found');

		////////

		$BlogUser = Blog\BlogUser::GetByPair(
			$Post->BlogID,
			$this->User->ID
		);

		if(!$BlogUser)
		$this->Quit(3, 'user does not have any blog access');

		if(!$Post->CanUserEdit($BlogUser))
		$this->Quit(3, 'user does not have blog edit access');

		////////

		$Goto = $Post->Blog->GetURL();
		$Post->Drop();

		$this->SetGoto($Goto);
		return;
	}

}
