<?php ##########################################################################
################################################################################

namespace Nether\Blog\Routes;

use Nether\Atlantis;
use Nether\Avenue;
use Nether\Blog;
use Nether\Common;
use Nether\Surface;

use Nether\Blog\Plugin\Interfaces\PostView\AdminMenuAuditInterface;
use Nether\Blog\Plugin\Interfaces\PostView\AdminMenuSectionInterface;

################################################################################
################################################################################

class BlogHandler
extends Atlantis\PublicWeb {

	#[Avenue\Meta\RouteHandler('@Nether.Blog.BlogURL')]
	#[Avenue\Meta\ConfirmWillAnswerRequest]
	public function
	Index(string $BlogAlias):
	void {

		($this->Data)
		->Page(Common\Filters\Numbers::Page(...))
		->Drafts(Common\Filters\Numbers::BoolNullable(...))
		->Tag(Common\Filters\Text::TrimmedNullable(...))
		->Q(Common\Filters\Text::TrimmedNullable(...))
		->Format(Common\Filters\Text::TrimmedNullable(...));

		$Blog = NULL;
		$BlogUser = NULL;
		$Posts = NULL;
		$ShowingDrafts = FALSE;

		$Tag = NULL;
		$MoreTags = [];

		////////

		if($this->Data->Format === 'rss')
		array_unshift($this->Surface->Themes, 'rss');

		////////

		$Blog = Blog\Blog::GetByField('Alias', $BlogAlias);

		if($this->User)
		$BlogUser = $Blog->GetUser($this->User->ID);

		if($BlogUser && $BlogUser->CanWrite()) {
			if($this->Data->Exists('Drafts'))
			$ShowingDrafts = $this->Data->Drafts;
		}

		////////

		$BlogTags = $Blog->GetTags();

		////////

		//if($this->App->Local)
		//$this->App->GetLocalData('Blog.Index.OptShowDrafts');

		if($this->Data->Tag) {
			$Tag = Atlantis\Tag\Entity::GetByField('Alias', $this->Data->Tag);

			$MoreTags[] = $Tag->ID;
		}

		$Posts = $Blog->GetRecentPosts(
			Page: $this->Data->Page,
			Drafts: $ShowingDrafts,
			MoreTags: $MoreTags,
			SearchTitle: $this->Data->Q
		);

		// clearly not really the popular posts atm.
		$Popular = $Posts->Distill(
			fn(Blog\Post $Post, int $Key)
			=> $Key < 4
		);

		////////


		($this->Surface)
		->Set('Page.Title', $Blog->Title)
		->Set('Page.URL', $Blog->GetURL())
		->Area('blog/index', [
			'Blog'          => $Blog,
			'BlogUser'      => $BlogUser,
			'BlogTags'      => $BlogTags,
			'Posts'         => $Posts,
			'Popular'       => $Popular,
			'OptShowDrafts' => $ShowingDrafts
		]);

		return;
	}

	protected function
	IndexWillAnswerRequest(string $BlogAlias):
	int {

		$Blog = Blog\Blog::GetByField('Alias', $BlogAlias);

		////////

		if(!$Blog)
		return Avenue\Response::CodeNope;

		////////

		return Avenue\Response::CodeOK;
	}

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	#[Avenue\Meta\RouteHandler('@Nether.Blog.PostURL')]
	#[Avenue\Meta\ConfirmWillAnswerRequest]
	#[Avenue\Meta\ExtraDataArgs]
	public function
	ViewPost(string $BlogAlias, int $PostID, ?string $PostAlias, Blog\Post $Post):
	void {

		$BlogUser = NULL;
		$PostPhotos = NULL;
		$PostVideos = NULL;
		$AdminMenu = NULL;
		$ExtraData  = new Common\Datastore; // to be filled by more plugins.

		////////

		if($this->User)
		$BlogUser = $Post->Blog->GetUser($this->User->ID);

		$PostPhotos = $Post->FetchPhotos();
		$PostVideos = $Post->FetchVideos();
		$PostProfiles = $Post->FetchRelatedProfiles();


		$AdminMenu = static::BlogPostViewAdminMenu(
			$this->App,
			$Post,
			$ExtraData
		);

		////////

		($this->Surface)
		->Set('Page.ImageURL', new Atlantis\WebURL($Post->GetCoverImageURL('lg') ?? ''))
		->Set('Page.Title', sprintf(
			'%s - %s',
			$Post->Title,
			$Post->Blog->Title
		))
		->Area('blog/view', [
			'Blog'      => $Post->Blog,
			'BUsr'      => $BlogUser,
			'Post'      => $Post,

			'AdminMenu' => $AdminMenu,
			'Photos'    => $PostPhotos,
			'Videos'    => $PostVideos,
			'Related'   => $PostProfiles,

			'Profiles'  => $PostProfiles, // @deprecated 2024-06-23
			'BlogUser'  => $BlogUser      // @deprecated 2024-06-23
		]);

		return;
	}

	protected function
	ViewPostWillAnswerRequest(string $BlogAlias, int $PostID, ?string $PostAlias, Avenue\Struct\ExtraData $ExtraData):
	int {

		$Post = Blog\Post::GetByID($PostID);
		$Blog = Blog\Blog::GetByField('Alias', $BlogAlias);

		// first thing handle if the requested post was even found
		// anywhere at all.

		if(!$Post)
		return Avenue\Response::CodeNope;

		// if this post has been linked to the wrong blog (changing id in
		// the url), or the blog has been renamed, or literally any other
		// reason that the url suggests a blog this post does not belong to
		// then redirect to the correct place.

		if(!$Blog || $Blog->ID !== $Post->Blog->ID) {
			$this->Response->SetHeader(
				'Location',
				$Post->Blog->GetPostURL($Post)
			);

			return Avenue\Response::CodeRedirectPerm;
		}

		// if this is an old link and the alias has been renamed since
		// the original posting then redirect to the updated url.

		if($PostAlias && $PostAlias !== $Post->Alias) {
			$this->Response->SetHeader(
				'Location',
				$Post->Blog->GetPostURL($Post)
			);

			return Avenue\Response::CodeRedirectPerm;
		}

		////////

		$ExtraData['Post'] = $Post;

		return Avenue\Response::CodeOK;
	}

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	#[Common\Meta\Date('2024-02-09')]
	#[Common\Meta\Info('Allow plugins add things to the Blog Post Admin Menu.')]
	static public function
	BlogPostViewAdminMenu(Atlantis\Engine $App, Blog\Post $Profile, Common\Datastore $ExtraData):
	Atlantis\Struct\DropdownMenu {

		$AdminMenu = Atlantis\Struct\DropdownMenu::New();

		if(!$App->User || !$App->User->IsAdmin())
		return $AdminMenu;

		////////

		$Plugins = $App->Plugins->GetInstanced(AdminMenuSectionInterface::class);
		$Audits = $App->Plugins->GetInstanced(AdminMenuAuditInterface::class);

		$Sections = Common\Datastore::FromArray([
			'before'  => NULL,
			'editing' => NULL,
			'tagging' => NULL,
			'media'   => NULL,
			'danger'  => NULL,
			'after'   => NULL
		]);

		// have the plugins prepare their button lists merging them all down
		// into one list. plugins loaded later can override plugins loaded
		// earlier if the aliases collide. this is on purpose.

		$Sections->RemapKeyValue(function(string $Key) use($Profile, $Plugins, $ExtraData) {
			return $Plugins->Compile(
				fn(Common\Datastore $C, AdminMenuSectionInterface $S)
				=> $C->MergeRight($S->GetItemsForSection( $Profile, $Key, $ExtraData ) ?? [])
			);
		});

		// allow plugins to audit menu items in case they wanted to replace
		// or remove something.

		$Audits->Each(function(AdminMenuAuditInterface $Audit) use($Profile, $Sections, $ExtraData) {
			$Audit->AuditItems($Profile, $Sections, $ExtraData);
			return;
		});

		// cook the buttons into the admin menu.

		$Sections->EachKeyValue(function(string $Key, Common\Datastore $Items) use($AdminMenu) {

			if(!$Items->Count())
			return;

			////////

			if($Key === 'danger') {
				$AdminMenu->Items->Push(Atlantis\Struct\DropdownItem::New(Title: '~'));
				$AdminMenu->Items->Push(Atlantis\Struct\DropdownItem::New(Title: '-'));
			}

			else {
				$AdminMenu->Items->Push(Atlantis\Struct\DropdownItem::New(Title: '~'));
			}

			////////

			$AdminMenu->Items->MergeRight($Items);

			return;
		});

		////////

		return $AdminMenu;
	}


}
