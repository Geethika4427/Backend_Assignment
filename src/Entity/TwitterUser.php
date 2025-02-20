<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Repository\TwitterUserRepository;

#[ORM\Entity(repositoryClass: TwitterUserRepository::class)]
#[ORM\Table(name: "twitter_users")]
class TwitterUser
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100, unique: true)]
    private ?string $twitterId = null;  // Twitter User ID

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $username = null; // Twitter username

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $email = null;

    public function getId(): ?int 
    {
        return $this->id;
    }

    public function getTwitterId(): ?string 
    {
        return $this->twitterId;
    }

    public function setTwitterId(string $twitterId): static 
    {
        $this->twitterId = $twitterId;
        return $this;
    }

    public function getUsername(): ?string 
    {
        return $this->username;
    }

    public function setUsername(?string $username): static 
    {
        $this->username = $username;
        return $this;
    }

    public function getEmail(): ?string 
    {
        return $this->email;
    }

    public function setEmail(?string $email): static 
    {
        $this->email = $email;
        return $this;
    }
}   
