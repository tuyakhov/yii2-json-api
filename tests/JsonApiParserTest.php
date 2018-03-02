<?php
/**
 * @author Anton Tuyakhov <atuyakhov@gmail.com>
 */

namespace tuyakhov\jsonapi\tests;


use tuyakhov\jsonapi\JsonApiParser;
use yii\helpers\Json;
use yii\web\BadRequestHttpException;

class JsonApiParserTest extends TestCase
{
    public function testEmptyBody()
    {
        $parser = new JsonApiParser();
        $body = '';
        $this->assertEquals([], $parser->parse($body, ''));
    }

    public function testMissingData()
    {
        $parser = new JsonApiParser();
        $this->expectException(BadRequestHttpException::class);
        $body = Json::encode(['incorrect-member']);
        $parser->parse($body, '');
    }

    public function testSingleResource()
    {
        $parser = new JsonApiParser();
        $body = Json::encode([
           'data' => [
               'type' => 'resource-models',
               'attributes' => [
                   'field1' => 'test',
                   'field2' => 2,
                   'first-name' => 'Bob'
               ],
               'relationships' => [
                   'author' => [
                       'data' => [
                           'id' => '321',
                           'type' => 'resource-models'
                       ]
                   ],
                   'client' => [
                       'data' => [
                           ['id' => '321', 'type' => 'resource-models'],
                           ['id' => '123', 'type' => 'resource-models']
                       ]
                   ]
               ]
           ]
        ]);
        $this->assertEquals([
            'ResourceModel' => [
                'field1' => 'test',
                'field2' => 2,
                'first_name' => 'Bob',
            ],
            'relationships' => [
                'author' => [
                    'ResourceModel' => [
                        ['id' => '321']
                    ]
                ],
                'client' => [
                    'ResourceModel' => [
                        ['id' => '321'],
                        ['id' => '123']
                    ]
                ]
            ]
        ], $parser->parse($body, ''));
    }

    public function testMultiple()
    {
        $parser = new JsonApiParser();
        $resourceActual = [
            'type' => 'resource-models',
            'id' => 12,
            'attributes' => [
                'field1' => 'test',
                'field2' => 2,
                'first-name' => 'Bob'
            ],
        ];
        $resourceExpected = [
            'id' => 12,
            'field1' => 'test',
            'field2' => 2,
            'first_name' => 'Bob',
        ];
        $body = Json::encode([
            'data' => [
                $resourceActual,
                $resourceActual
            ]
        ]);
        $this->assertEquals([
            'ResourceModel' => [
                $resourceExpected,
                $resourceExpected
            ],
        ], $parser->parse($body, ''));
    }
}