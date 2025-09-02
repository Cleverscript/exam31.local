<?php

namespace B24\Academy\UI\Tasks;

use Bitrix\Main\Page\Asset;
use CComponentEngine;

class ListPageMutator
{
    private const LIST_PAGE_TEMPLATES = [
        'personal' => 'company/personal/user/#user_id#/tasks/',
        'group' => 'workgroups/group/#group_id#/tasks/',
    ];
    public static function handleOnEpilog()
    {
        $page = CComponentEngine::parseComponentPath('/', self::LIST_PAGE_TEMPLATES, $vars);

        if ($page === false) {
            return;
        }

        Asset::getInstance()->addCss('/local/css/b24.academy/tasks/list.css');
    }
}