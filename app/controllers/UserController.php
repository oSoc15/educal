<?php

class UserController extends \BaseController {

    protected $layout = 'layout.master';

    /**
     * Get all users for a school, where they can be activated as well by the administration
     * Show these on view
     * TODO: PERMISSIONS List users by school
     */
    public function index()
    {
        // If user is logged in, get school_id, find respective users
        if(Sentry::check()) {
            $user = Sentry::getUser();
            $users = null;
            $schoolName = null;
            $schools = School::get();
            $schoolsArray = [];
            foreach ($schools as $school){
                $schoolsArray[$school->id] = $school->name;
            }
            if ($user->hasAccess('school'))
            {
                $users = User::where('id','<>',$user->id)->get();
                $schoolName = 'Userlist';
            } elseif ($user->hasAccess('user')) {
                $schoolId = $user->school_id;
                // Get all users with this school_id, except for the logged in user
                $users = User::where('school_id', $schoolId)
                    ->where('id','<>',$user->id)
                    ->get();
                $school = School::where('id', $schoolId)->first();
            } else {
                return Redirect::route('calendar.index');
            }
            return View::make('user.index')
                ->with('users', $users)
                ->with('school', $school)
                ->with('schools',$schoolsArray);

        }
        // If no permissions, redirect to calendar index
        return Redirect::route('calendar.index');
    }

    /**
     * Get a single user based on his ID, the admin can edit certain settings in this view
     * TODO: PERMISSIONS List users by schoolsingle user
     * TODO: Activate user possibility
     */
    public function show($id)
    {
        // If user is logged in, get school_id, find respective users
        if(Sentry::check()) {
            $user = Sentry::getUser();
            if ($user->hasAnyAccess(array('school','user'))){
                $user = Sentry::findUserByID($id);
                $user->load('school');
                $user->load('groups');

                return View::make('user.show')
                    ->with('user', $user);
            }else{
                // If no permissions, redirect to calendar index
                return Redirect::route('calendar.index');
            }
        }   else{
            // If not logged in, redirect to the login screen
            return Redirect::route('landing');
        }
    }

    public function auth()
    {
        try
        {
            // Login credentials
            $credentials = array(
                'email'    => Input::get('lemail'),
                'password' => Input::get('password'),
            );

            // Authenticate the user
            $user = Sentry::authenticate($credentials, false);

            // If "remember me" is checked, make cookie, else don't make cookie
            if(Input::get('remember')) {
                Sentry::loginAndRemember($user);
            } else {
                Sentry::login($user);
            }

            //Get the users prefered language
            $user = Sentry::getUser();
            if($user->lang != null && $user->lang != ''){
                Session::put('lang', $user->lang);
            }elseif($user->school != null){
                Session::put('lang', $user->school->lang);
            }else{
                Session::put('lang', 'nl');
            }

            //Set the language
            App::setLocale(Session::get('lang'));

            // Redirect to logged in page
            return Redirect::route('calendar.index');
        }
            // Error handling
        catch (Cartalyst\Sentry\Users\LoginRequiredException $e)
        {
            // No email input
            $errorMessage = 'Login field is required.';
        }
        catch (Cartalyst\Sentry\Users\PasswordRequiredException $e)
        {
            // No password input
            $errorMessage = 'Password field is required.';
        }
        catch (Cartalyst\Sentry\Users\WrongPasswordException $e)
        {
            // Wrong password input
            $errorMessage = 'Wrong password. Try again.';
        }
        catch (Cartalyst\Sentry\Users\UserNotFoundException $e)
        {
            $errorMessage = 'User was not found.';
        }
        catch (Cartalyst\Sentry\Users\UserNotActivatedException $e)
        {
            $errorMessage = 'User is not activated.';
        }
            // The following is only required if the throttling is enabled
        catch (Cartalyst\Sentry\Throttling\UserSuspendedException $e)
        {
            $errorMessage = 'User is suspended.';
        }
        catch (Cartalyst\Sentry\Throttling\UserBannedException $e)
        {
            $errorMessage = 'User is banned.';
        }
        // If there is an errormessage, return to login page
        // With errorMessage
        if($errorMessage) {
            return Redirect::route('landing')
                ->withInput()
                ->with('errorMessage', $errorMessage);
        }

    }

    public function store()
    {
        $validator = Validator::make(
            array(
                'name' => Input::get('name'),
                'surname' => Input::get('surname'),
                'email' => Input::get('email'),
                'school' => Input::get('school'),
                'password' => Input::get('password'),
                'password_confirmation' => Input::get('password_confirmation'),
                'tos' => Input::get('tos')
            ),
            array(
                'name' => 'required',
                'surname' => 'required',
                'school' => 'required',
                'email' => 'required|email|unique:users,email',
                'password' => 'required|min:8|confirmed',
                'tos' => 'required'
            )
        );
        if ($validator->fails())
        {
            $validator->getMessageBag()->add('usererror', 'Failed to make a user');
            return Redirect::route('landing')
                ->withInput()
                ->withErrors($validator);

        } else {
            Sentry::createUser(array(
                'email'    => Input::get('email'),
                'password' => Input::get('password'),
                'activated' => false,
                'school_id' => Input::get('school'),
                'first_name' => e(Input::get('name')),
                'last_name' => e(Input::get('surname')),
            ));
            return Redirect::route('landing');
        }
    }

