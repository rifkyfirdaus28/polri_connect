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

namespace OrangeHRM\Time\Api;

use OrangeHRM\Core\Api\V2\Endpoint;
use OrangeHRM\Core\Api\V2\EndpointResourceResult;
use OrangeHRM\Core\Api\V2\EndpointResult;
use OrangeHRM\Core\Api\V2\Model\ArrayModel;
use OrangeHRM\Core\Api\V2\RequestParams;
use OrangeHRM\Core\Api\V2\ResourceEndpoint;
use OrangeHRM\Core\Api\V2\Validator\ParamRule;
use OrangeHRM\Core\Api\V2\Validator\ParamRuleCollection;
use OrangeHRM\Core\Api\V2\Validator\Rule;
use OrangeHRM\Core\Api\V2\Validator\Rules;
use OrangeHRM\Entity\Project;
use OrangeHRM\Time\Traits\Service\ProjectServiceTrait;

class ValidateProjectNameAPI extends Endpoint implements ResourceEndpoint
{
    use ProjectServiceTrait;

    public const PARAMETER_PROJECT_ID = 'projectId';
    public const PARAMETER_PROJECT_NAME = 'projectName';
    public const PARAMETER_CUSTOMER_ID = 'customerId';
    public const PARAMETER_IS_CHANGEABLE = 'valid';

    public const PARAM_RULE_PROJECT_NAME_MAX_LENGTH = 50;

    /**
     * @inheritDoc
     */
    public function getOne(): EndpointResult
    {
        $projectName = $this->getRequestParams()->getString(
            RequestParams::PARAM_TYPE_QUERY,
            self::PARAMETER_PROJECT_NAME
        );
        $projectId = $this->getRequestParams()->getIntOrNull(
            RequestParams::PARAM_TYPE_QUERY,
            self::PARAMETER_PROJECT_ID
        );
        $customerId = $this->getRequestParams()->getInt(
            RequestParams::PARAM_TYPE_QUERY,
            self::PARAMETER_CUSTOMER_ID
        );
        if (!is_null($projectId)) {
            $project = $this->getProjectService()->getProjectDao()->getProjectById($projectId);
            $this->throwRecordNotFoundExceptionIfNotExist($project, Project::class);
        }
        $isChangeableProjectName = $this->getProjectService()
            ->getProjectDao()
            ->isProjectNameTaken($projectName, $customerId, $projectId);
        return new EndpointResourceResult(
            ArrayModel::class,
            [
                self::PARAMETER_IS_CHANGEABLE => $isChangeableProjectName
            ]
        );
    }

    /**
     * @inheritDoc
     */
    public function getValidationRuleForGetOne(): ParamRuleCollection
    {
        return new ParamRuleCollection(
            new ParamRule(
                self::PARAMETER_PROJECT_NAME,
                new Rule(Rules::STRING_TYPE),
                new Rule(Rules::LENGTH, [null, self::PARAM_RULE_PROJECT_NAME_MAX_LENGTH]),
            ),
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(
                    self::PARAMETER_PROJECT_ID,
                    new Rule(Rules::POSITIVE),
                )
            ),
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(
                    self::PARAMETER_CUSTOMER_ID,
                    new Rule(Rules::POSITIVE),
                )
            ),
        );
    }

    /**
     * @inheritDoc
     */
    public function update(): EndpointResult
    {
        throw $this->getNotImplementedException();
    }

    /**
     * @inheritDoc
     */
    public function getValidationRuleForUpdate(): ParamRuleCollection
    {
        throw $this->getNotImplementedException();
    }

    /**
     * @inheritDoc
     */
    public function delete(): EndpointResult
    {
        throw $this->getNotImplementedException();
    }

    /**
     * @inheritDoc
     */
    public function getValidationRuleForDelete(): ParamRuleCollection
    {
        throw $this->getNotImplementedException();
    }
}
