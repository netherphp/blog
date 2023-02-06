<?php

namespace Nether\Blog\Routes;

use Nether\Atlantis;
use Nether\Avenue;
use Nether\Blog;
use Nether\Common;

class BlogHandler
extends Atlantis\PublicWeb {

	#[Avenue\Meta\RouteHandler('/+:BlogAlias:')]
	#[Avenue\Meta\RouteHandler('/blog/:BlogAlias:')]
	#[Avenue\Meta\ConfirmWillAnswerRequest]
	public function
	Index(string $BlogAlias):
	void {

		$Blog = Blog\Blog::GetByField('Alias', $BlogAlias);
		$Posts = $Blog->GetRecentPosts();

		// clearly not really the popular posts atm.
		$Popular = $Posts->Distill(
			fn(Blog\Post $Post, int $Key)
			=> $Key < 4
		);

		$this->Surface->Area('blog/index', [
			'Blog'         => $Blog,
			'Posts'        => $Posts,
			'Popular'      => $Popular
		]);

		return;
	}

	protected function
	IndexWillAnswerRequest(string $BlogAlias):
	int {

		$Blog = Blog\Blog::GetByField('Alias', $BlogAlias);

		////////

		if(!$Blog)
		return Avenue\Response::CodeNotFound;

		////////

		return Avenue\Response::CodeOK;
	}

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	#[Avenue\Meta\RouteHandler('/+:BlogAlias:/:PostID:')]
	#[Avenue\Meta\RouteHandler('/+:BlogAlias:/:PostID:/:PostAlias:')]
	#[Avenue\Meta\RouteHandler('/blog/:BlogAlias:/:PostID:')]
	#[Avenue\Meta\RouteHandler('/blog/:BlogAlias:/:PostID:/:PostAlias:')]
	#[Avenue\Meta\ConfirmWillAnswerRequest]
	public function
	ViewPost(string $BlogAlias, int $PostID, ?string $PostAlias=NULL):
	void {

		$Post = Blog\Post::GetByID($PostID);

		$this->Surface->Area('blog/view', [
			'Blog' => $Post->Blog,
			'Post' => $Post
		]);

		return;
	}

	protected function
	ViewPostWillAnswerRequest(string $BlogAlias, int $PostID, ?string $PostAlias=NULL):
	int {

		$Post = Blog\Post::GetByID($PostID);
		$Blog = Blog\Blog::GetByField('Alias', $BlogAlias);

		// first thing handle if the requested post was even found
		// anywhere at all.

		if(!$Post)
		return Avenue\Response::CodeNotFound;

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

		return Avenue\Response::CodeOK;
	}

}
