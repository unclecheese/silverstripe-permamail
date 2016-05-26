<?php

/**
 * A user-defined email template. HTML is editable in the CMS using
 * a Javascript-driven code editor. Templates can have default subjects.
 *
 * @author  Uncle Cheese <unclecheese@leftandmain.com>
 * @package  silverstripe-permamail
 */
class PermamailTemplate extends DataObject {

	private static $db = array (
		'Identifier' => 'Varchar',
		'Subject' => 'Varchar(255)',
		'From' => 'Varchar',
		'Content' => 'HTMLText',
		'TestEmailAddress' => 'Varchar'
	);

	private static $has_many = array (
		'TestVariables' => 'PermamailTemplateVariable'
	);

	private static $indexes = array (
		'Identifier' => true
	);

	private static $summary_fields = array (
		'Identifier' => 'Template name',
		'Subject' => 'Default subject'
	);

	private static $defaults = array (
		'Content' => "<html>\n<body>\n\n</body>\n</html>"
	);

	private static $singular_name = 'Email template';

	private static $plural_name = 'Email templates';

	private static $better_buttons_actions = array (
		'testemail'
	);

	/**
	 * Gets a PermamailTemplate object by its identifier
	 * @param  string $id
	 * @return PermamailTemplate
	 */
	public static function get_by_identifier($id) {
		return PermamailTemplate::get()->filter('Identifier', $id)->first();
	}

	public function getBetterButtonsActions() {
		$f = parent::getBetterButtonsActions();
		$f->push(BetterButtonCustomAction::create('testemail','Send a test')
			->setRedirectType(BetterButtonCustomAction::REFRESH)			
		);

		return $f;
	}

	/**
	 * Gets the {@link FieldList} for editing the record
	 * @return FieldList
	 */
	public function getCMSFields() {
		// Requirements for Ace editor
		Requirements::javascript(PERMAMAIL_DIR.'/javascript/ace/ace.js');
		Requirements::javascript(PERMAMAIL_DIR.'/javascript/ace/theme-chrome.js');
		Requirements::javascript(PERMAMAIL_DIR.'/javascript/ace/mode-html.js');
		Requirements::javascript(PERMAMAIL_DIR.'/javascript/jquery-ace.js');
		Requirements::javascript(PERMAMAIL_DIR.'/javascript/ace-init.js');
		
		$fields = FieldList::create(TabSet::create("Root"));
		$fields->addFieldToTab('Root.Main', TextField::create('Identifier','Template name (no spaces, alphanumeric characters only)'));
		$fields->addFieldToTab('Root.Main', TextField::create('Subject','Default subject (optional)'));
		$fields->addFieldToTab('Root.Main', TextField::create('From','Default "from" address (optional)'));
		$fields->addFieldToTab('Root.Main',
			TextareaField::create('Content', 'Template content')
				->addExtraClass('ace')
				->setRows(30)
				->setColumns(100)
				->setFieldHolderTemplate('PermamailTemplateEditor')
		);

		$fields->addFieldsToTab("Root.Tests", array (
			EmailField::create('TestEmailAddress','Test email address'),
			GridField::create('TestVariables','Test variables', $this->TestVariables(), GridFieldConfig_RecordEditor::create())
		));

		return $fields;
	}

	/**
	 * Send a test email to the given address, populating it with the given values
	 * for all template variables
	 */
	public function testemail() {
		$vars = array ();
		foreach($this->TestVariables() as $v) {
			$vars[$v->Variable] = $v->getVariableValue();
		}		
		$to = $this->TestEmailAddress ?: Config::inst()->get('Email', 'admin_email');
		$e = Permamail::create()
			->setTo($to)
			->setSubject($this->Subject)
			->setUserTemplate($this->Identifier)
			->populateTemplate($vars)
			->send();

		return 'Test email sent to ' . $this->TestEmailAddress;
	}

	/**
	 * Sanitise the identifier and populate the variables list
	 */
	public function onBeforeWrite() {
		parent::onBeforeWrite();
		$slug = singleton('SiteTree')->generateURLSegment($this->Identifier);
		$original_slug = $slug;
		$i = 0;
		while($t = PermamailTemplate::get()
			->filter(array("Identifier" => "$slug"))
			->exclude(array("ID" => $this->ID))
			->first()
		) {	
			$i++;
			$slug = $original_slug."-{$i}";
		}
		$this->Identifier = $slug;

		$reflector = EmailReflectionTemplate::create();
		$reflector->process($this->Content);

		$vars = array ();
		foreach($reflector->getTopLevelVars() as $var => $type) {
			$vars[$var] = false;
		}
		foreach($reflector->getTopLevelBlocks() as $block) {
			$vars[$block->getName()] = $block->isLoop();
		}
		// Remove any variables that are no longer in the template
		if($this->TestVariables()->exists()) {
			$this->TestVariables()->exclude(array('Variable' => array_keys($vars)))->removeAll();
		}
		
		$currentVars = $this->TestVariables()->column('Variable');

		foreach($vars as $var => $isList) {
			if(!in_array($var, $currentVars)) {
				$v = PermamailTemplateVariable::create(array(
					'Variable' => $var,
					'PermamailTemplateID' => $this->ID,
					'List' => $isList
				));
				$v->write();
			}
		}
	}


	/**
	 * Better than a numeric ID...
	 * @return string
	 */
	public function getTitle() {
		return $this->Identifier;
	}
	

	/**
	 * Defines the view permission
	 * @param  Member $member
	 * @return boolean
	 */
	public function canView($member = null) {
		return Permission::check('CMS_ACCESS_CMSMain');
	}

	/**
	 * Defines the edit permission
	 * @param  Member $member
	 * @return boolean
	 */
	public function canEdit($member = null) {
		return Permission::check('CMS_ACCESS_CMSMain');
	}

	/**
	 * Defines the create permission
	 * @param  Member $member
	 * @return boolean
	 */
	public function canCreate($member = null) {
		return Permission::check('CMS_ACCESS_CMSMain');
	}

	/**
	 * Defines the delete permission
	 * @param  Member $member
	 * @return boolean
	 */
	public function canDelete($member = null) {
		return Permission::check('CMS_ACCESS_CMSMain');
	}

}