<?php

namespace App\EventListener;

use App\Event\CommentCreatedEvent;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class CommentNotificationSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private MailerInterface $mailer,
        private UrlGeneratorInterface $urlGenerator,
        private string $adminEmail = 'admin@example.com'
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            CommentCreatedEvent::NAME => 'onCommentCreated',
        ];
    }

    public function onCommentCreated(CommentCreatedEvent $event): void
    {
        $comment = $event->getComment();
        $user = $comment->getUser();
        $motw = $comment->getMOTW();

        // Truncate content for preview
        $contentPreview = mb_substr($comment->getContent(), 0, 100);
        if (mb_strlen($comment->getContent()) > 100) {
            $contentPreview .= '...';
        }

        // Generate admin URL
        $adminUrl = $this->urlGenerator->generate(
            'app_admin_comments',
            [],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        // Create and send email
        $email = (new TemplatedEmail())
            ->from(new Address('noreply@motw.com', 'MOTW Notifications'))
            ->to($this->adminEmail)
            ->subject('Nouveau commentaire en attente de validation')
            ->html($this->generateEmailBody($user?->getName() ?? 'Utilisateur inconnu', $contentPreview, $motw?->getName() ?? 'Publication', $adminUrl));

        try {
            $this->mailer->send($email);
        } catch (\Exception $e) {
            // Log error but don't throw to avoid breaking comment submission
            error_log('Failed to send comment notification email: ' . $e->getMessage());
        }
    }

    private function generateEmailBody(string $author, string $content, string $motwTitle, string $adminUrl): string
    {
        return sprintf(
            '<html><body>
                <h2>Nouveau commentaire publié</h2>
                <p><strong>Auteur :</strong> %s</p>
                <p><strong>Publication :</strong> %s</p>
                <p><strong>Contenu :</strong></p>
                <p>%s</p>
                <p><a href="%s">Voir tous les commentaires dans l\'admin</a></p>
            </body></html>',
            htmlspecialchars($author),
            htmlspecialchars($motwTitle),
            nl2br(htmlspecialchars($content)),
            htmlspecialchars($adminUrl)
        );
    }
}
