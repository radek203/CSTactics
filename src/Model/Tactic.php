<?php

declare(strict_types=1);

namespace CSApp\Model;

class Tactic {

    private int $id;
    private string $name;
    private int $mapId;
    private ?string $text1;
    private ?string $text2;
    private ?string $text3;
    private ?string $text4;
    private ?string $text5;
    private ?string $img1;
    private ?string $img2;
    private ?string $img3;
    private ?string $img4;
    private ?string $img5;

    public function __construct(int $id, string $name, int $mapId, ?string $text1, ?string $text2, ?string $text3, ?string $text4, ?string $text5, ?string $img1, ?string $img2, ?string $img3, ?string $img4, ?string $img5)
    {
        $this->id = $id;
        $this->name = $name;
        $this->mapId = $mapId;
        $this->text1 = $text1;
        $this->text2 = $text2;
        $this->text3 = $text3;
        $this->text4 = $text4;
        $this->text5 = $text5;
        $this->img1 = $img1;
        $this->img2 = $img2;
        $this->img3 = $img3;
        $this->img4 = $img4;
        $this->img5 = $img5;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getMapId(): int
    {
        return $this->mapId;
    }

    public function getTexts(): array
    {
        return [
            $this->text1,
            $this->text2,
            $this->text3,
            $this->text4,
            $this->text5
        ];
    }

    public function getImgs(): array
    {
        return [
            $this->img1,
            $this->img2,
            $this->img3,
            $this->img4,
            $this->img5
        ];
    }

}