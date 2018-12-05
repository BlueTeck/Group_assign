<?php

namespace Kanboard\Plugin\Group_assign\Filter;

use Kanboard\Plugin\Group_assign\Model\MultiselectMemberModel;
use Kanboard\Core\Filter\FilterInterface;
use Kanboard\Filter\BaseFilter;
use Kanboard\Model\TaskModel;
use Kanboard\Model\UserModel;
use Kanboard\Model\GroupMemberModel;
use Kanboard\Model\GroupModel;
use PicoDb\Database;


class TaskAssigneeFilter extends BaseFilter implements FilterInterface
{
    /**
     * Database object
     *
     * @access private
     * @var Database
     */
    private $db;
    /**
     * Set database object
     *
     * @access public
     * @param  Database $db
     * @return TaskAssigneeFilter
     */
    public function setDatabase(Database $db)
    {
        $this->db = $db;
        return $this;
    }
    
    /**
     * Current user id
     *
     * @access private
     * @var int
     */
    private $currentUserId = 0;

    /**
     * Set current user id
     *
     * @access public
     * @param  integer $userId
     * @return TaskAssigneeFilter
     */
    public function setCurrentUserId($userId)
    {
        $this->currentUserId = $userId;
        return $this;
    }

    /**
     * Get search attribute
     *
     * @access public
     * @return string[]
     */
    public function getAttributes()
    {
        return array('assignee');
    }

    /**
     * Apply filter
     *
     * @access public
     * @return string
     */
    public function apply()
    {
        if (is_int($this->value) || ctype_digit($this->value)) {
            $this->query->beginOr();
            $this->query->eq(TaskModel::TABLE.'.owner_id', $this->value);
            $this->query->addCondition(TaskModel::TABLE.".owner_gp IN (SELECT group_id FROM ".GroupMemberModel::TABLE." WHERE ".GroupMemberModel::TABLE.".user_id='$this->value')");
            $this->query->addCondition(TaskModel::TABLE.".owner_ms IN (SELECT group_id FROM ".MultiselectMemberModel::TABLE." WHERE ".MultiselectMemberModel::TABLE.".user_id='$this->value')");
            $this->query->closeOr();
        } else {
            switch ($this->value) {
                case 'me':
                    $this->query->beginOr();
                    $this->query->eq(TaskModel::TABLE.'.owner_id', $this->currentUserId);
                    $this->query->addCondition(TaskModel::TABLE.".owner_gp IN (SELECT group_id FROM ".GroupMemberModel::TABLE." WHERE ".GroupMemberModel::TABLE.".user_id='$this->currentUserId')");
                    $this->query->addCondition(TaskModel::TABLE.".owner_ms IN (SELECT group_id FROM ".MultiselectMemberModel::TABLE." WHERE ".MultiselectMemberModel::TABLE.".user_id='$this->currentUserId')");
                    $this->query->closeOr();
                    break;
                case 'nobody':
                    $this->query->eq(TaskModel::TABLE.'.owner_id', 0);
                    break;
                default:
                    $this->query->beginOr();
                    $this->query
                        ->join(GroupMemberModel::TABLE, 'user_id', 'id', UserModel::TABLE)
                        ->join(MultiselectMemberModel::TABLE, 'user_id', 'id', UserModel::TABLE)
                        ->ilike(UserModel::TABLE.'.username', '%'.$this->value.'%');
                    $this->query
                        ->join(GroupMemberModel::TABLE, 'user_id', 'id', UserModel::TABLE)
                        ->join(MultiselectMemberModel::TABLE, 'user_id', 'id', UserModel::TABLE)
                        ->ilike(UserModel::TABLE.'.name', '%'.$this->value.'%');
                    $this->query->addCondition(TaskModel::TABLE.".owner_gp IN (SELECT id FROM ".GroupModel::TABLE." WHERE ".GroupModel::TABLE.".name='$this->value')");
                    $this->query->closeOr();
            }
        }
    }
    

}
