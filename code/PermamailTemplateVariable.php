<?php


class PermamailTemplateVariable extends DataObject {

	private static $db = array (
		'Variable' => 'Varchar',
		'ValueType' => "Enum('static,random,query')",
		'RecordClass' => 'Varchar',
		'Value' => 'Varchar',
		'Query' => 'Varchar',
		'List' => 'Boolean'
	);


	private static $has_one = array (
		'PermamailTemplate' => 'PermamailTemplate'
	);


	private static $summary_fields = array (
		'Variable' => 'Variable name',
		'ValueType' => 'Value type'
	);


	public function getCMSFields() {
		if($this->List) {
			$opts = array(				
				'random' => 'A random list of objects',
				'query' => 'A specific list of objects'
			);
		}
		else {
			$opts = array(
				'static' => 'A static value',
				'random' => 'A random object',
				'query' => 'A specific object'
			);			
		}

		$map = ArrayLib::valuekey(
			ClassInfo::subclassesFor('DataObject')
		);
		unset($map['DataObject']);
		foreach($map as $k => $class) {
			if(ClassInfo::classImplements($class, 'TestOnly')) {
				unset($map[$class]);
			}
		}
		ksort($map);

		$f = FieldList::create(TabSet::create('Root'));
		$f->addFieldToTab('Root.Main', ReadonlyField::create('Variable','Variable name'));
		$f->addFieldToTab('Root.Main', OptionsetField::create('ValueType', 'Value type', $opts));
		$f->addFieldToTab('Root.Main', 
			DropdownField::create('RecordClass','Object type',$map )
			->displayIf('ValueType')
				->isEqualTo('random')
			->orIf('ValueType')
				->isEqualTo('query')
			->end()
		);
		$f->addFieldToTab('Root.Main', TextField::create('Value')
			->displayIf('ValueType')->isEqualTo('static')->end()
		);
		$f->addFieldToTab('Root.Main', TextField::create('Query','Query string')
				->displayIf('ValueType')->isEqualTo('query')->end()
				->setDescription('E.g. Name:StartsWith=Uncle&Status=Awesome')
		);

		return $f;
	}


	public function getVariableValue() {
		if($this->ValueType == 'static') {
			return $this->Value;
		}

		if($this->ValueType == 'random') {
			$list = DataList::create($this->RecordClass)->sort('RAND()');
			return $this->List ? $list->limit(5) : $list->first();
		}

		if($this->ValueType == 'query') {			
			parse_str($this->Query, $vars);			
			$list = DataList::create($this->RecordClass)->filter($vars);
			return $this->List ? $list->limit(5) : $list->first();
		}
	}


	public function canEdit($member = null) {
		return Permission::check("ADMIN");
	}

	public function canView($member = null) {
		return Permission::check("ADMIN");
	}

	public function canDelete($member = null) {
		return false;
	}

	public function canCreate($member = null) {
		return false;
	}

}