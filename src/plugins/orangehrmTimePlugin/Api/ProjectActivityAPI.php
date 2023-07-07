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

use OrangeHRM\Core\Api\CommonParams;
use OrangeHRM\Core\Api\V2\CrudEndpoint;
use OrangeHRM\Core\Api\V2\Endpoint;
use OrangeHRM\Core\Api\V2\EndpointCollectionResult;
use OrangeHRM\Core\Api\V2\EndpointResourceResult;
use OrangeHRM\Core\Api\V2\EndpointResult;
use OrangeHRM\Core\Api\V2\Model\ArrayModel;
use OrangeHRM\Core\Api\V2\ParameterBag;
use OrangeHRM\Core\Api\V2\RequestParams;
use OrangeHRM\Core\Api\V2\Validator\ParamRule;
use OrangeHRM\Core\Api\V2\Validator\ParamRuleCollection;
use OrangeHRM\Core\Api\V2\Validator\Rule;
use OrangeHRM\Core\Api\V2\Validator\Rules;
use OrangeHRM\Entity\ProjectActivity;
use OrangeHRM\Time\Api\Model\ProjectActivityModel;
use OrangeHRM\Time\Dto\ProjectActivitySearchFilterParams;
use OrangeHRM\Time\Exception\ProjectServiceException;
use OrangeHRM\Time\Traits\Service\ProjectServiceTrait;

class ProjectActivityAPI extends Endpoint implements CrudEndpoint
{
    use ProjectServiceTrait;

    public const PARAMETER_PROJECT_ID = 'projectId';
    public const PARAMETER_NAME = 'name';

    public const FILTER_PROJECT_ACTIVITY_NAME = 'projectActivityName';
    public const PARAM_RULE_STRING_MAX_LENGTH = 100;

    /**
     * @inheritDoc
     */
    public function getAll(): EndpointResult
    {
        $projectId = $this->getRequestParams()->getInt(
            RequestParams::PARAM_TYPE_ATTRIBUTE,
            self::PARAMETER_PROJECT_ID
        );
        $projectActivitySearchFilterParams = new ProjectActivitySearchFilterParams();
        $projectActivitySearchFilterParams->setProjectActivityName(
            $this->getRequestParams()->getStringOrNull(
                RequestParams::PARAM_TYPE_QUERY,
                self::FILTER_PROJECT_ACTIVITY_NAME
            )
        );
        $this->setSortingAndPaginationParams($projectActivitySearchFilterParams);
        $projectActivities = $this->getProjectService()
            ->getProjectActivityDao()
            ->getProjectActivityListByProjectId($projectId, $projectActivitySearchFilterParams);

        $projectActivityCount = $this->getProjectService()
            ->getProjectActivityDao()
            ->getProjectActivityCount($projectId, $projectActivitySearchFilterParams);

        return new EndpointCollectionResult(
            ProjectActivityModel::class,
            $projectActivities,
            new ParameterBag(
                [CommonParams::PARAMETER_TOTAL => $projectActivityCount]
            )
        );
    }

