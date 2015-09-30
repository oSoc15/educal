<?php


/**
 * Class Appointment
 *
 * An appointment is an "event", and belongs to a calendar. An appointment can inherit from a parent event,
 * for example in the case of recurring events, all events can be grouped to one parent.
 */
class Appointment extends Eloquent
{

    protected $appends = array('editable', 'color');

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'appointments';

    //returns the group the appointment belongs to
    public function calendar()
    {
        return $this->belongsTo('Calendar', 'calendar_id', 'id');
    }

    public function parent()
    {
        return $this->belongsTo('Appointment', 'parent_id', 'id');
    }

    public function getEditableAttribute()
    {
        try {
            if (!Sentry::check()) {
                return false;
            }
            foreach (Sentry::getUser()->Calendars as $calendar) {
                if ($calendar->id == $this->Calendar->id) {
                    return true;
                }
            }
        } catch (Exception $e) {
            return false;
        }

        return false;
    }

    public function getColorAttribute()
    {
        return $this->calendar->color;
    }

}
