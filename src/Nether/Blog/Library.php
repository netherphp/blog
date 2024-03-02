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
	Atlantis\Plugins\DashboardElementInterface,
	Atlantis\Plugins\UploadHandlerInterface {

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	public function
	OnLoad(...$Argv):
	void {

		$App = Atlantis\Engine::From($Argv);

		($App->Config)
		->BlendRight([
			static::ConfEnable     => TRUE,
			static::ConfStorageKey => 'Default',
			static::ConfBlogURL    => '/+:BlogAlias:',
			static::ConfPostURL    => '/+:BlogAlias:/:PostID:/:PostAlias:'
		]);

		//($App->Plugins)
		//->Register(
		//	Atlantis\Plugins\AccessTypeDefineInterface::class,
		//	Plugins\AccessTypeDefine::class
		//);

		$App->Plugins->RegisterInterfaceNamespace('Nether\Blog\Plugin\Interfaces');
		$App->Plugins->Register(Plugins\BlogPostAdminMenuDefault::class);

		BlogTagLink::Register();
		PostTagLink::Register();
		Atlantis\Struct\EntityRelationship::Register('Blog.Entity', Blog::class);
		Atlantis\Struct\EntityRelationship::Register('Blog.Post', Post::class);

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

	public function
	OnUploadFinalise(Atlantis\Engine $App, string $UUID, string $Name, string $Type, Storage\File $File):
	void {

		switch($Type) {
			case 'bloghead':
				$this->OnUploadFinaliseHead($App, $UUID, $Name, $File);
			break;
			case 'blogicon':
				$this->OnUploadFinaliseIcon($App, $UUID, $Name, $File);
			break;
			case 'blogimg':
				$this->OnUploadFinaliseImage($App, $UUID, $Name, $File);
			break;
			case 'posthead':
				$this->OnUploadFinalisePostHead($App, $UUID, $Name, $File);
			break;
		}

		return;
	}

	protected function
	OnUploadFinaliseHead(Atlantis\Engine $App, string $UUID, string $Name, Storage\File $File):
	void {

		$Storage = $App->Storage->Location('Default');
		$Blog = Blog::GetByID($App->Router->Request->Data->ID);
		$UUID = "--blog-head-{$Blog->ID}";
		$Path = sprintf(
			'blog/%s/head/original.%s',
			$Blog->UUID,
			$File->GetExtension()
		);

		// move the file to where it needs to live.

		$Storage->Put($Path, $File->Read());
		$File->DeleteParentDirectory();

		// track the file in the database.

		$File = $Storage->GetFileObject($Path);

		$Entity = Atlantis\Media\File::Insert([
			'UUID'   => $UUID,
			'UserID' => $App->User?->ID,
			'Name'   => $Name,
			'Type'   => $File->GetType(),
			'Size'   => $File->GetSize(),
			'URL'    => $File->GetStorageURL()
		]);

		$Entity->GenerateExtraFiles();

		$Blog->Update([
			'ImageHeaderID' => $Entity->ID
		]);

		return;
	}

	protected function
	OnUploadFinaliseIcon(Atlantis\Engine $App, string $UUID, string $Name, Storage\File $File):
	void {

		$Storage = $App->Storage->Location('Default');
		$Blog = Blog::GetByID($App->Router->Request->Data->ID);
		$UUID = "--blog-icon-{$Blog->ID}";
		$Path = sprintf(
			'blog/%s/icon/original.%s',
			$Blog->UUID,
			$File->GetExtension()
		);

		// move the file to where it needs to live.

		$Storage->Put($Path, $File->Read());
		$File->DeleteParentDirectory();

		// track the file in the database.

		$File = $Storage->GetFileObject($Path);

		$Entity = Atlantis\Media\File::Insert([
			'UUID'   => $UUID,
			'UserID' => $App->User?->ID,
			'Name'   => $Name,
			'Type'   => $File->GetType(),
			'Size'   => $File->GetSize(),
			'URL'    => $File->GetStorageURL()
		]);

		$Entity->GenerateExtraFiles();

		$Blog->Update([
			'ImageIconID' => $Entity->ID
		]);

		return;
	}

	protected function
	OnUploadFinaliseImage(Atlantis\Engine $App, string $UUID, string $Name, Storage\File $File):
	void {

		$Storage = $App->Storage->Location('Default');
		$Blog = Blog::GetByID($App->Router->Request->Data->ID);
		$Path = sprintf(
			'blog/%s/%s/original.%s',
			$Blog->UUID,
			$UUID,
			$File->GetExtension()
		);

		// move the file to where it needs to live.

		$Storage->Put($Path, $File->Read());
		$File->DeleteParentDirectory();

		// track the file in the database.

		$File = $Storage->GetFileObject($Path);

		$Entity = Atlantis\Media\File::Insert([
			'UUID'   => $UUID,
			'UserID' => $App->User?->ID,
			'Name'   => $Name,
			'Type'   => $File->GetType(),
			'Size'   => $File->GetSize(),
			'URL'    => $File->GetStorageURL()
		]);

		$Entity->GenerateExtraFiles();

		$Blog->Update([
			'ImageHeaderID' => $Entity->ID
		]);

		return;
	}

	protected function
	OnUploadFinalisePostHead(Atlantis\Engine $App, string $UUID, string $Name, Storage\File $File):
	void {

		$App->Library['Atlantis']->OnUploadFinalise($App, $UUID, $Name, 'default', $File);

		////////

		$Upload = Atlantis\Media\File::GetByUUID($UUID);

		if(!$Upload)
		throw new Exception("Upload {$UUID} Not Found");

		//$Post = Post::GetByID($App->Router->Request->Data->ID);

		//if(!$Post)
		//throw new Exception("Post {$App->Router->Request->Data->ID} Not Found");

		////////

		//$Post->Update([ 'CoverImageID' => $Upload->ID ]);

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
