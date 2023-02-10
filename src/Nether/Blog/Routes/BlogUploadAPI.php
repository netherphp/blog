<?php

namespace Nether\Blog\Routes;

use Nether\Atlantis;
use Nether\Blog;
use Nether\Common;
use Nether\Storage;
use FileEye;

use Exception;
use SplFileInfo;
use Imagick;

class BlogUploadAPI
extends Atlantis\Routes\UploadAPI {

	#[Atlantis\Meta\RouteHandler('/api/blog/entity/header', Verb: 'POST')]
	#[Atlantis\Meta\RouteAccessTypeUser]
	public function
	BlogEntityUploadHeader():
	void {

		$this->Queue(
			static::KiOnUploadComplete,
			$this->OnUploadHeader(...),
			FALSE
		);

		$this->ChunkPost();
		return;
	}

	#[Atlantis\Meta\RouteHandler('/api/blog/entity/icon', Verb: 'POST')]
	#[Atlantis\Meta\RouteAccessTypeUser]
	public function
	BlogEntityUploadIcon():
	void {

		$this->Queue(
			static::KiOnUploadComplete,
			$this->OnUploadIcon(...),
			FALSE
		);

		$this->ChunkPost();
		return;
	}

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	public function
	OnUploadHeader(string $Name, Storage\File $File):
	void {

		($this->Data)
		->ID(Common\Datafilters::TypeInt(...));

		$this->HandleBlogImageUpload(
			$this->Data->ID,
			$Name,
			$File,
			'blog/header/%s.jpeg',
			'--blog-header-%d',
			'ImageHeaderID'
		);

		return;
	}

	public function
	OnUploadIcon(string $Name, Storage\File $File):
	void {

		($this->Data)
		->ID(Common\Datafilters::TypeInt(...));

		$this->HandleBlogImageUpload(
			$this->Data->ID,
			$Name,
			$File,
			'blog/icon/%s.jpeg',
			'--blog-icon-%d',
			'ImageIconID'
		);

		return;
	}

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	protected function
	HandleBlogImageUpload(int $BlogID, string $Name, Storage\File $File, string $Path, string $UUID, string $Field):
	void {

		$Storage = NULL;
		$Blog = NULL;
		$Filedata = NULL;
		$Filepath = NULL;
		$Filesize = NULL;
		$Filetype = NULL;

		try {
			$Storage = $this->PrepareStorageSystem();
			$Blog = $this->PrepareBlog($BlogID);

			$Filepath = sprintf($Path, $Blog->UUID);
			$Filedata = $this->ProcessImageData($File);

			$Storage->Put($Filepath, $Filedata);
			$File->DeleteParentDirectory();
		}

		catch(Exception $Error) {
			$this->CleanupAfterFailure($File);
			$this->Quit(1, $Error->GetMessage());
		}

		// handle storage of the upload.

		try {
			$File = $Storage->GetFileObject($Filepath);
			$Filesize = $File->GetSize();
			$Filetype = $File->GetType();

			$Entry = Atlantis\Struct\FileUpload::Insert([
				'UUID' => sprintf($UUID, $Blog->ID),
				'Name' => $Name,
				'Size' => $Filesize,
				'Type' => $Filetype,
				'URL'  => $Storage->GetStorageURL($Filepath)
			]);

			$Blog->Update([
				$Field => $Entry->ID
			]);
		}

		catch(Exception $Error) {
			$this->Quit(2, $Error->GetMessage());
		}

		return;
	}

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	protected function
	PrepareStorageSystem():
	Storage\Adaptor {

		$Key = $this->App->Config[Blog\Library::ConfStorageKey];
		$Storage = $this->App->Storage->Location($Key);

		if(!$Storage)
		throw new Exception("no storage location {$Key} defined");

		return $Storage;
	}

	protected function
	PrepareBlog(?int $BlogID):
	Blog\Blog {

		if(!$BlogID)
		throw new Exception('no Blog ID specified');

		////////

		$Blog = Blog\Blog::GetByID($BlogID);

		if(!$Blog)
		throw new Exception('blog not found');

		////////

		$BlogUser = Blog\BlogUser::GetByPair(
			$Blog->ID,
			$this->User->ID
		);

		if(!$BlogUser || !$BlogUser->CanAdmin())
		throw new Exception('user does not have blog admin access');

		////////

		return $Blog;
	}

	protected function
	ProcessImageData(Storage\File $File):
	string {

		if(!$File->IsImage())
		throw new Exception('file is not image');

		////////

		$Data = NULL;

		try {
			$Img = new Imagick;
			$Img->ReadImageBlob($File->Read(), $File->Path);

			$Img->SetFormat('jpeg');
			$Img->SetCompressionQuality(92);

			$Data = $Img->GetImageBlob();
			$Img->Destroy();
		}

		catch(Exception $Error) {
			throw new Exception('imagick hated that');
		}

		return $Data;
	}

	protected function
	CleanupAfterFailure(Storage\File $File):
	void {

		$File->DeleteParentDirectory();
		return;
	}

}
