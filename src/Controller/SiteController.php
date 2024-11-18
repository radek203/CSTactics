<?php

declare(strict_types=1);

namespace CSApp\Controller;

use CSApp\SteamAuth\SteamAuthUtils;
use CSApp\View;

class SiteController extends AbstractController
{

    public function run(): void
    {
        $this->checkLogin();
        $this->switchSite();

        $view = new View();
        $view->display($this->params, $this->config);
    }

    private function checkLogin(): void
    {
        if ($this->request->paramGet('login')) {
            SteamAuthUtils::steamLogin($this->config, $this->request);
        }
        if ($this->request->paramGet('logout')) {
            SteamAuthUtils::steamLogout($this->config);
        }
        if ($this->request->paramGet('update')) {
            SteamAuthUtils::steamUpdate($this->config, $this->request);
        }
        if ($this->request->paramSession('steamid')) {
            SteamAuthUtils::steamLoadProfile($this->config, $this->request);

            if ($this->db->checkLogin(intval($this->request->paramSession('steam_steamid')))) {
                $this->params['logged_in'] = TRUE;
                return;
            }

            $this->request->setSession('login_error', 'Poproś właściciela strony o dodanie twojego steam id do bazy kont!');
        }
        if ($this->request->paramSession('login_error')) {
            $this->params['login_error'] = $this->request->paramSession('login_error');
            session_unset();
            session_destroy();
        }
        $this->params['logged_in'] = FALSE;
    }

    private function switchSite(): void
    {
        $siteName = $this->request->paramGet('action', 'main');
        $site = 'load' . ucfirst($siteName) . 'Action';
        if (!method_exists($this, $site)) {
            $siteName = 'main';
            $site = 'loadMainAction';
        }
        $this->$site();
        $this->params['site'] = $siteName;
    }

    private function loadMainAction(): void
    {
        if ($this->params['logged_in']) {
            header('Location: ' . $this->config['site']['url'] . 'maps');
            exit();
        }
    }

    private function loadMapsAction(): void
    {
        if (!$this->params['logged_in']) {
            header('Location: ' . $this->config['site']['url']);
            exit();
        }
        if ($this->request->paramSession('search_error')) {
            $this->params['search_error'] = $this->request->paramSession('search_error');
            $this->request->unsetSession('search_error');
        }
        $this->params['maps'] = $this->db->getMaps();
    }

    private function loadTacticsAction(): void
    {
        if (!$this->params['logged_in']) {
            header('Location: ' . $this->config['site']['url']);
            exit();
        }
        $map = $this->db->getMap(intval($this->request->paramGet('params')));
        if (!$map) {
            $this->request->setSession('search_error', 'Podana mapa id: ' . $this->request->paramGet('params') . ' nie istnieje!');
            header('Location: ' . $this->config['site']['url'] . 'maps');
            exit();
        }
        $this->params['map'] = $map;
        $this->params['tactics'] = $this->db->getTactics($map->getId());
    }

    private function loadTacticAction(): void
    {
        if (!$this->params['logged_in']) {
            header('Location: ' . $this->config['site']['url']);
            exit();
        }
        $tactic = $this->db->getTactic(intval($this->request->paramGet('params')));
        if (!$tactic) {
            $this->request->setSession('search_error', 'Podana taktyka id: ' . $this->request->paramGet('params') . ' nie istnieje!');
            header('Location: ' . $this->config['site']['url'] . 'maps');
            exit();
        }
        $this->params['tactic'] = $tactic;
        $this->params['map'] = $this->db->getMap($tactic->getMapId());
    }

}