<?php

namespace Nether\Blog;

use Nether\Atlantis;
use Nether\Avenue;
use Nether\Common;

class Library
extends Common\Library
implements
	Atlantis\Plugins\DashboardSidebarInterface,
	Atlantis\Plugins\AccessTypeDefineInterface {

	const
	ConfEnable       = 'Nether.Blog.Enable',
	ConfBlogsPerUser = 'Nether.Blog.BlogsPerUser';

	const
	AccessBlogCreate = 'Nether.Blog.Create';

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	public function
	OnLoad(...$Argv):
	void {

		static::$Config->BlendRight([
			static::ConfEnable => TRUE
		]);

		return;
	}

	public function
	OnReady(... $Argv):
	void {

		$App = $Argv['App'];

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

		if($App->User && $App->User->IsAdmin())
		$Sidebar->Push(new Dashboard\BlogAdminGroup);

		return;
	}

	////////////////////////////////////////////////////////////////
	// AccessTypeDefineInterface ///////////////////////////////////

	public function
	OnAccessTypeDefine(Atlantis\Engine $App, Common\Datastore $List):
	void {

		if(!$App->Config[static::ConfEnable])
		return;

		$List->MergeRight([
			new Atlantis\User\AccessTypeDef(
				static::AccessBlogCreate, 1,
				'Allows user to create new blogs.'
			),
			new Atlantis\User\AccessTypeDef(
				static::AccessBlogCreate, 0,
				'Prevent user from creating new blogs.'
			)
		]);

		return;
	}


}
