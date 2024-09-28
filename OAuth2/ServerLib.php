<?php namespace AuthExtension\OAuth2;

use AuthExtension\Entities\User;
use AuthExtension\Models\UserModel;
use Config\AuthExtension;
use Config\Database;
use DebugTool\Data;
use OAuth2\GrantType\ClientCredentials;
use OAuth2\OpenID\GrantType\AuthorizationCode;
use OAuth2\RequestInterface;
use OAuth2\Response;
use OAuth2\ResponseType\JwtAccessToken;
use OAuth2\Server;
use OAuth2\Server as OAuth2Server;

class ServerLib {

    private static ServerLib $instance;

    public static function getInstance(): ServerLib {
        if (!isset(self::$instance)) {
            self::$instance = new ServerLib();
        }
        return self::$instance;
    }

    public Server $server;
    public Pdo $storage;

    private function __construct() {
        $this->setup();
    }

    private function setup(): void {
        /** @var AuthExtension $authConfig */
        $authConfig = config('AuthExtension');

        $this->storage = $this->getStorage();

        $idTokenResponseType = new IdTokenResponseType($this->storage, $this->storage, $this->storage, [
            'issuer' => ($_SERVER['HTTP_X_SCHEME'] ?? 'http') . '://' . $_SERVER['HTTP_HOST'],
            'id_lifetime' => $authConfig->oauthAccessTokenLifeTime ?? 900,
        ]);

        // OAuth 2.0 Server configuration.
        // Public & Private key are stored in the PDO storage.
        $this->server = new OAuth2Server(
            $this->storage,
            [
                'store_encrypted_token_string' => true,
                'use_openid_connect' => true,
                'access_lifetime' => $authConfig->oauthAccessTokenLifeTime ?? 900,
                'www_realm' => 'Service',
                'token_param_name' => 'access_token',
                'token_bearer_header_name' => 'Bearer',
                'enforce_state' => true,
                'require_exact_redirect_uri' => true,
                'allow_implicit' => false,
                'enforce_pkce' => true,
                'allow_credentials_in_request_body' => true,
                'allow_public_clients' => true,
                'always_issue_new_refresh_token' => true,
                'unset_refresh_token_after_use' => true,

                'issuer' => ($_SERVER['HTTP_X_SCHEME'] ?? 'http') . '://' . $_SERVER['HTTP_HOST'],
                'refresh_token_lifetime' => $authConfig->oauthRefreshTokenLifeTime ?? (7 * DAY),
            ],

            // Grant Types
            [
                'authorization_code' => new AuthorizationCode($this->storage),
                'refresh_token' => new RefreshTokenGrantType($this->storage, $idTokenResponseType, [
                    'always_issue_new_refresh_token' => true
                ]),
                'client_credentials' => new ClientCredentials($this->storage),
            ],

            // Response Types
            [
                'code' => new \OAuth2\OpenID\ResponseType\AuthorizationCode($this->storage),
                'id_token' => $idTokenResponseType,
                'token' => new JwtAccessToken(
                    $this->storage,
                    $this->storage,
                    $this->storage,
                    [
                        'issuer' => base_url('oauth'),
                        'use_jwt_access_tokens' => true,
                        'jwt_extra_payload_callable' => function(string $clientId, string $userId, ?string $scope) {
                            return [
                                'kid' => 'id1'
                            ];
                        },
                        'access_lifetime' => $authConfig->oauthAccessTokenLifeTime ?? 900,
                    ],
                    new JwtEncryption(),
                )
            ]
        );

        $this->server->setScopeUtil(new ScopeUtil($this->storage));
    }

    private function getStorage(): Pdo {
        /** @var AuthExtension $authConfig */
        $authConfig = config('AuthExtension');

        $db = new Database();
        $dbGroupName = $authConfig->dbGroupName;
        $dbGroup = $db->{$dbGroupName};

        if (isset($dbGroup['DSN']) && strlen($dbGroup['DSN']) > 0) {
            $dsn = $dbGroup['DSN'];
        } else {
            $dsn = "mysql:dbname={$dbGroup['database']};host={$dbGroup['hostname']}";
        }

        return new Pdo(
            [
                'dsn' => $dsn,
                'username' => $dbGroup['username'],
                'password' => $dbGroup['password']
            ],
            [
                'user_table' => 'users',
            ]
        );
    }

    /**
     * @param RequestInterface $request
     * @param null $scope
     * @return array
     */
    public function authorize(RequestInterface $request, $scope = null) {
        $response = [
            'authorized' => null,
            'client_id' => null,
            'user_id' => null,
            'reason' => null,
            'username' => null,
            'user_data' => null,
        ];

        // OAuth 2.0 authentication & scope.
        $oauthResponse = new Response();

        $scopes = $scope ? explode(' ', $scope) : [null];
        $authorized = false;
        foreach ($scopes as $scope) {
            if ($this->server->verifyResourceRequest($request, $oauthResponse, $scope)) {
                $authorized = true;
                break;
            }
        }

        if (!$authorized) {

            $responseBody = json_decode($oauthResponse->getResponseBody());
            if (isset($responseBody->error_description)) {
                $response['reason'] = $responseBody->error_description;
            } else if (isset($responseBody->error)) {
                $response['reason'] = $responseBody->error;
            }
            $response['authorized'] = false;

        } else {

            $token = $this->server->getAccessTokenData($request, $oauthResponse);
            $response['token'] = $token;
            $response['client_id'] = $token['client_id'];

            /** @var User $user */
            $user = (new UserModel())
                ->where('id', $token['user_id'])
                ->find();

            if (!$user->exists()) {
                $response['authorized'] = false;
                $response['reason'] = 'Unknown user';
            } else {
                $response['authorized'] = true;
                $response['user_id'] = $user->id;
                $response['username'] = $user->username;
                $response['user_data'] = $user->toArray();
            }

        }

        return $response;
    }

}
