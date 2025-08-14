<?php B_PROLOG_INCLUDED === true || die();

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Loader;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\Text\HtmlFilter;
use Bitrix\Main\UI\PageNavigation;
use Bitrix\UI\Toolbar\Facade\Toolbar;

use Bitrix\Main\Error;
use Bitrix\Main\Errorable;
use Bitrix\Main\ErrorCollection;
use Bitrix\Main\ErrorableImplementation;

use Exam31\Ticket\SomeElementTable;

Loader::includeModule('exam31.ticket');
Loader::includeModule('ui');

class ExamElementsListComponent extends CBitrixComponent implements Errorable
{
	use ErrorableImplementation;
	protected const DEFAULT_PAGE_SIZE = 20;
	protected const GRID_ID = 'EXAM31_GRID_ELEMENT';

    protected static $totalRowsCount;

	public function __construct($component = null)
	{
		parent::__construct($component);
		$this->errorCollection = new ErrorCollection();
	}

	public function onPrepareComponentParams($arParams): array
	{
		if (!Loader::includeModule('exam31.ticket'))
		{
			$this->errorCollection->setError(
				new Error(Loc::getMessage('EXAM31_TICKET_MODULE_NOT_INSTALLED'))
			);
			return $arParams;
		}

		$arParams['ELEMENT_COUNT'] = (int) $arParams['ELEMENT_COUNT'];
		if ($arParams['ELEMENT_COUNT'] <= 0)
		{
			$arParams['ELEMENT_COUNT'] = static::DEFAULT_PAGE_SIZE;
		}
		return $arParams;
	}

	private function displayErrors(): void
	{
		foreach ($this->getErrors() as $error)
		{
			ShowError($error->getMessage());
		}
	}

	public function executeComponent(): void
	{
		if ($this->hasErrors())
		{
			$this->displayErrors();
			return;
		}

        $request = Bitrix\Main\Context::getCurrent()->getRequest();
        $page = 1;
        if (isset($request[static::GRID_ID . '_nav'])) {
            $page = (int) preg_replace('/[^0-9]/', '', $request[static::GRID_ID . '_nav']);
        }

        // Get grid options
        $gridOptions = new Bitrix\Main\Grid\Options(static::GRID_ID);
        $navParams = $gridOptions->GetNavParams();

        $limit = $navParams['nPageSize'];

        // Get filter
        $filterOption = new Bitrix\Main\UI\Filter\Options(static::GRID_ID);
        $filterData = $filterOption->getFilter([]);
        $filter = [];

        if (!empty($filterData['TITLE'])) {
            $filter['TITLE'] = trim($filterData['TITLE']);
        }

        // Добавляем фильтр в тулбар
        Toolbar::addFilter([
            'FILTER_ID' => static::GRID_ID,
            'GRID_ID' => static::GRID_ID, // Указываем ID грида, к которому относится фильтр
            'FILTER' => [
                [
                    'id' => 'TITLE',
                    'name' => Loc::getMessage('EXAM_ELEMENTS_LIST_FILTER_LABEL_TITLE_NAME'),
                    'type' => 'string'
                ],
            ],
            'ENABLE_LIVE_SEARCH' => true,
            'ENABLE_LABEL' => true,
            'RESET_TO_DEFAULT_MODE' => true,
        ]);

        // Добавляем кнопку в тулбар
        Toolbar::addButton([
            //"color" => \Bitrix\UI\Buttons\Color::LIGHT,
            //"icon" => \Bitrix\UI\Buttons\Icon::DONE,
            "click" => new \Bitrix\UI\Buttons\JsEvent(
                "alert('addButton')"
            ),
            "text" => Loc::getMessage('EXAM_ELEMENTS_LIST_TOOLBAR_BTN_NAME')
        ]);

        $this->arResult['TOTAL_ROWS_COUNT'] = $this->totalRowsCount($filter);
		$this->arResult['ITEMS'] = $this->getSomeElementList($page, $limit, $filter);
		$this->arResult['grid'] = $this->prepareGrid($this->arResult['ITEMS'], $limit);

		$this->includeComponentTemplate();

		global $APPLICATION;
		$APPLICATION->SetTitle(Loc::getMessage('EXAM31_ELEMENTS_LIST_PAGE_TITLE'));
	}

