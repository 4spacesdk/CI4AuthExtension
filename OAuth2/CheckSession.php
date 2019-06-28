<?php namespace AuthExtension\OAuth2;

use CodeIgniter\HTTP\Request;
use CodeIgniter\HTTP\Response;

/**
 * Class CheckSession
 * @package AuthExtension\OAuth2
 */
class CheckSession {

    public static function handle() {
        echo <<<EOT
<!DOCTYPE html>
<html>
    <head>
        <meta http-equiv="content-type" content="text/html;charset=UTF-8" />
        <title>OP iFrame</title>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/crypto-js/3.1.9-1/crypto-js.js"></script>
    </head>
    <body>
        <script type='text/javascript'>
            window.addEventListener("message", receiveMessage, false);
            
            function getCookie(c_name) {
                var i, x, y, ARRcookies = document.cookie.split(";");
                for (i = 0; i < ARRcookies.length; i++) {
                    x = ARRcookies[i].substr(0, ARRcookies[i].indexOf("="));
                    y = ARRcookies[i].substr(ARRcookies[i].indexOf("=") + 1);
                    x = x.replace(/^\s+|\s+$/g, "");
                    if (x == c_name) {
                        return unescape(y);
                    }
                }
            }
            
            function receiveMessage(e) {
                var client_id = e.data.split(' ')[0];
                var session_state = e.data.split(' ')[1];
                var salt = session_state.split('.')[1];
                
                var opbs = getCookie('opbs');
                var ss = CryptoJS.SHA256(client_id + e.origin + opbs + salt) + "." + salt;
                var state = '';
                if (session_state == ss) {
                    state = 'unchanged';
                } else {
                    state = 'changed';
                }
                e.source.postMessage(state, e.origin);
            };
        </script>
    </body>
</html>
EOT;
        exit;
    }

}