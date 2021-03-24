<?php
// namespace MediaWiki\Extension\NaylorAMS;

// not really needed, but left here in case one day the AuthenticateUser endpoint is removed

use \MediaWiki\Auth\AuthManager;
use PluggableAuthLogin;
use User;

class NaylorAMSWithAuthToken extends PluggableAuth {

    private $userId = 0;
    private $username = '';
    private $email = '';
    private $realname = '';

    public function authenticate(&$id, &$username, &$realname, &$email, &$errorMsg) {
        // Initialize singletons
        $config = Config::newInstance();
        $authManager = AuthManager::singleton();

        $authKey = $username;

        // get $NaylorAMSBaseUrl and $NaylorAMSSecurityKey
        $baseUrl = $config->get( "BaseUrl" );
        $securityKey = $config->get( "SecurityKey" );

        // Make cURL request to Naylor AMS ValidateAuthenticationToken endpoint
        $validateAuthEndpoint = $baseUrl . '/api/ValidateAuthenticationToken';

        $validateAuthResult = timberlakeRequest(
            $validateAuthEndpoint,
            array(
                'token' => $authKey
            ),
            $securityKey
        );
        $naylorUserId = $validateAuthResult['ValidateAuthenticationTokenRequest'] ?? '';

        if ($naylorUserId === '') {
            $errorMsg = 'The Naylor user ID could not be retrieved. This may indicate the authentication token was invalid.';
            return false;
        }

        $basicInfoEndpoint = $baseUrl . '/api/GetBasicMemberInfo';
        $userDetailsResult = timberlakeRequest(
            $basicInfoEndpoint,
            array(
                'ContactID' => $naylorUserId,
            ),
            $securityKey
        );
        
        // Up to this point, the $username is actually the authentication token
        $username = 'the actual username';
        // Check if user already exists; otherwise, leave $id invalid and make a new user
        $user = User::newFromName( $username );
		if ( $user !== false && $user->getId() !== 0 ) {
			$id = $user->getId();
        }
        
        return true;
    }

    /*
     * Makes a REST call to the API using cURL
    */
    protected function timberlakeRequest($endpoint, $params, $securityKey) {
        $postFields = array_merge(
            $params,
            array(
                'securityKey' => $securityKey
            )
        );
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $endpoint);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postFields));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $responseStr = curl_exec($ch);
        curl_close($ch);
        $responseArr = new SimpleXMLElement($responseStr);
        return $responseArr;
    }
}