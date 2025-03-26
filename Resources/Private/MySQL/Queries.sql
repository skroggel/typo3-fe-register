--- Find doubles by username
SELECT pid,
			 username,
			 deleted,
			 disable,
			 crdate,
			 DATE_FORMAT(FROM_UNIXTIME(`crdate`), '%e %b %Y') AS 'crdate_formatted', tstamp,
			 DATE_FORMAT(FROM_UNIXTIME(`tstamp`), '%e %b %Y') AS 'tstamp_formatted'
FROM fe_users AS mto
WHERE EXISTS (SELECT 1 FROM fe_users AS mti WHERE mti.username = mto.username LIMIT 1, 1)
ORDER BY username


-- Records to anonymized (change tstamp accordingly!)
SELECT `fe_users`.*
FROM `fe_users` `fe_users`
WHERE (`fe_users`.`tx_extbase_type` = 0)
	AND (((`fe_users`.`deleted` = 1) AND (`fe_users`.`tx_feregister_data_protection_status` < 1)) AND
			 ((`fe_users`.`tstamp` > 0) AND (`fe_users`.`tstamp` <= 1727109546)))
