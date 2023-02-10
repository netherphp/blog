<?php

namespace Nether\Blog;

use Nether\Atlantis;
use Nether\Common;
use Nether\Database;
use Nether\User;

use Exception;

#[Database\Meta\TableClass('Blogs', 'BL')]
class Blog
extends Database\Prototype {

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
	#[Common\Meta\PropertyPatchable]
	#[Common\Meta\PropertyFilter(['Nether\\Common\\Datafilters', 'TypeIntNullable'])]
	public ?int
	$ImageHeaderID;

	#[Database\Meta\TypeIntBig(Unsigned: TRUE, Default: NULL)]
	public ?string
	$ImageHeaderURL;

	#[Database\Meta\TypeIntBig(Unsigned: TRUE, Default: NULL)]
	#[Common\Meta\PropertyPatchable]
	#[Common\Meta\PropertyFilter(['Nether\\Common\\Datafilters', 'TypeIntNullable'])]
	public ?int
	$ImageIconID;

	#[Database\Meta\TypeIntBig(Unsigned: TRUE, Default: NULL)]
	public ?string
	$ImageIconURL;

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

	public ?Atlantis\Struct\FileUpload
	$ImageHeader = NULL;

	public ?Atlantis\Struct\FileUpload
	$ImageIcon = NULL;

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	protected function
	OnReady(Common\Prototype\ConstructArgs $Args):
	void {

		if($Args->InputHas('IH_ID'))
		$this->ImageHeader = Atlantis\Struct\FileUpload::FromPrefixedDataset($Args->Input, 'IH_');

		$this->ImageHeaderURL = $this->ImageHeader->GetPublicURL();

		if($Args->InputHas('II_ID'))
		$this->ImageIcon = Atlantis\Struct\FileUpload::FromPrefixedDataset($Args->Input, 'II_');

		return;
	}

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	public function
	GetRecentPosts(int $Page=1):
	Database\Struct\PrototypeFindResult {

		return Post::Find([
			'BlogID' => $this->ID,
			'Page'   => $Page,
			'Sort'   => 'newest'
		]);
	}

	public function
	GetURL():
	string {

		$Format = '/+:BlogAlias:';

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

		$Format = '/+:BlogAlias:/:PostID:/:PostAlias:';

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


		Atlantis\Struct\FileUpload::JoinMainTables($SQL, $JAlias, 'ImageHeaderID', $TPre, 'IH');
		Atlantis\Struct\FileUpload::JoinMainTables($SQL, $JAlias, 'ImageIconID', $TPre, 'II');

		//Common\Dump::Var($SQL, TRUE);

		return;
	}

	static public function
	JoinExtendFields(Database\Verse $SQL, ?string $TPre=NULL):
	void {

		$Table = static::GetTableInfo();
		$TPre = $Table->GetPrefixedAlias($TPre);

		Atlantis\Struct\FileUpload::JoinMainFields($SQL, $TPre, 'IH');
		Atlantis\Struct\FileUpload::JoinMainFields($SQL, $TPre, 'II');

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
		$Dataset['Alias'] = Common\Datafilters::PathableKeySingle($Dataset['Title']);

		if(!$Dataset['Alias'])
		throw new Exception('blog must have a valid alias');

		////////

		return parent::Insert($Dataset);
	}

}
