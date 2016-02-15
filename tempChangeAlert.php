#!/usr/bin/php
<?php
if (PHP_SAPI !== 'cli')
{
	die();
}
/**
 * File       tempChangeAlert.php
 * Created    2/27/15 3:03 PM
 * Author     Matt Thomas | matt@betweenbrain.com | http://betweenbrain.com
 * Support    https://github.com/betweenbrain/
 * Copyright  Copyright (C) 2015 betweenbrain llc. All Rights Reserved.
 * License    GNU GPL v2 or later
 */

$helper = new Helper;
$helper->checkTemps();

// Display only fatal errors
error_reporting(1);
error_reporting(E_ERROR);

/**
 * Class helper
 */
class Helper
{
	private $currentTemp;
	private $lastTemp;

	/**
	 * Constructor: sets global config
	 */
	function __construct()
	{
		$this->setConfig();
		$this->tempFile = dirname(__FILE__) . '/last_temp';
	}

	/**
	 * Read the config file and sets its values accordingly
	 */
	private function setConfig()
	{
		foreach (json_decode(file_get_contents(dirname(__FILE__) . '/config.json')) as $name => $value)
		{
			$this->{$name} = $value;
		}
	}

	/**
	 * Checks and compares the current versus last recorded temp
	 */
	public function checkTemps()
	{
		if ($this->getCurrentTemp())
		{
			$this->compareTemps();
		}
	}

	/**
	 * Returns the current temp
	 *
	 * @return mixed
	 */
	public function returnCurrentTemp()
	{
		if ($this->getCurrentTemp())
		{
			return $this->currentTemp;
		}
	}

	/**
	 * Compares the last recorded temp and the current one
	 */
	private function compareTemps()
	{
		$this->getLastTemp();

		if ($this->currentTemp > $this->targetTemp && $this->lastTemp < $this->targetTemp)
		{
			$this->sendEmail("The current temperature is $this->currentTemp and has exceed the target threshold. Turn on the heater cables.", $this->recipient, 'Temp Change Alert');
		}

		if ($this->currentTemp < $this->targetTemp && $this->lastTemp > $this->targetTemp)
		{
			$this->sendEmail("The current temperature is $this->currentTemp and has fallen below the target threshold. Turn off the heater cables.", $this->recipient, 'Temp Change Alert');
		}

		$this->setLastTemp();
	}

	/**
	 * Gets the current temperature
	 */
	private function getCurrentTemp()
	{
		$url = 'http://api.openweathermap.org/data/2.5/weather?q=' . $this->city . '&units=imperial';

		if ($this->apiKey)
		{
			$url = $url . '&APPID=' . $this->apiKey;
		}

		$response = json_decode(file_get_contents($url));

		if ($response)
		{
			$this->currentTemp = $response->main->temp;

			return true;
		}
	}

	/**
	 * @param $message
	 * @param $recipient
	 * @param $subject
	 */
	private function sendEmail($message, $recipient, $subject)
	{
		$headers = 'From: webmaster@example.com' . "\r\n" .
			'Reply-To: webmaster@example.com' . "\r\n" .
			'X-Mailer: PHP/' . phpversion();

		mail($recipient, $subject, $message, $headers);

	}

	/**
	 * Gets the last temperature retrieved
	 */
	private function getLastTemp()
	{
		$this->lastTemp = (file_exists($this->tempFile)) ? file_get_contents($this->tempFile) : $this->currentTemp;
	}

	/**
	 * Stores the most recent temp for later use
	 */
	private function setLastTemp()
	{
		/**
		 * Store last temp
		 */
		file_put_contents($this->tempFile, $this->currentTemp);
	}
}
