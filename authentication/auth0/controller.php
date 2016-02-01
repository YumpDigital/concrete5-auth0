<?php
namespace Application\Authentication\Auth0;

defined('C5_EXECUTE') or die('Access Denied');

use Concrete\Core\Authentication\LoginException;
use Concrete\Core\Authentication\Type\OAuth\OAuth2\GenericOauth2TypeController;
use User;

/**
 * This class provides support for logging into Concrete5 via Auth0.com
 * (and any of the methods they provide, including Windows, ADFS, SAML and a plethora 
 * of social logins like Google, Facebook etc.)
 * 
 * Still in very early beta version, so use at your own risk.
 * 
 * By Simon East (@SimoEast) at Yump.com.au
 * 
 * Copyright 2016.
 */
class Controller extends GenericOauth2TypeController
{
    /**
     * Stores the result returned from $auth0->getUser()
     * 
     * Looks something like this...
     * 
     *     Array
     *     (
     *         [email] => simon@yump.com.au
     *         [email_verified] => 1
     *         [user_id] => auth0|56975523xxxxxxx711e7
     *         [picture] => https://s.gravatar.com/avatar/59a4e8xxxx7d19ba2?s=480&r=pg&d=https%3A%2F%2Fcdn.auth0.com%2Favatars%2Fsi.png
     *         [nickname] => simon
     *         [identities] => Array
     *             (
     *                 [0] => Array
     *                     (
     *                         [user_id] => 5697xxxxxxxxxxxx711e7
     *                         [provider] => auth0
     *                         [connection] => Username-Password-Authentication
     *                         [isSocial] => 
     *                     )

     *             )

     *         [updated_at] => 2016-01-15T01:00:59.553Z
     *         [created_at] => 2016-01-14T07:58:27.945Z
     *         [name] => simon@xxxxxxxx.com.au
     *         [last_ip] => 203.111.222.333
     *         [last_login] => 2016-01-15T01:00:59.553Z
     *         [logins_count] => 20
     *     )
     * @var array
     */
    protected $user = [];

    /**
     * The internal name (or 'handle') of this authentication method
     */
    public function getHandle()
    {
        return 'auth0';
    }
    
    public function supportsRegistration()
    {
        return \Config::get('auth.auth0.registration_enabled', false);
    }

    /**
     * When users are verified externally but not in concrete5 system yet, 
     * we create them and assign them to this particular c5 group.
     * 
     * This setting can be modified from Dashboard > Settings
     * 
     * @return int
     */
    public function registrationGroupID() 
    {
        return \Config::get('auth.auth0.registration_group');
    }

    /**
     * A string of HTML to display an icon next to the authentication method
     */
    public function getAuthenticationTypeIconHTML()
    {
        return '<i class="fa fa-user"></i>';
    }
    
    /**
     * When authenticating, user visits remote service and then is redirected back to
     * /ccm/system/authentication/oauth2/auth0/callback?code=xxxxxx
     * which triggers this function.
     * 
     * Here we need to validate the ?code=xxx using a server-side PHP call and log the user in
     * if everything validates OK.
     * 
     * @return null
     */
    public function handle_authentication_callback()
    {
        try {
            
            // Setup the Auth0 API object with settings stored in the CMS
            $auth0 = new \Auth0\SDK\Auth0(array(
                'domain'        => \Config::get('auth.auth0.domain'),
                'client_id'     => \Config::get('auth.auth0.client_id'),
                'client_secret' => \Config::get('auth.auth0.client_secret'),
                'redirect_uri'  => (string) \URL::to('/ccm/system/authentication/oauth2/auth0/callback'),
                // 'debug' => true,
            ));
            
            // Print out debug messages (when debug = true)
            $auth0->setDebugger(function($message){
                echo "Auth0: $message<br>";
            })
            // ->setDebugMode(true)
            ;
            
            $this->user = $auth0->getUser();
            
            // We will now have an array that looks something like this...
            // Array
            // (
            //     [email] => simon@yump.com.au
            //     [email_verified] => 1
            //     [user_id] => auth0|56975523xxxxxxx711e7
            //     [picture] => https://s.gravatar.com/avatar/59a4e8xxxx7d19ba2?s=480&r=pg&d=https%3A%2F%2Fcdn.auth0.com%2Favatars%2Fsi.png
            //     [nickname] => simon
            //     [identities] => Array
            //         (
            //             [0] => Array
            //                 (
            //                     [user_id] => 5697xxxxxxxxxxxx711e7
            //                     [provider] => auth0
            //                     [connection] => Username-Password-Authentication
            //                     [isSocial] => 
            //                 )

            //         )

            //     [updated_at] => 2016-01-15T01:00:59.553Z
            //     [created_at] => 2016-01-14T07:58:27.945Z
            //     [name] => simon@xxxxxxxx.com.au (or a full name if that is present in database)
            //     [given_name] => John
            //     [family_name] => Citizen
            //     [last_ip] => 203.111.222.182
            //     [last_login] => 2016-01-15T01:00:59.553Z
            //     [logins_count] => 20
            // )
            
            // echo '<pre>'; print_r($this->user); echo '</pre>';
            
            if ($this->user) {
                // User was authenticated via Auth0 successfully...
                $user = $this->registerOrLoginUser($this->user);
                // d($user);
                
                // Do final login steps and redirect user to home screen
                // Call the necessary functions in AuthenticationTypeController and the login page controller
                $this->completeAuthentication($user);
                
            } else { 
                // TODO: Send an email to Yump team indicating a problem
                // Redirect back to login page with the following message
                $this->showError('Unfortunately you do not appear to have access to the intranet. Please contact <a href="mailto:support@yump.com.au">support@yump.com.au</a> for assistance.');
                // die;
            }
            
        } catch (\Exception $e) {
            // TODO: Send an email to Yump team indicating a problem
            // echo "Exception: " . $e->getMessage();
            $this->showError('Oops, there was a problem connecting to the authentication server and we could not log you in. Please contact <a href="mailto:support@yump.com.au">support@yump.com.au</a> for assistance.<br><br><small style="font-size: 66%; opacity: 0.7">' . $e->getMessage() . '</small>');
        }
        
    }
    
