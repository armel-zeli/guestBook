<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Annotation\ApiResource;
use App\Repository\CommentRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;

/**
 * @ORM\Entity(repositoryClass=CommentRepository::class)
 * @ORM\HasLifecycleCallbacks()
 * @ApiResource(
 *     collectionOperations={"get"={"normalization_context"={"groups"="comment:list"}}},
 *     itemOperations={"get"={"normalization_context"={"groups"="comment:item"}}},
 *     order={"createdAt"="DESC"},
 *     paginationEnabled=false
 * )
 * @ApiFilter(SearchFilter::class, properties={"conference": "exact"})
 */
class Comment
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     * @Groups({"comment:list","comment:item"})
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     * @Assert\NotBlank
     * @Groups({"comment:list","comment:item"})
     */
    private $author;

    /**
     * @ORM\Column(type="text")
     * @Assert\NotBlank
     * @Groups({"comment:list","comment:item"})
     */
    private $text;

    /**
     * @ORM\Column(type="string", length=255)
     * @Assert\NotBlank
     * @Assert\Email
     * @Groups({"comment:list","comment:item"})
     */
    private $email;

    /**
     * @ORM\Column(type="datetime")
     * @Groups({"comment:list","comment:item"})
     */
    private $createdAt;

    /**
     * @ORM\ManyToOne(targetEntity=Conference::class, inversedBy="comments")
     * @ORM\JoinColumn(nullable=false)
     * @Groups({"comment:list","comment:item"})
     */
    private $conference;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Groups({"comment:list","comment:item"})
     */
    private $photoFilename;

    /**
     * @ORM\Column(type="string", length=255, options={"default": "submitted"})
     */
    private $state = 'submitted';

    /**
     * @return string
     */
    public function __toString()
    {
        return (string) $this->getEmail();
    }

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return null|string
     */
    public function getAuthor(): ?string
    {
        return $this->author;
    }

    /**
     * @param string $author
     * @return Comment
     */
    public function setAuthor(string $author): self
    {
        $this->author = $author;

        return $this;
    }

    /**
     * @return null|string
     */
    public function getText(): ?string
    {
        return $this->text;
    }

    /**
     * @param string $text
     * @return Comment
     */
    public function setText(string $text): self
    {
        $this->text = $text;

        return $this;
    }

    /**
     * @return null|string
     */
    public function getEmail(): ?string
    {
        return $this->email;
    }

    /**
     * @param string $email
     * @return Comment
     */
    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    /**
     * @return \DateTimeInterface|null
     */
    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }


    /**
     * @param \DateTimeInterface $createdAt
     * @return Comment
     */
    public function setCreatedAt(\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }


    /**
     * @ORM\PrePersist()
     */
    public function setCreatedAtValue()
    {
        $this->createdAt = new \DateTime();
    }

    /**
     * @return Conference|null
     */
    public function getConference(): ?Conference
    {
        return $this->conference;
    }

    /**
     * @param Conference|null $conference
     * @return Comment
     */
    public function setConference(?Conference $conference): self
    {
        $this->conference = $conference;

        return $this;
    }

    /**
     * @return null|string
     */
    public function getPhotoFilename(): ?string
    {
        return $this->photoFilename;
    }

    /**
     * @param null|string $photoFilename
     * @return Comment
     */
    public function setPhotoFilename(?string $photoFilename): self
    {
        $this->photoFilename = $photoFilename;

        return $this;
    }

    public function getState(): ?string
    {
        return $this->state;
    }

    public function setState(string $state): self
    {
        $this->state = $state;

        return $this;
    }
}
