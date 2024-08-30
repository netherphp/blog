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
		->BlogID(Common\Filters\Numbers::IntType(...))
		->Editor(Common\Filters\Text::TrimmedNullable(...))
		->Title(Common\Filters\Text::TrimmedNullable(...))
		->Alias(Common\Filters\Text::TrimmedNullable(...))
		->Content(Common\Filters\Text::TrimmedNullable(...))
		->CoverImageID(Common\Filters\Numbers::IntNullable(...))
		->Enabled(Common\Filters\Numbers::IntType(...))
		->OptUseLinkDate(Common\Filters\Numbers::BoolType(...))
		->Plugins(Common\Filters\Text::TrimmedNullable(...))
		->Date(function(Common\Struct\DatafilterItem $I){
			$Val = Common\Filters\Text::TrimmedNullable($I->Value);
			return $Val ?? (new Common\Date)->Get(Common\Values::DateFormatYMD);
		})
		->PostPhoto(
			fn(Common\Struct\DatafilterItem $In)=>
			isset($_FILES['PostPhoto']) ? $_FILES['PostPhoto'] : NULL
		)
		->SiteTags(
			fn(Common\Struct\DatafilterItem $I)=> explode(',', $I->Value),
			Common\Filters\Lists::ArrayOf(...),
			Common\Filters\Numbers::IntType(...)
		);

		$PluginData = $this->Data->Plugins;
		$Plugins = Blog\Struct\BlogPostPluginData::FromEncoded($PluginData);
		$Now = Common\Date::CurrentUnixtime();
		$TimeSorted = $Now;

		$SiteTagConf = $this->App->Config[Atlantis\Key::ConfSiteTags] ?: [];
		$SiteTags = NULL;
		$Tag = NULL;

		if(count($this->Data->SiteTags))
		$SiteTags = Atlantis\Tag\Entity::Find([
			'ID'   => $this->Data->SiteTags,
			'Type' => 'site'
		]);

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
				'ExtraData' => [ 'Plugins' => $Plugins->ToArray() ]
			]));

			// associate the default site tags with the posts.

			//if(count($SiteTagConf) && !$SiteTags->Count())
			//$this->Quit(5, 'No site tags have been selected.');

			if($SiteTags && $SiteTags->Count())
			foreach($SiteTags as $Tag) {
				Blog\PostTagLink::InsertByPair($Tag->ID, $Post->UUID);
			}

			if($this->Data->PostPhoto) {
				$Importer = Atlantis\Util\FileUploadImporter::FromUploadItem(
					$this->App,
					$this->Data->PostPhoto
				);

				$Image = $Importer->GetFileObject();

				$Post->Update([ 'CoverImageID'=> $Image->ID ]);
			}

			// run the plugin apis.

			if($Plugins->Create->Count())
			$this->HandlePluginsBlogPostEntityCreate($Plugins->Create, $Post);

			if($Plugins->Save->Count())
			$this->HandlePluginsBlogPostEntitySave($Plugins->Save, $Post);
		}

		catch(Blog\Error\PostMissingData $Err) {
			$this->Quit(4, $Err->GetMessage());
		}

		catch(Throwable $Err) {
			$this->Quit(PHP_INT_MAX, "WTF: {$Err->GetMessage()}");
		}

		$this->SetPayload($Post->DescribeForPublicAPI());
		$this->SetGoto($Post->GetURL());

		return;
	}

	#[Atlantis\Meta\RouteHandler('/api/blog/post', Verb: 'PATCH')]
	#[Atlantis\Meta\RouteAccessTypeUser]
	public function
	BlogPostPatch():
	void {

		// 1 no id
		// 2 not found
		// 3 permission denied
		// 4 no site tags selected

		($this->Data)
		->ID(Common\Filters\Numbers::IntType(...))
		->OptUseLinkDate(Common\Filters\Numbers::BoolType(...))
		->Plugins(Common\Filters\Text::TrimmedNullable(...))
		->SiteTags([
			(fn(Common\Struct\DatafilterItem $I)=> explode(',', $I->Value)),
			[ Common\Filters\Lists::ArrayOf(...), Common\Filters\Text::TrimmedNullable(...) ],
			[ Common\Filters\Lists::ArrayOf(...), Common\Filters\Numbers::IntType(...) ]
		]);

		////////

		if(!$this->Data->ID)
		$this->Quit(1, 'no ID specified');

		////////

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

		$Plugins = new Blog\Struct\BlogPostPluginData(match(TRUE) {
			isset($Post->ExtraData['Plugins'])
			=> (array)$Post->ExtraData['Plugins'],

			default
			=> []
		});

		////////

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

		////////////////
		////////////////

		$SiteTags = new Common\Datastore;
		$SiteTagsConf = $this->App->Config[Atlantis\Key::ConfSiteTags] ?: [];
		$SiteTagsCurr = $Post->GetTagsIndexedByID()->Filter(
			fn(Atlantis\Tag\Entity $T)
			=> $T->Type === 'site'
		);

		// find the tags that were asked for to confirm they exist before
		// adding them.

		if(count($this->Data->SiteTags))
		$SiteTags->MergeRight(Atlantis\Tag\Entity::Find([
			'Type' => 'site',
			'ID'   => $this->Data->SiteTags
		]));

		// complain if this framework is configured to use site tags but
		// this post has none.

		if((count($SiteTagsConf) && $SiteTags->Count()) || (count($SiteTagsConf)===0 && $SiteTags->Count()===0)) {
			// add the tags that do not exist and de-index the ones that do so
			// we can strip off any now unused tags.

			$SiteTags->Each(function(Atlantis\Tag\Entity $Tag) use($Post, &$SiteTagsCurr) {
				if(!isset($SiteTagsCurr[$Tag->ID])) {
					Blog\BlogTagLink::InsertByPair($Tag->ID, $Post->UUID);
					return;
				}

				unset($SiteTagsCurr[$Tag->ID]);
				return;
			});

			Common\Datastore::FromArray($SiteTagsCurr)
			->Each(function(Atlantis\Tag\Entity $Tag) use($Post) {
				Atlantis\Tag\EntityLink::DeleteByPair($Tag->ID, $Post->UUID);
				return;
			});
		}

		////////////////
		////////////////

		if($Plugins->Update)
		$this->HandlePluginsBlogPostEntityUpdate($Plugins->Update, $Post);

		if($Plugins->Save)
		$this->HandlePluginsBlogPostEntitySave($Plugins->Save, $Post);

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
	HandlePluginsBlogPostEntityCreate(Common\Datastore $Plugins, Blog\Post $Post):
	void {

		return;
	}

	#[Common\Meta\Info('Called when a post is updated.')]
	protected function
	HandlePluginsBlogPostEntityUpdate(Common\Datastore $Plugins, Blog\Post $Post):
	void {

		return;
	}

	#[Common\Meta\Info('Called after a post has been created or updated.')]
	protected function
	HandlePluginsBlogPostEntitySave(Common\Datastore $Plugins, Blog\Post $Post):
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
