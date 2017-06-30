<?php

/**
 * A wrapper for the core Email class that adds functionality for
 * a few features, including user-defined templates, but most of all
 * captures the send() method call to make a write to the database.
 *
 * By default, this class is injected to override the core Email class
 * so that Email::create() can be used in lieu of `new Permamail()`. If
 * there is a case where Permamail should only be used on certain emails,
 * you can overload the config to not inject this class into Email and 
 * use Permamail::create() (or `new Permamail()`) instead on a case-by-case
 * basis.
 *
 * @author  Uncle Cheese <unclecheese@leftandmain.com>
 * @package  silverstripe-permamail
 * 
 */
class Permamail extends Email {

	/**
	 * The identifier of the user-defined template to use
	 * @var string
	 */
	protected $userTemplate;

	/**
	 * A list of {@link Member} objectsthat will receive the email
	 * @var array|SS_List
	 */
	protected $members;

	/**
	 * Persists a {@link SentEmail} record to the database
	 * @return SentEmail
	 */
	protected function persist() {
		$record = SentEmail::create(array(
			'To' => $this->To(),
			'From' => $this->From(),
			'Subject' => $this->Subject(),
			'Body' => $this->Body(),
			'CC' => $this->CC(),
			'BCC' => $this->BCC(),
			'SerializedEmail' => serialize($this)
		));		
		$record->write();

		return $record;
	}

	/**
	 * Send the email to a list of members
	 * @param  array|SS_List $members 
	 * @return Permamail
	 */
	public function toMembers($members) {
		if($members instanceof Member) {
			$this->members = new ArrayList(array($members));
		}
		elseif(is_array($members) || $members instanceof SS_List) {
			$this->members = $members;
		}

		return $this;
	}

	/**
	 * Sends the a plain or HTML version of the email
	 * @param  int  $messageID 
	 * @param  boolean $plain     	 
	 */
	protected function doSend($messageID = null, $plain = false) {

		$this->extend('onBeforeDoSend', $this);

		if(!$this->Subject() && $this->getUserTemplate()) {
			$this->setSubject($this->getUserTemplate()->Subject);
		}

		$from = $this->From();
		$config = Config::inst()->forClass('Email');

		if(!$from) {
			if($this->getUserTemplate()) {
				$from = $this->getUserTemplate()->From;
			}
			if(!$from) {
				$from = $config->send_all_emails_from ?: $config->admin_email;
			}

			$this->setFrom($from);
		}


		// Check if a list of Member objects has been given
		if($this->members) {
			foreach($this->members as $m) {
				$this->setTo($m->Email);
				$this->populateTemplate(array(
					'RecipientMember' => $m
				));
			}
		}

		if($this->config()->test_mode) {
			$this->parseVariables($plain);
		}
		else {
			if($plain) {
				parent::sendPlain($messageID);
			}
			else {
				parent::send($messageID);
			}
		}

		$this->extend('onAfterDoSend', $this);

		$this->persist();
	}

	/**
	 * Sends an HTML email 
	 * @param  int $messageID
	 */
	public function send($messageID = null) {
		$this->doSend($messageID, false);
	}

	/**
	 * Sends a plain text email
	 * @param  int $messageID 	 
	 */
	public function sendPlain($messageID = null) {
		$this->doSend($messageID, true);
	}

	/**
	 * Sets the user-defined template to use
	 * @param string $identifier The identifier of the {@link PermamailTemplate}
	 * @return  Permamail
	 */
	public function setUserTemplate($identifier) {
		$template = PermamailTemplate::get_by_identifier($identifier);
		if(!$template) {
			$template = PermamailTemplate::create(array(
				'Identifier' => $identifier,
			));
			$template->write();
		}
		
		$this->userTemplate = $identifier;

		return $this;
	}

	/**
	 * Gets the {@link PermamailTemplate} by its identifier
	 * @return PermamailTemplate
	 */
	public function getUserTemplate() {
		if($this->userTemplate) {
			return PermamailTemplate::get_by_identifier($this->userTemplate);
		}

		return false;
	}

	/**
	 * Overload the Email::parseVariables() method to use a user-defined template
	 * @param  boolean $isPlain
	 * @return Permamail
	 */
	public function parseVariables($isPlain = false) {
		$origState = Config::inst()->get('SSViewer', 'source_file_comments');
		Config::inst()->update('SSViewer', 'source_file_comments', false);
		$userTemplate = $this->getUserTemplate();
		
		if(!$this->parseVariables_done) {
			$this->parseVariables_done = true;

			// Parse $ variables in the base parameters
			$data = $this->templateData();
			
			// Process a .SS template file
			$fullBody = $this->body;
			if($userTemplate || $this->ss_template && !$isPlain) {
				// Requery data so that updated versions of To, From, Subject, etc are included
				$data = $this->templateData();
				
				$template = $userTemplate ? SSViewer::fromString($userTemplate->Content) : new SSViewer($this->ss_template);
				
				if($template instanceof SSViewer_FromString || $template->exists()) {
					$fullBody = $template->process($data);
				}
			}
			
			// Rewrite relative URLs
			$this->body = HTTP::absoluteURLs($fullBody);			
		}
		Config::inst()->update('SSViewer', 'source_file_comments', $origState);

		return $this;
	}
}
