<?php
/**
 * Created by PhpStorm.
 * User: aze
 * Date: 18/03/2021
 * Time: 17:47
 */

namespace App\Message;


class CommentMessage
{
    private $id;
    private $context;
    private $reviewUrl;

    public function __construct(int $id, string $reviewUrl, array $context = [])
    {
        $this->id = $id;
        $this->context = $context;
        $this->reviewUrl = $reviewUrl;
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return array
     */
    public function getContext(): array
    {
        return $this->context;
    }

    /**
     * @return string
     */
    public function getReviewUrl(): string
    {
        return $this->reviewUrl;
    }

}