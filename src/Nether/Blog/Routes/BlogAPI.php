<?php

namespace Nether\Blog\Routes;

use Nether\Atlantis;
use Nether\Avenue;
use Nether\Blog;
use Nether\Common;

use Throwable;

class BlogAPI
extends Atlantis\ProtectedAPI {

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
		->BlogID(Common\Datafilters::TypeInt(...))
		->Title(Common\Datafilters::TrimmedText(...))
		->Alias(Common\Datafilters::TrimmedText(...))
		->Content(Common\Datafilters::TrimmedText(...));

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
		$this->Quit(3, 'user does not have blog writer access');

		////////

		try {
			Blog\Post::Insert([
				'BlogID'  => $BlogUser->BlogID,
				'UserID'  => $BlogUser->UserID,
				'Title'   => $this->Data->Title,
				'Alias'   => $this->Data->Alias,
				'Content' => $this->Data->Content
			]);
		}

		catch(Blog\Error\PostMissingData $Err) {
			$this->Quit(4, $Err->GetMessage());
		}

		catch(Throwable $Err) {
			$this->Quit(PHP_INT_MAX, "WTF: {$Err->GetMessage()}");
		}

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
		$this->Quit(3, 'user does not have blog access');

		if(!$BlogUser->CanEdit())
		if($Post->UserID !== $this->User->ID)
		$this->Quit(3, 'user does not have blog edit access');

		////////

		$Post->Update($Post->Patch($this->Data));

		$this->SetPayload([
			'Post' => $Post
		]);

		return;
	}

	#[Atlantis\Meta\RouteHandler('/api/blog/post', Verb: 'DELETE')]
	#[Atlantis\Meta\RouteAccessTypeUser]
	public function
	BlogPostDelete():
	void {

		return;
	}

}
