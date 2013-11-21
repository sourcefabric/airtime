<?php

require_once('php-oauth2/Client.php');
require_once('php-oauth2/GrantType/IGrantType.php');
require_once('php-oauth2/GrantType/AuthorizationCode.php');

/** 
    The PHP PECL extension for OAuth only supports OAuth 1 and
    the Zend Framework 1.x OAuth stuff is only OAuth 1.
    That's why we're using this third party php-oauth2 thing.
*/

class MixcloudController extends Zend_Controller_Action
{
    const CLIENT_ID     = 'Z3PGyMLKAcxnEjYJYs';
    const CLIENT_SECRET = 'eTkGZZZaDhYpSBxwbb9EeXmv89hMg9VL';

    //const REDIRECT_URI           = 'http://url/of/this.php';
    const AUTHORIZATION_ENDPOINT = 'https://www.mixcloud.com/oauth/authorize';
    const TOKEN_ENDPOINT         = 'https://www.mixcloud.com/oauth/access_token';
        
    public function init()
    {
        //Disable rendering of this controller
        $this->view->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);
    }
    
    /*
    public function indexAction()
    {
    }
    */
    
    // http://myairtime/mixcloud/authorize
    public function authorizeAction()
    {
        $CC_CONFIG = Config::getConfig();
        $request = $this->getRequest();
        $baseUrl = $CC_CONFIG['baseUrl'] . ":" . $CC_CONFIG['basePort'];
        $user = Application_Model_User::GetCurrentUser();
        $userType = $user->getType();
     
        $redirectUri = 'http://' . $baseUrl . '/mixcloud/redirect';

        $client = new OAuth2\Client(self::CLIENT_ID, self::CLIENT_SECRET);
        if (!isset($_GET['code']))
        {
            $auth_url = $client->getAuthenticationUrl(self::AUTHORIZATION_ENDPOINT, $redirectUri);
            header('Location: ' . $auth_url);
            die('Redirect');
        }
        else
        {

        }
    }
    
    // http://myairtime/mixcloud/redirect
    public function redirectAction()
    {
        $this->_helper->viewRenderer->setNoRender(false);
            
        $CC_CONFIG = Config::getConfig();
        $request = $this->getRequest();
        $baseUrl = $CC_CONFIG['baseUrl'] . ":" . $CC_CONFIG['basePort'];
        
        //We have an OAuth code now, so next we need to ask for a request token.
        $redirectUri = 'http://' . $baseUrl . '/mixcloud/redirect';
        
        $client = new OAuth2\Client(self::CLIENT_ID, self::CLIENT_SECRET);          
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
        
        //TODO: Redirect back to the preferences page?
    }
    
    // http://myairtime/mixcloud/deauthorize
    public function deauthorizeAction()
    {
        $CC_CONFIG = Config::getConfig();
        $request = $this->getRequest();
        $baseUrl = $CC_CONFIG['baseUrl'] . ":" . $CC_CONFIG['basePort'];
        $user = Application_Model_User::GetCurrentUser();
        $userType = $user->getType();
        
        //Clear the previously saved request token from the preferences.
        Application_Model_Preference::setMixcloudRequestToken("");
    }
}


