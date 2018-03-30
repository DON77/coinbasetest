<?php
require_once('cache.php');

class Api
{
    protected $apiUrl = 'https://api.coinbase.com/v2/';

    protected $action;
    protected $pare;
    private $curl;
    protected $app;
    protected $cache;
    protected $cacheTime = 300;

    public function __construct($rout)
    {
        $this->app = $rout;
        $this->pare = $rout->request['get'] && isset($rout->request['get']['pare']) ? strtoupper($rout->request['get']['pare']) : '';
        $this->action = $rout->getAction();
    }

    protected function connect()
    {
        $url = '';
        switch ($this->action) {
            case 'spot':
                $url = $this->getSpotUrl();
                break;
            case 'rates':
            case 'gep':
                $url = $this->getRatesUrl();
        }
        try {
            if (empty($url)) {
                throw new Exception('Wrong api url');
            }
            $this->curl = curl_init($url);
            curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, 1);
            $this->createHeaders();
        } catch (Exception $e) {
            echo json_encode([
                'success' => 0,
                'message' => $e->getMessage()
            ]);
            die;
        }
    }

    protected function getSpotUrl()
    {
        return $this->apiUrl . 'prices/' . $this->pare . '/spot';
    }

    protected function getRatesUrl()
    {
        $currency = isset($this->app->request['get']['currency']) ? '?currency=' . strtoupper($this->app->request['get']['currency']) : '';
        return $this->apiUrl . 'exchange-rates' . $currency;
    }

    protected function createHeaders()
    {
        curl_setopt($this->curl, CURLOPT_HEADER, 0);
        curl_setopt($this->curl, CURLOPT_POST, 0);
        curl_setopt($this->curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($this->curl, CURLOPT_SSL_VERIFYPEER, false);
    }

    public function getData()
    {
        $this->connect();
        $output = curl_exec($this->curl);
        curl_close($this->curl);
        $output = json_decode($output, true);
        $this->cache = Cache::getData($this->action);
        if (empty($this->cache) || $this->cache['time'] < time() - $this->cacheTime) {
            Cache::setData([
                'action' => $this->action,
                'data' => [
                    'time' => time(),
                    'data' => $output['data']
                ]
            ]);
        }

        if (isset($output['warnings'])) {
            unset($output['warnings']);
        }
        
        switch ($this->action) {
            case 'rates':
                return $this->allData($output);
            case 'spot':
                return $this->pareData($output);
            case 'gep':
                return $this->gepData($output);
        }
    }

    public function pareData($output)
    {
        return json_encode([
            'success' => 1,
            'data' => $output
        ]);
    }

    public function allData($output)
    {
        return json_encode([
            'success' => 1,
            'data' => $output
        ]);
    }
    
    public function gepData($output)
    {
        if ($this->cache) {
            $output = $this->decorateGepData($output);
        }
        return json_encode([
            'success' => 1,
            'data' => $output
        ]);
    }

    protected function decorateGepData($output)
    {
        $cacheData = json_decode($this->cache['data'], true);
        $outData = $output['data'];
        if ($this->cache['time'] < time() - $this->cacheTime || $outData['currency'] != $cacheData['currency']) {
            return $output;
        }

        foreach ($outData['rates'] as $key => &$val) {
            $val = $val + ($val-$cacheData['rates'][$key]) / 100 * 10 + $val / 100 * 5;
        }
        $output['data'] = $outData;

        return $output;

    }
}