<?php


namespace Shenhou\Dingtalk;


class Process
{
    /**
     * 3.3 根据搜索条件获取实例详情
     * @param string $formUuid 表单ID
     * @param array $searchFieldJson 根据表单内组件值查询
     * @param null|string $taskId 任务ID
     * @param null|string $instanceStatus 实例状态可选值为：RUNNING,TERMINATED,COMPLETED,ERROR。分别代表：运行中，已终止，已完成，异常。
     * @param null|string $approvedResult 流程审批结果可选值为：agree, disagree。分别表示：同意， 拒绝。
     * @param int $currentPage 当前页必须大于0默认1
     * @param int $pageSize 每页记录数,必须大于0默认10不能大于100
     * @param null|string $originatorId 根据流程发起人工号查询
     * @param null|string $createFrom createFrom和createTo两个时间构造一个时间段。查询在该时间段创建的数据列表字符串格式，且为yyyy-MM-DD格式
     * @param null|string $createTo createFrom和createTo两个时间构造一个时间段。查询在该时间段创建的数据列表。字符串格式，且为yyyy-MM-DD格式。和createFrom一起，相当于查询在2018-01-01到2018-01-31之间(包含01和31号)创建的数据。
     * @param null|string $modifiedFrom modifiedFrom和modifiedTo构成一个时间段，查询在该时间段有修改的数据列表,字符串格式，且为yyyy-MM-DD格式
     * @param null|string $modifiedTo modifiedFrom和modifiedTo构成一个时间段，查询在该时间段有修改的数据列表,字符串格式，且为yyyy-MM-DD格式
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getInstances($formUuid, $searchFieldJson, $taskId = null, $instanceStatus = null, $approvedResult = null, $currentPage = 1, $pageSize = 10, $originatorId = null, $createFrom = null, $createTo = null, $modifiedFrom = null, $modifiedTo = null)
    {
        $data = [
            'formUuid' => $formUuid,
            'taskId' => $taskId,
            'instanceStatus' => $instanceStatus,
            'approvedResult' => $approvedResult,
            'currentPage' => $currentPage,
            'pageSize' => $pageSize,
            'originatorId' => $originatorId,
            'createFrom' => $createFrom,
            'createTo' => $createTo,
            'modifiedFrom' => $modifiedFrom,
            'modifiedTo' => $modifiedTo,
        ];
        if (!empty($searchFieldJson)) {
            $data['searchFieldJson'] = json_encode($searchFieldJson);
        }
        $data = array_filter($data);
        $res = common::curlPost('/yida_vpc/process/getInstances.json', $data);
        return $res;
    }
}