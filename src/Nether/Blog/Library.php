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
	Atlantis\Plugins\AccessTypeDefineInterface,
	Atlantis\Plugins\UploadHandlerInterface {

	const
	ConfEnable       = 'Nether.Blog.Enable',
	ConfBlogsPerUser = 'Nether.Blog.BlogsPerUser',
	ConfStorageKey   = 'Nether.Blog.StorageKey',
	ConfBlogURL      = 'Nether.Blog.BlogURL',
	ConfPostURL      = 'Nether.Blog.PostURL';

	const
	AccessBlogCreate = 'Nether.Blog.Create';

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	public function
	OnLoad(...$Argv):
	void {

		static::$Config->BlendRight([
			static::ConfEnable     => TRUE,
			static::ConfStorageKey => 'Default',
			static::ConfBlogURL    => '/+:BlogAlias:',
			static::ConfPostURL    => '/+:BlogAlias:/:PostID:/:PostAlias:'
		]);

		return;
	}

	public function
	OnReady(... $Argv):
	void {

		$App = $Argv['App'];

		BlogTagLink::RegisterType();
		PostTagLink::RegisterType();

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

}
