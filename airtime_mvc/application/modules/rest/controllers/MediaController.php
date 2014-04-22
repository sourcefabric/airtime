<?php

use Airtime\MediaItem\AudioFile;
use Airtime\MediaItem\AudioFileQuery;
use \BasePeer;
use \Propel;

class Rest_MediaController extends Zend_Rest_Controller
{
    //fields that are not modifiable via our RESTful API
    private $blackList = array(
        'id',
        'directory',
        'filepath',
        'file_exists',
        'updated_at',
        'created_at',
        'last_played',
        'silan_check'
    );

    //fields we should never expose through our RESTful API
    private $privateFields = array(
        'file_exists',
        'silan_check'
    );

    public function init()
    {
        $this->view->layout()->disableLayout();
    }
    
    public function indexAction()
    {
        if (!$this->verifyAuth(true, true))
        {
            return;
        }
        
        /*
        $files_array = array();
        foreach (AudioFileQuery::create()->find() as $file)
        {
            array_push($files_array, $this->sanitizeResponse($file));
        }
        
        $this->getResponse()
        ->setHttpResponseCode(200)
        ->appendBody(json_encode($files_array));
        */
        
        //TODO: Use this simpler code instead after we upgrade to Propel 1.7 (Airtime 2.6.x branch):
        $this->getResponse()
            ->setHttpResponseCode(200)
            ->appendBody(json_encode(AudioFileQuery::create()->find()->toArray(BasePeer::TYPE_FIELDNAME)));

    }

    public function downloadAction()
    {
        if (!$this->verifyAuth(true, true))
        {
            return;
        }

        $id = $this->getId();
        if (!$id) {
            return;
        }

        $file = AudioFileQuery::create()->findPk($id);
        if ($file) {
            
            $baseUrl = Application_Common_OsPath::getBaseDir();

            $this->getResponse()
                ->setHttpResponseCode(200)
                ->appendBody($this->_redirect($file->getRelativeFileUrl($baseUrl).'/download/true'));
        } else {
            $this->fileNotFoundResponse();
        }
    }
    
    public function getAction()
    {
        if (!$this->verifyAuth(true, true))
                {
            return;
        }
        
        $id = $this->getId();
        if (!$id) {
            return;
        }

        $file = AudioFileQuery::create()->findPk($id);
        if ($file) {
            
            $this->getResponse()
                ->setHttpResponseCode(200)
                ->appendBody(json_encode($this->sanitizeResponse($file)));
        } else {
            $this->fileNotFoundResponse();
        }
    }
    
    public function postAction()
    {
        if (!$this->verifyAuth(true, true))
        {
            return;
        }

        //If we do get an ID on a POST, then that doesn't make any sense
        //since POST is only for creating.
        if ($id = $this->_getParam('id', false)) {
            $resp = $this->getResponse();
            $resp->setHttpResponseCode(400);
            $resp->appendBody("ERROR: ID should not be specified when using POST. POST is only used for file creation, and an ID will be chosen by Airtime"); 
            return;
        }

        if (Application_Model_Systemstatus::isDiskOverQuota()) {
            $this->getResponse()
                ->setHttpResponseCode(400)
                ->appendBody("ERROR: Disk Quota reached.");
            return;
        }

        $file = new AudioFile();
        $whiteList = $this->removeBlacklistedFieldsFromRequestData($this->getRequest()->getPost());

        if (!$this->validateRequestData($file, $whiteList)) {
            $file->setTrackTitle($_FILES["file"]["name"]);
            $file->save();
            return;
        } else {

            $file->fromArray($whiteList);
            $file->setOwnerId($this->getOwnerId());
            $file->setTrackTitle($_FILES["file"]["name"]);
            $file->setFileHidden(true);
            $file->save();

            $callbackUrl = $this->getRequest()->getScheme() . '://' . $this->getRequest()->getHttpHost() . $this->getRequest()->getRequestUri() . "/" . $file->getPrimaryKey();
    
            $this->processUploadedFile($callbackUrl, $_FILES["file"]["name"], $this->getOwnerId());
    
            $this->getResponse()
                ->setHttpResponseCode(201)
                ->appendBody(json_encode($this->sanitizeResponse($file)));
        }
    }