    /**
     * Login a user (and register them if necessary)
     * 
     * Called from handle_authentication_callback() to perform the conversion
     * from Auth0 user object to a concrete5 object.
     * 
     * Will register an Auth0 user in concrete5 if they do not exist already
     * 
     * Assumes that the user has already been verified and authenticated prior
     * to calling this function
     * 
     * @param array $auth0User - as returned from $auth0->getUser()
     * @return \User
     */
    protected function registerOrLoginUser($auth0User)
    {
        if (empty($auth0User['email']))
            return false;        
        
        // Does this user exist (based on their email address)
        $userId = \Database::connection()->fetchColumn('SELECT uID FROM Users WHERE uEmail=?', [$auth0User['email']]);        
        if ($userId) {
            // Log them in, then return
            $user = \User::loginByUserID($userId);
            if ($user && !$user->isError()) {
                return $user;
            }
        }
        
        // Otherwise, create them if they do not exist currently
        // Return logged in user object
        // This function is in GenericOAuthTypeController, and uses the functions
        // below to retreive the user's data
        // Assumes the $auth0 data has already been stored in $this->user
        return $this->createUser();
  
    }
    
    //---------------- Functions for returning user's details ------------------
    public function supportsFirstName() { return true; }
    public function supportsLastName() { return true; }
    public function supportsFullName() { return true; }
    public function supportsUsername() { return true; }
    public function supportsVerifiedEmail() { return false; }
    public function supportsEmail() { return true; }
    public function getEmail() { return $this->user['email']; }
    public function getFirstName() { return $this->user['given_name']; }
    public function getLastName() { return $this->user['family_name']; }
    public function getFullName() { return $this->user['name']; }
    public function getUsername() { return $this->user['nickname']; }
    public function getUniqueId() { return $this->user['user_id']; }


    /**
     * Displays the form for editing the authentication method
     * 
     * Saving the form calls saveAuthenticationType()
     */
    public function edit()
    {
        $this->set('form', \Loader::helper('form'));
        $this->set('domain', \Config::get('auth.auth0.domain', ''));
        $this->set('client_id', \Config::get('auth.auth0.client_id', ''));
        $this->set('client_secret', \Config::get('auth.auth0.client_secret', ''));
        $this->set('registration_enabled', \Config::get('auth.auth0.registration_enabled', ''));
        $this->set('registration_group', \Config::get('auth.auth0.registration_group', ''));

        $list = new \GroupList();
        $list->includeAllGroups();
        $this->set('groups', $list->getResults());

        // $this->set('whitelist', \Config::get('auth.auth0.email_filters.whitelist', array()));
        // $blacklist = array_map(function($entry) {
        //     return json_encode($entry);
        // }, \Config::get('auth.auth0.email_filters.blacklist', array()));

        // $this->set('blacklist', $blacklist);
    }
    

    /**
     * Called when saving the authentication type under:
     * Dashboard > System & Settings > Login & Registration > Authentication Types > Auth0
     * 
     * @param array $args - form data from POST
     * @return null
     */
    public function saveAuthenticationType($args)
    {
        \Config::save('auth.auth0.domain', $args['domain']);
        \Config::save('auth.auth0.client_id', $args['client_id']);
        \Config::save('auth.auth0.client_secret', $args['client_secret']);
        \Config::save('auth.auth0.registration_enabled', !!$args['registration_enabled']);
        \Config::save('auth.auth0.registration_group', intval($args['registration_group'], 10));

        // $whitelist = array();
        // foreach (explode(PHP_EOL, $args['whitelist']) as $entry) {
        //     $whitelist[] = trim($entry);
        // }

        // $blacklist = array();
        // foreach (explode(PHP_EOL, $args['blacklist']) as $entry) {
        //     $blacklist[] = json_decode(trim($entry), true);
        // }

        // \Config::save('auth.auth0.email_filters.whitelist', array_values(array_filter($whitelist)));
        // \Config::save('auth.auth0.email_filters.blacklist', array_values(array_filter($blacklist)));
    }


    /**
     * Here we can apply any extra checks to use to confirm that the session is a valid one
     * such as perhaps validating a user's email against a specific whitelist of domains?
     * 
     * Don't think this is necessary currently
     * 
     * @return boolean true/false whether session is valid
     */
    public function isValid()
    {
        return true;
    }

}
