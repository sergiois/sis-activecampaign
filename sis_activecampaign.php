<?php
/**
 * @package     Joomla.Plugin
 * @subpackage  User.SIS_ActiveCampaign
 *
 * @copyright	Copyright © 2021 SergioIglesiasNET - All rights reserved.
 * @license		GNU General Public License v2.0
 * @author 		Sergio Iglesias (@sergiois)
 */

use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\CMSPlugin;

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die( 'Restricted access' );

class PlgUserSIS_ActiveCampaign extends CMSPlugin
{
	protected $app;

	function onUserAfterSave($user, $isnew, $success, $msg)
	{
		$app = Factory::getApplication();
		
		if ($app->isClient('administrator'))
		{
			return;
		}

		if($isnew && $app->isClient('site'))
		{
			try
			{  
				$url = $this->params->get('apiurl') . "/admin/api.php";
				$api_key = $this->params->get('apikey');
				$url .= "?api_key=" . $api_key;

				$sub = $status = array();

				$email = $user['email'];
				$first_name = $user['name'];
				$listid = $this->params->get('listid');

				$sub[] = "p[" . $listid . "]=" . $listid;
				$status[] = "status[" . $listid . "]=1";

				$substr = implode("&", $sub);
				$statusstr = implode("&", $status);

				$url_subscriber_add = $url . "&api_action=subscriber_add&api_output=json";

				$post_str = "email={$email}&first_name=".$first_name;
				$post_str .= "&" . $substr . "&" . $statusstr;

				$c = curl_init();
				curl_setopt($c, CURLOPT_URL, $url_subscriber_add);
				curl_setopt($c, CURLOPT_POST, 1);
				curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt($c, CURLOPT_FOLLOWLOCATION, 1);
				curl_setopt($c, CURLOPT_SSL_VERIFYPEER, 0);
				curl_setopt($c, CURLOPT_POSTFIELDS, $post_str);
				$response = (string)curl_exec($c);
				curl_close($c);
				$result = json_decode($response);

				// Comprobamos si hay etiquetas
				$tags = explode(',',$this->params->get('tags'));
				if(count($tags) > 0)
				{
					foreach($tags as $tag)
					{
						$url_tag_add = $url . "&api_action=contact_tag_add&api_output=json";
						$post_tag = array(
							'email' => $email,
							'tags' => $tag
						);
						$c = curl_init();
						curl_setopt($c, CURLOPT_URL, $url_tag_add);
						curl_setopt($c, CURLOPT_POST, 1);
						curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
						curl_setopt($c, CURLOPT_FOLLOWLOCATION, 1);
						curl_setopt($c, CURLOPT_SSL_VERIFYPEER, 0);
						curl_setopt($c, CURLOPT_POSTFIELDS, $post_tag);
						$response = (string)curl_exec($c);
						curl_close($c);
						$result = json_decode($response);
					}
				}
			}
			catch (SoapFault $exception)
			{  
				echo 'CATCH: '.$exception->faultstring; 
			}
		}
	}
}