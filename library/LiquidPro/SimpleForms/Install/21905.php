<?php

class LiquidPro_SimpleForms_Install_21902 extends LiquidPro_SimpleForms_Install_Abstract
{
    public function install(&$db)
    {
		// get a list of tables that are MyISAM
		$tables = $db->fetchAll('
			SELECT
				*
			FROM `INFORMATION_SCHEMA`.`TABLES`
			WHERE `TABLE_SCHEMA` = DATABASE()
				AND `TABLE_NAME` LIKE \'lpsf_%\'
				AND `ENGINE` = \'MyISAM\'
		');

		// convert MyISAM tables to InnoDB
		foreach ($tables as $table)
		{
			$db->query("ALTER TABLE `" . $table['TABLE_NAME'] . "` ENGINE = 'InnoDB'");
		}

        return true;
    }
}