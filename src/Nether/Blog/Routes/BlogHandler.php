<?php

namespace Nether\Blog\Routes;

use Nether\Atlantis;
use Nether\Avenue;
use Nether\Blog;
use Nether\Common;

class BlogHandler
extends Atlantis\PublicWeb {

	#[Avenue\Meta\RouteHandler('@Nether.Blog.BlogURL')]
	#[Avenue\Meta\ConfirmWillAnswerRequest]
	public function
	Index(string $BlogAlias):
	void {

		($this->Data)
		->Page(Common\Filters\Numbers::Page(...))
		->Drafts(Common\Filters\Numbers::BoolNullable(...));

		$Blog = NULL;
		$BlogUser = NULL;
		$Posts = NULL;
		$ShowingDrafts = FALSE;

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

		$Posts = $Blog->GetRecentPosts(
			Page: $this->Data->Page,
			Drafts: $ShowingDrafts
		);

		// clearly not really the popular posts atm.
		$Popular = $Posts->Distill(
			fn(Blog\Post $Post, int $Key)
			=> $Key < 4
		);

		////////


		($this->Surface)
		->Set('Page.Title', $Blog->Title)
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
	public function
	ViewPost(string $BlogAlias, int $PostID, ?string $PostAlias, Blog\Post $Post):
	void {

		$BlogUser = NULL;
		$PostPhotos = NULL;
		$PostVideos = NULL;

		////////

		if($this->User)
		$BlogUser = $Post->Blog->GetUser($this->User->ID);

		$PostPhotos = $Post->FetchPhotos();
		$PostVideos = $Post->FetchVideos();

		////////

		($this->Surface)
		->Set('Page.ImageURL', new Atlantis\WebURL($Post->GetCoverImageURL('lg') ?? ''))
		->Set('Page.Title', sprintf(
			'%s - %s',
			$Post->Title,
			$Post->Blog->Title
		))
		->Area('blog/view', [
			'Blog'     => $Post->Blog,
			'Post'     => $Post,
			'Photos'   => $PostPhotos,
			'Videos'   => $PostVideos,
			'BlogUser' => $BlogUser
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

		$ExtraData['Post'] = $Post;

		return Avenue\Response::CodeOK;
	}

}
