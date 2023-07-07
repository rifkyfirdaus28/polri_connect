<?php
/**
 * OrangeHRM is a comprehensive Human Resource Management (HRM) System that captures
 * all the essential functionalities required for any enterprise.
 * Copyright (C) 2006 OrangeHRM Inc., http://www.orangehrm.com
 *
 * OrangeHRM is free software; you can redistribute it and/or modify it under the terms of
 * the GNU General Public License as published by the Free Software Foundation; either
 * version 2 of the License, or (at your option) any later version.
 *
 * OrangeHRM is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY;
 * without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with this program;
 * if not, write to the Free Software Foundation, Inc., 51 Franklin Street, Fifth Floor,
 * Boston, MA  02110-1301, USA
 */

namespace Orangehrm\Rest\Api\Model;

use Orangehrm\Rest\Api\Entity\Serializable;

trait ModelTrait
{
    /**
     * @var array
     */
    private $filter = [];

    /**
     * @var array
     */
    private $attributeNames = [];

    /**
     * @var null|Serializable|object
     */
    private $entity = null;

    /**
     * Set attribute names which should include to model
     * @param array $filter
     */
    public function setFilters(array $filter)
    {
        $this->filter = $filter;
    }

    /**
     * Override output array attributes
     * @param array $attributeNames
     */
    public function setAttributeNames(array $attributeNames)
    {
        $this->attributeNames = $attributeNames;
    }

    /**
     * Set Api entity class
     * @param Serializable|object $entity
     */
    public function setEntity($entity)
    {
        $this->entity = $entity;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        $array = [];
        if (!is_null($this->entity)) {
            foreach ($this->filter as $index => $attribute) {
                if (is_array($attribute)) {
                    $value = $this->entity;
                    foreach ($attribute as $func) {
                        if (!is_null($value)) {
                            $value = call_user_func([$value, $func]);
                        }
                    }
                } else {
                    // Only work with camel cased get methods with particular attribute
                    $getMethodName = "get" . ucfirst($attribute);
                    $value = $this->entity->$getMethodName();
                }
                $key = empty($this->attributeNames[$index]) ? $attribute :
                    $this->attributeNames[$index];
                if (is_array($key)) {
                    $array = array_merge_recursive($array, $this->makeNestedArray($key, $value));
                } else {
                    $array[$key] = $value;
                }
            }
        }
        return $array;
    }

    /**
     * @param array $keys
     * @param $value
     * @return array
     */
    private function makeNestedArray(array $keys, $value): array
    {
        $array = [];
        $key = array_shift($keys);

        if (!isset($keys[0])) {
            $array[$key] = $value;
        } else {
            $array[$key] = $this->makeNestedArray($keys, $value);
        }

        return $array;
    }
}
