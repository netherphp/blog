<?php

namespace Nether\Blog;

use Nether\Atlantis;
use Nether\Common;
use Nether\Database;
use Nether\User;

use Exception;

#[Database\Meta\TableClass('Blogs', 'BL')]
class Blog
extends Atlantis\Prototype {

	#[Database\Meta\TypeIntBig(Unsigned: TRUE, AutoInc: TRUE)]
	#[Database\Meta\PrimaryKey]
	public int
	$ID;

	#[Database\Meta\TypeChar(Size: 36)]
	public string
	$UUID;

	#[Database\Meta\TypeChar(Size: 64, Variable: TRUE)]
	#[Common\Meta\PropertyPatchable]
	#[Common\Meta\PropertyFilter(['Nether\\Common\\Datafilters', 'TrimmedText'])]
	public string
	$Alias;

	#[Database\Meta\TypeChar(Size: 64, Variable: TRUE)]
	#[Common\Meta\PropertyPatchable]
	#[Common\Meta\PropertyFilter(['Nether\\Common\\Datafilters', 'TrimmedText'])]
	public string
	$Title;

	#[Database\Meta\TypeChar(Size: 64, Variable: TRUE)]
	#[Common\Meta\PropertyPatchable]
	#[Common\Meta\PropertyFilter(['Nether\\Common\\Datafilters', 'TrimmedText'])]
	public ?string
	$Tagline;

	#[Database\Meta\TypeText]
	#[Common\Meta\PropertyPatchable]
	#[Common\Meta\PropertyFilter(['Nether\\Common\\Datafilters', 'TrimmedText'])]
	public ?string
	$Details;

	#[Database\Meta\TypeIntBig(Unsigned: TRUE, Default: NULL)]
	#[Database\Meta\ForeignKey('Uploads', 'ID')]
	#[Common\Meta\PropertyPatchable]
	#[Common\Meta\PropertyFilter(['Nether\\Common\\Datafilters', 'TypeIntNullable'])]
	public ?int
	$ImageHeaderID;

	#[Database\Meta\TypeIntBig(Unsigned: TRUE, Default: NULL)]
	#[Database\Meta\ForeignKey('Uploads', 'ID')]
	#[Common\Meta\PropertyPatchable]
	#[Common\Meta\PropertyFilter(['Nether\\Common\\Datafilters', 'TypeIntNullable'])]
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
	#[Common\Meta\PropertyFilter(['Nether\\Common\\Datafilters', 'TypeInt'])]
	public int
	$OptAdult;

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	public ?Atlantis\Media\File
	$ImageHeader = NULL;

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
	GetRecentPosts(int $Page=1, int $Limit=10, bool $Admin=FALSE):
	Database\Struct\PrototypeFindResult {

		return Post::Find([
			'BlogID'  => $this->ID,
			'Enabled' => $Admin ? NULL : 1,
			'Page'    => $Page,
			'Limit'   => $Limit,
			'Sort'    => 'newest'
		]);
	}

	public function
	GetURL():
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

		return $Output;
	}

	public function
	GetWriteURL():
	string {

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
	GetHeaderURL():
	string {

		if($this->ImageHeader)
		return $this->ImageHeader->GetPublicURL();

		return '';
	}

	public function
	GetIconURL():
	string {

		if($this->ImageIcon)
		return $this->ImageIcon->GetPublicURL();

		return '';
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
	JoinExtendTables(Database\Verse $SQL, string $JAlias='Main', ?string $TPre=NULL):
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
	JoinExtendFields(Database\Verse $SQL, ?string $TPre=NULL):
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
		$Dataset['Alias'] = Common\Datafilters::SlottableKey($Dataset['Title']);

		if(!$Dataset['Alias'])
		throw new Exception('blog must have a valid alias');

		////////

		return parent::Insert($Dataset);
	}

}
