<?php

/* !
 * HybridAuth
 * http://hybridauth.sourceforge.net | http://github.com/hybridauth/hybridauth
 * (c) 2009-2012, HybridAuth authors | http://hybridauth.sourceforge.net/licenses.html
 */

/**
 * Microsoft Graph OAuth2 Class
 *
 * @package             HybridAuth providers package
 * @author              Stormboard
 */

/**
 * Hybrid_Providers_Live - Windows Live provider adapter based on OAuth2 protocol
 */
class Hybrid_Providers_MicrosoftGraph extends Hybrid_Provider_Model_OAuth2{

  /**
   * {@inheritdoc}
   */
  public $scope = "openid user.read contacts.read";

  /**
   * {@inheritdoc}
   */
  function initialize(){
    parent::initialize();

    // Provider api end-points
    $this->api->auth_bearer = true;
    $this->api->api_base_url = 'https://graph.microsoft.com/v1.0/';
    $this->api->authorize_url = 'https://login.microsoftonline.com/common/oauth2/v2.0/authorize';
    $this->api->token_url = 'https://login.microsoftonline.com/common/oauth2/v2.0/token';

    // Override the redirect uri when it's set in the config parameters. This way we prevent
    // redirect uri mismatches when authenticating with Live.com
    if(isset($this->config['redirect_uri']) && !empty($this->config['redirect_uri'])){
      $this->api->redirect_uri = $this->config['redirect_uri'];
    }
  }

  /**
   * {@inheritdoc}
   */
  function getUserProfile(){

    //Refresh tokens if needed
    $this->refreshToken();
    $data = $this->api->get("me");

    if(!isset($data->id)){
      throw new Exception("User profile request failed! {$this->providerId} returned an invalid response: ".Hybrid_Logger::dumpData($data), 6);
    }

    $this->user->profile->identifier = property_exists($data, 'id') ? $data->id : '';
    $this->user->profile->firstName = property_exists($data, 'givenName') ? $data->givenName : '';
    $this->user->profile->lastName = property_exists($data, 'surname') ? $data->surname : '';
    $this->user->profile->displayName = property_exists($data, 'displayName') ? trim($data->displayName) : '';
    $this->user->profile->email = property_exists($data, 'userPrincipalName') ? $data->userPrincipalName : '';

    return $this->user->profile;
  }

  /**
   * Windows Live api does not support retrieval of email addresses (only hashes :/)
   * {@inheritdoc}
   */
  function getUserContacts(){
    //Refresh tokens if needed
    //$this->refreshToken();
    $response = $this->api->get('me/contacts?$top=50');

    if($this->api->http_code != 200){
      throw new Exception('User contacts request failed! '.$this->providerId.' returned an error: '.$this->errorMessageByStatus($this->api->http_code));
    }

    if(!isset($response->data) || (isset($response->errcode) && $response->errcode != 0)){
      return array();
    }

    $contacts = array();

    foreach($response->data as $item){
      $uc = new Hybrid_User_Contact();

      $uc->identifier = (property_exists($item, 'id')) ? $item->id : "";
      $uc->displayName = (property_exists($item, 'name')) ? $item->name : "";
      $uc->email = (property_exists($item, 'emails')) ? $item->emails->preferred : "";
      $contacts[] = $uc;
    }

    return $contacts;
  }

}
