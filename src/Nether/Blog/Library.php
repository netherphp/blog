<?php

namespace Nether\Blog;

use Nether\Atlantis;
use Nether\Avenue;
use Nether\Common;
use Nether\Storage;

use Exception;

class Library
extends Common\Library
implements
	Atlantis\Plugins\DashboardSidebarInterface,
	Atlantis\Plugins\DashboardElementInterface {

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	public function
	OnLoad(...$Argv):
	void {

		$App = Atlantis\Engine::From($Argv);

		////////

		($App->Config)
		->BlendRight([
			Key::ConfEnable        => TRUE,
			Key::ConfStorageKey    => 'Default',
			Key::ConfBlogURL       => '/+:BlogAlias:',
			Key::ConfPostURL       => '/+:BlogAlias:/:PostID:/:PostAlias:',
			Key::ConfEditorDefault => 'html'
		]);

		$App->Plugins->RegisterInterfaceNamespace('Nether\Blog\Plugin\Interfaces');

		($App->Plugins)
		->Register(Plugins\PostView\AdminMenuDefault::class)
		->Register(Plugins\FileUploadHandler::class)
		->Register(Plugins\Dashboard\ContentInfoWidget::class);

		BlogTagLink::Register();
		PostTagLink::Register();
		Atlantis\Struct\EntityRelationship::Register(Blog::EntType, Blog::class);
		Atlantis\Struct\EntityRelationship::Register(Post::EntType, Post::class);

		return;
	}

	public function
	OnReady(... $Argv):
	void {

		$App = Atlantis\Engine::From($Argv);

		if($App->Router->GetSource() === 'dirscan') {
			$RouterPath = dirname(__FILE__);
			$Scanner = new Avenue\RouteScanner("{$RouterPath}/Routes");
			$Map = $Scanner->Generate();

			////////

			$Map['Verbs']->Each(
				fn(Common\Datastore $Handlers)
				=> $App->Router->AddHandlers($Handlers)
			);

			$Map['Errors']->Each(
				fn(Avenue\Meta\RouteHandler $Handler, int $Code)
				=> $App->Router->AddErrorHandler($Code, $Handler)
			);
		}

		return;
	}

	////////////////////////////////////////////////////////////////
	// DashboardSidebarInterface ///////////////////////////////////

	public function
	OnDashboardSidebar(Atlantis\Engine $App, Common\Datastore $Sidebar):
	void {

		if(!$App->Config[static::ConfEnable])
		return;

		if($App->User)
		$Sidebar->Push(new Dashboard\BlogSidebarGroup($App));

		return;
	}

	public function
	OnDashboardElement(Atlantis\Engine $App, Common\Datastore $Elements):
	void {

		if(!$App->User)
		return;

		$Blogs = BlogUser::Find([ 'UserID'=> $App->User->ID, 'Limit'=> 0 ]);

		if(count($Blogs))
		$Elements->Push(new Dashboard\BlogElement($App, $Blogs));

		return;
	}

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////
	// DEPRECATED //////////////////////////////////////////////////

	const
	ConfEnable       = 'Nether.Blog.Enable',
	ConfBlogsPerUser = 'Nether.Blog.BlogsPerUser',
	ConfStorageKey   = 'Nether.Blog.StorageKey',
	ConfBlogURL      = 'Nether.Blog.BlogURL',
	ConfPostURL      = 'Nether.Blog.PostURL';

	const
	AccessBlogCreate = 'Nether.Blog.Create';

}
