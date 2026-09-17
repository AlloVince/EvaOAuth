<?php

declare(strict_types=1);

namespace Eva\EvaOAuth\Engine;

use League\OAuth2\Client\OptionProvider\HttpBasicAuthOptionProvider;

final class EncodedBasicAuthOptionProvider extends HttpBasicAuthOptionProvider
{
    public function getAccessTokenOptions($method, #[\SensitiveParameter] array $params)
    {
        $params['client_id'] = urlencode($params['client_id']);
        $params['client_secret'] = urlencode($params['client_secret']);
        return parent::getAccessTokenOptions($method, $params);
    }
}
