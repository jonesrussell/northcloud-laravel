<?php

use JonesRussell\NorthCloud\Mail\Transport\SendGridTransport;
use SendGrid\Response;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

beforeEach(function () {
    $this->sendGridClient = Mockery::mock(\SendGrid::class);
    $this->transport = new SendGridTransport($this->sendGridClient);
});

it('sends a basic email via SendGrid API', function () {
    $email = (new Email)
        ->from(new Address('sender@example.com', 'Sender'))
        ->to(new Address('recipient@example.com', 'Recipient'))
        ->subject('Test Subject')
        ->html('<p>Hello</p>')
        ->text('Hello');

    $response = Mockery::mock(Response::class);
    $response->shouldReceive('statusCode')->andReturn(202);
    $response->shouldReceive('body')->andReturn('');

    $this->sendGridClient
        ->shouldReceive('send')
        ->once()
        ->withArgs(function (\SendGrid\Mail\Mail $mail) {
            $json = json_decode(json_encode($mail), true);

            return ($json['from']['email'] ?? '') === 'sender@example.com'
                && ($json['from']['name'] ?? '') === 'Sender'
                && ($json['subject'] ?? '') === 'Test Subject'
                && ($json['personalizations'][0]['to'][0]['email'] ?? '') === 'recipient@example.com';
        })
        ->andReturn($response);

    $sentMessage = $this->transport->send($email);

    expect($sentMessage)->not->toBeNull();
});

it('sends email with cc and bcc', function () {
    $email = (new Email)
        ->from(new Address('sender@example.com', 'Sender'))
        ->to(new Address('recipient@example.com', 'Recipient'))
        ->cc(new Address('cc@example.com', 'CC'))
        ->bcc(new Address('bcc@example.com', 'BCC'))
        ->subject('Test Subject')
        ->html('<p>Hello</p>');

    $response = Mockery::mock(Response::class);
    $response->shouldReceive('statusCode')->andReturn(202);
    $response->shouldReceive('body')->andReturn('');

    $this->sendGridClient
        ->shouldReceive('send')
        ->once()
        ->andReturn($response);

    $sentMessage = $this->transport->send($email);

    expect($sentMessage)->not->toBeNull();
});

it('sends email with attachments', function () {
    $email = (new Email)
        ->from(new Address('sender@example.com', 'Sender'))
        ->to(new Address('recipient@example.com', 'Recipient'))
        ->subject('Test Subject')
        ->html('<p>Hello</p>')
        ->attach('file content', 'document.pdf', 'application/pdf');

    $response = Mockery::mock(Response::class);
    $response->shouldReceive('statusCode')->andReturn(202);
    $response->shouldReceive('body')->andReturn('');

    $this->sendGridClient
        ->shouldReceive('send')
        ->once()
        ->andReturn($response);

    $sentMessage = $this->transport->send($email);

    expect($sentMessage)->not->toBeNull();
});

it('sends email with reply-to', function () {
    $email = (new Email)
        ->from(new Address('sender@example.com', 'Sender'))
        ->to(new Address('recipient@example.com', 'Recipient'))
        ->replyTo(new Address('reply@example.com', 'Reply'))
        ->subject('Test Subject')
        ->html('<p>Hello</p>');

    $response = Mockery::mock(Response::class);
    $response->shouldReceive('statusCode')->andReturn(202);
    $response->shouldReceive('body')->andReturn('');

    $this->sendGridClient
        ->shouldReceive('send')
        ->once()
        ->withArgs(function (\SendGrid\Mail\Mail $mail) {
            $json = json_decode(json_encode($mail), true);

            return ($json['reply_to']['email'] ?? '') === 'reply@example.com';
        })
        ->andReturn($response);

    $sentMessage = $this->transport->send($email);

    expect($sentMessage)->not->toBeNull();
});

it('throws TransportException on API error', function () {
    $email = (new Email)
        ->from(new Address('sender@example.com', 'Sender'))
        ->to(new Address('recipient@example.com', 'Recipient'))
        ->subject('Test Subject')
        ->html('<p>Hello</p>');

    $response = Mockery::mock(Response::class);
    $response->shouldReceive('statusCode')->andReturn(401);
    $response->shouldReceive('body')->andReturn('{"errors":[{"message":"Invalid API key"}]}');

    $this->sendGridClient
        ->shouldReceive('send')
        ->once()
        ->andReturn($response);

    $this->transport->send($email);
})->throws(TransportException::class);

it('throws TransportException when SendGrid client throws', function () {
    $email = (new Email)
        ->from(new Address('sender@example.com', 'Sender'))
        ->to(new Address('recipient@example.com', 'Recipient'))
        ->subject('Test Subject')
        ->html('<p>Hello</p>');

    $this->sendGridClient
        ->shouldReceive('send')
        ->once()
        ->andThrow(new \Exception('Connection failed'));

    $this->transport->send($email);
})->throws(TransportException::class);

it('returns sendgrid as string representation', function () {
    expect((string) $this->transport)->toBe('sendgrid');
});
