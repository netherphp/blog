<?php

namespace Nether\Blog\Struct;

use Nether\Blog;
use Nether\Common;

use Stringable;
use Exception;

class EditorLink
extends Common\Prototype
implements Stringable {

	#[Common\Meta\PropertyListable]
	#[Common\Meta\PropertyPatchable]
	#[Common\Meta\PropertyFilter([ Common\Datafilters::class, 'TrimmedText' ])]
	public string
	$Title;

	#[Common\Meta\PropertyListable]
	#[Common\Meta\PropertyPatchable]
	#[Common\Meta\PropertyFilter([ Common\Datafilters::class, 'TrimmedText' ])]
	public string
	$Date;

	#[Common\Meta\PropertyListable]
	#[Common\Meta\PropertyPatchable]
	#[Common\Meta\PropertyFilter([ Common\Datafilters::class, 'TrimmedText' ])]
	public string
	$URL;

	#[Common\Meta\PropertyListable]
	#[Common\Meta\PropertyPatchable]
	#[Common\Meta\PropertyFilter([ Common\Datafilters::class, 'TrimmedText' ])]
	public string
	$Excerpt;

	#[Common\Meta\PropertyListable]
	#[Common\Meta\PropertyPatchable]
	#[Common\Meta\PropertyFilter([ Common\Datafilters::class, 'TrimmedText' ])]
	public string
	$Content;

	////////////////////////////////////////////////////////////////
	// implements Stringable ///////////////////////////////////////

	public function
	__ToString():
	string {

		$Props = array_map(
			(fn(Common\Prototype\PropertyInfo $P)=> $this->{$P->Name}),
			Common\Meta\PropertyListable::FromClass(static::class)
		);

		return json_encode($Props);
	}

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	protected function
	OnReady(Common\Prototype\ConstructArgs $Args):
	void {

		if(strlen($this->Content) < 32)
		if(trim(strip_tags($this->Content)) === '')
		$this->Content = '';

		return;
	}

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	public function
	GetTime():
	int {

		$Date = Common\Date::FromDateString($this->Date);

		return $Date->GetUnixtime();
	}

	public function
	GetExcerpt(int $Len=100):
	string {

		$Output = preg_replace('#<[Bb][Rr] ?/?>#', ' ', $this->Excerpt);
		$Bits = explode(' ', strip_tags($Output), ($Len + 1));
		$Output = join(' ', array_slice($Bits, 0, $Len));

		if(count($Bits) > $Len)
		if(!str_ends_with($Output, '.'))
		$Output .= '...';

		return $Output;
	}

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	static public function
	FromJSON(string $JSON):
	static {

		$Data = json_decode($JSON, TRUE);

		if(!is_array($Data))
		throw new Exception('failed to parse json');

		return new static($Data);
	}

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	static public function
	New(string $Title=NULL, string $Date=NULL, string $URL=NULL, string $Excerpt=NULL, string $Content=NULL):
	static {

		$Props = Common\Meta\PropertyPatchable::FromClass(static::class);
		$Name = NULL;
		$Filters = NULL;
		$Func = NULL;

		////////

		foreach($Props as $Name => $Filters)
		foreach($Filters as $Func)
		$$Name = $Func($$Name);

		////////

		return new static([
			'Title'   => $Title,
			'Date'    => $Date,
			'URL'     => $URL,
			'Excerpt' => $Excerpt,
			'Content' => $Content
		]);
	}

}
