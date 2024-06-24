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
		->Plugins(Common\Filters\Text::TrimmedNullable(...))
		->Editor([
			Common\Filters\Text::SlottableKey(...),
			Common\Filters\Text::TrimmedNullable(...)
		]);

		////////

		$PluginData = $this->Data->Plugins;
		$Plugins = Blog\Struct\BlogPostPluginData::FromEncoded($PluginData);
		$Values = new Common\Datastore;

		$SiteTagConf = $this->App->Config[Atlantis\Key::ConfSiteTags] ?: [];
		$SiteTags = (
			Atlantis\Util::FetchSiteTagsAll()
			->Remap(function(Atlantis\Tag\Entity $Tag) use($SiteTagConf) {
				return (object)[
					'Tag'      => $Tag,
					'Selected' => in_array($Tag->Alias, $SiteTagConf)
				];
			})
		);

		if($Plugins->Values->Count())
		$Values->MergeRight($Plugins->GetValues($this->App));

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
			'Blogs'       => $Blogs,
			'SiteTags'    => $SiteTags,
			'PluginData'  => $PluginData
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

	#[Atlantis\Meta\RouteHandler('/dashboard/blog/post')]
	#[Atlantis\Meta\RouteAccessTypeUser]
	#[Avenue\Meta\ConfirmWillAnswerRequest]
	public function
	BlogPost():
	void {

		($this->Data)
		->ID(Common\Filters\Numbers::IntNullable(...))
		->Plugins(Common\Filters\Text::TrimmedNullable(...))
		->Editor([
			Common\Filters\Text::SlottableKey(...),
			Common\Filters\Text::TrimmedNullable(...)
		]);

		$Trail = Common\Datastore::FromArray([
			Atlantis\Struct\Item::New(UUID: 'blog', Title: 'Blog', URL: '/dashboard/blog'),
			Atlantis\Struct\Item::New(UUID: 'bwrite', Title: "Write New Post")
		]);

		////////

		$PluginData = $this->Data->Plugins;
		$Plugins = Blog\Struct\BlogPostPluginData::FromEncoded($PluginData);
		$Values = new Common\Datastore;

		$SiteTagConf = $this->App->Config[Atlantis\Key::ConfSiteTags] ?: [];
		$SiteTags = (
			Atlantis\Util::FetchSiteTagsAll()
			->Remap(function(Atlantis\Tag\Entity $Tag) use($SiteTagConf) {
				return (object)[
					'Tag'      => $Tag,
					'Selected' => in_array($Tag->Alias, $SiteTagConf)
				];
			})
		);

		if($Plugins->Values->Count())
		$Values->MergeRight($Plugins->GetValues($this->App));

		////////

		$Blogs = Blog\BlogUser::Find([
			'UserID'    => $this->User->ID,
			'Writer'    => 1,
			'Remappers' => [ fn(Blog\BlogUser $BU)=> $BU->Blog ]
		]);

		($this->Surface)
		->Area('blog/dashboard/blog-write', [
			'Trail'       => $Trail,

			'Post'        => NULL,
			'Values'      => $Values,
			'Editor'      => $this->Data->Editor,
			'Blogs'       => $Blogs,
			'SiteTags'    => $SiteTags,
			'PluginData'  => $PluginData
		]);

		return;
	}

	protected function
	BlogPostWillAnswerRequest():
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
	#[Avenue\Meta\ExtraDataArgs]
	public function
	BlogEdit(Blog\Post $Post):
	void {

		($this->Data)
		->ID(Common\Filters\Numbers::IntNullable(...))
		->Plugins(Common\Filters\Text::TrimmedNullable(...));

		////////

		$PluginData = $this->Data->Plugins;
		$Plugins = Blog\Struct\BlogPostPluginData::FromEncoded($PluginData);
		$Values = new Common\Datastore;

		$PostTags = $Post->GetTagsIndexedByID();
		$SiteTags = (
			Atlantis\Util::FetchSiteTagsAll()
			->Remap(function(Atlantis\Tag\Entity $Tag) use($PostTags) {
				return (object)[
					'Tag'      => $Tag,
					'Selected' => isset($PostTags[$Tag->ID])
				];
			})
		);

		////////

		$Blogs = Blog\BlogUser::Find([
			'UserID'    => $this->User->ID,
			'Writer'    => 1,
			'Remappers' => [ fn(Blog\BlogUser $BU)=> $BU->Blog ]
		]);

		////////

		($this->Surface)
		->Wrap('blog/dashboard/blog-write', [
			'Post'       => $Post,
			'Values'     => $Values,
			'Blogs'      => $Blogs,
			'Editor'     => $Post->Editor,
			'SiteTags'   => $SiteTags,
			'PluginData' => $PluginData
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
	#[Avenue\Meta\ExtraDataArgs]
	public function
	BlogSettings(Blog\Blog $Blog):
	void {

		$PageTitle = sprintf('Blog Settings - %s', $Blog->Title);

		$Trail = Common\Datastore::FromArray([
			Atlantis\Struct\Item::New(UUID: 'blog', Title: 'Blog', URL: '/dashboard/blog'),
			Atlantis\Struct\Item::New(UUID: 'bsettings', Title: "Settings: {$Blog->Title}")
		]);

		////////

		($this->Surface)
		->Set('Page.Title', $PageTitle)
		->Area('blog/dashboard/blog-settings', [
			'Blog'  => $Blog,
			'Trail' => $Trail
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

}
