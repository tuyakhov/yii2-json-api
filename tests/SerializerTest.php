<?php
/**
 * @author Anton Tuyakhov <atuyakhov@gmail.com>
 */
namespace tuyakhov\jsonapi\tests;

use tuyakhov\jsonapi\tests\data\ResourceModel;
use tuyakhov\jsonapi\Serializer;
use yii\base\InvalidValueException;
use yii\data\ArrayDataProvider;

class SerializerTest extends TestCase
{
    protected function setUp()
    {
        parent::setUp();
        ResourceModel::$fields = ['field1', 'field2'];
        ResourceModel::$extraFields = [];
    }

    // https://github.com/tuyakhov/yii2-json-api/pull/9
    public function testSerializeIdentifier()
    {
        ResourceModel::$id = [];
        $model = new ResourceModel();
        $serializer = new Serializer();
        $this->expectException(InvalidValueException::class);
        $serializer->serialize($model);
    }
    
    public function testSerializeModelErrors()
    {
        $serializer = new Serializer();
        $model = new ResourceModel();
        $model->addError('field1', 'Test error');
        $model->addError('field2', 'Multiple error 1');
        $model->addError('field2', 'Multiple error 2');
        $model->addError('first_name', 'Member name check');
        $this->assertEquals([
            [
                'source' => ['pointer' => "/data/attributes/field1"],
                'detail' => 'Test error',
                'status' => '422'
            ],
            [
                'source' => ['pointer' => "/data/attributes/field2"],
                'detail' => 'Multiple error 1',
                'status' => '422'
            ],
            [
                'source' => ['pointer' => "/data/attributes/first-name"],
                'detail' => 'Member name check',
                'status' => '422'
            ]
        ], $serializer->serialize($model));
    }

    public function testSerializeModelData()
    {
        $serializer = new Serializer();
        ResourceModel::$id = 123;
        $model = new ResourceModel();
        $this->assertSame([
            'data' => [
                'id' => '123',
                'type' => 'resource-models',
                'attributes' => [
                    'field1' => 'test',
                    'field2' => 2,
                ],
                'links' => [
                    'self' => ['href' => 'http://example.com/resource/123']
                ]
            ]
        ], $serializer->serialize($model));

        ResourceModel::$fields = ['first_name'];
        ResourceModel::$extraFields = ['extraField1'];
        $model->extraField1 = new ResourceModel();
        $relationship = [
            'data' => ['id' => '123', 'type' => 'resource-models'],
            'links' => [
                'self' => ['href' => 'http://example.com/resource/123/relationships/extra-field1'],
                'related' => ['href' => 'http://example.com/resource/123/extra-field1'],
            ]
        ];
        $resource = [
            'id' => '123',
            'type' => 'resource-models',
            'attributes' => [
                'first-name' => 'Bob',
            ],
            'relationships' => [
                'extra-field1' => [
                    'links' => $relationship['links']
                ]
            ],
            'links' => [
                'self' => ['href' => 'http://example.com/resource/123']
            ]
        ];
        $expected = [
            'data' => $resource
        ];
        $this->assertSame($expected, $serializer->serialize($model));
        $_POST[$serializer->request->methodParam] = 'POST';
        \Yii::$app->request->setQueryParams(['include' => 'extra-field1']);
        $expected['included'][] = $resource;
        $expected['data']['relationships']['extra-field1'] = $relationship;
        $this->assertSame($expected, $serializer->serialize($model));
    }

    public function testExpand()
    {
        $serializer = new Serializer();
        $compoundModel = $includedModel = [
            'id' => '123',
            'type' => 'resource-models',
            'attributes' => [
                'field1' => 'test',
                'field2' => 2,
            ],
            'relationships' => [
                'extra-field1' => [
                    'data' => ['id' => '123', 'type' => 'resource-models'],
                    'links' => [
                        'self' => ['href' => 'http://example.com/resource/123/relationships/extra-field1'],
                        'related' => ['href' => 'http://example.com/resource/123/extra-field1'],
                    ]
                ]
            ]
        ];
        unset($includedModel['relationships']['extra-field1']['data']);
        $compoundModel['links'] = $includedModel['links'] = [
            'self' => ['href' => 'http://example.com/resource/123']
        ];
        $model = new ResourceModel();
        ResourceModel::$fields = ['field1', 'field2'];
        ResourceModel::$extraFields = ['extraField1'];
        $model->extraField1 = new ResourceModel();

        \Yii::$app->request->setQueryParams(['include' => 'extra-field1']);
        $this->assertSame([
            'data' => $compoundModel,
            'included' => [
                $includedModel
            ]
        ], $serializer->serialize($model));

        \Yii::$app->request->setQueryParams(['include' => 'extra-field1,extra-field2']);
        $this->assertSame([
            'data' => $compoundModel,
            'included' => [
                $includedModel
            ]
        ], $serializer->serialize($model));

        \Yii::$app->request->setQueryParams(['include' => 'field1,extra-field2']);
        $compoundModel['relationships'] = [
            'extra-field1' => [
                'links' => [
                    'self' => ['href' => 'http://example.com/resource/123/relationships/extra-field1'],
                    'related' => ['href' => 'http://example.com/resource/123/extra-field1'],
                ]
            ]
        ];
        $this->assertSame([
            'data' => $compoundModel
        ], $serializer->serialize($model));
    }

