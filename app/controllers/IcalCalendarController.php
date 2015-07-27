<?php

/**
 * Class IcalCalendarController
 * This controller generates and renders .ics(iCal)-files with the help of the Eluceo plugin.
 * These can be generated by school+calendar, or by an individual id.
 */
class IcalCalendarController extends \BaseController
{

    /**
     * Find correct appointments depending on $school and $calendar
     * Process these appointments and render them to an .ics file which will be returned for download
     * @param  $school_slug int the school slug
     * @param  $calendar_slug int the calendar slug
     * @return mixed cal.ics download file
     *
     */
    public function index($school_slug, $calendar_slug)
    {
        // Create an empty appointments array, which we will fill with appointments to render later

        $appointments = CalendarController::getAppointmentsBySlugs($school_slug, $calendar_slug);
        // Compose iCal with the help of the eluceo plugin
        $calendar = self::composeIcal($appointments);

        return $calendar->render();
    }

    /**
     * This function composes the contents of the iCal file, which are eventually rendered by the index function.
     * This function makes use of the Eluceo plugin
     * @param $appointments
     * @return \Eluceo\iCal\Component\Calendar
     */
    public function composeIcal($appointments)
    {
        // Set default timezone (PHP 5.4)
        date_default_timezone_set('Europe/Berlin');

        // Create new calendar object
        $calendar = new \Eluceo\iCal\Component\Calendar('-//Open Knowledge//EduCal//NL');
        // TODO: Define a good name for the Calendar (calendar name or smth)
        $calendar->setName("educal Calendar");

        // Loop through appointments and add them to the calendar.
        foreach ($appointments as $appointment) {

            // TODO: Add location
            // Create an event
            $event = new \Eluceo\iCal\Component\Event();
            $event->setSummary($appointment['attributes']['title']);
            $event->setDescription(str_replace("\r\n", " ", $appointment['attributes']['description']));
            $event->setDtStart(new \DateTime($appointment['attributes']['start']));
            $event->setDtEnd(new \DateTime($appointment['attributes']['end']));
            $event->setNoTime($appointment['attributes']['allDay']);

            // Generate unique ID apIDed
            $uid = 'ap' . $appointment['attributes']['id'] . 'ed';
            $event->setUniqueId($uid);

            //$event->setStatus('TENTATIVE');

            // Recurrence option (e.g. New Year happens every year)
            // Set recurrence rule
            /* if ($appointment['attributes']['repeat_type']) {

                $recurrenceRule = new \Eluceo\iCal\Property\Event\RecurrenceRule();
                // Check the repeat type (day, week, month, year) and set the corresponding recurrence rule
                switch ($appointment['attributes']['repeat_type']) {
                    case 'd':
                        $recurrenceRule->setFreq(\Eluceo\iCal\Property\Event\RecurrenceRule::FREQ_DAILY);
                        break;
                    case 'w':
                        $recurrenceRule->setFreq(\Eluceo\iCal\Property\Event\RecurrenceRule::FREQ_WEEKLY);
                        break;
                    case 'M':
                        $recurrenceRule->setFreq(\Eluceo\iCal\Property\Event\RecurrenceRule::FREQ_MONTHLY);
                        break;
                    case 'y':
                        $recurrenceRule->setFreq(\Eluceo\iCal\Property\Event\RecurrenceRule::FREQ_YEARLY);
                        break;
                }

                $recurrenceRule->setInterval($appointment['attributes']['repeat_freq']);
                $recurrenceRule->setCount($appointment['attributes']['nr_repeat']);

                $event->setRecurrenceRule($recurrenceRule);
            } */

            // Adding Timezone (optional)
            $event->setUseTimezone(true);
            $event->setTimeTransparency('TRANSPARENT');

            // Add event to calendar
            $calendar->addEvent($event);
        }

        // Set headers
        header('Content-Type: text/calendar; charset=utf-8');
        header('Content-Disposition: attachment; filename="cal.ics"');

        // Output
        return $calendar;
    }

}
