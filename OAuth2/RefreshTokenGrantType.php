<?php namespace AuthExtension\OAuth2;

use OAuth2\ResponseType\AccessTokenInterface;
use OAuth2\GrantType\RefreshToken as BaseRefreshToken;
use OAuth2\Storage\RefreshTokenInterface;

/**
 * @author Brent Shaffer <bshafs at gmail dot com>
 */
class RefreshTokenGrantType extends BaseRefreshToken {

    private IdTokenResponseType $idTokenResponseType;

    public function __construct(RefreshTokenInterface $storage, IdTokenResponseType $idTokenResponseType, $config = []) {
        parent::__construct($storage, $config);
        $this->idTokenResponseType = $idTokenResponseType;
    }

    /**
     * Create access token
     *
     * @param AccessTokenInterface $accessToken
     * @param mixed $client_id - client identifier related to the access token.
     * @param mixed $user_id - user id associated with the access token
     * @param string $scope - scopes to be stored in space-separated string.
     * @return array
     */
    public function createAccessToken(AccessTokenInterface $accessToken, $client_id, $user_id, $scope): array {
        $token = parent::createAccessToken($accessToken, $client_id, $user_id, $scope);

        $scopes = explode(' ', trim($scope));
        $includeIdToken = in_array('openid', $scopes);
        if ($includeIdToken) {
            $token['id_token'] = $this->idTokenResponseType->createIdToken($client_id, $user_id);;
        }

        return $token;
    }

}
