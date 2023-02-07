<?php

namespace Eminiarts\Aura\Operations;

use Eminiarts\Aura\Resources\Operation;

class Webhook extends BaseOperation
{
    public function run(Operation $operation, $post, $operationLog)
    {
        // dd('send notification', $operation->toArray(), $post->toArray(), $operationLog->toArray());

        // throw an exception if there is no message
        $URL = $operation->options['URL'] ?? throw new \Exception('No URL');
        $method = $operation->options['method'] ?? 'POST';
        $headers = $operation->options['headers'] ?? [];
        $body = $operation->options['body'] ?? '';

        // call the webhook
        $client = new \GuzzleHttp\Client();
        $response = $client->request($method, $URL, [
            'headers' => $headers,
            'body' => $body,
        ]);

        $operationLog->status = 'success';
        $operationLog->save();
    }
}
