<?php
require_once dirname(__FILE__).'/Localizer.php';

function loadTranslations($langid)
{
    Localizer::LoadLanguageFiles('application', $langid, true);
    return Localizer::LoadLanguageFiles('templates', $langid, true);
}
?>