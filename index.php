<?php
require_once('apilib/api.php');

class Rout
{
    public $request;
    protected $action;
    protected $data;
    protected $isApi = false;

    public function __construct()
    {
        $this->request = [
            'server' => $_SERVER,
            'get' => $_GET,
            'post' => $_POST
        ];
        $this->setAction();
    }

    public function getAction()
    {
        return $this->action;
    }

    public function getData()
    {
        return $this->data;
    }

    protected function setAction()
    {
        $uri = str_replace('?' . $this->request['server']['QUERY_STRING'], '', $this->request['server']['REQUEST_URI']);
        $uri = trim($uri, '/');
        $uri_parts = explode('/', $uri);

        try {
            if ($uri_parts[0] != 'api' || empty($uri_parts[1])) {
                throw new Exception('Wrang api url');
            }
            $this->isApi = true;
            $this->action = $uri_parts[1];
        } catch (Exception $e) {
            echo json_encode([
                'success' => 0,
                'message' => $e->getMessage()
            ]);
            die;
        }
    }
}
header('Content-Type: application/json');

$routing = new Rout();
$api = new Api($routing);
echo $api->getData();
die;