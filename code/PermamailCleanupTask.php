<?php

/**
 * Removes all {@link SentEmail} records up to a specific timestamp,
 * for example, everything but the last 30 days.
 *
 * Accepts two parameters: "count" and "unit", e.g. ?count=30&unit=days
 *
 * @author  Uncle Cheese <unclecheese@leftandmain.com>
 * @package  silverstripe-permamail
 */
class PermamailCleanupTask extends BuildTask {

	/**
	 * @var string $title Shown in the overview on the {@link TaskRunner}
	 * HTML or CLI interface. Should be short and concise, no HTML allowed.
	 */
	protected $title = 'Permamail clean-up task';
	
	/**
	 * @var string $description Describe the implications the task has,
	 * and the changes it makes. Accepts HTML formatting.
	 */
	protected $description = 'Removes old SentEmail records. Takes two parameters: <em>count</em> and <em>unit</em>, 
							  where <em>count</em> is the number of units of time to go back before truncating 
							  the records. E.g. <em>?count=30&unit=days</em> will keep the last 30 days of emails.';
	
	/**
 	 * Execute the task	 
	 */
	public function run($request) {
		$units = array ('seconds', 'hours', 'minutes', 'days', 'weeks', 'months', 'years');
		$unit = $request->requestVar('unit');
		if(!$unit || !in_array($unit, $units)) {
			throw new RuntimeException("Please specify a 'unit' parameter. Possible values: ".implode(', ', $units));
		}

		$count = $request->requestVar('count');
		if(!$count) {
			throw new RuntimeException("Please specify a 'count' parameter for the number of $unit you want to to keep.");
		}

		$stamp = strtotime("-{$count} $unit");
		$date = date('Y-m-d H:i:s');

		$count = SentEmail::get()->filter(array(
			'Created:LessThan' => $date
		))->count();
		
		DB::query("DELETE FROM \"SentEmail\" WHERE Created < '$date'");

		echo "Deleted $count records.";
	}

}