    /**
     * Creates a new user from the back-office side
     * @return mixed
     */
    public function create()
    {
        $validator = Validator::make(
            array(
                'name' => Input::get('name'),
                'surname' => Input::get('surname'),
                'email' => Input::get('email'),
                'school' => Input::get('school'),
                'password' => Input::get('password'),
                'password_confirmation' => Input::get('password_confirmation'),
            ),
            array(
                'name' => 'required',
                'surname' => 'required',
                'school' => 'required',
                'email' => 'required|email|unique:users,email',
                'password' => 'required|min:8|confirmed',
            )
        );
        if ($validator->fails())
        {
            $validator->getMessageBag()->add('usererror', 'Failed to make a user');
            return Redirect::back()
                ->withInput()
                ->withErrors($validator);

        }
        else{
            Sentry::createUser(array(
                'email'    => Input::get('email'),
                'password' => Input::get('password'),
                'activated' => true,
                'school_id' => Input::get('school'),
                'first_name' => Input::get('name'),
                'last_name' => Input::get('surname'),
            ));
            return Redirect::back();
        }
    }


    /**
     * Remove a user from a school
     * @param $id = userID
     * TODO: PERMISSIONS remove user from school
     */
    public function removeUserFromSchool($id)
    {
        // If user is logged in, get school_id, find respective users
        if(Sentry::check()) {
            $user = Sentry::getUser();
            if ($user->hasAnyAccess(array('school','user'))){
                try
                {
                    // Find the user using the user id
                    $selectedUser = Sentry::findUserById($id);
                    /**
                     * Check if the selected user is in the admins group,
                     * ->true: check if he is the last person in that group
                     *          -> true: don't allow user to be removed (school needs 1 admin at least)
                     *          -> false: delete user from school
                     * ->false: safe to remove user from school
                     */
                    if(($selectedUser->hasAccess('admin') && $selectedUser->school_id == $user->school_id) || $user->school_id == null) {
                        $school = School::find($selectedUser->school_id);
                        $group = Sentry::findGroupByName($school->short.'_admin');
                        $users = Sentry::findAllUsersInGroup($group);
                        if(count($users)>1) {
                            // Delete the user
                            $selectedUser->delete();
                            // Return to the previous page
                            return Redirect::back();
                        } else {
                            $error = "You can't remove this user.";
                            // Return to the previous page
                            return Redirect::route('user.index')->with('error', $error);
                        }
                    } else {
                        // Delete the user
                        $selectedUser->delete();
                        // Return to the previous page
                        return Redirect::route('user.index');
                    }
                }
                catch (Cartalyst\Sentry\Users\UserNotFoundException $e)
                {
                    $error = 'User was not found.';
                    // Return to the previous page
                    return Redirect::route('user.index')->with('error', $error);
                }
            } else {
                // If no permissions, redirect to calendar index
                return Redirect::route('calendar.index');
            }
        } else {
            // If not logged in, redirect to the login screen
            return Redirect::route('landing');
        }
    }

    /**
     * Activate a user so that he gets access to the school (as a teacher for example)
     * @param $id = userID
     * @return mixed
     */
    public function activateUser($id)
    {
        // If user is logged in, get school_id, find respective users
        if(Sentry::check()) {
            $loggedInUser = Sentry::getUser();
            if ($loggedInUser->hasAnyAccess(array('school','user'))){
                // Find the user using the user id
                try {
                    $user = Sentry::findUserById($id);

                    if(($loggedInUser->school_id == $user->school_id || $loggedInUser->hasAccess('school')) && $user->id != $loggedInUser->id) {

                    } else {
                        return Redirect::route('calendar.index');
                    }
                } catch(Cartalyst\Sentry\Users\UserNotFoundException $e) {
                    return Redirect::route('calendar.index');
                }

                $activationCode = $user->getActivationCode();
                // Attempt to activate the user
                if($user->activated == 0) {
                    $user->attemptActivation($activationCode);
                    return $user;
                } else {
                    // Deactivate user
                    $user->activated = 0;
                    $user->save();
                    return $user;
                }
            } else {
                // If no permissions, redirect to calendar index
                return Redirect::route('calendar.index');
            }
        }   else{
            // If not logged in, redirect to the login screen
            return Redirect::route('calendar.index');
        }
    }


    /**
     * Edit user settings for a given ID (if permissions allow it), otherwise edit own user settings
     * @param $id = userID
     * TODO: Try catch block
     */
    public function editUser($id = null)
    {
        // If user is logged in, get school_id, find respective users
        if(Sentry::check()) {
            $user = Sentry::getUser();
            if ($user->hasAnyAccess(array('school','user'))){
                if($id != null) {
                    $user = Sentry::findUserById($id);
                } else {
                    $user = Sentry::getUser();
                }
                return View::make('user.edit')
                    ->with('user', $user);
            }else{
                // If no permissions, redirect to calendar index
                return Redirect::route('calendar.index');
            }
        }   else{
            // If not logged in, redirect to the login screen
            return Redirect::route('landing');
        }
    }

