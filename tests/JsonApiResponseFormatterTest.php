<?php
/**
 * @author Anton Tuyakhov <atuyakhov@gmail.com>
 */
namespace tuyakhov\jsonapi\tests;


use tuyakhov\jsonapi\JsonApiResponseFormatter;
use tuyakhov\jsonapi\Serializer;
use tuyakhov\jsonapi\tests\data\ResourceModel;
use yii\helpers\Json;
use yii\web\Response;
use yii\web\ServerErrorHttpException;

class JsonApiResponseFormatterTest extends TestCase
{
    public function testFormatException()
    {
        $formatter = new JsonApiResponseFormatter();
        $exception = new ServerErrorHttpException('Server error');
        $response = new Response();
        $response->setStatusCode($exception->statusCode);
        $response->data = [
            'name' => $exception->getName(),
            'message' => $exception->getMessage(),
            'code' => $exception->getCode(),
            'status' => $exception->statusCode
        ];
        $formatter->format($response);
        $this->assertJson($response->content);
        $this->assertSame(Json::encode([
            'errors' => [
                [
                    'code' => '0',
                    'status' => '500',
                    'title' => Response::$httpStatuses[500],
                    'detail' => 'Server error',
                ]
            ]
        ]), $response->content);
    }

    public function testFormModelError()
    {
        $formatter = new JsonApiResponseFormatter();
        $exception = new ServerErrorHttpException('Server error');
        $response = new Response();
        $response->setStatusCode($exception->statusCode);
        $serializer = new Serializer();
        $model = new ResourceModel();
        $model->addError('field1', 'Error');
        $model->addError('field2', 'Test Error');
        $response->data = $serializer->serialize($model);
        $formatter->format($response);
        $this->assertJson($response->content);
        $this->assertSame(Json::encode([
            'errors' => [
                [
                    'source' => ['pointer' => "/data/attributes/field1"],
                    'detail' => 'Error',
                    'status' => '422'
                ],
                [
                    'source' => ['pointer' => "/data/attributes/field2"],
                    'detail' => 'Test Error',
                    'status' => '422'
                ]
            ]
        ]), $response->content);
    }
}