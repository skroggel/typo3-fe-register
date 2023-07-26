#
# Table structure for table 'tx_feregister_domain_model_optin'
#
CREATE TABLE tx_feregister_domain_model_optin
(
	uid                  int(11) NOT NULL auto_increment,
	pid                  int(11) DEFAULT '0' NOT NULL,

	frontend_user_uid    int(11) DEFAULT '0' NOT NULL,
	frontend_user_update text,
	admins  						 varchar(255) DEFAULT '' NOT NULL,
	token_user           varchar(255) DEFAULT '' NOT NULL,
	token_yes            varchar(255) DEFAULT '' NOT NULL,
	token_no             varchar(255) DEFAULT '' NOT NULL,
	admin_token_yes      varchar(255) DEFAULT '' NOT NULL,
	admin_token_no       varchar(255) DEFAULT '' NOT NULL,
	category             varchar(255) DEFAULT '' NOT NULL,
	foreign_table        varchar(255) DEFAULT '' NOT NULL,
	foreign_uid          int(11) DEFAULT '0' NOT NULL,
	parent_foreign_table varchar(255) DEFAULT '' NOT NULL,
	parent_foreign_uid   int(11) DEFAULT '0' NOT NULL,
	approved             tinyint(1) DEFAULT '0' NOT NULL,
	admin_approved       tinyint(1) DEFAULT '0' NOT NULL,
	data                 longtext,

	tstamp               int(11) unsigned DEFAULT '0' NOT NULL,
	crdate               int(11) unsigned DEFAULT '0' NOT NULL,
	starttime            int(11) unsigned DEFAULT '0' NOT NULL,
	endtime              int(11) unsigned DEFAULT '0' NOT NULL,
	deleted              tinyint(1) unsigned DEFAULT '0' NOT NULL,

	PRIMARY KEY (uid),
	KEY                  parent (pid),
	KEY                  token_user (token_user),
	KEY                  token_yes (token_yes),
	KEY                  token_no (token_no),
	KEY                  admin_token_yes (admin_token_yes),
	KEY                  admin_token_no (admin_token_no),
	KEY                  endtime (endtime)
);


#
# Table structure for table 'fe_users'
#
CREATE TABLE fe_users
(

	tx_feregister_title                  int(11) unsigned DEFAULT '0' NOT NULL,
	tx_feregister_gender                 tinyint(4) DEFAULT '99' NOT NULL,
	tx_feregister_mobile                 varchar(255) DEFAULT ''        NOT NULL,
	tx_feregister_twitter_url            varchar(255) DEFAULT ''        NOT NULL,
	tx_feregister_facebook_url           varchar(255) DEFAULT ''        NOT NULL,
	tx_feregister_xing_url               varchar(255) DEFAULT ''        NOT NULL,

	tx_feregister_register_remote_ip     varchar(255) DEFAULT ''        NOT NULL,
	tx_feregister_language_key           varchar(255) DEFAULT 'default' NOT NULL,

	tx_feregister_login_error_count      tinyint(4) DEFAULT '0' NOT NULL,

	tx_feregister_consent	    			    varchar(255) DEFAULT ''        NOT NULL,
	tx_feregister_consent_privacy			  tinyint(1) DEFAULT '0' NOT NULL,
	tx_feregister_consent_terms				  tinyint(1) DEFAULT '0' NOT NULL,
	tx_feregister_consent_marketing      tinyint(1) DEFAULT '0' NOT NULL,
	tx_feregister_data_protection_status tinyint(4) DEFAULT '0' NOT NULL,
);


CREATE TABLE fe_groups
(
	tx_feregister_is_membership               tinyint(4) unsigned DEFAULT '0' NOT NULL,
	tx_feregister_membership_opening_date     int(11) unsigned DEFAULT '0' NOT NULL,
	tx_feregister_membership_closing_date     int(11) unsigned DEFAULT '0' NOT NULL,
	tx_feregister_membership_mandatory_fields varchar(255) DEFAULT '' NOT NULL,
	tx_feregister_membership_admins           int(11) unsigned DEFAULT '0' NOT NULL,
	tx_feregister_membership_pid              int(11) unsigned DEFAULT '0' NOT NULL,
	KEY  tx_feregister_is_membership (tx_feregister_is_membership),
);


#
# Table structure for table 'tx_feregister_fegroups_beusers_mm'
#
CREATE TABLE tx_feregister_fegroups_beusers_mm
(
	uid_local       int(11) unsigned DEFAULT '0' NOT NULL,
	uid_foreign     int(11) unsigned DEFAULT '0' NOT NULL,
	sorting         int(11) unsigned DEFAULT '0' NOT NULL,
	sorting_foreign int(11) unsigned DEFAULT '0' NOT NULL,

	KEY             uid_local (uid_local),
	KEY             uid_foreign (uid_foreign)
);



