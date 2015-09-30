<?php

/**
 * Class Calendarµ
 *
 * Calendars are the core objects of the app.
 * A calendar groups events. Calendars can inherit from "higher", less specific, parent calendars.
 * Calendars contain users who can add/edit/remove events.
 *
 */
class Calendar extends Eloquent
{
    protected $appends = array('url', 'editable');

    /**
     * Returns the group's School.
     *
     * @return  mixed
     */
    public function school()
    {
        return $this->belongsTo('School', 'school_id', 'id');
    }

    /**
     * Returns the appointments's of a group.
     *
     * @return  mixed
     */
    public function appointments()
    {
        return $this->hasMany('Appointment', 'calendar_id', 'id');
    }

    /**
     * Returns the users (editors) of this calendar.
     *
     * @return  mixed
     */
    public function users()
    {
        return $this->belongsToMany('User', 'users_calendars');
    }

    /**
     * Returns the parent calendar of this calendar, can be null!
     *
     * @return  mixed
     */
    public function parent()
    {
        return $this->hasOne('Calendar', 'parent_id', 'id');
    }

    public function getChildren()
    {
        return Calendar::where('parent_id', $this->id)->orderBy('name')->get();
    }

    /**
     * Get the school with this slug
     * @param string $slug the slug to search
     * @return $this the result
     */
    public static function getBySlug($slug)
    {
        return Calendar::where('slug', $slug)->first();
    }

    /**
     * Get the root (topmost) calendars for a school
     * @param $school_id
     * @return $this
     */
    public static function getRootCalendars($school_id)
    {
        return Calendar::where('school_id', $school_id)
          ->orderBy('name')
          ->where('parent_id', NULL)
          ->get();
    }

    /**
     * Get the root (topmost) calendars for a school
     * @param $school_id
     * @return $this
     */
    public static function getSchoolCalendars($school_id)
    {
        return Calendar::where('school_id', $school_id)->orderBy('name')->get();
    }

    public function getEditableAttribute()
    {
        try {
            if (!Sentry::check()) {
                return false;
            }
            foreach (Sentry::getUser()->Calendars as $calendar) {
                if ($calendar->id == $this->id) {
                    return true;
                }
            }
        } catch (Exception $e) {
            return false;
        }

        return false;
    }

    public function getUrlAttribute()
    {
        return route("api.calendar.events", [$this->id]);
    }

    public function getParentCalendarAppointments()
    {
        $calendar = $this;
        // Add all parent calendars
        while ($calendar->parent_id > 0) {
            $calendar = $calendar::find($calendar->parent_id);
            $calendar->load('appointments');
            foreach ($calendar->appointments as $appointment) {
                array_push($this->appointments, $appointment);
            }
        }
    }
}
