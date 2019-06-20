<?php

/**
 * Jira OAuth2 for 3LO Class
 *
 * @package             HybridAuth providers package
 * @author              Michael Bollman
 */

/**
 * Hybrid_Providers_Jira3LO - Jira provider adapter based on OAuth2 protocol
 */
class Hybrid_Providers_Jira3LO extends Hybrid_Provider_Model_OAuth2{

  /**
   * {@inheritdoc}
   */
  public $scope = "read:jira-user,write:jira-work,read:jira-work,offline_access";

  /**
   * {@inheritdoc}
   */
  function initialize(){
    parent::initialize();

    // Provider api end-points
    $this->api->auth_bearer = true;
    $this->api->api_base_url = 'https://api.atlassian.com/';
    $this->api->authorize_url = 'https://login.microsoftonline.com/common/oauth2/v2.0/authorize';
    $this->api->token_url = 'https://auth.atlassian.com/oauth/token';

    // Override the redirect uri when it's set in the config parameters. This way we prevent
    // redirect uri mismatches when authenticating with Live.com
    if(isset($this->config['redirect_uri']) && !empty($this->config['redirect_uri'])){
      $this->api->redirect_uri = $this->config['redirect_uri'];
    }
  }
}
