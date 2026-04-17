<?php
namespace Sport2\Core;

class AltenarClient
{
    private string $baseUrl;
    private array $defaultParams;
    private int $timeout;
    private int $retryCount;
    private int $retryDelay;
    private Logger $logger;

    public function __construct(Logger $logger)
    {
        $cfg = sport2_config()['altenar'];
        $this->baseUrl      = rtrim($cfg['base_url'], '/');
        $this->defaultParams = $cfg['params'];
        $this->timeout       = $cfg['timeout'];
        $this->retryCount    = $cfg['retry_count'];
        $this->retryDelay    = $cfg['retry_delay'];
        $this->logger        = $logger;
    }

    public function getAllSports(): ?array
    {
        return $this->request('GetAllSports', [
            'sportIds' => '0', 'regionIds' => '0',
            'index' => '0', 'cnt' => '100', 'sortBy' => '1',
        ]);
    }

    public function getLiveEvents(int $sportId = 0): ?array
    {
        return $this->request('GetLiveEvents', [
            'sportId' => $sportId, 'cnt' => '500', 'index' => '0',
        ]);
    }

    public function getUpcoming(int $sportId = 0, int $count = 200, int $index = 0): ?array
    {
        return $this->request('GetUpcoming', [
            'sportId' => $sportId, 'cnt' => $count, 'index' => $index,
        ]);
    }

    private function request(string $endpoint, array $params = []): ?array
    {
        $allParams = array_merge($this->defaultParams, $params);
        $url = $this->baseUrl . '/' . $endpoint . '?' . http_build_query($allParams);

        for ($attempt = 1; $attempt <= $this->retryCount; $attempt++) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_TIMEOUT        => $this->timeout,
                CURLOPT_HTTPHEADER     => [
                    'Accept: application/json',
                    'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) Sport2/1.0',
                ],
            ]);

            $body = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);

            if ($error) {
                $this->logger->warn("CURL error on {$endpoint} (attempt {$attempt})", ['error' => $error]);
                if ($attempt < $this->retryCount) sleep($this->retryDelay);
                continue;
            }

            if ($httpCode !== 200) {
                $this->logger->warn("HTTP {$httpCode} on {$endpoint} (attempt {$attempt})");
                if ($attempt < $this->retryCount) sleep($this->retryDelay);
                continue;
            }

            $data = json_decode($body, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->logger->warn("JSON decode error on {$endpoint}", ['error' => json_last_error_msg()]);
                if ($attempt < $this->retryCount) sleep($this->retryDelay);
                continue;
            }

            if (!empty($data['Error'])) {
                $this->logger->warn("API error on {$endpoint}", ['response' => $data]);
                return null;
            }

            return $data;
        }

        $this->logger->error("All {$this->retryCount} attempts failed for {$endpoint}");
        return null;
    }
}
