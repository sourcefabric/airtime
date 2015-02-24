<?php
require_once 'customvalidators/ConditionalNotEmpty.php';
require_once 'customvalidators/PasswordNotEmpty.php';

class Application_Form_MixcloudPreferences extends Zend_Form_SubForm
{
    public function init()
    {
        $CC_CONFIG = Config::getConfig();
        if (!$CC_CONFIG['mixcloud'] ||
             $CC_CONFIG['mixcloud_client_id'] === '')
        {
            return;
        }
        
        $this->setDecorators(array(
            array('ViewScript', array('viewScript' => 'form/preferences_mixcloud.phtml'))
        ));
    
        $isMixcloudConnected = true;
        if (Application_Model_Preference::GetMixcloudRequestToken() === "") {
            $isMixcloudConnected = false;
        }
                
        //Connect to MixCloud
        $elem = $this->addElement(
            ( $isMixcloudConnected ? 'hidden' : 'button'), 
            'ConnectToMixcloud', array(
            'label'      => _('Connect to Mixcloud'),
            'required'   => false,
            'decorators' => array(
                'ViewHelper'
            ),
        ));

        //Disconnect from MixCloud
        $this->addElement(
            ( $isMixcloudConnected ? 'button' : 'hidden'), 
            'DisconnectFromMixcloud', array(
            'label'      => _('Disconnect from Mixcloud'),
            'required'   => false,
            'decorators' => array(
                'ViewHelper'
            ),
        ));
        
        //Automatic Mixcloud uploads
        $this->addElement('checkbox', 'MixcloudAutoUpload', array(
            'label'      => _('Automatically Upload Recorded Shows'),
            'required'   => false,
            'value' => Application_Model_Preference::GetAutoUploadRecordedShowToMixcloud(),
            'decorators' => array(
                'ViewHelper'
            )
        ));
    }

}
