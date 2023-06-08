<?php

namespace Nether\Blog;

use Nether\Atlantis;
use Nether\User;
use Nether\Common;
use Nether\Database;

use Exception;

#[Database\Meta\TableClass('BlogUsers', 'BU')]
class BlogUser
extends Atlantis\Prototype {

	// admin can do anything
	// editor can edit/delete anything but not change settings.
	// writer can only edit their own posts.

	#[Database\Meta\TypeIntBig(Unsigned: TRUE)]
	#[Database\Meta\ForeignKey('Blogs', 'ID')]
	public int
	$BlogID;

	#[Database\Meta\TypeIntBig(Unsigned: TRUE)]
	#[Database\Meta\ForeignKey('Users', 'ID')]
	public int
	$UserID;

	#[Database\Meta\TypeIntTiny(Unsigned: TRUE, Default: 0)]
	public int
	$Admin;

	#[Database\Meta\TypeIntTiny(Unsigned: TRUE, Default: 0)]
	public int
	$Editor;

	#[Database\Meta\TypeIntTiny(Unsigned: TRUE, Default: 0)]
	public int
	$Writer;

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	public Blog
	$Blog;

	public User\Entity
	$User;

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	protected function
	OnReady(Common\Prototype\ConstructArgs $Args):
	void {

		if($Args->InputExists('BL_ID'))
		$this->Blog = Blog::FromPrefixedDataset($Args->Input, 'BL_');

		if($Args->InputExists('U_ID'))
		$this->User = User\Entity::FromPrefixedDataset($Args->Input, 'U_');

		return;
	}

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	public function
	CanAdmin():
	bool {

		return $this->Admin === 1;
	}

	public function
	CanEdit():
	bool {

		return (
			FALSE
			|| $this->Editor === 1
			|| $this->Admin === 1
		);
	}

	public function
	CanWrite():
	bool {

		return (
			FALSE
			|| $this->Writer === 1
			|| $this->Editor === 1
			|| $this->Admin === 1
		);
	}

	public function
	SetAsAdmin():
	static {

		$this->Update([
			'Admin'  => 1,
			'Editor' => 1,
			'Writer' => 1
		]);

		return $this;
	}

	public function
	SetAsEditor():
	static {

		$this->Update([
			'Admin'  => 0,
			'Editor' => 1,
			'Writer' => 1
		]);

		return $this;
	}

	public function
	SetAsWriter():
	static {

		$this->Update([
			'Admin'  => 0,
			'Editor' => 0,
			'Writer' => 1
		]);

		return $this;
	}

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	static public function
	GetByPair(int $BlogID, int $UserID):
	?static {

		$Table = static::GetTableInfo();
		$DBM = new Database\Manager;

		$Opt = new Common\Datastore([
			':BlogID' => $BlogID,
			':UserID' => $UserID
		]);

		$SQL = (
			($DBM->NewVerse(static::$DBA))
			->Select("{$Table->Name} Main")
			->Fields('Main.*')
			->Where('Main.BlogID=:BlogID AND Main.UserID=:UserID')
			->Limit(1)
		);

		static::FindExtendTables($SQL, $Opt);

		$Result = $SQL->Query($Opt->GetData());

		if(!$Result->IsOK())
		throw new Exception($Result->GetError());

		////////

		$Row = $Result->Next();

		if(!$Row)
		return NULL;

		////////

		return new static((array)$Row);
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

		//Common\Dump::Var($SQL, TRUE);

		return;
	}

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	static protected function
	FindExtendOptions(Common\Datastore $Input):
	void {

		$Input
		->Define('BlogID', NULL)
		->Define('UserID', NULL)
		->Define('Writer', NULL)
		->Define('Editor', NULL)
		->Define('Admin', NULL);

		return;
	}

	static protected function
	FindExtendFilters(Database\Verse $SQL, Common\Datastore $Input):
	void {

		if($Input['UserID'] !== NULL)
		$SQL->Where('Main.UserID=:UserID');

		if($Input['BlogID'] !== NULL)
		$SQL->Where('Main.BlogID=:BlogID');

		if($Input['Writer'] !== NULL)
		$SQL->Where('Main.Writer=:Writer');

		return;
	}

}

