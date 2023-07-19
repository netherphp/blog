<?php

namespace Nether\Blog;

use ArrayAccess;
use Nether\Atlantis;
use Nether\Avenue;
use Nether\Common;
use Nether\Database;
use Nether\User;

use Exception;

#[Database\Meta\TableClass('BlogPosts', 'BP')]
class Post
extends Atlantis\Prototype {

	#[Database\Meta\TypeIntBig(Unsigned: TRUE)]
	#[Database\Meta\ForeignKey('Blogs', 'ID')]
	public int
	$BlogID;

	#[Database\Meta\TypeIntBig(Unsigned: TRUE)]
	#[Database\Meta\ForeignKey('Users', 'ID')]
	public int
	$UserID;

	#[Database\Meta\TypeIntBig(Unsigned: TRUE)]
	#[Database\Meta\ForeignKey('Uploads', 'ID')]
	#[Common\Meta\PropertyPatchable]
	#[Common\Meta\PropertyFilter([ Common\Datafilters::class, 'TypeIntNullable' ])]
	public ?int
	$CoverImageID;

	#[Database\Meta\TypeIntBig(Unsigned: TRUE, Default: NULL)]
	public int
	$TimeCreated;

	#[Database\Meta\TypeIntBig(Unsigned: TRUE, Default: NULL)]
	public int
	$TimeUpdated;

	#[Database\Meta\TypeIntBig(Unsigned: TRUE, Default: NULL)]
	#[Database\Meta\FieldIndex]
	public int
	$TimeSorted;

	#[Database\Meta\TypeIntTiny(Default: 0, Nullable: FALSE)]
	#[Common\Meta\PropertyPatchable]
	#[Common\Meta\PropertyFilter([ Common\Datafilters::class, 'TypeInt' ])]
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

	#[Database\Meta\TypeVarChar(Size: 255)]
	#[Common\Meta\PropertyListable]
	public string
	$Alias;

	#[Database\Meta\TypeVarChar(Size: 255)]
	#[Common\Meta\PropertyListable]
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
	#[Common\Meta\PropertyListable]
	#[Common\Meta\PropertyPatchable]
	#[Common\Meta\PropertyFilter(['Nether\\Common\\Datafilters','TypeInt'])]
	public int
	$OptAdult;

	#[Database\Meta\TypeIntTiny(Default: 0, Nullable: FALSE)]
	#[Common\Meta\PropertyListable]
	#[Common\Meta\PropertyPatchable]
	#[Common\Meta\PropertyFilter(['Nether\\Common\\Datafilters','TypeInt'])]
	public int
	$OptComments;

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	#[Database\Meta\TableJoin('BlogID', Extend: TRUE)]
	#[Common\Meta\PropertyListable]
	public Blog
	$Blog;

	#[Database\Meta\TableJoin('UserID', Extend: TRUE)]
	public User\Entity
	$User;

	#[Database\Meta\TableJoin('CoverImageID')]
	public Atlantis\Media\File
	$CoverImage;

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	#[Common\Meta\PropertyFactory('FromTime', 'TimeCreated')]
	public Common\Date
	$DateCreated;

	#[Common\Meta\PropertyFactory('FromTime', 'TimeUpdated')]
	public Common\Date
	$DateUpdated;

	#[Common\Meta\PropertyFactory('FromTime', 'TimeSorted')]
	public Common\Date
	$DateSorted;

	////////////////////////////////////////////////////////////////
	// Common\Prototype Overloads //////////////////////////////////

	protected function
	OnReady(Common\Prototype\ConstructArgs $Args):
	void {

		if($Args->InputHas('BL_ID'))
		$this->Blog = Blog::FromPrefixedDataset($Args->Input, 'BL_');

		if($Args->InputHas('U_ID'))
		$this->User = User\Entity::FromPrefixedDataset($Args->Input, 'U_');

		if($Args->InputHas('UP_ID'))
		$this->CoverImage = Atlantis\Media\File::FromPrefixedDataset($Args->Input, 'UP_');

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
		$Output['Content'] = match($this->Editor) {
			'link'
			=> Struct\EditorLink::New(
				$Input['Title'],
				$Input['Date'],
				$Input['URL'],
				$Input['Excerpt'],
				$Input['Content']
			),

			default
			=> $Input['Content']
		};

		return $Output;
	}

	public function
	Update(iterable $Dataset):
	static {

		$Dataset = (array)$Dataset;

		////////

		if(array_key_exists('Content', $Dataset))
		$Dataset['ContentHTML'] = $this->ParseContent($Dataset['Content']);

		////////

		return parent::Update($Dataset);
	}

	////////////////////////////////////////////////////////////////
	// Atlantis\Prototype Overloads ////////////////////////////////

	public function
	DescribeForPublicAPI():
	array {

		$Data = parent::DescribeForPublicAPI();

		return $Data;
	}

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	public function
	GetExcerpt(int $Len=100):
	string {

		$Output = match($this->Editor) {
			'editorjs'
			=> $this->GetExcerptFromJSON($Len),

			'link'
			=> $this->GetExcerptFromLink($Len),

			default
			=> $this->GetExcerptFromHTML($Len)
		};

		return $Output;
	}

	protected function
	GetExcerptFromJSON(int $Len=100):
	string {

		return '';
	}

	protected function
	GetExcerptFromLink(int $Len=100):
	string {

		$Link = Struct\EditorLink::FromJSON($this->Content);

		$Output = preg_replace('#<[Bb][Rr] ?/?>#', ' ', $Link->Excerpt);
		$Bits = explode(' ', strip_tags($Output), ($Len + 1));
		$Output = join(' ', array_slice($Bits, 0, $Len));

		if(count($Bits) > $Len)
		if(!str_ends_with($Output, '.'))
		$Output .= '...';

		return $Output;
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

	public function
	GetCoverImageURL(string $Size='md'):
	?string {

		$URL = NULL;

		if(isset($this->CoverImage)) {
			$URL = $this->CoverImage->GetPublicURL();
			$URL = str_replace('original.', "{$Size}.", $URL);
			return $URL;
		}

		if(isset($this->Blog->ImageHeader)) {
			$URL = $this->Blog->ImageHeader->GetPublicURL();
			$URL = str_replace('original.', "{$Size}.", $URL);
			return $URL;
		}

		return NULL;
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
	ParseContent(?string $Input=NULL, ?string $Editor=NULL):
	?string {

		$Input ??= $this->Content;
		$Editor ??= $this->Editor;

		////////

		return match($Editor) {
			'json'
			=> $this->ParseContentAsJSON($Input),

			'link'
			=> $this->ParseContentAsLink($Input),

			default
			=> $this->ParseContentAsHTML($Input)
		};
	}

	public function
	ParseContentAsJSON(string $Input):
	string {

		// ...

		return '';
	}

	public function
	ParseContentAsLink(string $Input):
	string {

		ob_start();
		Common\Dump::Var($Input, TRUE);
		$Output = ob_get_clean();

		return $Output;
	}

	public function
	ParseContentAsHTML(string $Input):
	string {

		$Output = $Input;

		// ...

		return $Output;
	}

	public function
	UpdateHTML():
	static {

		$this->Update([
			'ContentHTML' => $this->ParseContent()
		]);

		return $this;
	}

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	#[Common\Meta\Deprecated('2023-07-19', 'TableJoin attribute replaced this')]
	static public function
	_JoinExtendTables(Database\Verse $SQL, string $JAlias='Main', ?string $TPre=NULL):
	void {

		$Table = static::GetTableInfo();
		$TPre = $Table->GetPrefixedAlias($TPre);
		$JAlias = $Table->GetPrefixedAlias($JAlias);

		Blog::JoinMainTables($SQL, $JAlias, 'BlogID', $TPre);
		Blog::JoinExtendTables($SQL, $TPre, $TPre);

		User\Entity::JoinMainTables($SQL, $JAlias, 'UserID', $TPre);
		User\Entity::JoinExtendTables($SQL, $TPre, $TPre);

		Atlantis\Media\File::JoinMainTables($SQL, $JAlias, 'CoverImageID', $TPre);


		return;
	}

	#[Common\Meta\Deprecated('2023-07-19', 'TableJoin attribute replaced this')]
	static public function
	_JoinExtendFields(Database\Verse $SQL, ?string $TPre=NULL):
	void {

		$Table = static::GetTableInfo();
		$TPre = $Table->GetPrefixedAlias($TPre);

		Blog::JoinMainFields($SQL, $TPre);
		Blog::JoinExtendFields($SQL, $TPre);

		User\Entity::JoinMainFields($SQL, $TPre);
		User\Entity::JoinExtendFields($SQL, $TPre);

		Atlantis\Media\File::JoinMainFields($SQL, $TPre);

		return;
	}

	static protected function
	FindExtendOptions(Common\Datastore $Input):
	void {

		$Input
		->Define('BlogID', NULL)
		->Define('Enabled', 1)
		->Define('Sort', 'newest')
		->Define('Schedule', TRUE);

		return;
	}

	static protected function
	FindExtendFilters(Database\Verse $SQL, Common\Datastore $Input):
	void {

		parent::FindExtendFilters($SQL, $Input);

		if($Input['BlogID'] !== NULL)
		$SQL->Where('Main.BlogID=:BlogID');

		if($Input['Enabled'] !== NULL)
		$SQL->Where('Main.Enabled=:Enabled');

		if($Input['Schedule'] !== NULL) {
			if($Input['Schedule'] === TRUE) {
				$SQL->Where('Main.TimeSorted <= :TimeSortedSchedule');
				$Input[':TimeSortedSchedule'] = Common\Date::CurrentUnixtime();
			}
		}

		return;
	}

	static protected function
	FindExtendSorts(Database\Verse $SQL, Common\Datastore $Input):
	void {

		switch($Input['Sort']) {
			case 'newest':
				$SQL->Sort('Main.TimeSorted', $SQL::SortDesc);
				break;
			break;
			case 'oldest':
				$SQL->Sort('Main.TimeSorted', $SQL::SortAsc);
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

		$Now = Common\Date::CurrentUnixtime();
		$UUID = Common\UUID::V7();
		$Post = NULL;

		$Data = new Common\Datastore($Input);
		$Data->BlendRight([
			'UUID'        => $UUID,
			'UserID'      => NULL,
			'BlogID'      => NULL,
			'Title'       => NULL,
			'Alias'       => NULL,
			'TimeCreated' => $Now,
			'TimeUpdated' => $Now,
			'TimeSorted'  => $Now
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

		$Post = parent::Insert($Data);
		$Post->UpdateHTML();

		return $Post;
	}

}
