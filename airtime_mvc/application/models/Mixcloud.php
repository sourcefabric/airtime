<?php

class Application_Model_Mixcloud
{
    public static function uploadMixcloud($id)
    {
        $cmd = "/usr/lib/airtime/utils/mixcloud-uploader $id > /dev/null &";
        Logging::info("Uploading to mixcloud with command: $cmd");
        exec($cmd);
    }
}