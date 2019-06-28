<?php namespace AuthExtension\OAuth2;

use App\Entities\User;
use App\Models\UserModel;
use CodeIgniter\Config\Config;
use Config\Database;
use OAuth2\GrantType\ClientCredentials;
use OAuth2\RequestInterface;
use OAuth2\Response;
use OAuth2\Server as OAuth2Server;
use OAuth2\Storage\Pdo;

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
     * @return mixed
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
        $db = new Database();
        $dbGroupName = Config::get('AuthExtension')->dbGroupName;
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
                'access_lifetime' => 900, // 900 = 15min
                'require_exact_redirect_uri' => false // For silent refresh.
            ]
        );

        $this->server->addGrantType(new ClientCredentials($this->storage));
    }

    public function authorize(RequestInterface $request) {
        $response = new Response();

        // OAuth 2.0 authentication & scope.
        if(!$this->server->verifyResourceRequest($request, $response, null)) {
            $this->error = json_decode($response->getResponseBody());
            return false;
        }

        $token = $this->server->getAccessTokenData($request, $response);
        $client = $this->server->getStorage('client')->getClientDetails($token['client_id']);

        $user = new User();

        switch($client['grant_types']) {
            case 'client_credentials':
                $clientId = $token['client_id'];
                /** @var User $user */
                $user = (new UserModel())
                    ->where('username', $clientId)
                    ->find();
                break;
            case 'implicit':
                $user = $user->getModel()
                    ->where('id', $token['user_id'])
                    ->find();
                break;
        }

        if(!$user->exists()) {
            $this->error = ['error' => 'Unknown user'];
            return false;
        }

        return $user;
    }

    public function getError() {
        return $this->error;
    }

}