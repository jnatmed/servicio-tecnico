<?php

namespace Paw\App\Models;

use Exception;

class GraphMailer
{
    private $clientId;
    private $clientSecret;
    private $tenantId;
    private $fromEmail;
    private $accessToken;

    public function __construct()
    {
        global $config;

        $this->clientId     = $config->get('GRAPH_CLIENT_ID');
        $this->clientSecret = $config->get('GRAPH_CLIENT_SECRET');
        $this->tenantId     = $config->get('GRAPH_TENANT_ID');
        $this->fromEmail    = $config->get('GRAPH_USER_FROM');

        $this->accessToken = $this->obtenerToken();
    }

    private function obtenerToken()
    {
        $url = "https://login.microsoftonline.com/{$this->tenantId}/oauth2/v2.0/token";

        $params = [
            'client_id' => $this->clientId,
            'scope' => 'https://graph.microsoft.com/.default',
            'client_secret' => $this->clientSecret,
            'grant_type' => 'client_credentials',
        ];

        $curl = curl_init($url);
        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($params),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/x-www-form-urlencoded',
            ],
        ]);

        $response = curl_exec($curl);
        if (curl_errno($curl)) {
            throw new Exception('Error al obtener token de Microsoft Graph: ' . curl_error($curl));
        }

        $data = json_decode($response, true);
        return $data['access_token'] ?? throw new Exception('No se pudo obtener el token de acceso.');
    }

    public function send($toEmail, $toName, $subject, $textPart, $htmlPart)
    {
        $url = 'https://graph.microsoft.com/v1.0/users/' . $this->fromEmail . '/sendMail';

        $body = [
            'message' => [
                'subject' => $subject,
                'body' => [
                    'contentType' => 'HTML',
                    'content' => $htmlPart ?: $textPart,
                ],
                'toRecipients' => [
                    [
                        'emailAddress' => [
                            'address' => $toEmail,
                            'name' => $toName,
                        ]
                    ]
                ],
            ],
            'saveToSentItems' => true
        ];

        $curl = curl_init($url);
        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($body),
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $this->accessToken,
                'Content-Type: application/json',
            ],
        ]);

        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        if ($httpCode >= 400) {
            throw new Exception("Error al enviar correo. HTTP $httpCode: $response");
        }

        return true;
    }
}
