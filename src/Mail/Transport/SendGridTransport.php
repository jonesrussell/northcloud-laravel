<?php

declare(strict_types=1);

namespace JonesRussell\NorthCloud\Mail\Transport;

use SendGrid;
use SendGrid\Mail\Mail;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Component\Mime\MessageConverter;

class SendGridTransport extends AbstractTransport
{
    public function __construct(private SendGrid $client)
    {
        parent::__construct();
    }

    protected function doSend(SentMessage $message): void
    {
        $email = MessageConverter::toEmail($message->getOriginalMessage());
        $envelope = $message->getEnvelope();

        $sendGridMail = new Mail;

        $sender = $envelope->getSender();
        $sendGridMail->setFrom($sender->getAddress(), $sender->getName());
        $sendGridMail->setSubject($email->getSubject() ?? '');

        foreach ($envelope->getRecipients() as $recipient) {
            $sendGridMail->addTo($recipient->getAddress(), $recipient->getName());
        }

        foreach ($email->getCc() as $cc) {
            $sendGridMail->addCc($cc->getAddress(), $cc->getName());
        }

        foreach ($email->getBcc() as $bcc) {
            $sendGridMail->addBcc($bcc->getAddress(), $bcc->getName());
        }

        foreach ($email->getReplyTo() as $replyTo) {
            $sendGridMail->setReplyTo($replyTo->getAddress(), $replyTo->getName());
            break; // SendGrid only supports one reply-to
        }

        if ($email->getHtmlBody()) {
            $sendGridMail->addContent('text/html', $email->getHtmlBody());
        }

        if ($email->getTextBody()) {
            $sendGridMail->addContent('text/plain', $email->getTextBody());
        }

        foreach ($email->getAttachments() as $attachment) {
            $headers = $attachment->getPreparedHeaders();
            $filename = $headers->getHeaderParameter('Content-Disposition', 'filename') ?? 'attachment';
            $contentType = $headers->get('Content-Type')?->getBodyAsString() ?? 'application/octet-stream';
            $disposition = $headers->getHeaderBody('Content-Disposition');

            $sendGridMail->addAttachment(
                base64_encode($attachment->getBody()),
                $contentType,
                $filename,
                $disposition === 'inline' ? 'inline' : 'attachment',
            );
        }

        try {
            $response = $this->client->send($sendGridMail);
        } catch (\Exception $exception) {
            throw new TransportException(
                sprintf('Request to SendGrid API failed: %s', $exception->getMessage()),
                0,
                $exception
            );
        }

        $statusCode = $response->statusCode();

        if ($statusCode < 200 || $statusCode >= 300) {
            throw new TransportException(
                sprintf(
                    'SendGrid API returned status %d: %s',
                    $statusCode,
                    $response->body()
                )
            );
        }
    }

    public function __toString(): string
    {
        return 'sendgrid';
    }
}