    /**
     * @inheritDoc
     */
    public function getValidationRuleForGetAll(): ParamRuleCollection
    {
        return new ParamRuleCollection(
            $this->getProjectIdParamRule(),
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(
                    self::FILTER_PROJECT_ACTIVITY_NAME,
                    new Rule(Rules::STRING_TYPE),
                    new Rule(Rules::LENGTH, [null, self::PARAM_RULE_STRING_MAX_LENGTH])
                ),
            ),
            ...$this->getSortingAndPaginationParamsRules(ProjectActivitySearchFilterParams::ALLOWED_SORT_FIELDS)
        );
    }

    /**
     * @return ParamRule
     */
    private function getProjectIdParamRule(): ParamRule
    {
        return new ParamRule(
            self::PARAMETER_PROJECT_ID,
            new Rule(Rules::POSITIVE)
        );
    }

    /**
     * @inheritDoc
     */
    public function create(): EndpointResult
    {
        $projectActivity = new ProjectActivity();
        $this->setParamsToProjectActivity($projectActivity);
        $this->getProjectService()->getProjectActivityDao()->saveProjectActivity($projectActivity);
        return new EndpointResourceResult(ProjectActivityModel::class, $projectActivity);
    }

    /**
     * @param ProjectActivity $projectActivity
     * @return void
     */
    private function setParamsToProjectActivity(ProjectActivity $projectActivity): void
    {
        list($projectId) = $this->getUrlAttributes();
        $activityName = $this->getRequestParams()->getString(RequestParams::PARAM_TYPE_BODY, self::PARAMETER_NAME);
        $projectActivity->setName($activityName);
        $projectActivity->getDecorator()->setProjectById($projectId);
    }

    /**
     * @inheritDoc
     */
    public function getValidationRuleForCreate(): ParamRuleCollection
    {
        return new ParamRuleCollection(
            ...$this->getCommonBodyValidationRules(),
        );
    }

    /**
     * @inheritDoc
     */
    public function delete(): EndpointResult
    {
        try {
            $ids = $this->getRequestParams()->getArray(RequestParams::PARAM_TYPE_BODY, CommonParams::PARAMETER_IDS);
            $this->getProjectService()->getProjectActivityDao()->deleteProjectActivities($ids);
            return new EndpointResourceResult(ArrayModel::class, $ids);
        } catch (ProjectServiceException $projectServiceException) {
            throw $this->getBadRequestException($projectServiceException->getMessage());
        }
    }

    /**
     * @inheritDoc
     */
    public function getValidationRuleForDelete(): ParamRuleCollection
    {
        return new ParamRuleCollection(
            $this->getValidationDecorator()->requiredParamRule($this->getProjectIdParamRule()),
            new ParamRule(
                CommonParams::PARAMETER_IDS,
                new Rule(Rules::ARRAY_TYPE),
                new Rule(
                    Rules::EACH,
                    [new Rules\Composite\AllOf(new Rule(Rules::POSITIVE))]
                )
            )
        );
    }

    /**
     * @inheritDoc
     */
    public function getOne(): EndpointResult
    {
        list($projectId, $projectActivityId) = $this->getUrlAttributes();
        $projectActivity = $this->getProjectService()
            ->getProjectActivityDao()
            ->getProjectActivityByProjectIdAndProjectActivityId($projectId, $projectActivityId);
        $this->throwRecordNotFoundExceptionIfNotExist($projectActivity, ProjectActivity::class);
        return new EndpointResourceResult(ProjectActivityModel::class, $projectActivity);
    }

    /**
     * @inheritDoc
     */
    public function getValidationRuleForGetOne(): ParamRuleCollection
    {
        return new ParamRuleCollection(
            new ParamRule(
                CommonParams::PARAMETER_ID,
                new Rule(Rules::POSITIVE)
            ),
            $this->getProjectIdParamRule(),
        );
    }

    /**
     * @inheritDoc
     */
    public function update(): EndpointResult
    {
        list($projectId, $projectActivityId) = $this->getUrlAttributes();
        $projectActivity = $this->getProjectService()
            ->getProjectActivityDao()
            ->getProjectActivityByProjectIdAndProjectActivityId($projectId, $projectActivityId);
        $this->throwRecordNotFoundExceptionIfNotExist($projectActivity, ProjectActivity::class);
        $this->setParamsToProjectActivity($projectActivity);
        $this->getProjectService()->getProjectActivityDao()->saveProjectActivity($projectActivity);
        return new EndpointResourceResult(ProjectActivityModel::class, $projectActivity);
    }

    /**
     * @inheritDoc
     */
    public function getValidationRuleForUpdate(): ParamRuleCollection
    {
        return new ParamRuleCollection(
            new ParamRule(
                CommonParams::PARAMETER_ID,
                new Rule(Rules::POSITIVE)
            ),
            ...$this->getCommonBodyValidationRules(),
        );
    }

    /**
     * @return ParamRule[]
     */
    private function getCommonBodyValidationRules(): array
    {
        return [
            $this->getValidationDecorator()->requiredParamRule($this->getProjectIdParamRule()),
            $this->getValidationDecorator()->requiredParamRule(
                new ParamRule(
                    self::PARAMETER_NAME,
                    new Rule(Rules::STRING_TYPE),
                    new Rule(Rules::LENGTH, [null, self::PARAM_RULE_STRING_MAX_LENGTH])
                )
            )
        ];
    }

    /**
     * @return array
     */
    private function getUrlAttributes(): array
    {
        $projectActivityId = $this->getRequestParams()->getInt(
            RequestParams::PARAM_TYPE_ATTRIBUTE,
            CommonParams::PARAMETER_ID
        );
        $projectId = $this->getRequestParams()->getInt(
            RequestParams::PARAM_TYPE_ATTRIBUTE,
            self::PARAMETER_PROJECT_ID
        );
        return [$projectId, $projectActivityId];
    }
}
