<?php

namespace Nether\Blog\Struct;

use Nether\Atlantis;
use Nether\Blog;
use Nether\Common;

class BlogPostPluginData
extends Common\Prototype {

	#[Common\Meta\PropertyFactory('FromArray', 'Create')]
	#[Common\Meta\PropertyListable]
	public array|Common\Datastore
	$Create = [];

	#[Common\Meta\PropertyFactory('FromArray', 'Update')]
	#[Common\Meta\PropertyListable]
	public array|Common\Datastore
	$Update = [];

	#[Common\Meta\PropertyFactory('FromArray', 'Save')]
	#[Common\Meta\PropertyListable]
	public array|Common\Datastore
	$Save = [];

	#[Common\Meta\PropertyFactory('FromArray', 'Values')]
	#[Common\Meta\PropertyListable]
	public array|Common\Datastore
	$Values = [];

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	public function
	ToArray():
	array {

		return [
			'Create' => $this->Create,
			'Update' => $this->Update,
			'Save'   => $this->Save,
			'Values' => $this->Values
		];
	}

	public function
	ToJSON():
	string {

		return Common\Filters\text::Tabbify(json_encode(
			$this->ToArray(),
			JSON_PRETTY_PRINT
		));
	}

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	public function
	Count():
	int {

		return (0
			+ $this->Create->Count()
			+ $this->Update->Count()
			+ $this->Save->Count()
			+ $this->Values->Count()
		);
	}

	public function
	Encode():
	string {

		return static::EncodeObject($this);
	}

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	public function
	GetValues(Atlantis\Engine $App):
	Common\Datastore {

		$Output = new Common\Datastore;

		////////

		($this->Values)
		->Each(function(mixed $VData, mixed $Class) use($App, $Output) {
			($App->Plugins)
			->Get(Blog\Plugins\BlogPostEditorValuesInterface::class)
			->Filter(fn(string $C)=> $C === $Class)
			->Map(fn(string $C)=> new $C)
			->Each(
				fn(Blog\Plugins\BlogPostEditorValuesInterface $Plugin)
				=> $Output->MergeRight($Plugin->GetValues(
					$App,
					Common\Datastore::FromArray($VData ?: []),
					NULL
				))
			);

			return;
		});

		////////

		return $Output;
	}

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	static public function
	FromEncoded(?string $Data):
	static {

		return static::DecodeString($Data);
	}

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	static public function
	EncodeObject(self $Obj):
	string {

		$Output = Common\Filters\Text::Base64Encode(
			json_encode($Obj->ToArray())
		);

		return $Output;
	}

	static public function
	DecodeString(?string $Encoded):
	static {

		$Data = Common\Filters\Text::DatasetFromJSON(
			Common\Filters\Text::Base64Decode($Encoded ?: '')
		);

		return new static($Data);
	}

}
