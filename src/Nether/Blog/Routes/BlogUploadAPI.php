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

		$this->ChunkFinalise();
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
