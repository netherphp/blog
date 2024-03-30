<?php

////////////////////////////////////////////////////////////////////////////////
namespace Nether\Blog\Plugins; /////////////////////////////////////

use Nether\Atlantis;
use Nether\Blog;
use Nether\Common;

////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////

class BlogPostAdminMenuDefault
extends Atlantis\Plugin
implements Blog\Plugin\Interfaces\AdminMenuSectionInterface {

	public function
	GetItemsForSection(Blog\Post $Profile, string $Key, Common\Datastore $ExtraData):
	?Common\Datastore {

		return match($Key) {
			'editing'
			=> $this->GetItemsForEditing($Profile, $ExtraData),

			'tagging'
			=> $this->GetItemsForTagging($Profile, $ExtraData),

			'media'
			=> $this->GetItemsForMedia($Profile, $ExtraData),

			'danger'
			=> $this->GetItemsForDangerZone($Profile, $ExtraData),

			default
			=> NULL
		};
	}

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	protected function
	GetItemsForEditing(Blog\Post $Post, Common\Datastore $ExtraData):
	Common\Datastore {

		$Output = new Common\Datastore;

		////////

		($Output)
		->Shove('BlogPostEditMenuTitle', Atlantis\Struct\DropdownItem::New(
			Title: '# Editing'
		))
		->Shove('BlogPostEditEditor', Atlantis\Struct\DropdownItem::New(
			Title: 'Post Editor',
			Icon: 'mdi-pencil',
			URL: $Post->GetEditURL(),
			Attr: $Post->GetDataAttr([ 'blogpost-cmd' => 'edit' ], TRUE)
		));

		////////

		return $Output;
	}

	protected function
	GetItemsForTagging(Blog\Post $Post, Common\Datastore $ExtraData):
	Common\Datastore {

		$Output = new Common\Datastore;

		////////

		($Output)
		->Shove('BlogPostTaggingMenuTitle', Atlantis\Struct\DropdownItem::New(
			Title: '# Tags & Related'
		))
		->Shove('BlogPostTaggingEdit', Atlantis\Struct\DropdownItem::New(
			Title: 'Tags',
			Icon: 'mdi-tag-multiple',
			Attr: $Post->GetDataAttr([ 'post-cmd'=> 'tags' ], TRUE)
		))
		->Shove('BlogPostTaggingERLink', Atlantis\Struct\DropdownItem::New(
			Title: 'Related Profiles',
			Icon: 'mdi-text-box-multiple-outline',
			Attr: $Post->GetDataAttr([
				'profile-cmd' => 'erlink',
				'er-type'     => Atlantis\Profile\Entity::EntType,
				'er-title'    => 'Related Profiles',
				'parent-type' => 'Blog.Post'
			], TRUE)
		));

		////////

		return $Output;
	}

	protected function
	GetItemsForMedia(Blog\Post $Post, Common\Datastore $ExtraData):
	Common\Datastore {

		$Output = new Common\Datastore;

		////////

		($Output)
		->Shove('ProfileMediaMenuTitle', Atlantis\Struct\DropdownItem::New(
			Title: '# Media'
		))
		->Shove('ProfileMediaUploadPhoto', Atlantis\Struct\DropdownItem::New(
			Title: 'Upload Photos',
			Icon: 'mdi-upload',
			Attr: $Post->GetDataAttr([ 'photolib-cmd'=> 'upload', 'parent-type'=> Blog\Key::EntityTypePost, 'parent-uuid'=> $Post->UUID ], TRUE)
		))
		->Shove('ProfileMediaAddVideoURL2', Atlantis\Struct\DropdownItem::New(
			Title: 'Add Video',
			Icon: 'mdi-video-plus',
			Attr: $Post->GetDataAttr([ 'videotp-cmd'=> 'new2', 'other-type'=> Blog\Key::EntityTypePost, 'other-uuid'=> $Post->UUID ], TRUE)
		));

		////////

		return $Output;
	}

	protected function
	GetItemsForDangerZone(Blog\Post $Post, Common\Datastore $ExtraData):
	Common\Datastore {

		$Output = new Common\Datastore;

		////////

		if(!$Post->Enabled)
		$Output->Shove('ProfileStateEnable', Atlantis\Struct\DropdownItem::New(
			Title: 'Publish Post',
			Icon: 'mdi-eye',
			Attr: $Post->GetDataAttr([ 'post-cmd'=> 'publish' ], TRUE)
		));

		else
		$Output->Shove('ProfileStateDisable', Atlantis\Struct\DropdownItem::New(
			Title: 'Disable Post',
			Icon: 'mdi-eye-off',
			Attr: $Post->GetDataAttr([ 'post-cmd'=> 'draft' ], TRUE)
		));

		////////

		($Output)
		->Shove('ProfileStateDelete', Atlantis\Struct\DropdownItem::New(
			Title: 'Delete',
			Icon: 'mdi-trash-can',
			Attr: $Post->GetDataAttr([ 'post-cmd'=> 'delete' ], TRUE),
			Warn: Atlantis\Struct\DropdownItem::Danger
		));

		////////

		return $Output;
	}

};
