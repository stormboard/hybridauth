<?php
/**
 * LinkedIn OAuth2 Class
 *
 * Hybrid_Providers_LinkedIn - LinkedIn provider adapter based on OAuth2 protocol
 */
class Hybrid_Providers_LinkedIn extends Hybrid_Provider_Model_OAuth2{

  /**
   * {@inheritdoc}
   */
  public $scope = "r_liteprofile r_emailaddress w_member_social";

  /**
   * {@inheritdoc}
   */
  function initialize(){
    parent::initialize();

    // Provider api end-points
    $this->api->auth_bearer = true;
    $this->api->api_base_url = 'https://api.linkedin.com/v2/';
    $this->api->authorize_url = 'https://www.linkedin.com/oauth/v2/authorization';
    $this->api->token_url = 'https://www.linkedin.com/oauth/v2/accessToken';

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

    $fields = [
      'id',
      'firstName',
      'lastName',
      'profilePicture(displayImage~:playableStreams)',
    ];

    //Refresh tokens if needed
    $this->refreshToken();
    $data = $this->api->get('me?projection=(' . implode(',', $fields) . ')');

    if(!isset($data->id)){
      throw new Exception("User profile request failed! {$this->providerId} returned an invalid response: ".Hybrid_Logger::dumpData($data), 6);
    }

    $this->user->profile->identifier = $data->id;

    if(property_exists($data, 'firstName')){
      $this->user->profile->firstName =  $this->getName($data->firstName);
    }
    if(property_exists($data, 'lastName')){
      $this->user->profile->lastName =  $this->getName($data->lastName);
    }

    $this->user->profile->email = $this->getUserEmail();
    $this->user->profile->displayName = trim($this->user->profile->firstName.' '.$this->user->profile->lastName);

    return $this->user->profile;
  }

  /**
   * Retrieve the name from the object
   *
   * @param $attr
   */
  private function getName($attr): string{

    if(property_exists($attr, 'localized')){
      // Catch the easiest one en_US
      if(property_exists($attr->localized, 'en_US')){
        return $attr->localized->en_US;
      }

      // Use the user's preferredLocale to retrieve their name
      if(property_exists($attr, 'preferredLocale')){
        $preferredLocale = $attr->preferredLocale;
        if(property_exists($preferredLocale, 'country') && property_exists($preferredLocale, 'language')){
          $locale = implode('_', [$preferredLocale->language, $preferredLocale->country]);

          if(!empty($attr->localized) && !empty($attr->localized->$locale)){
            return $attr->localized->$locale;
          }
        }
      }
    }

    return '';
  }

  /**
   * Returns an email address of user.
   *
   * @return string
   *   The user email address.
   */
  private function getUserEmail(): string{

    $data = $this->api->get('emailAddress?q=members&projection=(elements*(handle~))');

    if(!empty($data->elements)){
      foreach($data->elements as $element){
        if(!empty($element->{'handle~'}) && !empty($element->{'handle~'}->emailAddress)){
          return $element->{'handle~'}->emailAddress;
        }
      }
    }

    return '';
  }
}