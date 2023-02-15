<?php

namespace Nether\Blog;

use ArrayAccess;
use Nether\Avenue;
use Nether\Common;
use Nether\Database;
use Nether\User;

use Exception;

#[Database\Meta\TableClass('BlogPosts', 'BP')]
class Post
extends Database\Prototype {

	#[Database\Meta\TypeIntBig(Unsigned: TRUE, AutoInc: TRUE)]
	#[Database\Meta\PrimaryKey]
	public int
	$ID;

	#[Database\Meta\TypeIntBig(Unsigned: TRUE)]
	#[Database\Meta\ForeignKey('Blogs', 'ID')]
	public int
	$BlogID;

	#[Database\Meta\TypeIntBig(Unsigned: TRUE)]
	#[Database\Meta\ForeignKey('Users', 'ID')]
	public int
	$UserID;

	#[Database\Meta\TypeIntBig(Unsigned: TRUE)]
	#[Common\Meta\PropertyPatchable]
	#[Common\Meta\PropertyFilter(['Nether\\Common\\Datafilters','TypeIntNullable'])]
	public int
	$ImageID;

	#[Database\Meta\TypeIntBig(Unsigned: TRUE)]
	public int
	$TimeCreated;

	#[Database\Meta\TypeIntBig(Unsigned: TRUE)]
	public int
	$TimeUpdated;

	#[Database\Meta\TypeIntTiny(Default: 0, Nullable: FALSE)]
	#[Common\Meta\PropertyPatchable]
	#[Common\Meta\PropertyFilter(['Nether\\Common\\Datafilters','TypeInt'])]
	public int
	$Enabled;

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
	$CountCodeblocks;

	#[Database\Meta\TypeIntBig(Unsigned: TRUE, Default: 0)]
	public int
	$TimeToRead;

	#[Database\Meta\TypeChar(Size: 36)]
	public string
	$UUID;

	#[Database\Meta\TypeVarChar(Size: 255)]
	public string
	$Alias;

	#[Database\Meta\TypeVarChar(Size: 255)]
	#[Common\Meta\PropertyPatchable]
	#[Common\Meta\PropertyFilter(['Nether\\Common\\Datafilters','TrimmedText'])]
	public string
	$Title;

	#[Database\Meta\TypeVarChar(Size: 32, Default: 'html')]
	public string
	$Editor;

	#[Database\Meta\TypeText]
	#[Common\Meta\PropertyPatchable]
	#[Common\Meta\PropertyFilter(['Nether\\Common\\Datafilters','TrimmedText'])]
	public string
	$Content;

	#[Database\Meta\TypeText]
	public string
	$ContentHTML;

	#[Database\Meta\TypeIntTiny(Default: 0, Nullable: FALSE)]
	#[Common\Meta\PropertyPatchable]
	#[Common\Meta\PropertyFilter(['Nether\\Common\\Datafilters','TypeInt'])]
	public int
	$OptAdult;

	#[Database\Meta\TypeIntTiny(Default: 0, Nullable: FALSE)]
	#[Common\Meta\PropertyPatchable]
	#[Common\Meta\PropertyFilter(['Nether\\Common\\Datafilters','TypeInt'])]
	public int
	$OptComments;

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	public Blog
	$Blog;

	public User\Entity
	$User;

	public Common\Date
	$DateCreated;

	public Common\Date
	$DateUpdated;

	////////////////////////////////////////////////////////////////
	// Common\Prototype Overloads //////////////////////////////////

	protected function
	OnReady(Common\Prototype\ConstructArgs $Args):
	void {

		$this->DateCreated = Common\Date::FromTime($this->TimeCreated);
		$this->DateUpdated = Common\Date::FromTime($this->TimeUpdated);

		if($Args->InputHas('BL_ID'))
		$this->Blog = Blog::FromPrefixedDataset($Args->Input, 'BL_');

		if($Args->InputHas('U_ID'))
		$this->User = User\Entity::FromPrefixedDataset($Args->Input, 'U_');

		return;
	}

	////////////////////////////////////////////////////////////////
	// Database\Prototype Overloads ////////////////////////////////

	public function
	Patch(array|ArrayAccess $Input):
	array {

		$Output = parent::Patch($Input);

		if(array_key_exists('Title', $Output))
		$Output['Alias'] = Common\Datafilters::SlottableKey($Output['Title']);

		if(array_key_exists('Content', $Output))
		$Output['ContentHTML'] = $this->ParseContent($Output['Content']);

		return $Output;
	}

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	public function
	GetExcerpt():
	string {

		$Output = match($this->Editor) {
			'editorjs'
			=> $this->GetExcerptFromJSON(),

			default
			=> $this->GetExcerptFromHTML()
		};

		return $Output;
	}

	protected function
	GetExcerptFromJSON():
	string {

		return '';
	}

	protected function
	GetExcerptFromHTML(int $Len=100):
	string {

		$Output = preg_replace('#<[Bb][Rr] ?/?>#', ' ', $this->Content);

		$Bits = explode(' ', strip_tags($Output), ($Len + 1));
		$Output = join(' ', array_slice($Bits, 0, $Len));

		if(count($Bits) > $Len)
		if(!str_ends_with($Output, '.'))
		$Output .= '...';

		return $Output;
	}

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	public function
	GetURL():
	string {

		return $this->Blog->GetPostURL($this);
	}

	public function
	GetEditURL():
	string {

		return sprintf(
			'/dashboard/blog/edit?id=%d',
			$this->ID
		);
	}

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	public function
	CanUserEdit(BlogUser $Who):
	bool {

		if($Who->UserID !== $this->UserID)
		return $Who->CanEdit();

		return $Who->CanWrite();
	}

	public function
	ParseContent(string $Input):
	?string {

		if($this->Editor === 'json')
		return $this->ParseContentAsJSON($Input);

		return $this->ParseContentAsHTML($Input);
	}

	public function
	ParseContentAsJSON(string $Input):
	string {

		// ...

		return '';
	}

	public function
	ParseContentAsHTML(string $Input):
	string {

		$Output = $Input;

		// ...

		return $Output;
	}

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	static public function
	JoinExtendTables(Database\Verse $SQL, string $JAlias='Main', ?string $TPre=NULL):
	void {

		$Table = static::GetTableInfo();
		$TPre = $Table->GetPrefixedAlias($TPre);
		$JAlias = $Table->GetPrefixedAlias($JAlias);

		Blog::JoinMainTables($SQL, $JAlias, 'BlogID', $TPre);
		Blog::JoinExtendTables($SQL, $TPre, $TPre);

		User\Entity::JoinMainTables($SQL, $JAlias, 'UserID', $TPre);
		User\Entity::JoinExtendTables($SQL, $TPre, $TPre);

		return;
	}

	static public function
	JoinExtendFields(Database\Verse $SQL, ?string $TPre=NULL):
	void {

		$Table = static::GetTableInfo();
		$TPre = $Table->GetPrefixedAlias($TPre);

		Blog::JoinMainFields($SQL, $TPre);
		Blog::JoinExtendFields($SQL, $TPre);

		User\Entity::JoinMainFields($SQL, $TPre);
		User\Entity::JoinExtendFields($SQL, $TPre);

		return;
	}

	static protected function
	FindExtendOptions(Common\Datastore $Input):
	void {

		($Input)
		->Define('BlogID', NULL)
		->Define('Sort', 'newest');

		return;
	}

	static protected function
	FindExtendFilters(Database\Verse $SQL, Common\Datastore $Input):
	void {

		if($Input['BlogID'] !== NULL)
		$SQL->Where('Main.BlogID=:BlogID');

		return;
	}

	static protected function
	FindExtendSorts(Database\Verse $SQL, Common\Datastore $Input):
	void {

		switch($Input['Sort']) {
			case 'newest':
				$SQL->Sort('Main.TimeCreated', $SQL::SortDesc);
				break;
			break;
			case 'oldest':
				$SQL->Sort('Main.TimeCreated', $SQL::SortAsc);
				break;
			break;
		}

		return;
	}

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	static public function
	Insert(iterable $Input):
	?static {

		$Now = time();

		$Data = new Common\Datastore($Input);
		$Data->BlendRight([
			'UserID'      => NULL,
			'BlogID'      => NULL,
			'Title'       => NULL,
			'Alias'       => NULL,
			'TimeCreated' => $Now,
			'TimeUpdated' => $Now
		]);

		////////

		if(!$Data['UserID'])
		throw new Error\PostMissingData('UserID');

		if(!$Data['BlogID'])
		throw new Error\PostMissingData('BlogID');

		if(!$Data['Title'])
		throw new Error\PostMissingData('Title');

		////////

		if(!$Data['Alias'])
		$Data['Alias'] = Avenue\Util::MakeKey($Data['Title']);
		else
		$Data['Alias'] = Avenue\Util::MakeKey($Data['Alias']);

		if(!$Data['Alias'])
		$Data['Alias'] = Common\UUID::V7();

		////////

		return parent::Insert($Data);
	}

}