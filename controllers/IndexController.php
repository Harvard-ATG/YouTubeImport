<?php
/**
 * YoutubeImport
 *
 * @copyright Copyright 2014 UCSC Library Digital Initiatives
 * @license http://www.gnu.org/licenses/gpl-3.0.txt GNU GPLv3
 */

/**
 * The YoutubeImport index controller class.
 *
 * @package YoutubeImport
 */
class YoutubeImport_IndexController extends Omeka_Controller_AbstractActionController
{    

  public function indexAction()
  {
    if(isset($_REQUEST['youtube-import-submit']) )
      {
	if(isset($_REQUEST['youtube-number']) && $_REQUEST['youtube-number']=='single')
	  $this->_importSingle();

	if(isset($_REQUEST['youtube-number']) && $_REQUEST['youtube-number']=='multiple')
	  $this->_importMultiple();

      }

    $this->view->form_collection_options = $this->_getFormCollectionOptions();
    $this->view->form_userrole_options = $this->_getFormUserRoleOptions();

      
  }

  
  private function _importMultiple()
  {
     require_once dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'jobs' . DIRECTORY_SEPARATOR . 'import.php';

    if(isset($_REQUEST['youtube-url']))
      $url = $_REQUEST['youtube-url'];
    else
      die("ERROR WITH PHOTOSET ID POST VAR");

    if(isset($_REQUEST['youtube-collection']))
      $collection = $_REQUEST['youtube-collection'];
    else
      $collection = 0;

    //this is not yet implemented in the view or javascript
    if(isset($_REQUEST['youtube-selecting'])&&$_REQUEST['youtube-selecting']=="true")
      {
	$selecting = true;
	$selected = $_REQUEST['youtube-selected'];
      } 
    else 
      {
	$selecting = false;
	$selected = array();
      }

    if(isset($_REQUEST['youtube-public']))
      $public = $_REQUEST['youtube-public'];
    else 
      $public = false;


    if(isset($_REQUEST['youtube-userrole']))
      $userRole = $_REQUEST['youtube-userrole'];
    else
      $userRole = 0;

    $options = array(
		     'url'=>$url,
		     'collection'=>$collection,
		     'selecting'=>$selecting,
		     'selected'=>$selected,
		     'public'=>$public,
		     'userRole'=>$userRole
		     );

    $dispacher = Zend_Registry::get('job_dispatcher');

    $dispacher->sendLongRunning('YoutubeImport_ImportJob',$options);
    //Zend_Registry::get('bootstrap')->getResource('jobs')->sendLongRunning('YoutubeImport_ImportJob',);

    $flashMessenger = $this->_helper->FlashMessenger;
    $flashMessenger->addMessage('Your Youtube videos are now being imported. This process may take a few minutes. You may continue to work while the photos are imported in the background. You may notice some strange behavior while the photos are uploading, but it will all be over soon.',"success");
  }

  private function _importSingle()
  {
    require_once dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'jobs' . DIRECTORY_SEPARATOR . 'import.php';
    require_once dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'libraries' . DIRECTORY_SEPARATOR . 'Google' . DIRECTORY_SEPARATOR . 'Client.php';
    require_once dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'libraries' . DIRECTORY_SEPARATOR . 'Google' . DIRECTORY_SEPARATOR . 'Services' . DIRECTORY_SEPARATOR . 'YouTube.php';

    $client = new Google_Client();
  	$client->setApplicationName("Omeka _Youtube_Import");
  	$client->setDeveloperKey(YoutubeImport_ImportJob::$youtube_api_key);
  	
  	$service = new Google_Service_YouTube($client);

    if(isset($_REQUEST['youtube-url']))
      $url = $_REQUEST['youtube-url'];
    else
      die("ERROR WITH PHOTOSET ID POST VAR");
/*
    $expUrl = explode("/",$url);

    if(count($expUrl)>1)
      $photoID = $expUrl[5];
    else
      $photoID = $url;
*/

$videoID = $url;

    if(isset($_REQUEST['youtube-collection']))
      $collection = $_REQUEST['youtube-collection'];
    else
      $collection = 0;

    if(isset($_REQUEST['youtube-public']))
      $public = $_REQUEST['youtube-public'];
    else 
      $public = false;

    if(isset($_REQUEST['youtube-userrole']))
      $userRole = $_REQUEST['youtube-userrole'];
    else
      $userRole = 0;

    $post = YoutubeImport_ImportJob::GetVideoPost($videoID,$service,$collection,$userRole,$public);

    $record = new Item();

    $record->setPostData($post);

    if ($record->save(false)) {
      // Succeed silently, since we're in the background	
    } else {
      error_log($record->getErrors());
    }
 
    $flashMessenger = $this->_helper->FlashMessenger;
    $flashMessenger->addMessage('Your youtube video was imported into Omeka successfully','success');

  }

  /**
   * Get an array to be used in formSelect() containing all collections.
   * 
   * @return array
   */
  private function _getFormCollectionOptions()
  {
    $collections = get_records('Collection',array(),'0');
    $options = array('0'=>'Create New Collection');
    foreach ($collections as $collection)
      {
	if(isset($collection->getElementTexts('Dublin Core','Title')[0]))
	  {
	    $title = $collection->getElementTexts('Dublin Core','Title')[0];
	    $options[$collection->id]=$title;
	  }
      }
    return $options;
  }

  /**
   * Get an array to be used in formSelect() containing possible roles for users.
   * 
   * @return array
   */
  private function _getFormUserRoleOptions()
  {
    $options = array(
		     '0'=>'No Role',
		     '37'=>'Contributor',
		     '39'=>'Creator',
		     '45'=>'Publisher'
		     );
    return $options;
  }


}
