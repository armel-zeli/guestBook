<?php
/**
 * Created by PhpStorm.
 * User: aze
 * Date: 18/03/2021
 * Time: 17:50
 */

namespace App\MessageHandler;


use App\ImageOptimizer;
use App\Notification\CommentValidatedNotification;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use App\Message\CommentMessage;
use App\Notification\CommentReviewNotification;
use App\Repository\CommentRepository;
use App\SpamChecker;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Notifier\NotifierInterface;
use Symfony\Component\Workflow\WorkflowInterface;

class CommentMessageHandler implements MessageHandlerInterface
{
    private $spamChecker;
    private $entityManager;
    private $commentRepository;
    private $bus;
    private $workflow;
    private $logger;
    private $notifier;
    private $imageOptimizer;
    private $photoDir;

    /**
     * CommentMessageHandler constructor.
     * @param SpamChecker $spamChecker
     * @param EntityManagerInterface $entityManager
     * @param CommentRepository $commentRepository
     * @param MessageBusInterface $bus
     * @param WorkflowInterface $commentStateMachine
     * @param NotifierInterface $notifier
     * @param ImageOptimizer $imageOptimizer
     * @param string $photoDir
     * @param LoggerInterface|null $logger
     */
    public function __construct(
        SpamChecker $spamChecker,
        EntityManagerInterface $entityManager,
        CommentRepository $commentRepository,
        MessageBusInterface $bus,
        WorkflowInterface $commentStateMachine,
        NotifierInterface $notifier,
        ImageOptimizer $imageOptimizer,
        string $photoDir,
        LoggerInterface $logger = null
    )
    {
        $this->spamChecker = $spamChecker;
        $this->entityManager = $entityManager;
        $this->commentRepository = $commentRepository;
        $this->bus = $bus;
        $this->workflow = $commentStateMachine;
        $this->logger = $logger;
        $this->notifier = $notifier;
        $this->photoDir = $photoDir;
        $this->imageOptimizer = $imageOptimizer;
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
            $notification = new CommentReviewNotification($comment, $message->getReviewUrl());

            $this->notifier->send($notification, ...$this->notifier->getAdminRecipients());

        } elseif ($this->workflow->can($comment, 'optimize')) {

            if ($comment->getPhotoFilename()) {
                $this->imageOptimizer->resize($this->photoDir . '/' . $comment->getPhotoFilename());
            }
            $this->workflow->apply($comment, 'optimize');
            $this->entityManager->flush();

            $notification = new CommentValidatedNotification($comment);
            $this->notifier->send($notification,...$this->notifier->getAdminRecipients());

        } elseif ($this->logger) {
            $this->logger->debug('Dropping comment message', ['comment' => $comment->getId(), 'state' => $comment->getState()]);
        }

    }

}