<?php

namespace Nether\Blog;

use Nether\Atlantis;
use Nether\Common;
use Nether\Database;
use Nether\User;

use Exception;

#[Database\Meta\TableClass('Blogs', 'BL')]
class Blog
extends Atlantis\Prototype
implements Atlantis\Interfaces\ExtraDataInterface {

	const
	EntType = 'Blog.Entity';

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	#[Database\Meta\TypeChar(Size: 64, Variable: TRUE)]
	#[Common\Meta\PropertyListable]
	#[Common\Meta\PropertyPatchable]
	#[Common\Meta\PropertyFilter([ Common\Filters\Text::class , 'Trimmed'])]
	public string
	$Alias;

	#[Database\Meta\TypeChar(Size: 64, Variable: TRUE)]
	#[Common\Meta\PropertyListable]
	#[Common\Meta\PropertyPatchable]
	#[Common\Meta\PropertyFilter([ Common\Filters\Text::class , 'Trimmed'])]
	public string
	$Title;

	#[Database\Meta\TypeChar(Size: 64, Variable: TRUE)]
	#[Common\Meta\PropertyListable]
	#[Common\Meta\PropertyPatchable]
	#[Common\Meta\PropertyFilter([ Common\Filters\Text::class , 'Trimmed'])]
	public ?string
	$Tagline;

	#[Database\Meta\TypeText]
	#[Common\Meta\PropertyListable]
	#[Common\Meta\PropertyPatchable]
	#[Common\Meta\PropertyFilter([ Common\Filters\Text::class , 'Trimmed'])]
	public ?string
	$Details;

	#[Database\Meta\TypeIntBig(Unsigned: TRUE, Default: NULL)]
	#[Database\Meta\ForeignKey('Uploads', 'ID')]
	#[Common\Meta\PropertyPatchable]
	#[Common\Meta\PropertyFilter([ Common\Filters\Numbers::class, 'IntNullable'])]
	public ?int
	$ImageHeaderID;

	#[Database\Meta\TypeIntBig(Unsigned: TRUE, Default: NULL)]
	#[Database\Meta\ForeignKey('Uploads', 'ID')]
	#[Common\Meta\PropertyPatchable]
	#[Common\Meta\PropertyFilter([ Common\Filters\Numbers::class, 'IntNullable'])]
	public ?int
	$ImageIconID;

	#[Database\Meta\TypeIntBig(Unsigned: TRUE, Default: 0)]
	public int
	$TimeCreated;

	#[Database\Meta\TypeIntBig(Unsigned: TRUE, Default: 0)]
	public int
	$TimeUpdated;

	#[Database\Meta\TypeIntBig(Unsigned: TRUE, Default: 0)]
	public int
	$CountPosts;

	#[Database\Meta\TypeIntBig(Unsigned: TRUE, Default: 0)]
	public int
	$CountViews;

	#[Database\Meta\TypeIntBig(Unsigned: TRUE, Default: 0)]
	public int
	$CountComments;

	#[Database\Meta\TypeIntBig(Unsigned: TRUE, Default: 0)]
	public int
	$CountImages;

	#[Database\Meta\TypeIntBig(Unsigned: TRUE, Default: 0)]
	public int
	$CountCodeBlocks;

	#[Database\Meta\TypeIntBig(Unsigned: TRUE, Default: 0)]
	public int
	$CountReadingTime;

	#[Database\Meta\TypeIntTiny(Unsigned: TRUE, Default: 0)]
	#[Common\Meta\PropertyPatchable]
	#[Common\Meta\PropertyFilter([ Common\Filters\Numbers::class, 'IntType' ])]
	public int
	$OptAdult;

	use
	Atlantis\Packages\ExtraData;

	////////

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	#[Database\Meta\TableJoin('ImageHeaderID', 'IH')]
	#[Common\Meta\PropertyListable]
	public ?Atlantis\Media\File
	$ImageHeader = NULL;

	#[Database\Meta\TableJoin('ImageIconID', 'II')]
	#[Common\Meta\PropertyListable]
	public ?Atlantis\Media\File
	$ImageIcon = NULL;

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	protected function
	OnReady(Common\Prototype\ConstructArgs $Args):
	void {

		if($Args->InputHas('IH_ID'))
		$this->ImageHeader = Atlantis\Media\File::FromPrefixedDataset($Args->Input, 'IH_');

		if($Args->InputHas('II_ID'))
		$this->ImageIcon = Atlantis\Media\File::FromPrefixedDataset($Args->Input, 'II_');

		return;
	}

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	public function
	DescribeForPublicAPI():
	array {

		$Data = parent::DescribeForPublicAPI();

		return $Data;

		return [
			'ID'             => $this->ID,
			'URL'            => $this->GetURL(),
			'Title'          => $this->Title,
			'Blog'           => $this->Tagline,
			'Details'        => $this->Details,
			'ImageIconURL'   => NULL,
			'ImageHeaderURL' => NULL
		];
	}

	public function
	GetRecentPosts(int $Page=1, int $Limit=10, ?bool $Drafts=FALSE, bool $SiteTags=TRUE, iterable $MoreTags=NULL, ?string $SearchTitle=NULL, ?string $SearchDate=NULL):
	Database\ResultSet {

		$Tags = new Common\Datastore;

		if($SiteTags) {
			$Tags = Util::FetchSiteTags();
			$Tags->Remap(fn(Atlantis\Tag\Entity $T)=> $T->ID);
		}

		if($MoreTags) {
			$Tags->MergeLeft($MoreTags);
		}

		return Post::Find([
			'TagID'       => $Tags->Count() ? $Tags : NULL,
			'BlogID'      => $this->ID,
			'SearchTitle' => $SearchTitle,
			'SearchDate'  => $SearchDate,
			'Enabled'     => $Drafts === NULL ? $Drafts : (int)$Drafts,
			'Schedule'    => $Drafts ? NULL : TRUE,
			'Page'        => $Page,
			'Limit'       => $Limit,
			'Sort'        => 'newest'
		]);
	}

	public function
	GetPageURL():
	string {

		$Format = Library::Get(Library::ConfBlogURL);

		$Tokens = [
			':BlogID:'    => $this->ID,
			':BlogAlias:' => $this->Alias
		];

		$Output = $Format;
		$Token = NULL;
		$Value = NULL;

		foreach($Tokens as $Token => $Value)
		$Output = str_replace($Token, $Value, $Output);

		return $Output;
	}

	public function
	GetPostURL(Post $Post):
	string {

		$Format = Library::Get(Library::ConfPostURL);
		$STags = $Post->GetTags(Atlantis\Tag\Entity::KeepThoseTypedSite(...));

		////////

		$Tokens = [
			':BlogID:'    => $this->ID,
			':BlogAlias:' => $this->Alias,
			':PostID:'    => $Post->ID,
			':PostAlias:' => $Post->Alias
		];

		$Output = $Format;
		$Token = NULL;
		$Value = NULL;

		foreach($Tokens as $Token => $Value)
		$Output = str_replace($Token, $Value, $Output);

		////////

		return Atlantis\Util::RewriteURL($Output, $STags);
	}

	public function
	GetBlockEditorURL():
	string {

		return sprintf(
			'/dashboard/blog/editor/%s',
			$this->UUID
		);
	}

	public function
	GetWriteURL():
	string {

		$Mode = Library::Get(Key::ConfEditorDefault);

		if($Mode === 'new')
		return sprintf(
			'/dashboard/blog/editor/%s',
			$this->UUID
		);

		return sprintf(
			'/dashboard/blog/write?id=%d',
			$this->ID
		);
	}

	public function
	GetSettingsURL():
	string {

		return sprintf(
			'/dashboard/blog/settings?id=%d',
			$this->ID
		);
	}

	public function
	GetManageUsersURL():
	string {

		return sprintf(
			'/dashboard/blog/users/%s',
			$this->UUID
		);
	}

	public function
	GetHeaderURL(string $Size='lg'):
	string {

		if($this->ImageHeader)
		return str_replace(
			'original.', "{$Size}.",
			$this->ImageHeader->GetPublicURL()
		);

		if($this->ImageHeaderID) {
			$this->ImageHeader = Atlantis\Media\File::GetByID(
				$this->ImageHeaderID
			);

			return $this->ImageHeader->GetPublicURL();
		}

		return '';
	}

	public function
	GetHeaderStyleBG():
	string {

		$URL = $this->GetHeaderURL();

		////////

		if(!$URL)
		return '';

		return sprintf('background-image: url(%s);', $URL);
	}

	public function
	GetIconURL(string $Size='md'):
	string {

		if($this->ImageIcon)
		return str_replace(
			'original.', "{$Size}.",
			$this->ImageIcon->GetPublicURL()
		);

		if($this->ImageIconID) {
			$this->ImageIcon = Atlantis\Media\File::GetByID(
				$this->ImageIconID
			);

			return $this->ImageIcon->GetPublicURL();
		}

		return '';
	}

	public function
	GetIconStyleBG():
	string {

		$URL = $this->GetIconURL();

		////////

		if(!$URL)
		return '';

		return sprintf('background-image: url(%s);', $URL);
	}

	public function
	UpdateTags():
	static {

		$Tag = NULL;

		// first fetch an index of all the tags this blog already has
		// assigned to it.

		$Old = array_flip(
			$this
			->GetTags()
			->Filter(fn(Atlantis\Tag\Entity $T)=> $T->Type === 'blog')
			->Remap(fn(Atlantis\Tag\Entity $T)=> $T->ID)
			->GetData()
		);

		// now fetch a list of all the tags used by posts on this blog.

		$New = PostTagLink::Find([
			'BlogID'     => $this->ID,
			'Remappers'  => PostTagLink::KeepTheTag(...),
			'GroupByTag' => TRUE
		]);

		// add new tags.

		foreach($New as $Tag) {
			/** @var Atlantis\Tag\Entity $Tag */

			// if we already have this tag.

			if(array_key_exists($Tag->ID, $Old)) {
				unset($Old[$Tag->ID]);
				continue;
			}

			// we do not yet have this tag.

			BlogTagLink::Insert([
				'TagID'      => $Tag->ID,
				'EntityUUID' => $this->UUID
			]);
		}

		// remove stale tags.

		foreach(array_flip($Old) as $Tag)
		BlogTagLink::DeleteByPair($Tag, $this->UUID);

		return $this;
	}

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	public function
	GetUser(int $UserID):
	?BlogUser {

		return BlogUser::GetByPair($this->ID, $UserID);
	}

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	static public function
	_JoinExtendTables(Database\Verse $SQL, string $JAlias='Main', ?string $TPre=NULL):
	void {

		$Table = static::GetTableInfo();
		$TPre = $Table->GetPrefixedAlias($TPre);
		$JAlias = $Table->GetPrefixedAlias($JAlias);


		Atlantis\Media\File::JoinMainTables($SQL, $JAlias, 'ImageHeaderID', $TPre, 'IH');
		Atlantis\Media\File::JoinMainTables($SQL, $JAlias, 'ImageIconID', $TPre, 'II');

		//Common\Dump::Var($SQL, TRUE);

		return;
	}

	static public function
	_JoinExtendFields(Database\Verse $SQL, ?string $TPre=NULL):
	void {

		$Table = static::GetTableInfo();
		$TPre = $Table->GetPrefixedAlias($TPre);

		Atlantis\Media\File::JoinMainFields($SQL, $TPre, 'IH');
		Atlantis\Media\File::JoinMainFields($SQL, $TPre, 'II');

		return;
	}

	static public function
	Insert(iterable $Input):
	?static {

		$Now = time();
		$UUID = Common\UUID::V7();
		$Defines = [
			'UUID'        => $UUID,
			'TimeCreated' => $Now,
			'TimeUpdated' => $Now,

			'Title'       => NULL,
			'Alias'       => NULL
		];

		$Dataset = Common\Datastore::NewMerged($Defines, $Input);

		////////

		if(!$Dataset['Title'])
		throw new Exception('blog must have a title');

		if(!$Dataset['Alias'])
		$Dataset['Alias'] = Common\Filters\Text::SlottableKey($Dataset['Title']);

		if(!$Dataset['Alias'])
		throw new Exception('blog must have a valid alias');

		////////

		return parent::Insert($Dataset);
	}

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	#[Common\Meta\Deprecated('2024-06-26', 'use GetPageURL() instead.')]
	public function
	GetURL():
	string {

		return $this->GetPageURL();
	}

}
