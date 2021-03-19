<?php
/**
 * Created by PhpStorm.
 * User: aze
 * Date: 18/03/2021
 * Time: 17:50
 */

namespace App\MessageHandler;


use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use App\Message\CommentMessage;
use App\Repository\CommentRepository;
use App\SpamChecker;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Workflow\WorkflowInterface;
use Symfony\Bridge\Twig\Mime\NotificationEmail;
use Symfony\Component\Mailer\MailerInterface;

class CommentMessageHandler implements MessageHandlerInterface
{
    private $spamChecker;
    private $entityManager;
    private $commentRepository;
    private $bus;
    private $workflow;
    private $logger;
    private $mailer;
    private $adminEmail;

    /**
     * CommentMessageHandler constructor.
     * @param SpamChecker $spamChecker
     * @param EntityManagerInterface $entityManager
     * @param CommentRepository $commentRepository
     * @param MessageBusInterface $bus
     * @param WorkflowInterface $commentStateMachine
     * @param MailerInterface $mailer
     * @param string $adminEmail
     * @param LoggerInterface|null $logger
     */
    public function __construct(
        SpamChecker $spamChecker,
        EntityManagerInterface $entityManager,
        CommentRepository $commentRepository,
        MessageBusInterface $bus,
        WorkflowInterface $commentStateMachine,
        MailerInterface $mailer,
        string $adminEmail,
        LoggerInterface $logger = null
    )
    {
        $this->spamChecker = $spamChecker;
        $this->entityManager = $entityManager;
        $this->commentRepository = $commentRepository;
        $this->bus = $bus;
        $this->workflow = $commentStateMachine;
        $this->logger = $logger;
        $this->adminEmail = $adminEmail;
        $this->mailer = $mailer;
    }

    public function __invoke(CommentMessage $message)
    {
        $comment = $this->commentRepository->find($message->getId());

        if (!$comment) {
            return;
        }

        if ($this->workflow->can($comment, 'accept')) {
            $score = $this->spamChecker->getSpamScore($comment, $message->getContext());
            $transition = 'accept';
            if (2 === $score) {
                $transition = 'reject_spam';
            } elseif (1 === $score) {
                $transition = 'might_be_spam';
            }
            $this->workflow->apply($comment, $transition);
            $this->entityManager->flush();

            $this->bus->dispatch($message);
        } elseif (
            $this->workflow->can($comment, 'publish') ||
            $this->workflow->can($comment, 'publish_ham')
        ) {
            $this->mailer->send((new NotificationEmail())
                ->subject('New comment posted')
                ->htmlTemplate('emails/comment_notification.html.twig')
                ->from($this->adminEmail)
                ->to($this->adminEmail)
                ->context(['comment'=>$comment])
            );

        }elseif ($this->logger){
            $this->logger->debug('Dropping comment message', ['comment' => $comment->getId(), 'state' => $comment->getState()]);
        }

    }

}