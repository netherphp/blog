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

		$this->ChunkPost();
		return;
	}

	#[Atlantis\Meta\RouteHandler('/api/blog/entity/header', Verb: 'POSTFINAL')]
	#[Atlantis\Meta\RouteAccessTypeUser]
	public function
	BlogEntityUploadHeaderFinal():
	void {

		$this->Queue(
			static::KiOnUploadFinalise,
			$this->OnUploadHeader(...),
			FALSE
		);

		$this->ChunkFinalise();
		return;
	}

	#[Atlantis\Meta\RouteHandler('/api/blog/entity/icon', Verb: 'POST')]
	#[Atlantis\Meta\RouteAccessTypeUser]
	public function
	BlogEntityUploadIcon():
	void {

		$this->ChunkPost();
		return;
	}

	#[Atlantis\Meta\RouteHandler('/api/blog/entity/icon', Verb: 'POSTFINAL')]
	#[Atlantis\Meta\RouteAccessTypeUser]
	public function
	BlogEntityUploadIconFinal():
	void {

		$this->Queue(
			static::KiOnUploadFinalise,
			$this->OnUploadIcon(...),
			FALSE
		);

		$this->ChunkFinalise();
		return;
	}

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	public function
	OnUploadHeader(string $Name, string $UUID, Storage\File $Source):
	void {

		($this->Data)
		->ID(Common\Datafilters::TypeInt(...));

		$this->HandleBlogImageUpload(
			$this->Data->ID,
			$Name,
			$Source,
			'blog/%s/header/original.jpeg',
			'--blog-header-%d',
			'ImageHeaderID'
		);

		return;
	}

	public function
	OnUploadIcon(string $Name, string $UUID, Storage\File $Source):
	void {

		($this->Data)
		->ID(Common\Datafilters::TypeInt(...));

		$this->HandleBlogImageUpload(
			$this->Data->ID,
			$Name,
			$Source,
			'blog/%s/icon/original.jpeg',
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
			$Storage = $this->PrepareStorageLocation(Blog\Library::Get(Blog\Library::ConfStorageKey));
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

			$Entry = Atlantis\Media\File::Insert([
				'UserID' => $this->User->ID,
				'UUID'   => sprintf($UUID, $Blog->ID),
				'Name'   => $Name,
				'Size'   => $Filesize,
				'Type'   => $Filetype,
				'URL'    => $Storage->GetStorageURL($Filepath)
			]);

			$Entry->GenerateExtraFiles();

			if(!isset($Blog->{$Field}) || $Blog->{$Field} !== $Entry->ID)
			$Blog->Update([ $Field => $Entry->ID ]);
		}

		catch(Exception $Error) {
			$this->Quit(2, $Error->GetMessage());
		}

		return;
	}

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

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
