/*********************************************************************************
 * Copyright (C) 2011-2013 X2Engine Inc. All Rights Reserved.
 *
 * X2Engine Inc.
 * P.O. Box 66752
 * Scotts Valley, California 95067 USA
 *
 * Company website: http://www.x2engine.com
 * Community and support website: http://www.x2community.com
 *
 * X2Engine Inc. grants you a perpetual, non-exclusive, non-transferable license
 * to install and use this Software for your internal business purposes.
 * You shall not modify, distribute, license or sublicense the Software.
 * Title, ownership, and all intellectual property rights in the Software belong
 * exclusively to X2Engine.
 *
 * THIS SOFTWARE IS PROVIDED "AS IS" AND WITHOUT WARRANTIES OF ANY KIND, EITHER
 * EXPRESS OR IMPLIED, INCLUDING WITHOUT LIMITATION THE IMPLIED WARRANTIES OF
 * MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE, TITLE, AND NON-INFRINGEMENT.
 ********************************************************************************/
DROP TABLE IF EXISTS x2_admin;
/*&*/
CREATE TABLE x2_admin(
	id						INT				NOT NULL AUTO_INCREMENT PRIMARY KEY,
	timeout					INT,
	webLeadEmail			VARCHAR(255),
	webLeadEmailAccount		INT NOT NULL DEFAULT -1,
	webTrackerCooldown		INT				DEFAULT 60,
	enableWebTracker		TINYINT			DEFAULT 1,
	currency				VARCHAR(3)		NULL,
	chatPollTime			INT				DEFAULT 2000,
	ignoreUpdates			TINYINT			DEFAULT 0,
	rrId					INT				DEFAULT 0,
	leadDistribution		VARCHAR(255),
	onlineOnly				TINYINT,
	emailBulkAccount		INT	NOT NULL DEFAULT -1,
	emailNotificationAccount	INT NOT NULL DEFAULT -1,
	emailFromName			VARCHAR(255)	NOT NULL DEFAULT "X2CRM",
	emailFromAddr			VARCHAR(255)	NOT NULL DEFAULT '',
	emailBatchSize			INT				NOT NULL DEFAULT 200,
	emailInterval			INT				NOT NULL DEFAULT 60,
	emailUseSignature		VARCHAR(5)		DEFAULT "user",
	emailSignature			TEXT,
	emailType				VARCHAR(20)		DEFAULT "mail",
	emailHost				VARCHAR(255),
	emailPort				INT				DEFAULT 25,
	emailUseAuth			VARCHAR(5)		DEFAULT "user",
	emailUser				VARCHAR(255),
	emailPass				VARCHAR(255),
	emailSecurity			VARCHAR(10),
	installDate				BIGINT			NOT NULL,
	updateDate				BIGINT			NOT NULL,
	updateInterval			INT				NOT NULL DEFAULT 0,
	quoteStrictLock			TINYINT,
	googleIntegration		TINYINT,
	googleClientId			VARCHAR(255),
	googleClientSecret		VARCHAR(255),
	googleAPIKey			VARCHAR(255),
	inviteKey				VARCHAR(255),
	workflowBackdateWindow			INT			NOT NULL DEFAULT -1,
	workflowBackdateRange			INT			NOT NULL DEFAULT -1,
	workflowBackdateReassignment	TINYINT		NOT NULL DEFAULT 1,
	unique_id		VARCHAR(32) NOT NULL DEFAULT "none",
	edition			VARCHAR(10) NOT NULL DEFAULT "opensource",
	serviceCaseEmailAccount		INT NOT NULL DEFAULT -1,
	serviceCaseFromEmailAddress	TEXT,
	serviceCaseFromEmailName	TEXT,
	serviceCaseEmailSubject		TEXT,
	serviceCaseEmailMessage		TEXT,
	srrId						INT				DEFAULT 0,
	sgrrId						INT				DEFAULT 0,
	serviceDistribution			varchar(255),
	serviceOnlineOnly			TINYINT,
    corporateAddress            TEXT,
    eventDeletionTime           INT,
    eventDeletionTypes          TEXT,
    properCaseNames             INT             DEFAULT 1,
    contactNameFormat           VARCHAR(255),
	gaTracking_public			VARCHAR(20) NULL,
	gaTracking_internal			VARCHAR(20) NULL,
    sessionLog                  TINYINT         DEFAULT 0,
    userActionBackdating        TINYINT         DEFAULT 0,
    emailDropbox                TEXT,
    -- The following columns to be left in until the release after when JSON storage
    -- is implemented for emailDropbox, at which point in time they can be
    -- safely deleted.
	emailDropbox_alias			VARCHAR(50) DEFAULT NULL,
	emailDropbox_createContact	TINYINT	DEFAULT 1,
	emailDropbox_zapLineBreaks	TINYINT DEFAULT 0,
	emailDropbox_emptyContact	TINYINT DEFAULT 1,
	emailDropbox_logging		TINYINT DEFAULT 0,
    -- (end email dropbox settings)
    historyPrivacy              VARCHAR(20) DEFAULT "default",
    batchTimeout                INT DEFAULT 300,
    externalBaseUrl             VARCHAR(255) DEFAULT NULL
) COLLATE = utf8_general_ci;
/*&*/
DROP TABLE IF EXISTS x2_changelog;
/*&*/
CREATE TABLE x2_changelog(
	id						INT				NOT NULL AUTO_INCREMENT PRIMARY KEY,
	type					VARCHAR(50)		NOT NULL,
	itemId					INT				NOT NULL,
    recordName              VARCHAR(255),
	changedBy				VARCHAR(255)	NOT NULL,
	changed					TEXT			NULL,
    fieldName				VARCHAR(255),
    oldValue				TEXT,
    newValue				TEXT,
    diff					TINYINT			NOT NULL DEFAULT 0,
	timestamp				INT				NOT NULL DEFAULT 0
) COLLATE = utf8_general_ci;
/*&*/
DROP TABLE IF EXISTS x2_credentials;
/*&*/
CREATE TABLE x2_credentials(
	id			INT UNSIGNED	NOT NULL AUTO_INCREMENT PRIMARY KEY,
	name		VARCHAR(50)	NOT NULL, -- Descriptive title
	userId		INT	NULL, -- Null userId indicates system-wide account, i.e. marketing email
	private		TINYINT NOT NULL DEFAULT 1, -- If userId is null, anyone can use it
	isEncrypted	TINYINT NOT NULL DEFAULT 0, -- Set to 1 when encryption was used on save.
	modelClass	VARCHAR(50)	NOT NULL, -- The class of embedded model used for handling authentication data
	createDate	BIGINT DEFAULT NULL,
	lastUpdated	BIGINT DEFAULT NULL,
	auth		TEXT, -- encrypted (hopefully) authentication data
	INDEX(userId)
) COLLATE = utf8_general_ci;
/*&*/
DROP TABLE IF EXISTS x2_credentials_default;
/*&*/
CREATE TABLE x2_credentials_default(
	userId		INT NOT NULL, -- User ID
	serviceType	VARCHAR(50) NOT NULL, -- "email", "google" etc.
	credId		INT UNSIGNED NOT NULL, -- Credentials record id
	PRIMARY KEY(`userId`,`serviceType`)
) COLLATE = utf8_general_ci;
/*&*/
DROP TABLE IF EXISTS x2_criteria;
/*&*/
CREATE TABLE x2_criteria(
	id						INT				NOT NULL AUTO_INCREMENT PRIMARY KEY,
	modelType				VARCHAR(100),
	modelField				VARCHAR(250),
	modelValue				TEXT,
	comparisonOperator		VARCHAR(10),
	users					TEXT,
	type					VARCHAR(250)
) COLLATE = utf8_general_ci;
/*&*/
DROP TABLE IF EXISTS x2_dropdowns;
/*&*/
CREATE TABLE x2_dropdowns (
	id						INT				NOT NULL AUTO_INCREMENT PRIMARY KEY,
	name					VARCHAR(250),
	options					TEXT,
	multi					TINYINT DEFAULT 0,
    parent                  INT,
    parentVal               VARCHAR(250)
) AUTO_INCREMENT=1000 COLLATE = utf8_general_ci;
/*&*/
DROP TABLE IF EXISTS x2_fields;
/*&*/
CREATE TABLE x2_fields (
	id						INT				NOT NULL AUTO_INCREMENT PRIMARY KEY,
	modelName				VARCHAR(100),
	fieldName				VARCHAR(100),
	attributeLabel			VARCHAR(250),
	modified				INT				DEFAULT 0,
	custom					INT				DEFAULT 1,
	type					VARCHAR(20)		DEFAULT "varchar",
	required				TINYINT			DEFAULT 0,
    uniqueConstraint        TINYINT         DEFAULT 0,
    safe                    TINYINT         DEFAULT 1,
	readOnly				TINYINT			DEFAULT 0,
	linkType				VARCHAR(250),
	searchable				TINYINT			DEFAULT 0,
	relevance				VARCHAR(250),
	isVirtual				TINYINT			DEFAULT 0,
	INDEX (modelName),
	UNIQUE (modelName, fieldName)
) COLLATE = utf8_general_ci;
/*&*/
DROP TABLE IF EXISTS x2_form_layouts;
/*&*/
CREATE TABLE x2_form_layouts (
	id						INT				NOT NULL AUTO_INCREMENT PRIMARY KEY,
	model					VARCHAR(250)	NOT NULL,
	version					VARCHAR(250)	NOT NULL,
	scenario				VARCHAR(20)		NOT NULL DEFAULT 'Default',
	layout					TEXT,
	defaultView				TINYINT			NOT NULL DEFAULT 0,
	defaultForm				TINYINT			NOT NULL DEFAULT 0,
	createDate				BIGINT,
	lastUpdated				BIGINT
) AUTO_INCREMENT=1000 COLLATE = utf8_general_ci;
/*&*/
DROP TABLE IF EXISTS x2_lead_routing;
/*&*/
CREATE TABLE x2_lead_routing(
	id						INT				NOT NULL AUTO_INCREMENT PRIMARY KEY,
	criteria				TEXT,
	users					TEXT,
	priority				INT,
	rrId					INT				DEFAULT 0,
	groupType				INT
) COLLATE = utf8_general_ci;
/*&*/
/* These have foreign key constraints in them and should thus be dropped first: */
DROP TABLE IF EXISTS x2_list_criteria,x2_list_items;
/*&*/
DROP TABLE IF EXISTS x2_lists;
/*&*/
CREATE TABLE x2_lists (
	id						INT UNSIGNED	NOT NULL AUTO_INCREMENT PRIMARY KEY,
	assignedTo				VARCHAR(255),
	name					VARCHAR(100)	NOT NULL,
	description				VARCHAR(250)	NULL,
	type					VARCHAR(20)		NULL,
	logicType				VARCHAR(20)		DEFAULT "AND",
	modelName				VARCHAR(100)	NOT NULL,
	visibility				INT NOT NULL	DEFAULT 1,
	count					INT UNSIGNED	NOT NULL DEFAULT 0,
	createDate				BIGINT			NOT NULL,
	lastUpdated				BIGINT			NOT NULL,
	INDEX(assignedTo),
	INDEX(type)
) ENGINE InnoDB COLLATE utf8_general_ci;
/*&*/
CREATE TABLE x2_list_criteria (
	id						INT				NOT NULL AUTO_INCREMENT PRIMARY KEY,
	listId					INT				UNSIGNED NOT NULL,
	type					VARCHAR(20)		NULL,
	attribute				VARCHAR(40)		NULL,
	comparison				VARCHAR(10)		NULL,
	value					VARCHAR(100)	NOT NULL,
	INDEX (listId),
	FOREIGN KEY (listId) REFERENCES x2_lists(id) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE InnoDB COLLATE utf8_general_ci;
/*&*/
CREATE TABLE x2_list_items (
	id						INT UNSIGNED	NOT NULL AUTO_INCREMENT PRIMARY KEY,
	emailAddress			VARCHAR(255)	NULL,
	contactId				INT				UNSIGNED,
	listId					INT				UNSIGNED NOT NULL,
	uniqueId				VARCHAR(32)		NULL,
	sent					INT				NOT NULL DEFAULT 0,
	opened					INT				UNSIGNED NOT NULL DEFAULT 0,
	clicked					INT				UNSIGNED NOT NULL DEFAULT 0,
	unsubscribed			INT				UNSIGNED NOT NULL DEFAULT 0,
	INDEX (listId),
	INDEX (uniqueId),
	FOREIGN KEY (listId) REFERENCES x2_lists(id) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE InnoDB COLLATE utf8_general_ci;
/*&*/
DROP TABLE IF EXISTS x2_modules;
/*&*/
CREATE TABLE x2_modules (
	id						INT				NOT NULL AUTO_INCREMENT PRIMARY KEY,
	name					VARCHAR(100),
	title					VARCHAR(250),
	visible					INT,
	menuPosition			INT,
	searchable				INT,
	toggleable				INT,
	adminOnly				INT,
	editable				INT,
	custom					INT
) COLLATE = utf8_general_ci;
/*&*/
DROP TABLE IF EXISTS x2_notifications;
/*&*/
CREATE TABLE x2_notifications(
	id						INT				UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
	type					VARCHAR(20),
	comparison				VARCHAR(20),
	value					VARCHAR(250),
	text					TEXT,
	modelType				VARCHAR(250),
	modelId					INT				UNSIGNED,
	fieldName				VARCHAR(250),
	user					VARCHAR(20),
	createdBy				VARCHAR(20),
	viewed					TINYINT			DEFAULT 0,
	createDate				BIGINT
) COLLATE = utf8_general_ci;
/*&*/
DROP TABLE IF EXISTS x2_events;
/*&*/
CREATE TABLE x2_events(
	id						INT				UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
	type					VARCHAR(250),
    subtype                 VARCHAR(250),
    level                   INT,
    visibility              TINYINT             DEFAULT 1,
    text                    TEXT,
	associationType			VARCHAR(250),
	associationId			INT,
	user					VARCHAR(250),
	timestamp				BIGINT,
    lastUpdated             BIGINT,
    important               TINYINT             DEFAULT 0,
    sticky                  TINYINT             DEFAULT 0,
    color                   VARCHAR(10),
    fontColor               VARCHAR(10),
    linkColor               VARCHAR(10)
) COLLATE = utf8_general_ci;
/*&*/
DROP TABLE IF EXISTS x2_events_data;
/*&*/
CREATE TABLE x2_events_data(
	id						INT				UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
	type					VARCHAR(250),
    count                   INT,
	user					VARCHAR(250)
) COLLATE = utf8_general_ci;
/*&*/
DROP TABLE IF EXISTS x2_phone_numbers;
/*&*/
CREATE TABLE x2_phone_numbers(
	id						INT				UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
	modelId					INT				UNSIGNED NOT NULL,
	modelType				VARCHAR(100)	NOT NULL,
	number					VARCHAR(40)		NOT NULL,
    fieldName               VARCHAR(255),
	INDEX (modelType,modelId),
	INDEX (number)
) COLLATE = utf8_general_ci;
/*&*/
DROP TABLE IF EXISTS x2_profile;
/*&*/
CREATE TABLE x2_profile(
	id						INT				NOT NULL AUTO_INCREMENT PRIMARY KEY,
	fullName				VARCHAR(255)	NOT NULL,
	username				VARCHAR(50)		NOT NULL,
	officePhone				VARCHAR(40),
	cellPhone				VARCHAR(40),
	emailAddress			VARCHAR(255)	NOT NULL,
	notes					TEXT,
	status					TINYINT			NOT NULL,
	tagLine					VARCHAR(255),
	lastUpdated				BIGINT,
	updatedBy				VARCHAR(255),
	avatar					TEXT,
	allowPost				TINYINT			DEFAULT 1,
	disablePhoneLinks       TINYINT			DEFAULT 0,
	disableNotifPopup		TINYINT			DEFAULT 0,
	language				VARCHAR(40)		DEFAULT "",
	timeZone				VARCHAR(100)	DEFAULT "",
	resultsPerPage			INT DEFAULT		20,
	widgets					VARCHAR(255),
	widgetOrder				TEXT,
	widgetSettings			TEXT,
	activityFeedOrder       TINYINT         DEFAULT 0,
    /*backgroundColor			VARCHAR(6)		NULL,
	menuBgColor				VARCHAR(6)		NULL,
	menuTextColor			VARCHAR(6)		NULL,
	pageHeaderBgColor		VARCHAR(6)		NULL,
	pageHeaderTextColor		VARCHAR(6)		NULL,
    activityFeedWidgetBgColor       VARCHAR(6)              NULL,
    activityFeedWidgetTextColor     VARCHAR(6)              NULL,
	backgroundImg			VARCHAR(100)	NULL DEFAULT "",
	backgroundTiling		VARCHAR(10)		NULL DEFAULT "",
	pageOpacity				INT				NULL,*/
    theme                   TEXT,
    miscLayoutSettings      TEXT,
    notificationSound       VARCHAR(100)    NULL DEFAULT "X2_Notification.mp3",
    loginSound              VARCHAR(100)    NULL DEFAULT "",
	startPage				VARCHAR(30)		NULL,
	showSocialMedia			TINYINT			NOT NULL DEFAULT 0,
	showDetailView			TINYINT			NOT NULL DEFAULT 1,
	gridviewSettings		TEXT,
	formSettings			TEXT,
	emailUseSignature		VARCHAR(5)		DEFAULT "user",
	emailSignature			LONGTEXT,
	enableFullWidth			TINYINT			DEFAULT 1,
	showActions				VARCHAR(20),
	syncGoogleCalendarId	TEXT,
	syncGoogleCalendarAccessToken TEXT,
	syncGoogleCalendarRefreshToken TEXT,
	googleId				VARCHAR(250),
	userCalendarsVisible	TINYINT			DEFAULT 1,
	groupCalendarsVisible	TINYINT			DEFAULT 1,
	tagsShowAllUsers		TINYINT,
	hideCasesWithStatus		TEXT,
    hiddenTags              TEXT,
    address                 TEXT,
    defaultFeedFilters      TEXT,
    layout					TEXT,
    minimizeFeed            TINYINT         DEFAULT 0,
    fullscreen              TINYINT         DEFAULT 0,
    fullFeedControls        TINYINT         DEFAULT 0,
    feedFilters             TEXT,
    hideBugsWithStatus		TEXT,
    actionFilters           TEXT,
    oldActions              TINYINT         DEFAULT 0,
    mediaWidgetDrive        TINYINT         DEFAULT 0,
    historyShowAll          TINYINT         DEFAULT 0,
    historyShowRels         TINYINT         DEFAULT 0,
    googleRefreshToken      VARCHAR(255),
	UNIQUE(username, emailAddress),
	INDEX (username)
) COLLATE = utf8_general_ci;
/*&*/
DROP TABLE IF EXISTS x2_relationships;
/*&*/
CREATE TABLE x2_relationships (
	id						INT				NOT NULL AUTO_INCREMENT PRIMARY KEY,
	firstType				VARCHAR(100),
	firstId					INT,
	secondType				VARCHAR(100),
	secondId				INT
) COLLATE = utf8_general_ci;
/*&*/
/* The following needs to be dropped first; there is a foreign key constraint */
DROP TABLE IF EXISTS x2_role_to_workflow;
/*&*/
DROP TABLE IF EXISTS x2_roles;
/*&*/
CREATE TABLE x2_roles (
	id						INT				NOT NULL AUTO_INCREMENT PRIMARY KEY,
	name					VARCHAR(250),
	users					TEXT
) ENGINE InnoDB COLLATE = utf8_general_ci;
/*&*/
DROP TABLE IF EXISTS x2_role_exceptions;
/*&*/
CREATE TABLE x2_role_exceptions (
	id						INT				NOT NULL AUTO_INCREMENT primary key,
	workflowId				INT,
	stageId					INT,
	roleId					INT,
	replacementId int
) COLLATE = utf8_general_ci;
/*&*/
DROP TABLE IF EXISTS x2_role_to_permission;
/*&*/
CREATE TABLE x2_role_to_permission (
	id						INT				NOT NULL AUTO_INCREMENT PRIMARY KEY,
	roleId					INT,
	fieldId					INT NOT NULL,
	`permission`				INT,
    INDEX(`fieldId`),
    INDEX(`roleId`)
) COLLATE = utf8_general_ci;
/*&*/
DROP TABLE IF EXISTS x2_role_to_user;
/*&*/
CREATE TABLE x2_role_to_user (
	id						INT				NOT NULL AUTO_INCREMENT PRIMARY KEY,
	roleId					INT,
	userId					INT,
	type					VARCHAR(250)
) COLLATE = utf8_general_ci;
/*&*/
DROP TABLE IF EXISTS x2_sessions;
/*&*/
CREATE TABLE x2_sessions(
	id						CHAR(40)		PRIMARY KEY,
	user					VARCHAR(255),
	lastUpdated				BIGINT,
	IP						VARCHAR(40)		NOT NULL,
	status					TINYINT			NOT NULL DEFAULT 0
) COLLATE = utf8_general_ci;
/*&*/
DROP TABLE IF EXISTS x2_session_log;
/*&*/
CREATE TABLE x2_session_log(
	id						INT             NOT NULL AUTO_INCREMENT PRIMARY KEY,
    sessionId               CHAR(40),
	user					VARCHAR(255),
	timestamp				BIGINT,
	status					VARCHAR(250)
) COLLATE = utf8_general_ci;
/*&*/
DROP TABLE IF EXISTS x2_social;
/*&*/
CREATE TABLE x2_social(
	id						INT				NOT NULL AUTO_INCREMENT PRIMARY KEY,
	type					VARCHAR(40)		NOT NULL,
    subtype                 VARCHAR(250),
	data					TEXT,
	user					VARCHAR(20),
	associationId			INT,
	visibility				TINYINT			DEFAULT 1,
	timestamp				INT,
	lastUpdated				BIGINT
) COLLATE = utf8_general_ci;
/*&*/
DROP TABLE IF EXISTS x2_tags;
/*&*/
CREATE TABLE x2_tags(
	id						INT				NOT NULL AUTO_INCREMENT PRIMARY KEY,
	type					VARCHAR(50)		NOT NULL,
	itemId					INT				NOT NULL,
	taggedBy				VARCHAR(50)		NOT NULL,
	tag						VARCHAR(250)	NOT NULL,
	itemName				VARCHAR(250),
	timestamp				INT				NOT NULL DEFAULT 0,
	INDEX (tag)
) COLLATE = utf8_general_ci;
/*&*/
DROP TABLE IF EXISTS x2_temp_files;
/*&*/
CREATE TABLE x2_temp_files (
	id						INT				NOT NULL AUTO_INCREMENT PRIMARY KEY,
	folder					VARCHAR(10),
	name					TEXT,
	createDate				INT
) COLLATE = utf8_general_ci;
/*&*/
DROP TABLE IF EXISTS x2_urls;
/*&*/
CREATE TABLE x2_urls(
	id					INT					NOT NULL AUTO_INCREMENT PRIMARY KEY,
	title				VARCHAR(20)				NOT NULL,
	url					VARCHAR(256),
	userid				INT,
	timestamp			INT
) COLLATE = utf8_general_ci;
/*&*/
DROP TABLE IF EXISTS x2_track_emails;
/*&*/
CREATE TABLE x2_track_emails(
	id					INT					NOT NULL AUTO_INCREMENT PRIMARY KEY,
	actionId			INT,
	uniqueId			VARCHAR(32),
	opened				INT
) COLLATE = utf8_general_ci;
/*&*/
DROP TABLE IF EXISTS x2_imports;
/*&*/
CREATE TABLE x2_imports(
	id					INT				NOT NULL AUTO_INCREMENT PRIMARY KEY,
	importId			INT				NOT NULL,
	modelId				INT				NOT NULL,
	modelType			VARCHAR(250)	NOT NULL,
	timestamp			BIGINT
) COLLATE = utf8_general_ci;
/*&*/
DROP TABLE IF EXISTS x2_locations;
/*&*/
CREATE TABLE x2_locations(
	id					INT				NOT NULL AUTO_INCREMENT PRIMARY KEY,
	contactId			INT				NOT NULL,
	lat                 FLOAT			NOT NULL,
	lon                 FLOAT           NOT NULL
) COLLATE = utf8_general_ci;
/*&*/
DROP TABLE IF EXISTS x2_maps;
/*&*/
CREATE TABLE x2_maps(
    id                  INT				NOT NULL AUTO_INCREMENT PRIMARY KEY,
    owner               VARCHAR(250),
    name                VARCHAR(250),
    contactId           INT,
    params              TEXT,
    centerLat           FLOAT,
    centerLng           FLOAT,
    zoom                INT
) COLLATE = utf8_general_ci;
/*&*/
DROP TABLE IF EXISTS x2_tips;
/*&*/
CREATE TABLE x2_tips(
    id                  INT                             NOT NULL AUTO_INCREMENT PRIMARY KEY,
    tip                 TEXT,
    edition             VARCHAR(10),
    admin               TINYINT,
    module              VARCHAR(255)
) COLLATE = utf8_general_ci;
/*&*/
DROP TABLE IF EXISTS x2_like_to_post;
/*&*/
CREATE TABLE `x2_like_to_post` (
  userId             int unsigned     NOT NULL,
  postId             int unsigned     NOT NULL,
  INDEX (postId)
) COLLATE = utf8_general_ci;
/*&*/
DROP TABLE IF EXISTS x2_view_log;
/*&*/
CREATE TABLE `x2_view_log` (
	id						INT				AUTO_INCREMENT PRIMARY KEY,
	user                    VARCHAR(255),
	recordType				VARCHAR(255),
	recordId				INT,
	timestamp				BIGINT
) COLLATE = utf8_general_ci;
/*&*/
DROP TABLE IF EXISTS x2_chart_settings;
/*&*/
CREATE TABLE `x2_chart_settings` (
  `id`                      int(11)         NOT NULL AUTO_INCREMENT,
  `userId`                  int(11)         NOT NULL,
  `name`                    varchar(100)    NOT NULL,
  `chartType`               varchar(100)    NOT NULL,
  `settings`                TEXT,
  PRIMARY KEY (`id`),
  UNIQUE KEY `userId` (`userId`,`name`)
) COLLATE = utf8_general_ci;
/*&*/
/* This table has a foreign key constraint in it and should thus be dropped first: */
DROP TABLE IF EXISTS x2_trigger_logs;
/*&*/
DROP TABLE IF EXISTS x2_flows;
/*&*/
CREATE TABLE x2_flows(
	id						INT				AUTO_INCREMENT PRIMARY KEY,
	active					TINYINT			NOT NULL DEFAULT 1,
	name					VARCHAR(100)	NOT NULL,
	triggerType				VARCHAR(40)		NOT NULL,
	modelClass				VARCHAR(40),
	flow					TEXT,
	createDate				BIGINT			NOT NULL,
	lastUpdated				BIGINT			NOT NULL
) ENGINE=InnoDB, COLLATE = utf8_general_ci;
/*&*/
CREATE TABLE `x2_trigger_logs` (
  `id`                          INT             NOT NULL AUTO_INCREMENT,
  `flowId`                      INT             NOT NULL,
  `triggeredAt`                 BIGINT          NOT NULL,
  `triggerLog`                  TEXT,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`flowId`) REFERENCES x2_flows(`id`) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB, COLLATE = utf8_general_ci;
