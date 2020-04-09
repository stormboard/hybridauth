<?php
/*!
* HybridAuth
* http://hybridauth.sourceforge.net | http://github.com/hybridauth/hybridauth
* (c) 2009-2015, HybridAuth authors | http://hybridauth.sourceforge.net/licenses.html
*/

use Stormboard\Exception\DataValidationException;
use Stormboard\{JSON, Log};
use GuzzleHttp\Client as HTTPClient;
use GuzzleHttp\Exception\BadResponseException;

/**
 * Hybrid_Providers_AzureDevOps provider adapter based on OAuth2 protocol
 */
class Hybrid_Providers_AzureDevOps extends Hybrid_Provider_Model_OAuth2{
  /**
   * IDp wrappers initializer
   */
  function initialize(){
    parent::initialize();

    // Provider apis end-points
    $this->api->auth_bearer = true;
    $this->api->api_base_url = 'https://app.vssps.visualstudio.com/oauth2';
    $this->api->authorize_url = 'https://app.vssps.visualstudio.com/oauth2/authorize';
    $this->api->token_url = 'https://app.vssps.visualstudio.com/oauth2/token';
    $this->api->redirect_uri = 'https://stormboard.com/auth/azuredevops';

    // ability to override the redirect_uri
    if(isset($this->config['redirect_uri']) && !empty($this->config['redirect_uri'])){
      $this->api->redirect_uri = $this->config['redirect_uri'];
    }
  }

  /**
   * Retrieve a current access_token
   *
   * @return stdClass
   */
  public function refreshToken(){

    $token = null;
    $client = new HTTPClient();

    try{
      $res = $client->post($this->api->token_url, [
        'form_params' => [
          'client_assertion_type' => 'urn:ietf:params:oauth:client-assertion-type:jwt-bearer',
          'client_assertion' => urlencode($this->api->client_secret),
          'grant_type' => 'refresh_token',
          'assertion' => urlencode($this->api->refresh_token),
          'redirect_uri' => $this->api->redirect_uri
        ],
      ]);

      $token = JSON::decode((string)$res->getBody(), false);
    }catch(BadResponseException $e){
      Log::error('AzureDevOps RefreshToken Error', [
        'code' => $e->getCode(),
        'message' => $e->getMessage(),
      ]);
    }

    return $token;
  }
}
