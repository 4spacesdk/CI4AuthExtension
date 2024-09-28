<?php namespace AuthExtension\OAuth2;

use AuthExtension\Entities\User;
use LogicException;
use OAuth2\Encryption\EncryptionInterface;
use OAuth2\OpenID\ResponseType\IdToken;
use OAuth2\OpenID\Storage\UserClaimsInterface;
use OAuth2\Storage\PublicKeyInterface;

class IdTokenResponseType extends IdToken {

    protected IdTokenStorageInterface $tokenStorage;

    public function __construct(UserClaimsInterface $userClaimsStorage, PublicKeyInterface $publicKeyStorage, IdTokenStorageInterface $tokenStorage, array $config = array(), EncryptionInterface $encryptionUtil = null) {
        parent::__construct($userClaimsStorage, $publicKeyStorage, $config, $encryptionUtil);
        $this->tokenStorage = $tokenStorage;
    }

    public function createIdToken($client_id, $userInfo, $nonce = null, $userClaims = null, $access_token = null) {
        // pull auth_time from user info if supplied
        [$user_id, $auth_time] = $this->getUserIdAndAuthTime($userInfo);

        $token = [
            'iss'        => $this->config['issuer'],
            'sub'        => $user_id,
            'aud'        => $client_id,
            'iat'        => time(),
            'exp'        => time() + $this->config['id_lifetime'],
            'auth_time'  => $auth_time,
        ];

        if ($nonce) {
            $token['nonce'] = $nonce;
        }

        if ($access_token) {
            $token['at_hash'] = $this->createAtHash($access_token, $client_id);
        }

        // set custom claims
        $oauthUser = new User();
        $oauthUser->find($user_id);
        $userClaims['first_name'] = $oauthUser->first_name;
        $userClaims['last_name'] = $oauthUser->last_name;
        $userClaims['email'] = $oauthUser->username;
        $userClaims['scope'] = $oauthUser->scope;

        if ($userClaims) {
            $token += $userClaims;
        }

        $idToken = $this->encodeToken($token, $client_id);

        $this->tokenStorage->setIdToken(
            $idToken,
            $client_id,
            $user_id,
            $token['exp'],
            $nonce,
            json_encode($userClaims)
        );

        return $idToken;
    }

    /**
     * @param $userInfo
     * @return array
     * @throws LogicException
     */
    private function getUserIdAndAuthTime($userInfo): array {
        $auth_time = null;

        // support an array for user_id / auth_time
        if (is_array($userInfo)) {
            if (!isset($userInfo['user_id'])) {
                throw new LogicException('if $user_id argument is an array, user_id index must be set');
            }

            $auth_time = $userInfo['auth_time'] ?? null;
            $user_id = $userInfo['user_id'];
        } else {
            $user_id = $userInfo;
        }

        if (is_null($auth_time)) {
            $auth_time = time();
        }

        // userInfo is a scalar, and so this is the $user_id. Auth Time is null
        return array($user_id, $auth_time);
    }

}
