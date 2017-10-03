<?php
include_once "../../../config/config.php";
include_once ROOT_PATH.'lib/ajax.class.php';
include_once ADMIN_PATH."lib/functions.php";
include_once ADMIN_PATH.'lib/spyc.php';
include_once ADMIN_PATH.'lib/gcFeature.class.php';
include_once ADMIN_PATH.'lib/gcMapfile.class.php';
include_once ADMIN_PATH.'lib/gcFeatureQGis.class.php';
include_once ADMIN_PATH.'lib/gcQGisConfig.class.php';
include_once ROOT_PATH."lib/i18n.php";


$ajax = new GCAjax();

if(empty($_REQUEST['action'])) $ajax->error();

switch($_REQUEST['action']) {
	case 'refresh':
		if(empty($_REQUEST['project'])) $ajax->error(2);
		if(defined('PROJECT_MAPFILE') && PROJECT_MAPFILE){
            //GCAuthor::refreshProjectMapfile($_REQUEST['project'], ($_REQUEST['target'] == 'public'));
            $qgisfile = new gcQGisConfig();
            $qgisfile->writeProjectMapfile = true;
            $qgisfile->writeMap("project",$_REQUEST['project']);

            $localization = new GCLocalization($_REQUEST['project']);
            $alternativeLanguages = $localization->getAlternativeLanguages();
            if($alternativeLanguages){
                foreach($alternativeLanguages as $languageId => $foo) {
                    $qgisfile = new gcQGisConfig($languageId);
                    $qgisfile->writeMap('project', $project);
                }
            }
        } else {
            if(empty($_REQUEST['mapset'])) {
                //GCAuthor::refreshMapfiles($_REQUEST['project'], ($_REQUEST['target'] == 'public'));
                $qgisfile = new gcQGisConfig();
        		$qgisfile->writeMap("project",$_REQUEST['project']);

        		$localization = new GCLocalization($_REQUEST['project']);
        		$alternativeLanguages = $localization->getAlternativeLanguages();
        		if($alternativeLanguages){
        			foreach($alternativeLanguages as $languageId => $foo) {
        				$qgisfile = new gcQGisConfig($languageId);
        				$qgisfile->writeMap('project', $_REQUEST['project']);
        			}
        		}
            } else {
                $qgisfile = new gcQGisConfig();
        		$qgisfile->writeMap("mapset", $_REQUEST['mapset']);

        		$localization = new GCLocalization($_REQUEST['project']);
        		$alternativeLanguages = $localization->getAlternativeLanguages();
        		if($alternativeLanguages){
        			foreach($alternativeLanguages as $languageId => $foo) {
        				$qgisfile = new gcQGisConfig($languageId);
        				$qgisfile->writeMap('mapset', $_REQUEST['mapset']);
        			}
        		}
            }
        }
		$errors = GCError::get();
		if(!empty($errors)) {
			foreach($errors as &$error) $error = str_replace(array('"', "\n"), array('\"', '<br>'), $error);
			unset($error);
			$ajax->error(array('type'=>'qgis_errors', 'text'=>implode('<br><br>', $errors)));
		}
		$ajax->success();
	break;
}
