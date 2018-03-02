<?php
/**
 * @author Anton Tuyakhov <atuyakhov@gmail.com>
 */

namespace tuyakhov\jsonapi;

use yii\helpers\ArrayHelper;
use yii\web\BadRequestHttpException;
use \yii\web\JsonParser;

class JsonApiParser extends JsonParser
{
    /**
     * Converts 'type' member to form name
     * If not set, type will be converted to singular form.
     * For example, 'articles' will be converted to 'Article'
     * @var callable
     */
    public $formNameCallback = ['tuyakhov\jsonapi\Inflector', 'type2form'];

    /**
     * Converts member names to variable names
     * If not set, all special characters will be replaced by underscore
     * For example, 'first-name' will be converted to 'first_name'
     * @var callable
     */
    public $memberNameCallback = ['tuyakhov\jsonapi\Inflector', 'member2var'];

    /**
     * Parse resource object into the input data to populates the model
     * @inheritdoc
     */
    public function parse($rawBody, $contentType)
    {
        $array = parent::parse($rawBody, $contentType);
        if (!empty($array) && !ArrayHelper::keyExists('data', $array)) {
            if ($this->throwException) {
                throw new BadRequestHttpException('The request MUST include a single resource object as primary data.');
            }
            return [];
        }
        $data =  ArrayHelper::getValue($array, 'data', []);
        if (empty($data)) {
            return [];
        }
        if (ArrayHelper::isAssociative($data)) {
            $result = $this->parseResource($data);

            $relObjects = ArrayHelper::getValue($data, 'relationships', []);
            $result['relationships'] = $this->parseRelationships($relObjects);
        } else {
            foreach ($data as $object) {
                $resource = $this->parseResource($object);
                foreach (array_keys($resource) as $key) {
                    $result[$key][] = $resource[$key];
                }
            }
        }

        return isset($result) ? $result : $array;
    }

    /**
     * @param $type 'type' member of the document
     * @return string form name
     */
    protected function typeToFormName($type)
    {
        return call_user_func($this->formNameCallback, $type);
    }

    /**
     * @param array $memberNames
     * @return array variable names
     */
    protected function parseMemberNames(array $memberNames = [])
    {
        return array_map($this->memberNameCallback, $memberNames);
    }

    /**
     * @param $item
     * @return array
     * @throws BadRequestHttpException
     */
    protected function parseResource($item)
    {
        if (!$type = ArrayHelper::getValue($item, 'type')) {
            if ($this->throwException) {
                throw new BadRequestHttpException('The resource object MUST contain at least a type member');
            }
            return [];
        }
        $formName = $this->typeToFormName($type);

        $attributes = ArrayHelper::getValue($item, 'attributes', []);
        $attributes = array_combine($this->parseMemberNames(array_keys($attributes)), array_values($attributes));

        if ($id = ArrayHelper::getValue($item, 'id')) {
            $attributes['id'] = $id;
        }

        return [$formName => $attributes];
    }

    /**
     * @param array $relObjects
     * @return array
     */
    protected function parseRelationships(array $relObjects = [])
    {
        $relationships = [];
        foreach ($relObjects as $name => $relationship) {
            if (!ArrayHelper::keyExists('data', $relationship)) {
                continue;
            }
            $relData = ArrayHelper::getValue($relationship, 'data', []);
            if (!ArrayHelper::isIndexed($relData)) {
                $relData = [$relData];
            }
            $relationships[$name] = [];
            foreach ($relData as $identifier) {
                if (isset($identifier['type']) && isset($identifier['id'])) {
                    $formName = $this->typeToFormName($identifier['type']);
                    $relationships[$name][$formName][] = ['id' => $identifier['id']];
                }
            }
        }
        return $relationships;
    }
}