    public function testNestedRelationships()
    {
        ResourceModel::$fields = ['field1'];
        ResourceModel::$extraFields = ['extraField1'];
        $resource = new ResourceModel();
        $relationship = new ResourceModel();
        $subRelationship = new ResourceModel();
        $subRelationship->setId(321);
        $relationship->extraField1 = $subRelationship;
        $resource->extraField1 = $relationship;
        $compoundDocument = [
            'data' => [
                'id' => '123',
                'type' => 'resource-models',
                'attributes' => ['field1' => 'test'],
                'relationships' => [
                    'extra-field1' => [
                        'data' => ['id' => '123', 'type' => 'resource-models'],
                        'links' => [
                            'self' => ['href' => 'http://example.com/resource/123/relationships/extra-field1'],
                            'related' => ['href' => 'http://example.com/resource/123/extra-field1'],
                        ]
                    ],
                ],
                'links' => ['self' => ['href' => 'http://example.com/resource/123']],
            ],
            'included' => [
                [
                    'id' => '123',
                    'type' => 'resource-models',
                    'attributes' => ['field1' => 'test'],
                    'relationships' => [
                        'extra-field1' => [
                            'data' => ['id' => '321', 'type' => 'resource-models'],
                            'links' => [
                                'self' => ['href' => 'http://example.com/resource/123/relationships/extra-field1'],
                                'related' => ['href' => 'http://example.com/resource/123/extra-field1'],
                            ]
                        ],
                    ],
                    'links' => ['self' => ['href' => 'http://example.com/resource/123']],
                ],
                [
                    'id' => '321',
                    'type' => 'resource-models',
                    'attributes' => ['field1' => 'test'],
                    'relationships' => [
                        'extra-field1' => [
                            'links' => [
                                'self' => ['href' => 'http://example.com/resource/321/relationships/extra-field1'],
                                'related' => ['href' => 'http://example.com/resource/321/extra-field1'],
                            ]
                        ],
                    ],
                    'links' => ['self' => ['href' => 'http://example.com/resource/321']],
                ]
            ]
        ];

        $serializer = new Serializer();
        \Yii::$app->request->setQueryParams(['include' => 'extra-field1.extra-field1']);
        $this->assertSame($compoundDocument, $serializer->serialize($resource));
    }

    public function testIncludedDuplicates()
    {
        $serializer = new Serializer();

        $compoundModel = $includedModel = [
            'id' => '123',
            'type' => 'resource-models',
            'attributes' => [
                'field1' => 'test',
                'field2' => 2,
            ],
            'relationships' => [
                'extra-field1' => [
                    'data' => ['id' => '123', 'type' => 'resource-models'],
                    'links' => [
                        'self' => ['href' => 'http://example.com/resource/123/relationships/extra-field1'],
                        'related' => ['href' => 'http://example.com/resource/123/extra-field1'],
                    ]
                ],
                'extra-field2' => [
                    'data' => ['id' => '123', 'type' => 'resource-models'],
                    'links' => [
                        'self' => ['href' => 'http://example.com/resource/123/relationships/extra-field2'],
                        'related' => ['href' => 'http://example.com/resource/123/extra-field2'],
                    ]
                ]
            ]
        ];
        unset($includedModel['relationships']['extra-field1']['data']);
        unset($includedModel['relationships']['extra-field2']['data']);
        $compoundModel['links'] = $includedModel['links'] = [
            'self' => ['href' => 'http://example.com/resource/123']
        ];

        $model = new ResourceModel();
        ResourceModel::$fields = ['field1', 'field2'];
        ResourceModel::$extraFields = ['extraField1', 'extraField2'];
        $relationship = new ResourceModel();
        $relationship->extraField1 = new ResourceModel();
        $model->extraField2 = $relationship;
        $model->extraField1 = new ResourceModel();

        \Yii::$app->request->setQueryParams(['include' => 'extra-field1,extra-field2.extra-field1']);
        $this->assertSame([
            'data' => $compoundModel,
            'included' => [
                $includedModel
            ]
        ], $serializer->serialize($model));
        $this->assertSame([
            'data' => [$compoundModel],
            'included' => [
                $includedModel
            ],
            'links' => [
                'self' => ['href' => '/index.php?r=&include=extra-field1%2Cextra-field2.extra-field1&page=1']
            ],
            'meta' => [
                'total-count' => 1,
                'page-count' => 1,
                'current-page' => 1,
                'per-page' => 20
            ]
        ], $serializer->serialize(new ArrayDataProvider([
            'allModels' => [$model],
            'pagination' => [
                'route' => '/',
            ],
        ])));
    }

