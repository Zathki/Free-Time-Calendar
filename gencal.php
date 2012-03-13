<?php

set_include_path('library/'.get_include_path());

require_once 'Zend/Loader.php';

Zend_Loader::loadClass('Zend_Gdata');
Zend_Loader::loadClass('Zend_Gdata_AuthSub');
Zend_Loader::loadClass('Zend_Gdata_ClientLogin');
Zend_Loader::loadClass('Zend_Gdata_HttpClient');
Zend_Loader::loadClass('Zend_Gdata_Calendar');

$USERNAME = "<GOOGLE ACCOUNT USERNAME>";
$PASSWORD = "<GOOGLE ACCOUNT PASSWORD>";

$CALENDARS_TO_CHECK = array("<NAME OF SPECIFIC CALENDAR IN GOGLE CALENDAR TO USE>");

$DAYS_OFFSET_FROM_TODAY = 0;
$NUMBER_OF_DAYS = 30;
$LIMIT = 7;
$TENTATIVE_PLAN_ENDING_STRING = "?";

$INTERVAL = 300; //5 minutes

function processPageLoad()
{
  global $USERNAME;
  global $PASSWORD;
  global $CALENDARS_TO_CHECK;
  global $DAYS_OFFSET_FROM_TODAY;
  global $NUMBER_OF_DAYS;
  global $TENTATIVE_PLAN_ENDING_STRING;
  global $LIMIT;

  $client = getClientLoginHttpClient($USERNAME, $PASSWORD);
  $calFeed = getCalendars($client);
  
  $startDate = getDay($DAYS_OFFSET_FROM_TODAY); //today
  $endDate = getDay($DAYS_OFFSET_FROM_TODAY + $NUMBER_OF_DAYS); //a week from now
  
  $eventFeeds = array();
  foreach ($calFeed as $calendar) {
    if(in_array($calendar->title->text, $CALENDARS_TO_CHECK))
	  array_push($eventFeeds, getCalendarEvents($client, $calendar->content->src, $startDate, $endDate));
  }
  
  $availability = array();
  
  $i = 0;
  echo "<ul style='list-style-type: none; margin-bottom: 2px; padding-left: 0px'>";
  foreach ($eventFeeds as $eventFeed) {
    foreach($eventFeed as $event) {
		foreach ($event->when as $when) {
		  $day = date("l jS", strtotime($when->startTime.' +1 hours'));
		  $startTime = date("g:ia", strtotime($when->startTime.' +1 hours'));
		  $endTime = date("g:ia", strtotime($when->endTime.' +1 hours'));
		  echo "<li style='padding-bottom: 2px'><div style=\"font: bold 14px 'Century Gothic',Verdana, Arial; color: #333\"><b>$day</b></div>";
		  echo "<div style='font: 10px Verdana; color: #777'>$startTime - $endTime</div></li>";
		}
		$i++;
		if($i > $LIMIT)
		  break;
	  }
  }
  echo "</ul>";
  
  echo "<a target='_blank' style='color: #2361A1; font-size:10px;' href='https://www.google.com/calendar/embed?src=the.kidd.is%40gmail.com&ctz=America/Toronto'>Calendar</a>";
}

function getDay($offset)
{
	return mktime(0, 0, 0, date("m"), date("d")+$offset, date("Y"));
}

function getClientLoginHttpClient($user, $pass)
{
  $service = Zend_Gdata_Calendar::AUTH_SERVICE_NAME;
  $client = Zend_Gdata_ClientLogin::getHttpClient($user, $pass, $service);
  return $client;
}

/**
 * Outputs an HTML unordered list (ul), with each list item representing a
 * calendar in the authenticated user's calendar list.
 *
 * @param  Zend_Http_Client $client The authenticated client object
 * @return void
 */
function getCalendars($client)
{
  $gdataCal = new Zend_Gdata_Calendar($client);
  $calFeed = $gdataCal->getCalendarListFeed();
  return $calFeed;
}

/**
 * Outputs an HTML unordered list (ul), with each list item representing an
 * event on the authenticated user's calendar.  Includes the start time and
 * event ID in the output.  Events are ordered by starttime and include only
 * events occurring in the future.
 *
 * @param  Zend_Http_Client $client The authenticated client object
 * @return void
 */
function getCalendarEvents($client, $fullurl, $startDate, $endDate)
{
  $gdataCal = new Zend_Gdata_Calendar($client);
  
  preg_match("/(?P<url>.*)\/(?P<user>[^\/]+)\/(?P<visibility>[^\/]+)\/(?P<projection>[^\/]+)$/", $fullurl, $parts);
  
  $query = $gdataCal->newEventQuery($parts['url']);
  $query->setUser($parts['user']);
  $query->setOrderby('starttime');
  $query->setSortOrder('ascending');
  $query->setVisibility($parts['visibility']);
  $query->setProjection($parts['projection']);
  
  $query->setStartMin(date("Y-m-d", $startDate));
  //exclusive so add one more to the day
  $query->setStartMax(date("Y-m-d", mktime(0, 0, 0, date("m", $endDate), date("d", $endDate)+1, date("Y", $endDate))));
  
  try {
  $eventFeed = $gdataCal->getCalendarEventFeed($query);
  return $eventFeed;
  } catch (Zend_Gdata_App_Exception $e) {
	echo "Error: " . $e->getResponse();
  }
}

processPageLoad();