<?php

namespace Paw\App\Models;

use \Mailjet\Resources;
use Exception;

class MailjetMailer
{
    private $apiKey;
    private $secretKey;
    private $fromEmail;
    private $fromName;
    private $mj;

    public function __construct()
    {
        global $config;

        $this->apiKey = $config->get('API_KEY_MAIL');
        $this->secretKey = $config->get('SECRET_KEY_MAIL');
        $this->fromEmail = $config->get('FROM_MAIL');
        $this->fromName = $config->get('FROM_NAME');

        // Crear una instancia del cliente de Mailjet
        $this->mj = new \Mailjet\Client($this->apiKey, $this->secretKey, true, ['version' => 'v3.1']);
    }

    public function send($toEmail, $toName, $subject, $textPart, $htmlPart)
    {
        $body = [
            'Messages' => [
                [
                    'From' => [
                        'Email' => $this->fromEmail,
                        'Name' => $this->fromName,
                    ],
                    'To' => [
                        [
                            'Email' => $toEmail,
                            'Name' => $toName,
                        ],
                    ],
                    'Subject' => $subject,
                    'TextPart' => $textPart,
                    'HTMLPart' => $htmlPart,
                ],
            ],
        ];

        $response = $this->mj->post(Resources::$Email, ['body' => $body]);

        if (!$response->success()) {
            throw new Exception("Error sending email: " . $response->getStatus() . " " . $response->getReasonPhrase());
        }

        return $response->getData();
    }
}


?>
