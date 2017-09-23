<?php
/**
 * @author Anton Tuyakhov <atuyakhov@gmail.com>
 */

namespace tuyakhov\jsonapi;

/**
 * Interface for a “resource object” that represent an individual resource.
 */
interface ResourceInterface extends ResourceIdentifierInterface
{
    /**
     * The "attributes" member of the resource object representing some of the resource’s data.
     * @param array $fields specific fields that a client has requested.
     * @return array an array of attributes that represent information about the resource object in which it’s defined.
     */
    public function getResourceAttributes(array $fields = []);

    /**
     * The "relationships" member of the resource object describing relationships between the resource and other JSON API resources.
     * @param array $linked specific resource linkage that a client has requested.
     * @return ResourceIdentifierInterface[] represent references from the resource object in which it’s defined to other resource objects.
     */
    public function getResourceRelationships(array $linked = []);

    public function setResourceRelationship($name, $relationship);

}