	protected function getSomeElementList(int $page = 1, int $limit = 10, $filter = []): array
	{
        $offset = $limit * ($page - 1);

        $query = SomeElementTable::query();

        if (!empty($filter)) {
            foreach ($filter as $field => $value) {
                $query->whereLike($field, "%{$value}%");
            }
        }

        $query->where('ACTIVE', true)
            ->addOrder('ID', 'ASC')
            ->setSelect(['ID', 'DATE_MODIFY', 'TITLE', 'TEXT', 'CNT_INFO', 'ACTIVE_LANG'])
            ->setLimit($limit)
            ->setOffset($offset);

        $collection = $query->exec()
            ->fetchCollection();

        $items = [];

        foreach ($collection as $el) {
            $items[] = [
                'ID' => $el->get('ID'),
                'DATE_MODIFY' => $el->get('DATE_MODIFY'),
                'TITLE' => HtmlFilter::encode($el->get('TITLE')),
                'TEXT' => HtmlFilter::encode($el->get('TEXT')),
                'ACTIVE' => $el->get('ACTIVE_LANG'),
                'CNT_INFO' => $el->get('CNT_INFO'),
            ];
        }

		$preparedItems = [];
		foreach ($items as $item)
		{
			$item['DETAIL_URL'] = $this->getDetailPageUrl($item['ID']);
			$item['INFO_URL'] = $this->getInfoPageUrl($item['ID']);
			$item['DATE_MODIFY'] = $item['DATE_MODIFY'] instanceof DateTime
				? $item['DATE_MODIFY']->toString()
				: null;

			$preparedItems[] = $item;
		}
		return $preparedItems;
	}

    protected function totalRowsCount($filter = [])
    {
        if (!static::$totalRowsCount) {
            $query = SomeElementTable::query();

            if (!empty($filter)) {
                foreach ($filter as $field => $value) {
                    $query->whereLike($field, "%{$value}%");
                }
            }

            static::$totalRowsCount =  $query->where('ACTIVE', true)
                ->exec()
                ->getSelectedRowsCount();
        }

        return static::$totalRowsCount;
    }

    protected function getNavigation($limit)
    {
        // Page navigation
        $nav = new PageNavigation(static::GRID_ID . '_nav');
        $nav->allowAllRecords(false)->setPageSize($limit)->initFromUri();
        $nav->setRecordCount($this->totalRowsCount());

        return $nav;
    }

	protected function prepareGrid($items, $limit): array
	{
		return [
			'GRID_ID' => static::GRID_ID,
			'COLUMNS' => $this->getGridColums(),
			'ROWS' => $this->getGridRows($items),
			'TOTAL_ROWS_COUNT' => $this->totalRowsCount(),
			'SHOW_ROW_CHECKBOXES' => false,
			'SHOW_SELECTED_COUNTER' => false,
			'AJAX_MODE' => 'Y',
			'AJAX_OPTION_JUMP' => 'N',
			'AJAX_OPTION_HISTORY' => 'N',
            'NAV_OBJECT' => $this->getNavigation($limit),
            "SHOW_PAGESIZE" => true,
            'PAGE_SIZES' => [
                ['NAME' => '10', 'VALUE' => '10'],
                ['NAME' => '20', 'VALUE' => '20'],
                ['NAME' => '50', 'VALUE' => '50'],
                ['NAME' => '100', 'VALUE' => '100']
            ],
		];
	}

