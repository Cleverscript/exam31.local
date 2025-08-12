<?php
namespace Exam31\Ticket;

use Bitrix\Main\Entity;
use Bitrix\Main\ORM\Fields\Relations\Reference;
use Bitrix\Main\ORM\Query\Join;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Entity\ExpressionField;

class SomeElementTable extends Entity\DataManager
{
	static function getTableName(): string
	{
		return 'exam31_ticket_someelement';
	}
	static function getMap(): array
	{
		return array(
			(new Entity\IntegerField('ID'))
				->configurePrimary()
				->configureAutocomplete(),
			(new Entity\BooleanField('ACTIVE'))
                ->configureValues(0, 1)
                ->configureDefaultValue(1)
				->configureRequired(),
			(new Entity\DatetimeField('DATE_MODIFY'))
				->configureRequired()
				->configureDefaultValue(new DateTime()),
			(new Entity\StringField('TITLE'))
				->configureRequired(),
			new Entity\TextField('TEXT'),
            (new Reference(
                "INFO",
                SomeElementInfoTable::class,
                Join::on("this.ID", "ref.ELEMENT_ID")
            ))->configureJoinType('left'),
            new ExpressionField(
                'CNT_INFO',
                "(SELECT COUNT(1) CNT FROM `"  . SomeElementInfoTable::getTableName() . "` WHERE ELEMENT_ID = %s)",
                ['ID']
            ),
		);
	}

	static function getFieldsDisplayLabel(): array
	{
		$fields = SomeElementTable::getMap();
		$res = [];
		foreach ($fields as $field)
		{
			$title = $field->getTitle();
			$res[$title] = Loc::getMessage("EXAM31_SOMEELEMENT_{$title}_FIELD_LABEL") ?? $title;
		}
		return $res;
	}
}