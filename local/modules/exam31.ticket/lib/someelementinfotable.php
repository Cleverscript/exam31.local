<?php
namespace Exam31\Ticket;

use Bitrix\Main\Entity;
use Bitrix\Main\ORM\Fields\Relations\Reference;
use Bitrix\Main\ORM\Query\Join;
use Bitrix\Main\Localization\Loc;

class SomeElementInfoTable extends Entity\DataManager
{
	static function getTableName(): string
	{
		return 'exam31_ticket_someelementinfo';
	}
	static function getMap(): array
	{
		return array(
			(new Entity\IntegerField('ID'))
				->configurePrimary()
				->configureAutocomplete(),
			(new Entity\StringField('TITLE'))
				->configureRequired(),
            (new Entity\IntegerField('ELEMENT_ID'))
                ->configureRequired(),
            new Reference(
                "ELEMENT",
                SomeElementTable::class,
                Join::on("this.ELEMENT_ID", "ref.ID")
            ),
		);
	}

	static function getFieldsDisplayLabel(): array
	{
		$fields = SomeElementInfoTable::getMap();
		$res = [];
		foreach ($fields as $field)
		{
			$title = $field->getTitle();
			$res[$title] = Loc::getMessage("EXAM31_SOMEELEMENTINFO_{$title}_FIELD_LABEL") ?? $title;
		}
		return $res;
	}
}