    /**
     * Update userSettings
     * TODO: Permissions
     */
    public function updateUser($id)
    {
        if(Sentry::check()) {
            $user = Sentry::getUser();
            if ($user->hasAnyAccess(array('school','user'))){
                $user = Sentry::findUserById($id);

                $validator = Validator::make(
                    array(
                        'name' => Input::get('name'),
                        'surname' => Input::get('surname'),
                        'email' => Input::get('email'),
                        'password' => Input::get('password'),
                        'password_confirmation' => Input::get('password_confirmation'),
                    ),
                    array(
                        'name' => 'required',
                        'surname' => 'required',
                        'email' => 'required|email',
                        'password' => 'min:8|confirmed',
                    )
                );

                if($user->email != Input::get('email')) {
                    try
                    {
                        $user2 = Sentry::findUserByCredentials(array(
                            'email' => Input::get('email')
                        ));

                        // Add an error message in the message collection (MessageBag instance)
                        $validator->getMessageBag()->add('email', Lang::get('validation.unique', array('attribute ' => 'email ')));

                    }
                    catch (Cartalyst\Sentry\Users\UserNotFoundException $e)
                    {
                        $user->email = Input::get('email');
                    }
                }

                if ($validator->fails()) {
                    return Redirect::back()->withInput()->withErrors($validator);
                } else{
                    if(Input::get('password'))
                        $user->password     = Input::get('password');

                    $user->first_name   = e(Input::get('name'));
                    $user->last_name    = e(Input::get('surname'));

                    $user->save();

                    return Redirect::back();
                }
                return Redirect::back();
            }else{
                // If no permissions, redirect to calendar index
                return Redirect::route('calendar.index');
            }
        }   else{
            // If not logged in, redirect to the login screen
            return Redirect::route('landing');
        }
    }

    /**
     * Remove a user from selected group
     * @param $id
     * @param $groupId
     * TODO: Re-render dropdown menu to show users
     */
    public function removeFromGroup($id, $groupId)
    {
        if(Sentry::check()) {
            $user = Sentry::getUser();
            if ($user->hasAnyAccess(array('school','user'))){
                try
                {
                    // Find the user using the user id
                    $selectedUser = Sentry::findUserById($id);
                    /**
                     * Check if the selected user is in the admins group,
                     * ->true: check if he is the last person in that group
                     *          -> true: don't allow user to be removed (school needs 1 admin at least)
                     *          -> false: delete user from group
                     * ->false: safe to remove user from group
                     */
                    $group = Sentry::findGroupById($groupId);
                    if(($selectedUser->hasAccess('admin') && $selectedUser->school_id == $user->school_id) || $user->school_id == null) {
                        $school = School::find($selectedUser->school_id);

                        if($group->name == $school->short.'_admin') {
                            $users = Sentry::findAllUsersInGroup($group);
                            if(count($users)>1) {
                                // Delete the user
                                $selectedUser->removeGroup($group);
                                // Return to the previous page
                                return Redirect::back();
                            } else {
                                $error = "You can't remove this user.";
                                // Return to the previous page
                                return Redirect::back()->with('error', $error);
                            }
                        }
                    } else {
                        // Remove the user from group
                        $selectedUser->removeGroup($group);
                        // Return to the previous page
                        return Redirect::back();
                    }
                    /*// Find the user using the user id
                    $user = Sentry::findUserById($id);

                    // Find the group using the group id
                    $group = Sentry::findGroupById($groupId);

                    // Assign the group to the user
                    $user->removeGroup($group);*/

                }
                catch (Cartalyst\Sentry\Users\UserNotFoundException $e)
                {
                    $error = 'User was not found.';
                    // Return to the previous page
                    return Redirect::back()->with('error', $error);
                }
                catch (Cartalyst\Sentry\Groups\GroupNotFoundException $e)
                {
                    $error = 'Group was not found.';
                    // Return to the previous page
                    return Redirect::back()->with('error', $error);
                }
            }else{
                // If no permissions, redirect to calendar index
                return Redirect::route('calendar.index');
            }
        }   else{
            // If not logged in, redirect to the login screen
            return Redirect::route('landing');
        }
    }

    // Log out function
    public function logout()
    {
        // If user is logged in, then log out the user
        if(Sentry::check()) {
            Sentry::logout();
        }
        // Redirect to root
        return Redirect::route('landing');
    }

    public function addToGroup($group_id) {
        if(Sentry::check()) {
            $user = Sentry::getUser();
            if ($user->hasAnyAccess(array('school','user'))){
                $user = Sentry::findUserById(Input::get('user'));

                // Find the group using the group id
                $group = Sentry::findGroupById($group_id);

                $user->addGroup($group);

                return Redirect::back();
            }else{
                // If no permissions, redirect to calendar index
                return Redirect::route('calendar.index');
            }
        }   else{
            // If not logged in, redirect to the login screen
            return Redirect::route('landing');
        }
    }
}