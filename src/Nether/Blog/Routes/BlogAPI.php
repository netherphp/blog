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
		->ID(Common\Filters\Numbers::IntType(...));

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
		->Title(Common\Filters\Text::Trimmed(...))
		->Alias(Common\Filters\Text::Trimmed(...))
		->Tagline(Common\Filters\Text::Stripped(...))
		->Details(Common\Filters\Text::Trimmed(...))
		->CoverImageID(Common\Filters\Numbers::IntNullable(...));

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
		->ID(Common\Filters\Numbers::IntType(...));

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
		->ID(Common\Filters\Numbers::IntType(...));

		////////

		if(!$this->Query->ID)
		$this->Quit(1, 'no ID specified');

		////////

		$Post = Blog\Post::GetByID($this->Query->ID);

		if(!$Post)
		$this->Quit(2, 'post not found');

		////////

		$this->SetPayload($Post->DescribeForPublicAPI());

		return;
	}

	#[Atlantis\Meta\RouteHandler('/api/blog/post', Verb: 'POST')]
	#[Atlantis\Meta\RouteAccessTypeUser]
	public function
	BlogPostPost():
	void {

		($this->Data)
		->Editor(Common\Filters\Text::Trimmed(...))
		->BlogID(Common\Filters\Numbers::IntType(...))
		->Title(Common\Filters\Text::Trimmed(...))
		->Alias(Common\Filters\Text::Trimmed(...))
		->Content(Common\Filters\Text::Trimmed(...))
		->CoverImageID(Common\Filters\Numbers::IntNullable(...))
		->Enabled(Common\Filters\Numbers::IntType(...))
		->OptUseLinkDate(Common\Filters\Numbers::BoolType(...))
		->Plugins([
			Common\Filters\Text::Base64Decode(...),
			Common\Filters\Text::DatastoreFromJSON(...)
		]);

		$Plugins = $this->Data->Plugins;
		$Now = Common\Date::CurrentUnixtime();
		$TimeSorted = $Now;

		$SiteTags = Blog\Util::FetchSiteTags();
		$Tag = NULL;

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

			if($Content instanceof Blog\Struct\EditorLink) {
				if($this->Data->OptUseLinkDate)
				$TimeSorted = Common\Date::FromDateString($Content->Date)->GetUnixtime();
			}

			$Post = Blog\Post::Insert([
				'BlogID'       => $BlogUser->BlogID,
				'UserID'       => $BlogUser->UserID,
				'TimeSorted'   => $TimeSorted,
				'Editor'       => $this->Data->Editor,
				'Title'        => $this->Data->Title,
				'Alias'        => $this->Data->Alias,
				'CoverImageID' => $this->Data->CoverImageID,
				'Content'      => $Content,
				'Enabled'      => $this->Data->Enabled
			]);

			// take note of any plugins that were used in the creation
			// of this post.

			if($Plugins->Count())
			$Post->Update($Post->Patch([
				'ExtraData' => [ 'Plugins' => $Plugins ]
			]));

			// associate the default site tags with the posts.

			if($SiteTags->Count())
			foreach($SiteTags as $Tag) {
				Blog\PostTagLink::InsertByPair($Tag->ID, $Post->UUID);
			}

			// run the plugin apis.

			if($Plugins['Create'])
			$this->HandlePluginsBlogPostEntityCreate($Plugins['Create'], $Post);

			if($Plugins['Save'])
			$this->HandlePluginsBlogPostEntitySave($Plugins['Save'], $Post);
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
		->ID(Common\Filters\Numbers::IntType(...))
		->OptUseLinkDate(Common\Filters\Numbers::BoolType(...))
		->Plugins([
			Common\Filters\Text::Base64Decode(...),
			Common\Filters\Text::DatastoreFromJSON(...)
		]);

		////////

		if(!$this->Data->ID)
		$this->Quit(1, 'no ID specified');

		$Post = Blog\Post::GetByID($this->Data->ID);
		$Plugins = new Common\Datastore;

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

		// make note of the plugins we used when creating the post.

		if($Post->ExtraData['Plugins'])
		$Plugins->MergeRight($Post->ExtraData['Plugins']);

		// generate the patch set for updating the post.

		$Patchset = $Post->Patch($this->Data);

		if($Post->Editor === 'link') {
			if($this->Data->OptUseLinkDate)
			$Patchset['TimeSorted'] = match(TRUE) {
				(isset($Patchset['Content']) && isset($Patchset['Content']->Date))
				=> Common\Date::FromDateString($Patchset['Content']->Date)->GetUnixtime(),

				default
				=> Common\Date::FromDateString(Blog\Struct\EditorLink::FromJSON($Post->Content))->GetUnixtime(),
			};

			else
			$Patchset['TimeSorted'] = $Post->TimeCreated;
		}

		$Post->Update($Patchset);

		////////

		if($Plugins['Update'])
		$this->HandlePluginsBlogPostEntityUpdate((array)$Plugins['Update'], $Post);

		if($Plugins['Save'])
		$this->HandlePluginsBlogPostEntitySave((array)$Plugins['Save'], $Post);

		////////

		$this->SetGoto($Post->GetURL());
		return;
	}

	#[Atlantis\Meta\RouteHandler('/api/blog/post', Verb: 'DELETE')]
	#[Atlantis\Meta\RouteAccessTypeUser]
	public function
	BlogPostDelete():
	void {

		($this->Data)
		->ID(Common\Filters\Numbers::IntType(...));

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

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	#[Common\Meta\Info('Called when a new post is first created.')]
	protected function
	HandlePluginsBlogPostEntityCreate(array $Plugins, Blog\Post $Post):
	void {

		return;
	}

	#[Common\Meta\Info('Called when a post is updated.')]
	protected function
	HandlePluginsBlogPostEntityUpdate(array $Plugins, Blog\Post $Post):
	void {

		return;
	}

	#[Common\Meta\Info('Called after a post has been created or updated.')]
	protected function
	HandlePluginsBlogPostEntitySave(array $Plugins, Blog\Post $Post):
	void {

		$Class = NULL;
		$Data = NULL;

		foreach($Plugins as $Class => $Data)
		($this->App->Plugins)
		->Get(Blog\Plugins\BlogPostEntitySaveInterface::class)
		->Filter(fn(string $C)=> $C === $Class)
		->Map(fn(string $C)=> new $C)
		->Each(
			fn(Blog\Plugins\BlogPostEntitySaveInterface $Plugin)
			=> $Plugin->OnSave(
				$this->App,
				Common\Datastore::FromArray($Data ?: []),
				$Post
			)
		);


		return;
	}

}
