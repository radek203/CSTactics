<?php

declare(strict_types=1);

namespace CSApp\Model;

use PDO;

class SiteModel extends AbstractModel
{

	public function getMaps(): array
	{
		$maps = $this->db->getDatabase()->prepare('SELECT * FROM `cs_maps`');
		$maps->execute();
		$mapsAll = $maps->fetchAll(PDO::FETCH_ASSOC);

		return $mapsAll ? array_map(function ($value) {
            return ObjectMapper::mapToMapClass($value);
        }, $mapsAll) : [];
	}

	public function getMap(int $id): ?Map
    {
		$maps = $this->db->getDatabase()->prepare('SELECT * FROM `cs_maps` WHERE `id`=:id');
		$maps->bindParam(':id', $id);
		$maps->execute();
		$map = $maps->fetch();
		return $map ? ObjectMapper::mapToMapClass($map) : null;
	}

	public function getTactics(int $map): array
	{
		$tactics = $this->db->getDatabase()->prepare('SELECT * FROM `cs_maps_tactics` WHERE `map_id` = :mapid');
		$tactics->bindParam(':mapid', $map);
		$tactics->execute();
		$tacticsAll = $tactics->fetchAll(PDO::FETCH_ASSOC);

		return $tacticsAll ? array_map(function ($value) {
            return ObjectMapper::mapToTacticClass($value);
        }, $tacticsAll) : [];
	}

	public function getTactic(int $id): ?Tactic
	{
		$tactics = $this->db->getDatabase()->prepare('SELECT * FROM `cs_maps_tactics` WHERE `id`=:id');
		$tactics->bindParam(':id', $id);
		$tactics->execute();
		$tactic = $tactics->fetch();
		return $tactic ? ObjectMapper::mapToTacticClass($tactic) : null;
	}

	public function checkLogin(int $id): bool
	{
		$logins = $this->db->getDatabase()->prepare('SELECT * FROM `cs_logins` WHERE `steam_id`=:id');
		$logins->bindParam(':id', $id);
		$logins->execute();
		return (bool)$logins->fetch();
	}

}