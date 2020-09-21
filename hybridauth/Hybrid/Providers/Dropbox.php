<?php
/*!
* HybridAuth
* http://hybridauth.sourceforge.net | http://github.com/hybridauth/hybridauth
* (c) 2009-2015, HybridAuth authors | http://hybridauth.sourceforge.net/licenses.html
*/

/**
 * Hybrid_Providers_Dropbox provider adapter based on OAuth2 protocol
 *
 * http://hybridauth.sourceforge.net/userguide/IDProvider_info_Dropbox.html
 */
class Hybrid_Providers_Dropbox extends Hybrid_Provider_Model_OAuth2{
  /**
   * IDp wrappers initializer
   */

  function initialize(){
    if(!$this->config["keys"]["id"] || !$this->config["keys"]["secret"]){
      throw new Exception("Your application id and secret are required in order to connect to {$this->providerId}.", 4);
    }

    // override requested scope
    if(isset($this->config["scope"]) && !empty($this->config["scope"])){
      $this->scope = $this->config["scope"];
    }

    // include OAuth2 client
    require_once Hybrid_Auth::$config["path_libraries"]."OAuth/OAuth2Client.php";

    // create a new OAuth2 client instance
    $this->api = new DropboxV2Client($this->config["keys"]["id"], $this->config["keys"]["secret"], $this->endpoint);

    // If we have an access token, set it
    if($this->token("access_token")){
      $this->api->access_token = $this->token("access_token");
      $this->api->refresh_token = $this->token("refresh_token");
      $this->api->access_token_expires_in = $this->token("expires_in");
      $this->api->access_token_expires_at = $this->token("expires_at");
    }

    // Set curl proxy if exist
    if(isset(Hybrid_Auth::$config["proxy"])){
      $this->api->curl_proxy = Hybrid_Auth::$config["proxy"];
    }

    // Provider apis end-points
    $this->api->api_base_url = "https://api.dropboxapi.com/2/";
    $this->api->authorize_url = "https://www.dropbox.com/oauth2/authorize";
    $this->api->token_url = "https://api.dropboxapi.com/oauth2/token";
  }

  /**
   * {@inheritdoc}
   */
  function loginBegin() {
    $parameters = array("scope" => $this->scope, "token_access_type" => "offline");
    Hybrid_Auth::redirect($this->api->authorizeUrl($parameters));
  }

  /**
   * load the user profile from the IDp api client
   */
  function getUserProfile(){
    // refresh tokens if needed
    $this->refreshToken();

    try{
      $this->api->curl_header = array(
        'Authorization: Bearer '.$this->api->access_token,
      );
      $response = $this->api->api("users/get_current_account", 'POST', null);
    }catch(DropboxException $e){
      throw new Exception("User profile request failed! {$this->providerId} returned an error: $e", 6);
    }

    // check the last HTTP status code returned
    if($this->api->http_code != 200){
      throw new Exception("User profile request failed! {$this->providerId} returned an error. ".$this->errorMessageByStatus($this->api->http_code), 6);
    }

    $user = json_decode($response);
    if(!is_object($user) || !isset($user->account_id)){
      throw new Exception("User profile request failed! {$this->providerId} api returned an invalid response.", 6);
    }
    # store the user profile.
    $this->user->profile->identifier = (property_exists($user, 'account_id')) ? $user->account_id : "";
    $this->user->profile->profileURL = "";
    $this->user->profile->webSiteURL = "";
    $this->user->profile->photoURL = "";
    $this->user->profile->firstName = (property_exists($user, 'name')) ? (property_exists($user->name, 'display_name')) ? $user->name->display_name : "" : "";
    $this->user->profile->description = "";
    $this->user->profile->firstName = (property_exists($user, 'name')) ? (property_exists($user->name, 'given_name')) ? $user->name->given_name : "" : "";
    $this->user->profile->lastName = (property_exists($user, 'name')) ? (property_exists($user->name, 'surname')) ? $user->name->surname : "" : "";
    $this->user->profile->gender = "";
    $this->user->profile->language = "";
    $this->user->profile->age = "";
    $this->user->profile->birthDay = "";
    $this->user->profile->birthMonth = "";
    $this->user->profile->birthYear = "";
    $this->user->profile->email = (property_exists($user, 'email')) ? $user->email : "";
    $this->user->profile->emailVerified = "";
    if(property_exists($user, 'email_verified')){
      if($user->email_verified){
        $this->user->profile->emailVerified = $this->user->profile->email;
      }
    }
    $this->user->profile->phone = "";
    $this->user->profile->address = "";
    $this->user->profile->country = (property_exists($user, 'country')) ? $user->country : "";
    $this->user->profile->region = "";
    $this->user->profile->city = "";
    $this->user->profile->zip = "";

    return $this->user->profile;
  }
}
