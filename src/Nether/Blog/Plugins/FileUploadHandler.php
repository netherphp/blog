<?php ##########################################################################
################################################################################

namespace Nether\Blog\Plugins;

use Nether\Atlantis;
use Nether\Blog;
use Nether\Common;
use Nether\Storage;

################################################################################
################################################################################

class FileUploadHandler
extends Atlantis\Plugin
implements Atlantis\Plugin\Interfaces\Engine\FileUploadInterface {

	public function
	WillHandleUpload(string $Type, Atlantis\Media\File $Entity, Common\Datafilter $Data):
	bool {

		$WillHandleType = match($Type) {
			'blog-img-header' => TRUE,
			'blog-img-icon'   => TRUE,
			default           => FALSE
		};

		$HasNeededData = FALSE;

		if($Data->Exists('BlogID')) {
			$Blog = Blog\Blog::GetByID($Data->Get('BlogID'));
			$Data->Set('Blog.Entity', $Blog);
			$HasNeededData = TRUE;
		}

		////////

		$Will = (TRUE
			&& $WillHandleType
			&& $HasNeededData
		);

		return $Will;
	}

	public function
	OnHandleUpload(string $Type, Atlantis\Media\File $Entity, Common\Datafilter $Data):
	void {

		/** @var Blog\Blog $Blog */

		$Blog = $Data->Get('Blog.Entity');

		if($Type === 'blog-img-header')
		$Blog->Update([ 'ImageHeaderID'=> $Entity->ID ]);

		if($Type === 'blog-img-icon')
		$Blog->Update([ 'ImageIconID'=> $Entity->ID ]);

		return;
	}

};