	protected function getGridColums(): array
	{
		$fieldsLabel = SomeElementTable::getFieldsDisplayLabel();
		return [
			['id' => 'ACTIVE', 'default' => true, 'name' => $fieldsLabel['ACTIVE'] ?? 'ACTIVE'],
			['id' => 'ID', 'default' => true, 'name' => $fieldsLabel['ID'] ?? 'ID'],
			['id' => 'DATE_MODIFY', 'default' => true, 'name' => $fieldsLabel['DATE_MODIFY'] ?? 'DATE_MODIFY'],
			['id' => 'TITLE', 'default' => true, 'name' => $fieldsLabel['TITLE'] ?? 'TITLE'],
			['id' => 'TEXT', 'default' => true, 'name' => $fieldsLabel['TEXT'] ?? 'TEXT'],
			['id' => 'DETAIL', 'default' => true, 'name' => Loc::getMessage('EXAM31_ELEMENTS_LIST_GRIG_COLUMN_DETAIL_NAME')],
			['id' => 'INFO', 'default' => true, 'name' => Loc::getMessage('EXAM31_ELEMENTS_LIST_GRIG_COLUMN_INFO_NAME')],
		];
	}
	protected function getGridRows(array $items): array
	{
		if (empty($items))
		{
			return [];
		}

		$rows = [];
		foreach ($items as $key => $item)
		{
			$rows[$key] = [
				'id' => $item["ID"],
				'columns' => [
					'ID' => $item["ID"],
					'DATE_MODIFY' => $item["DATE_MODIFY"],
					'TITLE' => $item["TITLE"],
					'TEXT' => $item["TEXT"],
					'ACTIVE' => $item["ACTIVE"],
					'DETAIL' => $this->getDetailHTMLLink($item["DETAIL_URL"]),
					'INFO' => $this->getInfoHTMLLink($item["INFO_URL"], $item["CNT_INFO"]),
				],
                'actions' => [
                    [
                        'text' => Loc::getMessage('EXAM31_ELEMENTS_LIST_GRIG_COLUMN_DETAIL_NAME'),
                        'default' => true,
                        'onclick' => "BX.SidePanel.Instance.open('{$item["DETAIL_URL"]}', {
                            allowChangeHistory: true,
                            animationDuration: 100,
                            width: 1100,
                            cacheable: true,
                            autoFocus: true,
                            label: {
                                text: '" . Loc::getMessage('EXAM31_ELEMENTS_LIST_GRID_SLIDER_DETAIL_LABEL') . "',
                            }
                        })",
                    ],
                    [
                        'text' => Loc::getMessage('EXAM31_ELEMENTS_LIST_GRIG_COLUMN_INFO_NAME'),
                        'default' => true,
                        'onclick' => "BX.SidePanel.Instance.open('{$item["INFO_URL"]}', {
                            allowChangeHistory: true,
                            animationDuration: 100,
                            width: 1100,
                            cacheable: true,
                            autoFocus: true,
                            label: {
                                text: '" . Loc::getMessage('EXAM31_ELEMENTS_LIST_GRID_SLIDER_INFO_LABEL') . "',
                                color: '#FFFFFF',
                                bgColor: '#a4eba7',
                            }
                        })",
                    ],
                ]
			];
		}
		return $rows;
	}

	protected function getDetailPageUrl(int $id): string
	{
		return str_replace('#ELEMENT_ID#', $id, $this->arParams['DETAIL_PAGE_URL']);
	}
    protected function getInfoPageUrl(int $id): string
    {
        return str_replace('#ELEMENT_ID#', $id, $this->arParams['INFO_PAGE_URL']);
    }
	protected function getDetailHTMLLink(string $detail_url): string
	{
		return "<a href=\"" . $detail_url . "\">" . Loc::getMessage('EXAM31_ELEMENTS_LIST_GRIG_COLUMN_DETAIL_NAME') . "</a>";
	}
    protected function getInfoHTMLLink(string $detail_url, $cnt): string
    {
        return "<a href=\"" . $detail_url . "\">" . Loc::getMessage('EXAM31_ELEMENTS_LIST_GRIG_COLUMN_INFO_NAME_VALUE', ['#CNT#' => $cnt]) . "</a>";
    }
}