<?php namespace AuthExtension\OAuth2;

use App\Entities\User;
use App\Models\UserModel;
use CodeIgniter\Config\Config;
use Config\AuthExtension;
use Config\Database;
use OAuth2\GrantType\AuthorizationCode;
use OAuth2\GrantType\ClientCredentials;
use OAuth2\GrantType\RefreshToken;
use OAuth2\RequestInterface;
use OAuth2\Response;
use OAuth2\Server as OAuth2Server;
use OAuth2\Storage\Pdo;
use RestExtension\AuthorizeResponse;

/**
 * Class ServerLib
 * @package AuthExtension\OAuth2
 * @property OAuth2Server $server
 * @property PDO $storage
 * @property string $error
 */
class ServerLib {

    private static $instance;

    /**
     * @return ServerLib
     */
    public static function getInstance() {
        if(!self::$instance)
            self::$instance = new ServerLib();
        return self::$instance;
    }

    private function __construct() {
        $this->setup();
    }

    private function setup() {
        /** @var AuthExtension $config */
        $config = Config::get('AuthExtension');
        $db = new Database();
        $dbGroupName = $config->dbGroupName;
        $dbGroup = $db->{$dbGroupName};
        $dsn = "mysql:dbname={$dbGroup['database']};host={$dbGroup['hostname']}";
        $dbUsername = $dbGroup['username'];
        $dbPassword = $dbGroup['password'];

        // To match claims, you need to redefine the "users" table attributes.
        // http://openid.net/specs/openid-connect-core-1_0.html#Claims
        $config = array(
            'user_table' => 'users'
        );

        // MySql db.
        $this->storage = new Pdo(array(
            'dsn' => $dsn, 'username' => $dbUsername, 'password' => $dbPassword
        ), $config);

        // OAuth 2.0 Server configuration.
        // Public & Private key are stored in the PDO storage.
        $this->server = new OAuth2Server(
            $this->storage,
            [
                'enforce_state' => true,
                'allow_implicit' => true,
                'use_openid_connect' => true,
                'issuer' => (isset($_SERVER['HTTP_X_SCHEME']) ? $_SERVER['HTTP_X_SCHEME'] : 'http') . '://' . $_SERVER['HTTP_HOST'],
                /*
                 * Where a self-contained token (JWT token) is hard to revoke before its expiration time,
                 * a reference token only lives as long as it exists in the STS data store. This allows for scenarios like:
                 *
                 * - revoking the token in an “emergency” case (lost phone, phishing attack etc.)
                 * - invalidate tokens at user logout time or app uninstall
                 * https://leastprivilege.com/2015/11/25/reference-tokens-and-introspection/
                 *
                 *'use_jwt_access_tokens' => true,
                 */
                'id_lifetime' => 900,
                'access_lifetime' => $config->oauthAccessTokenLifeTime ?? 900, // 900 = 15min
                'require_exact_redirect_uri' => false, // For silent refresh.
                'always_issue_new_refresh_token' => true
            ]
        );

        $this->server->addGrantType(new ClientCredentials($this->storage));
        $this->server->addGrantType(new AuthorizationCode($this->storage));
        $this->server->addGrantType(new RefreshToken($this->storage, [
            'always_issue_new_refresh_token' => true
        ]));
    }

    /**
     * @param RequestInterface $request
     * @param null $scope
     * @return array
     */
    public function authorize(RequestInterface $request, $scope = null) {
        $response = [
            'authorized'    => null,
            'client_id'     => null,
            'user_id'       => null,
            'reason'        => null,
            'username'      => null
        ];

        // OAuth 2.0 authentication & scope.
        $oauthResponse = new Response();

        $scopes = $scope ? explode(' ', $scope) : [null];
        $authorized = false;
        foreach($scopes as $scope) {
            if($this->server->verifyResourceRequest($request, $oauthResponse, $scope)) {
                $authorized = true;
                break;
            }
        }

        if(!$authorized) {

            $responseBody = json_decode($oauthResponse->getResponseBody());
            if(isset($responseBody->error_description))
                $response['reason'] = $responseBody->error_description;
            else if(isset($responseBody->error))
                $response['reason'] = $responseBody->error;
            $response['authorized'] = false;

        } else {

            $token = $this->server->getAccessTokenData($request, $oauthResponse);
            $response['client_id'] = $token['client_id'];
            //$client = $this->server->getStorage('client')->getClientDetails($token['client_id']);

            $user = new User();
            $user = $user->_getModel()
                ->where('id', $token['user_id'])
                ->find();

            if(!$user->exists()) {
                $response['authorized'] = false;
                $response['reason'] = 'Unknown user';
            } else {
                $response['authorized'] = true;
                $response['user_id'] = $user->id;
                $response['username'] = $user->username;
            }

        }

        return $response;
    }

    public function getError() {
        return $this->error;
    }

}