    public function dataProviderSerializeDataProvider()
    {
        $bob = new ResourceModel();
        $bob->username = 'Bob';
        $bob->extraField1 = new ResourceModel();
        $expectedBob = ['id' => '123', 'type' => 'resource-models', 
            'attributes' => ['username' => 'Bob'],
            'links' => ['self' => ['href' => 'http://example.com/resource/123']],
            'relationships' => ['extra-field1' => [
                'links' => [
                    'related' => ['href' => 'http://example.com/resource/123/extra-field1'],
                    'self' => ['href' => 'http://example.com/resource/123/relationships/extra-field1']
                ]
            ]]];
        $tom = new ResourceModel();
        $tom->username = 'Tom';
        $tom->extraField1 = new ResourceModel();
        $expectedTom = [
            'id' => '123', 'type' => 'resource-models',
            'attributes' => ['username' => 'Tom'],
            'links' => ['self' => ['href' => 'http://example.com/resource/123']],
            'relationships' => ['extra-field1' => [
                'links' => [
                    'related' => ['href' => 'http://example.com/resource/123/extra-field1'],
                    'self' => ['href' => 'http://example.com/resource/123/relationships/extra-field1']
                ]
            ]]];
        return [
            [
                new ArrayDataProvider([
                    'allModels' => [
                        $bob,
                        $tom
                    ],
                    'pagination' => [
                        'route' => '/',
                    ],
                ]),
                [
                    'data' => [
                        $expectedBob,
                        $expectedTom
                    ],
                    'meta' => [
                        'total-count' => 2,
                        'page-count' => 1,
                        'current-page' => 1,
                        'per-page' => 20
                    ],
                    'links' => [
                        'self' => ['href' => '/index.php?r=&page=1']
                    ]
                ]
            ],
            [
                new ArrayDataProvider([
                    'allModels' => [
                        $bob,
                        $tom
                    ],
                    'pagination' => [
                        'route' => '/',
                        'pageSize' => 1,
                        'page' => 0
                    ],
                ]),
                [
                    'data' => [
                        $expectedBob
                    ],
                    'meta' => [
                        'total-count' => 2,
                        'page-count' => 2,
                        'current-page' => 1,
                        'per-page' => 1
                    ],
                    'links' => [
                        'self' => ['href' => '/index.php?r=&page=1&per-page=1'],
                        'next' => ['href' => '/index.php?r=&page=2&per-page=1'],
                        'last' => ['href' => '/index.php?r=&page=2&per-page=1']
                    ]
                ]
            ],
            [
                new ArrayDataProvider([
                    'allModels' => [
                        $bob,
                        $tom
                    ],
                    'pagination' => [
                        'route' => '/',
                        'pageSize' => 1,
                        'page' => 1
                    ],
                ]),
                [
                    'data' => [
                        $expectedTom
                    ],
                    'meta' => [
                        'total-count' => 2,
                        'page-count' => 2,
                        'current-page' => 2,
                        'per-page' => 1
                    ],
                    'links' => [
                        'self' => ['href' => '/index.php?r=&page=2&per-page=1'],
                        'first' => ['href' => '/index.php?r=&page=1&per-page=1'],
                        'prev' => ['href' => '/index.php?r=&page=1&per-page=1']
                    ]
                ]
            ],
            [
                new ArrayDataProvider([
                    'allModels' => [
                        $bob,
                        $tom
                    ],
                    'pagination' => false,
                ]),
                [
                    'data' => [
                        $expectedBob,
                        $expectedTom
                    ]
                ]
            ],
        ];
    }
    /**
     * @dataProvider dataProviderSerializeDataProvider
     *
     * @param \yii\data\DataProviderInterface $dataProvider
     * @param array $expectedResult
     */
    public function testSerializeDataProvider($dataProvider, $expectedResult)
    {
        $serializer = new Serializer();
        ResourceModel::$extraFields = ['extraField1'];
        ResourceModel::$fields = ['username'];
        $this->assertEquals($expectedResult, $serializer->serialize($dataProvider));
    }

    public function testFieldSets()
    {
        $serializer = new Serializer();
        ResourceModel::$id = 123;
        ResourceModel::$fields = ['field1', 'first_name', 'field2'];
        $model = new ResourceModel();
        $expectedModel = [
            'data' => [
                'id' => '123',
                'type' => 'resource-models',
                'attributes' => [
                    'first-name' => 'Bob',
                ],
                'links' => [
                    'self' => ['href' => 'http://example.com/resource/123']
                ]
            ]
        ];
        \Yii::$app->request->setQueryParams(['fields' => ['resource-models' => 'first-name']]);
        $this->assertSame($expectedModel, $serializer->serialize($model));
        $serializer->pluralize = false;
        \Yii::$app->request->setQueryParams(['fields' => ['resource-model' => 'first-name']]);
        $expectedModel['data']['type'] = 'resource-model';
        $this->assertSame($expectedModel, $serializer->serialize($model));
    }

    public function testTypeInflection()
    {
        $serializer = new Serializer();
        $serializer->pluralize = false;
        $model = new ResourceModel();
        ResourceModel::$fields = [];
        $this->assertSame([
            'data' => [
                'id' => '123',
                'type' => 'resource-model',
                'attributes' => [],
                'links' => [
                    'self' => ['href' => 'http://example.com/resource/123']
                ]
            ]
        ], $serializer->serialize($model));
    }
}