    public function putAction()
    {
        if (!$this->verifyAuth(true, true))
        {
            return;
        }
        
        $id = $this->getId();
        if (!$id) {
            return;
        }
        
        $file = AudioFileQuery::create()->findPk($id);

        $requestData = json_decode($this->getRequest()->getRawBody(), true);
        $whiteList = $this->removeBlacklistedFieldsFromRequestData($requestData);

        if (!$this->validateRequestData($file, $whiteList)) {
            $file->save();
            return;
        } else if ($file) {
            $file->fromArray($whiteList, BasePeer::TYPE_FIELDNAME);
            
            Logging::info($whiteList);

            //Our RESTful API takes "full_path" as a field, which we then split and translate to match
            //our internal schema. Internally, file path is stored relative to a directory, with the directory
            //as a foreign key to cc_music_dirs.
            if (isset($requestData["full_path"])) {
                Application_Model_Preference::updateDiskUsage(filesize($requestData["full_path"]));

                $fullPath = $requestData["full_path"];
                $storDir = Application_Model_MusicDir::getStorDir()->getDirectory();
                $pos = strpos($fullPath, $storDir);
                
                if ($pos !== FALSE)
                {
                    assert($pos == 0); //Path must start with the stor directory path
                    
                    //$filePathRelativeToStor = substr($fullPath, strlen($storDir));
                    $file->setFilepath($fullPath);
                    $file->setDirectory(1); //1 corresponds to the default stor/imported directory.
                }
            }    

            $file->save();
            $this->getResponse()
                ->setHttpResponseCode(200)
                ->appendBody(json_encode($this->sanitizeResponse($file)));
        } else {
            $file->setImportStatus(2)->save();
            $this->fileNotFoundResponse();
        }
    }

    public function deleteAction()
    {
        if (!$this->verifyAuth(true, true))
        {
            return;
        }
            
        $id = $this->getId();
        if (!$id) {
            return;
        }
        $file = AudioFileQuery::create()->findPk($id);
        if ($file) {
        	
        	//TODO this if block does the same thing for now, need to fix up deleting.
            if ($file->existsOnDisk()) {
                $file->delete(); //TODO: This checks your session permissions... Make it work without a session?
            }
            else {
            	$file->delete();
            }
            
            $this->getResponse()
                ->setHttpResponseCode(204);
        } else {
            $this->fileNotFoundResponse();
        }
    }

    private function getId()
    {
        if (!$id = $this->_getParam('id', false)) {
            $resp = $this->getResponse();
            $resp->setHttpResponseCode(400);
            $resp->appendBody("ERROR: No file ID specified."); 
            return false;
        } 
        return $id;
    }
    
    private function verifyAuth($checkApiKey, $checkSession)
    {
        //Session takes precedence over API key for now:
        if ($checkSession && $this->verifySession()) 
        {
            return true;
        }
        
        if ($checkApiKey && $this->verifyAPIKey())
        {
            return true;
        }
        
        $resp = $this->getResponse();
        $resp->setHttpResponseCode(401);
        $resp->appendBody("ERROR: Incorrect API key.");
               
        return false;
    }
    

    private function verifyAPIKey()
    {
        //The API key is passed in via HTTP "basic authentication":
        // http://en.wikipedia.org/wiki/Basic_access_authentication

        $CC_CONFIG = Config::getConfig();

        //Decode the API key that was passed to us in the HTTP request.
        $authHeader = $this->getRequest()->getHeader("Authorization");
        $encodedRequestApiKey = substr($authHeader, strlen("Basic "));
        $encodedStoredApiKey = base64_encode($CC_CONFIG["apiKey"][0] . ":");
        
        if ($encodedRequestApiKey === $encodedStoredApiKey) 
        {
            return true;
        } else {
            return false;
        }
        
        return false;
    }
    
