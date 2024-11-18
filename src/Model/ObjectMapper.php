<?php

declare(strict_types=1);

namespace CSApp\Model;

class ObjectMapper {

    public static function mapToMapClass(array $arr): Map
    {
        return new Map($arr['id'], $arr['name']);
    }

    public static function mapToTacticClass(array $arr): Tactic
    {
        return new Tactic(
            $arr['id'],
            $arr['name'],
            $arr['map_id'],
            $arr['text1'],
            $arr['text2'],
            $arr['text3'],
            $arr['text4'],
            $arr['text5'],
            $arr['img1'],
            $arr['img2'],
            $arr['img3'],
            $arr['img4'],
            $arr['img5']
        );
    }

}
