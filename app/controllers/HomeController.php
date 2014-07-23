<?php

class HomeController extends BaseController
{

    /*
    |--------------------------------------------------------------------------
    | Default Home Controller
    |--------------------------------------------------------------------------
    |
    | You may wish to use controllers instead of, or in addition to, Closure
    | based routes. That's great! Here is an example controller method to
    | get you started. To route to this controller, just add the route:
    |
    |	Route::get('/', 'HomeController@showWelcome');
    |
    */

    public function showWelcome()
    {
        // Check if user is logged in/activated
        if (Sentry::check()) {
            return Redirect::route('calendar.index');

        } else {
            // If user is not logged in, show the default landing page where users can log in or register
            $schools      = School::get();
            $schoolsArray = [];

            // Make a schoolsArray which is used to fill the school-dropdown when a user wants to register
            foreach ($schools as $school) {
                $schoolsArray[$school->id] = $school->name;
            }

            return View::make('landing')
                ->with("schools", $schoolsArray);
        }
    }
}