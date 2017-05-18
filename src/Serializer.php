<?php
/**
 * @author Anton Tuyakhov <atuyakhov@gmail.com>
 */

namespace tuyakhov\jsonapi;

use yii\base\Component;
use yii\base\InvalidValueException;
use yii\base\Model;
use yii\data\DataProviderInterface;
use yii\data\Pagination;
use yii\web\Link;
use yii\web\Linkable;
use yii\web\Request;
use yii\web\Response;

class Serializer extends Component
{
    /**
     * @var string the name of the query parameter containing the information about which fields should be returned
     * for a [[Model]] object. If the parameter is not provided or empty, the default set of fields as defined
     * by [[Model::fields()]] will be returned.
     */
    public $fieldsParam = 'fields';
    /**
     * @var string the name of the query parameter containing the information about which fields should be returned
     * in addition to those listed in [[fieldsParam]] for a resource object.
     */
    public $expandParam = 'include';
    /**
     * @var string the name of the envelope (e.g. `_links`) for returning the links objects.
     * It takes effect only, if `collectionEnvelope` is set.
     * @since 2.0.4
     */
    public $linksEnvelope = 'links';
    /**
     * @var string the name of the envelope (e.g. `_meta`) for returning the pagination object.
     * It takes effect only, if `collectionEnvelope` is set.
     * @since 2.0.4
     */
    public $metaEnvelope = 'meta';
    /**
     * @var Request the current request. If not set, the `request` application component will be used.
     */
    public $request;
    /**
     * @var Response the response to be sent. If not set, the `response` application component will be used.
     */
    public $response;
    /**
     * @var bool whether to automatically pluralize the `type` of resource.
     */
    public $pluralize = true;

    /**
     * Prepares the member name that should be returned.
     * If not set, all member names will be converted to recommended format.
     * For example, both 'firstName' and 'first_name' will be converted to 'first-name'.
     * @var callable
     */
    public $prepareMemberName = ['tuyakhov\jsonapi\Inflector', 'var2member'];

    /**
     * Converts a member name to an attribute name.
     * @var callable
     */
    public $formatMemberName = ['tuyakhov\jsonapi\Inflector', 'member2var'];


    /**
     * @inheritdoc
     */
    public function init()
    {
        if ($this->request === null) {
            $this->request = \Yii::$app->getRequest();
        }
        if ($this->response === null) {
            $this->response = \Yii::$app->getResponse();
        }
    }

    /**
     * Serializes the given data into a format that can be easily turned into other formats.
     * This method mainly converts the objects of recognized types into array representation.
     * It will not do conversion for unknown object types or non-object data.
     * @param mixed $data the data to be serialized.
     * @return mixed the converted data.
     */
    public function serialize($data)
    {
        if ($data instanceof Model && $data->hasErrors()) {
            return $this->serializeModelErrors($data);
        } elseif ($data instanceof ResourceInterface) {
            return $this->serializeResource($data);
        } elseif ($data instanceof DataProviderInterface) {
            return $this->serializeDataProvider($data);
        } else {
            return $data;
        }
    }

    /**
     * @param ResourceInterface $model
     * @return array
     */
    protected function serializeModel(ResourceInterface $model)
    {
        $fields = $this->getRequestedFields();
        $type = $this->pluralize ? Inflector::pluralize($model->getType()) : $model->getType();
        $fields = isset($fields[$type]) ? $fields[$type] : [];

        $attributes = $model->getResourceAttributes($fields);
        $attributes = array_combine($this->prepareMemberNames(array_keys($attributes)), array_values($attributes));

        $data = array_merge($this->serializeIdentifier($model), [
            'attributes' => $attributes,
        ]);

        $included = $this->getIncluded();
        $relationships = $model->getResourceRelationships();
        if (!empty($relationships)) {
            foreach ($relationships as $name => $items) {
                $relationship = [];
                if (is_array($items)) {
                    foreach ($items as $item) {
                        if ($item instanceof ResourceIdentifierInterface) {
                            $relationship[] = $this->serializeIdentifier($item);
                        }
                    }
                } elseif ($items instanceof ResourceIdentifierInterface) {
                    $relationship = $this->serializeIdentifier($items);
                }
                if (!empty($relationship)) {
                    $memberName = $this->prepareMemberNames([$name]);
                    $memberName = reset($memberName);
                    if (in_array($name, $included)) {
                        $data['relationships'][$memberName]['data'] = $relationship;
                    }
                    if ($model instanceof LinksInterface) {
                        $links = $model->getRelationshipLinks($memberName);
                        if (!empty($links)) {
                            $data['relationships'][$memberName]['links'] = Link::serialize($links);
                        }
                    }
                }
            }
        }

        if ($model instanceof Linkable) {
            $data['links'] = Link::serialize($model->getLinks());
        }

        return $data;
    }

