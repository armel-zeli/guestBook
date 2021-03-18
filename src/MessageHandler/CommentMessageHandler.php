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

class CommentMessageHandler implements MessageHandlerInterface
{
    private $spamChecker;
    private $entityManager;
    private $commentRepository;

    /**
     * CommentMessageHandler constructor.
     * @param SpamChecker $spamChecker
     * @param EntityManagerInterface $entityManager
     * @param CommentRepository $commentRepository
     */
    public function __construct(SpamChecker $spamChecker, EntityManagerInterface $entityManager, CommentRepository $commentRepository)
    {
        $this->spamChecker = $spamChecker;
        $this->entityManager = $entityManager;
        $this->commentRepository = $commentRepository;
    }

    public function __invoke(CommentMessage $message)
    {
        $comment = $this->commentRepository->find($message->getId());

        if(!$comment){
            return;
        }

        $spamScore = $this->spamChecker->getSpamScore($comment, $message->getContext());

        if((2===$spamScore) || (1 === $spamScore)){
            $comment->setState('spam');
        } else {
            $comment->setState('published');
        }

        $this->entityManager->flush();

    }

}