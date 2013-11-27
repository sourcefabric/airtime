<?php

require_once('php-oauth2/Client.php');
require_once('php-oauth2/GrantType/IGrantType.php');
require_once('php-oauth2/GrantType/AuthorizationCode.php');

/** 
    The PHP PECL extension for OAuth only supports OAuth 1 and
    the Zend Framework 1.x OAuth stuff is only OAuth 1.
    That's why we're using this third party php-oauth2 thing.
*/

/** This controller provides a simple API for managing our OAuth
    access to Mixcloud. It provides a few URLs that can be called:
         /mixcloud/authorize
         /mixcloud/deauthorize
         /mixcloud/redirect
         
*/
class MixcloudController extends Zend_Controller_Action
{
    protected $_clientId = '';
    protected $_clientSecret = '';

    const AUTHORIZATION_ENDPOINT = 'https://www.mixcloud.com/oauth/authorize';
    const TOKEN_ENDPOINT         = 'https://www.mixcloud.com/oauth/access_token';

    
    /** Common initialization that gets called before any of the below action
        functions get called. Zend doesn't recommend you override the constructor for some reason.
    */
    public function init()
    {
        $CC_CONFIG = Config::getConfig();
        $this->_clientId       = $CC_CONFIG['mixcloud_client_id'];
        $this->_clientSecret   = $CC_CONFIG['mixcloud_client_secret'];
    
        //Disable rendering of this controller
        $this->view->layout()->disableLayout(); //Don't inject the standard Now Playing header.
        $this->_helper->viewRenderer->setNoRender(true); //Don't use (phtml) templates
    }
    
    /** http://myairtime/mixcloud/authorize
     *  Prompt the user for their Mixcloud credentials using OAuth.
     */
    public function authorizeAction()
    {
        $CC_CONFIG = Config::getConfig();
        $request = $this->getRequest();
        $baseUrl = $CC_CONFIG['baseUrl'] . ":" . $CC_CONFIG['basePort'];
        $user = Application_Model_User::GetCurrentUser();
        $userType = $user->getType();
     
        $redirectUri = 'http://' . $baseUrl . '/mixcloud/redirect';

        $client = new OAuth2\Client($this->_clientId, $this->_clientSecret);
        if (!isset($_GET['code']))
        {
            $auth_url = $client->getAuthenticationUrl(self::AUTHORIZATION_ENDPOINT, $redirectUri);
            header('Location: ' . $auth_url);
            die('Redirect');
        }
    }
    
    /** http://myairtime/mixcloud/redirect
      * The URL that a user gets redirected to after 
      * a successful OAuth authorization.
      */
    public function redirectAction()
    {
        $this->_helper->viewRenderer->setNoRender(false);
            
        $CC_CONFIG = Config::getConfig();
        $request = $this->getRequest();
        $baseUrl = $CC_CONFIG['baseUrl'] . ":" . $CC_CONFIG['basePort'];
        
        //We have an OAuth code now, so next we need to ask for a request token.
        $redirectUri = 'http://' . $baseUrl . '/mixcloud/redirect';
        
        $client = new OAuth2\Client($this->_clientId, $this->_clientSecret);          
        $params = array('code' => $_GET['code'], 'redirect_uri' => $redirectUri);
        $response = $client->getAccessToken(self::TOKEN_ENDPOINT, 'authorization_code', $params);
        //var_dump($response, $response['result']);
        //parse_str($response['result'], $info);
        $info = $response['result'];
        $accessToken = $info['access_token'];
        
        //Save the request token to the Airtime preferences so we can use the Mixcloud API 
        //at any time later.
        Application_Model_Preference::setMixcloudRequestToken($accessToken);
        Application_Model_Preference::SetMixcloudUser("Connected");
        //Here's a test of the Mixcloud API using this access token:
        /*
        $client->setAccessToken($info['access_token']);
        $response = $client->fetch('https://api.mixcloud.com/spartacus/party-time/');
        var_dump($response, $response['result']);
        */
    }
    
    /** http://myairtime/mixcloud/deauthorize
      * Deauthorize the Airtime application by forgetting the OAuth request token.
      */
    public function deauthorizeAction()
    {
        $this->_helper->viewRenderer->setNoRender(false);
            
        $CC_CONFIG = Config::getConfig();
        $request = $this->getRequest();
        $baseUrl = $CC_CONFIG['baseUrl'] . ":" . $CC_CONFIG['basePort'];
        $user = Application_Model_User::GetCurrentUser();
        $userType = $user->getType();
        
        //Clear the previously saved request token from the preferences.
        Application_Model_Preference::setMixcloudRequestToken("");
    }
}