    /**
     * @param ResourceInterface $resource
     * @return array
     */
    protected function serializeResource(ResourceInterface $resource)
    {
        if ($this->request->getIsHead()) {
            return null;
        } else {
            $data = ['data' => $this->serializeModel($resource)];

            $included = $this->serializeIncluded($resource);
            if (!empty($included)) {
                $data['included'] = array_values($included);
            }

            return $data;
        }
    }

    /**
     * Serialize resource identifier object and make type juggling
     * @link http://jsonapi.org/format/#document-resource-object-identification
     * @param ResourceIdentifierInterface $identifier
     * @return array
     */
    protected function serializeIdentifier(ResourceIdentifierInterface $identifier)
    {
        $result = [];
        foreach (['id', 'type'] as $key) {
            $getter = 'get' . ucfirst($key);
            $value = $identifier->$getter();
            if ($value === null || is_array($value) || (is_object($value) && !method_exists($value, '__toString'))) {
                throw new InvalidValueException("The value {$key} of resource object " . get_class($identifier) . ' MUST be a string.');
            }
            if ($key === 'type' && $this->pluralize) {
                $value = Inflector::pluralize($value);
            }
            $result[$key] = (string) $value;
        }
        return $result;
    }

    /**
     * @param ResourceInterface $resource
     * @param array $includedObjects All included objects from previous serialization, prevent duplicate includes
     * @return array
     */
    protected function serializeIncluded($resource, $includedObjects = [])
    {
        $included = $this->getIncluded();
        $relationships = $resource->getResourceRelationships();
        foreach ($relationships as $name => $relationship) {
            if (!in_array($name, $included)) {
                continue;
            }
            if (!is_array($relationship)) {
                $relationship = [$relationship];
            }
            foreach ($relationship as $model) {
                if ($model instanceof ResourceInterface) {
                    $deduplicateKey = $model->getType() . '|' . $model->getId();
                    if (!isset($includedObjects[$deduplicateKey])) {
                        $includedObjects[$deduplicateKey] = $this->serializeModel($model);
                    }
                }
            }
        }
        return $includedObjects;
    }

    /**
     * Serializes a data provider.
     * @param DataProviderInterface $dataProvider
     * @return array the array representation of the data provider.
     */
    protected function serializeDataProvider($dataProvider)
    {
        if ($this->request->getIsHead()) {
            return null;
        } else {
            $models = [];
            $includedModels = [];

            foreach ($dataProvider->getModels() as $model) {
                if ($model instanceof ResourceInterface) {
                    $models[] = $this->serializeModel($model);

                    $includedModels = $this->serializeIncluded($model, $includedModels);
                }
            }

            $result = ['data' => $models];

            if (!empty($includedModels)) {
                $result['included'] = array_values($includedModels);
            }

            if (($pagination = $dataProvider->getPagination()) !== false) {
                return array_merge($result, $this->serializePagination($pagination));
            }

            return $result;
        }
    }

    /**
     * Serializes a pagination into an array.
     * @param Pagination $pagination
     * @return array the array representation of the pagination
     * @see addPaginationHeaders()
     */
    protected function serializePagination($pagination)
    {
        return [
            $this->linksEnvelope => Link::serialize($pagination->getLinks(true)),
            $this->metaEnvelope => [
                'total-count' => $pagination->totalCount,
                'page-count' => $pagination->getPageCount(),
                'current-page' => $pagination->getPage() + 1,
                'per-page' => $pagination->getPageSize(),
            ],
        ];
    }

    /**
     * Serializes the validation errors in a model.
     * @param Model $model
     * @return array the array representation of the errors
     */
    protected function serializeModelErrors($model)
    {
        $this->response->setStatusCode(422, 'Data Validation Failed.');
        $result = [];
        foreach ($model->getFirstErrors() as $name => $message) {
            $result[] = [
                'source' => ['pointer' => "/data/attributes/{$name}"],
                'detail' => $message,
            ];
        }

        return $result;
    }

    /**
     * @return array
     */
    protected function getRequestedFields()
    {
        $fields = $this->request->get($this->fieldsParam);

        if (!is_array($fields)) {
            $fields = [];
        }
        foreach ($fields as $key => $field) {
            $fields[$key] = array_map($this->formatMemberName, preg_split('/\s*,\s*/', $field, -1, PREG_SPLIT_NO_EMPTY));
        }
        return $fields;
    }

    protected function getIncluded()
    {
        $include = $this->request->get($this->expandParam);
        return is_string($include) ? array_map($this->formatMemberName, preg_split('/\s*,\s*/', $include, -1, PREG_SPLIT_NO_EMPTY)) : [];
    }


    /**
     * Format member names according to recommendations for JSON API implementations
     * @link http://jsonapi.org/format/#document-member-names
     * @param array $memberNames
     * @return array
     */
    protected function prepareMemberNames(array $memberNames = [])
    {
        return array_map($this->prepareMemberName, $memberNames);
    }
}