    private function verifySession()
    {
        $auth = Zend_Auth::getInstance();
        if ($auth->hasIdentity())
        {
            return true;
        }
        return false;
        
        //Token checking stub code. We'd need to change LoginController.php to generate a token too, but
        //but luckily all the token code already exists and works.
        //$auth = new Application_Model_Auth();
        //$auth->checkToken(Application_Model_Preference::getUserId(), $token);
    }

    private function fileNotFoundResponse()
    {
        $resp = $this->getResponse();
        $resp->setHttpResponseCode(404);
        $resp->appendBody("ERROR: Media not found."); 
    }

    private function invalidDataResponse()
    {
        $resp = $this->getResponse();
        $resp->setHttpResponseCode(400);
        $resp->appendBody("ERROR: Invalid data");
    }

    private function validateRequestData($file, $whiteList)
    {
        // EditAudioMD form is used here for validation
        $fileForm = new Application_Form_EditAudioMD();
        $fileForm->populate($whiteList);

        if (!$fileForm->isValidPartial($whiteList)) {
            $file->setImportStatus(2);
            $file->setFileHidden(true);
            $this->invalidDataResponse();
            return false;
        }
        return true;
    }

    private function processUploadedFile($callbackUrl, $originalFilename, $ownerId)
    {
        $CC_CONFIG = Config::getConfig();
        $apiKey = $CC_CONFIG["apiKey"][0];
                
        $tempFilePath = $_FILES['file']['tmp_name'];
        $tempFileName = basename($tempFilePath);
        
        //Only accept files with a file extension that we support.
        $fileExtension = pathinfo($originalFilename, PATHINFO_EXTENSION);
        if (!in_array(strtolower($fileExtension), explode(",", "ogg,mp3,oga,flac,wav,m4a,mp4,opus")))
        {
            @unlink($tempFilePath);
            throw new Exception("Bad file extension.");
        }
            
        //TODO: Remove uploadFileAction from ApiController.php **IMPORTANT** - It's used by the recorder daemon...
         
        $storDir = Application_Model_MusicDir::getStorDir();
        $importedStorageDirectory = $storDir->getDirectory() . "/imported/" . $ownerId;
        
        try {
            //Copy the temporary file over to the "organize" folder so that it's off our webserver
            //and accessible by airtime_analyzer which could be running on a different machine.
            $newTempFilePath = Application_Model_StoredFile::copyFileToStor($tempFilePath, $originalFilename);
        } catch (Exception $e) {
            @unlink($tempFilePath);
            Logging::error($e->getMessage());
            return;
        }
        
        Logging::info($newTempFilePath);
        //Logging::info("Old temp file path: " . $tempFilePath);

        //Dispatch a message to airtime_analyzer through RabbitMQ,
        //notifying it that there's a new upload to process!
        Application_Model_RabbitMq::SendMessageToAnalyzer($newTempFilePath,
                 $importedStorageDirectory, $originalFilename,
                 $callbackUrl, $apiKey);
    }

    private function getOwnerId()
    {
        try {
            if ($this->verifySession()) {
                $service_user = new Application_Service_UserService();
                return $service_user->getCurrentUser()->getDbId();
            } else {
                $defaultOwner = CcSubjsQuery::create()
                    ->filterByDbType('A')
                    ->orderByDbId()
                    ->findOne();
                if (!$defaultOwner) {
                    // what to do if there is no admin user?
                    // should we handle this case?
                    return null;
                }
                return $defaultOwner->getDbId();
            }
        } catch(Exception $e) {
            Logging::info($e->getMessage());
        }
    }

    /**
     * 
     * Strips out fields from incoming request data that should never be modified
     * from outside of Airtime
     * @param array $data
     */
    private function removeBlacklistedFieldsFromRequestData($data)
    {
        foreach ($this->blackList as $key) {
            unset($data[$key]);
        }
    
            return $data;
        }

    /**
     * 
     * Strips out the private fields we do not want to send back in API responses
     */
    //TODO: rename this function?
    public function sanitizeResponse($file)
    {
        $response = $file->toArray(BasePeer::TYPE_FIELDNAME);

        foreach ($this->privateFields as $key) {
            unset($response[$key]);
        }

        return $response;
    }

}