#
# Table structure for table 'tx_feregister_domain_model_consent'
#
CREATE TABLE tx_feregister_domain_model_consent
(

	uid                    int(11) NOT NULL auto_increment,
	pid                    int(11) DEFAULT '0' NOT NULL,

	parent                 int(11) DEFAULT '0',
	child                  int(11) DEFAULT '0',

	frontend_user          int(11) DEFAULT '0',
	opt_in     						 int(11) DEFAULT '0',

	foreign_table          varchar(255) DEFAULT '' NOT NULL,
	foreign_uid            varchar(255) DEFAULT '' NOT NULL,
	ip_address             varchar(255) DEFAULT '' NOT NULL,
	user_agent             longtext,
	extension_name         varchar(255) DEFAULT '' NOT NULL,
	plugin_name            varchar(255) DEFAULT '' NOT NULL,
	controller_name        varchar(255) DEFAULT '' NOT NULL,
	action_name            varchar(255) DEFAULT '' NOT NULL,
	comment                varchar(255) DEFAULT '' NOT NULL,
	server_host            varchar(255) DEFAULT '' NOT NULL,
	server_uri             text,
	server_referer_url     text,
	consent_privacy        int(1) DEFAULT '0' NOT NULL,
	consent_terms          int(1) DEFAULT '0' NOT NULL,
	consent_marketing      int(1) DEFAULT '0' NOT NULL,

	tstamp                 int(11) unsigned DEFAULT '0' NOT NULL,
	crdate                 int(11) unsigned DEFAULT '0' NOT NULL,
	deleted                tinyint(4) unsigned DEFAULT '0' NOT NULL,

	PRIMARY KEY (uid),
	KEY                    parent (pid),
	KEY                    opt_in (opt_in),
);

#
# Table structure for table 'tx_feregister_domain_model_encrypteddata'
#
CREATE TABLE tx_feregister_domain_model_encrypteddata
(

	uid            int(11) NOT NULL auto_increment,
	pid            int(11) DEFAULT '0' NOT NULL,

	frontend_user  int(11) DEFAULT '0' NOT NULL,
	foreign_uid    int(11) DEFAULT '0' NOT NULL,
	foreign_table  varchar(255) DEFAULT '' NOT NULL,
	foreign_class  varchar(255) DEFAULT '' NOT NULL,
	search_key     varchar(255) DEFAULT '' NOT NULL,
	encrypted_data text,

	tstamp         int(11) unsigned DEFAULT '0' NOT NULL,
	crdate         int(11) unsigned DEFAULT '0' NOT NULL,
	deleted        tinyint(4) unsigned DEFAULT '0' NOT NULL,

	PRIMARY KEY (uid),
	KEY            parent (pid),

);

#
# Table structure for table 'tx_feregister_domain_model_title'
#
CREATE TABLE tx_feregister_domain_model_title
(

	uid                       int(11) NOT NULL auto_increment,
	pid                       int(11) DEFAULT '0' NOT NULL,

	name                      varchar(255) DEFAULT '' NOT NULL,
	name_long                 varchar(255) DEFAULT '' NOT NULL,
	name_female               varchar(255) DEFAULT '' NOT NULL,
	name_female_long          varchar(255) DEFAULT '' NOT NULL,
	is_title_after            tinyint(4) unsigned DEFAULT '0' NOT NULL,
	is_included_in_salutation tinyint(4) unsigned DEFAULT '0' NOT NULL,
	is_checked                tinyint(4) unsigned DEFAULT '0' NOT NULL,

	tstamp                    int(11) unsigned DEFAULT '0' NOT NULL,
	crdate                    int(11) unsigned DEFAULT '0' NOT NULL,
	cruser_id                 int(11) unsigned DEFAULT '0' NOT NULL,
	deleted                   tinyint(4) unsigned DEFAULT '0' NOT NULL,
	hidden                    tinyint(4) unsigned DEFAULT '0' NOT NULL,
	sorting                   int(11) DEFAULT '0' NOT NULL,

	sys_language_uid          int(11) DEFAULT '0' NOT NULL,
	l10n_parent               int(11) DEFAULT '0' NOT NULL,
	l10n_diffsource           mediumblob,

	PRIMARY KEY (uid),
	KEY                       parent (pid),
);

#
# Table structure for table 'tx_feregister_domain_model_shippingaddress'
#
CREATE TABLE tx_feregister_domain_model_shippingaddress
(

	uid           int(11) NOT NULL auto_increment,
	pid           int(11) DEFAULT '0' NOT NULL,

	frontend_user int(11) unsigned DEFAULT '0' NOT NULL,
	gender        tinyint(4) DEFAULT '0' NOT NULL,
	title         int(11) DEFAULT '0' NOT NULL,
	first_name    varchar(255) DEFAULT '' NOT NULL,
	last_name     varchar(255) DEFAULT '' NOT NULL,
	company       varchar(255) DEFAULT '' NOT NULL,
	address       varchar(255) DEFAULT '' NOT NULL,
	zip           varchar(255) DEFAULT '' NOT NULL,
	city          varchar(255) DEFAULT '' NOT NULL,

	tstamp        int(11) unsigned DEFAULT '0' NOT NULL,
	crdate        int(11) unsigned DEFAULT '0' NOT NULL,
	cruser_id     int(11) unsigned DEFAULT '0' NOT NULL,
	deleted       tinyint(4) unsigned DEFAULT '0' NOT NULL,
	hidden        tinyint(4) unsigned DEFAULT '0' NOT NULL,
	status        tinyint(4) unsigned DEFAULT '0' NOT NULL,

	PRIMARY KEY (uid),
	KEY           parent (pid),

);

