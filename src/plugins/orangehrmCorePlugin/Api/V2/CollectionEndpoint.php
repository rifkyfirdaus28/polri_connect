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

namespace OrangeHRM\Core\Api\V2;

use OrangeHRM\Core\Api\V2\Exception\ForbiddenException;
use OrangeHRM\Core\Api\V2\Exception\NotImplementedException;
use OrangeHRM\Core\Api\V2\Exception\RecordNotFoundException;
use OrangeHRM\Core\Api\V2\Serializer\NormalizeException;
use OrangeHRM\Core\Api\V2\Validator\ParamRuleCollection;

interface CollectionEndpoint
{
    /**
     * Get collection of resources
     * @return EndpointResult
     * @throws NormalizeException
     * @throws NotImplementedException
     * @throws RecordNotFoundException
     * @throws ForbiddenException
     */
    public function getAll(): EndpointResult;

    /**
     * Validation rules for CollectionEndpoint::getAll
     * @return ParamRuleCollection
     * @throws NotImplementedException
     */
    public function getValidationRuleForGetAll(): ParamRuleCollection;

    /**
     * Create a new resource
     * @return EndpointResult
     * @throws NormalizeException
     * @throws NotImplementedException
     * @throws RecordNotFoundException
     * @throws ForbiddenException
     */
    public function create(): EndpointResult;

    /**
     * Validation rules for CollectionEndpoint::create
     * @return ParamRuleCollection
     * @throws NotImplementedException
     */
    public function getValidationRuleForCreate(): ParamRuleCollection;

    /**
     * Delete list of resources
     * @return EndpointResult
     * @throws NormalizeException
     * @throws NotImplementedException
     * @throws RecordNotFoundException
     * @throws ForbiddenException
     */
    public function delete(): EndpointResult;

    /**
     * Validation rules for CollectionEndpoint::delete
     * @return ParamRuleCollection
     * @throws NotImplementedException
     */
    public function getValidationRuleForDelete(): ParamRuleCollection;
}
