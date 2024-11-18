<?php
declare(strict_types=1);

namespace CSApp\SteamAuth;

use CSApp\Request;
use ErrorException;
use JetBrains\PhpStorm\NoReturn;

class SteamAuthUtils
{

    /**
     * Function sends LightOpenID request to Steam servers, redirects user to Steam login page and validates user login.
     * We need to use header() or script here depending on headers already sent or not.
     *
     * @param array $config
     * @param Request $request
     */
    public static function steamLogin(array $config, Request $request): void
    {
        try {
            $openid = new LightOpenID($config['steam']['domainname']);

            if (!$openid->getMode()) {
                $openid->setIdentity('https://steamcommunity.com/openid');
                header('Location: ' . $openid->authUrl());
            } elseif ($openid->getMode() === 'cancel') {
                $request->setSession('login_error', 'User has canceled authentication!');
            } elseif ($openid->validate()) {
                $steamID = str_replace('https://steamcommunity.com/openid/id/', '', $openid->getIdentity());
                $request->setSession('steamid', $steamID);

                if (!headers_sent()) {
                    header('Location: ' . $config['site']['url']);
                } else {
                    ?>
                    <script type="text/javascript">window.location.href = "<?php echo $config['site']['url'] ?>";</script>
                    <noscript>
                        <meta http-equiv="refresh" content="0;url=<?php echo $config['site']['url'] ?>">
                    </noscript>
                    <?php
                }
            } else {
                $request->setSession('login_error', 'User is not logged in.');
            }
        } catch (ErrorException $e) {
            echo $e->getMessage();
        }
        exit;
    }

    /**
     * Function logs out user and clears session.
     * We need to use header() function here, because we need to redirect user to page without 'logout' parameter in URL to avoid multiple logout calls.
     *
     * @param array $config
     * @return void
     */
    #[NoReturn] public static function steamLogout(array $config): void
    {
        session_unset();
        session_destroy();
        header('Location: ' . $config['site']['url']);
        exit;
    }

    /**
     * Function unsets 'steam_uptodate' session parameter to force user profile data update.
     * We need to use header() function here, because we need to redirect user to page without 'update' parameter in URL to avoid multiple updates.
     *
     * @param array $config
     * @param Request $request
     * @return void
     */
    #[NoReturn] public static function steamUpdate(array $config, Request $request): void
    {
        $request->unsetSession('steam_uptodate');
        header('Location: ' . $config['site']['url']);
        exit;
    }

    /**
     * Function loads user profile data from Steam API and saves it to session.
     * We use only first element from Steam API response, because we are requesting data only for one user.
     *
     * @param array $config
     * @param Request $request
     */
    public static function steamLoadProfile(array $config, Request $request): void
    {
        if (!$request->paramSession('steam_uptodate')) {
            $player = self::getDataFromAPI($config, $request->paramSession('steamid'))[0];

            $request->setSession('steam_steamid', $player['steamid']);
            $request->setSession('steam_personaname', $player['personaname']);
            $request->setSession('steam_avatarfull', $player['avatarfull']);
        }
    }

    /**
     * Function loads user profile(s) data from Steam API.
     *
     * @param array $config
     * @param string $steamids
     * @return array
     */
    public static function getDataFromAPI(array $config, string $steamids): array
    {
        $url = file_get_contents('https://api.steampowered.com/ISteamUser/GetPlayerSummaries/v0002/?key=' . $config['steam']['apikey'] . '&steamids=' . $steamids);
        $content = json_decode($url, true);

        return $content['response']['players'];
    }

}