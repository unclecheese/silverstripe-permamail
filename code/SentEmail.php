<?php

/**
 * Defines a record that stores an email that was sent via {@link Permamail}
 *
 * @author Uncle Cheese <unclecheese@leftandmain.com>
 * @package  silverstripe-permamail
 */
class SentEmail extends DataObject {

	private static $db = array (
		'To' => 'Varchar',
		'From' => 'Varchar',
		'Subject' => 'Varchar',
		'Body' => 'HTMLText',
		'CC' => 'Text',
		'BCC' => 'Text',
		'SerializedEmail' => 'Text'
	);

	private static $summary_fields = array (
		'Created.Nice' => 'Date',
		'To' => 'To',
		'Subject' => 'Subject'
	);

	private static $default_sort = 'Created DESC';

	/**
	 * Defines a list of methods that can be invoked by BetterButtons custom actions
	 * @var array
	 */
	private static $better_buttons_actions = array (
		'resend'
	);

	/**
	 * Gets a list of actions for the ModelAdmin interface
	 * @return FieldList
	 */
	public function getBetterButtonsActions() {
		$fields = parent::getBetterButtonsActions();
		$fields->push(BetterButtonCustomAction::create('resend','Resend')
			->setSuccessMessage('Resent')
			->setRedirectType(BetterButtonCustomAction::REFRESH)
		);
		
		return $fields;
	}

	/**
	 * Gets a list of form fields for editing the record.
	 * These records should never be edited, so a readonly list of fields
	 * is forced.
	 * 
	 * @return FieldList
	 */
	public function getCMSFields() {
		preg_match("/<body[^>]*>(.*?)<\/body>/is", $this->Body, $matches);
		$contents = $matches ? $matches[1] : "";

		$f = FieldList::create(
			ReadonlyField::create('To'),
			ReadonlyField::create('Subject'),
			ReadonlyField::create('BCC'),
			ReadonlyField::create('CC'),
			HeaderField::create('Email contents', 5),
			LiteralField::create('BodyContents', "<div class='field'>{$contents}</div>")
		);

		return $f;
	}

	/**
	 * Gets the {@link Permamail} object that was used to send this email
	 * @return Permamail
	 */
	public function getEmail() {
		if($this->SerializedEmail) {
			return unserialize($this->SerializedEmail);
		}

		return false;
	}

	/**
	 * A BetterButtons custom action that allows the email to be resent	 
	 */
	public function resend() {
		if($e = $this->getEmail()) {
			return $e->send();
		}
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
		return false;
	}

	/**
	 * Defines the create permission
	 * @param  Member $member
	 * @return boolean
	 */
	public function canCreate($member = null) {
		return false;
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