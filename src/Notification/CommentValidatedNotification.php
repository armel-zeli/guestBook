<?php
/**
 * Created by PhpStorm.
 * User: aze
 * Date: 17/06/2021
 * Time: 14:49
 */

namespace App\Notification;


use App\Entity\Comment;
use Symfony\Component\Notifier\Message\EmailMessage;
use Symfony\Component\Notifier\Notification\EmailNotificationInterface;
use Symfony\Component\Notifier\Notification\Notification;
use Symfony\Component\Notifier\Recipient\EmailRecipientInterface;
use Symfony\Component\Notifier\Recipient\RecipientInterface;


class CommentValidatedNotification extends Notification implements EmailNotificationInterface
{
    private $comment;

    public function __construct(Comment $comment)
    {
        $this->comment = $comment;

        parent::__construct('Comment validated');
    }

    public function asEmailMessage(EmailRecipientInterface $recipient, string $transport = null): ?EmailMessage
    {
        $message = EmailMessage::fromNotification($this, $recipient, $transport);
        $message->getMessage()
            ->htmlTemplate('emails/comment_validated.html.twig')
            ->context(['comment' => $this->comment]);

        return $message;
    }

    public function getChannels(RecipientInterface $recipient): array
    {
        return ['email'];
    }

}