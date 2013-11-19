<?php
require_once 'customvalidators/ConditionalNotEmpty.php';
require_once 'customvalidators/PasswordNotEmpty.php';

class Application_Form_MixcloudPreferences extends Zend_Form_SubForm
{

    public function init()
    {
        $this->setDecorators(array(
            array('ViewScript', array('viewScript' => 'form/preferences_mixcloud.phtml'))
        ));

        //enable mixcloud uploads
        $this->addElement('checkbox', 'UseMixcloud', array(
            'label'      => _('Automatically Upload Recorded Shows'),
            'required'   => false,
            'value' => Application_Model_Preference::GetAutoUploadRecordedShowToMixcloud(),
            'decorators' => array(
                'ViewHelper'
            )
        ));

        //enable mixcloud uploads option
        $this->addElement('checkbox', 'UploadToMixcloudOption', array(
            'label'      => _('Enable Mixcloud Upload'),
            'required'   => false,
            'value' => Application_Model_Preference::GetUploadToMixcloudOption(),
            'decorators' => array(
                'ViewHelper'
            )
        ));

        //Mixcloud Username
        $this->addElement('text', 'MixcloudUser', array(
            'class'      => 'input_text',
            'label'      => _('Mixcloud Email'),
            'filters'    => array('StringTrim'),
            'autocomplete' => 'off',
            'value' => Application_Model_Preference::GetMixcloudUser(),
            'decorators' => array(
                'ViewHelper'
            ),

            // By default, 'allowEmpty' is true. This means that our custom
            // validators are going to be skipped if this field is empty,
            // which is something we don't want
            'allowEmpty' => false,
            'validators' => array(
                new ConditionalNotEmpty(array('UploadToMixcloudOption'=>'1'))
            )
        ));

        //Mixcloud Password
        $this->addElement('password', 'MixcloudPassword', array(
            'class'      => 'input_text',
            'label'      => _('Mixcloud Password'),
            'filters'    => array('StringTrim'),
            'autocomplete' => 'off',
            'value' => Application_Model_Preference::GetMixcloudPassword(),
            'decorators' => array(
                'ViewHelper'
            ),

            // By default, 'allowEmpty' is true. This means that our custom
            // validators are going to be skipped if this field is empty,
            // which is something we don't want
            'allowEmpty' => false,
            'validators' => array(
                new ConditionalNotEmpty(array('UploadToMixcloudOption'=>'1'))
            ),
            'renderPassword' => true
        ));

        //Mixcloud Token
        $this->addElement('password', 'MixcloudToken', array(
            'class'      => 'input_text',
            'label'      => _('Mixcloud Token'),
            'filters'    => array('StringTrim'),
            'autocomplete' => 'off',
            'value' => Application_Model_Preference::GetMixcloudToken(),
            'decorators' => array(
                'ViewHelper'
            ),

            // By default, 'allowEmpty' is true. This means that our custom
            // validators are going to be skipped if this field is empty,
            // which is something we don't want
            'allowEmpty' => false,
            'validators' => array(
                new ConditionalNotEmpty(array('UploadToMixcloudOption'=>'1'))
            ),
            'renderPassword' => true
        ));
        
    }

}
