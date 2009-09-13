<?php

// Upgrade YOURLS and DB schema
function yourls_upgrade( $step, $oldver, $newver, $oldsql, $newsql ) {
	// As of now, there's no other upgrading possibility.
	// In the future this function may contain tests to upgrade
	// from any release to the latest
	yourls_upgrade_to_14( $step );
}


// Upgrade DB Schema from 1.3-RC1 or prior to 1.4
function yourls_upgrade_to_14( $step ) {
	
	switch( $step ) {
	case 1:
		// create table log & table options
		// update table url structure
		// update .htaccess
		yourls_create_tables_for_14();
		yourls_alter_url_table_to_14();
		yourls_create_htaccess();
		yourls_redirect_javascript( YOURLS_SITE."/admin/upgrade.php?step=2&oldver=1.3&newver=1.4&oldsql=100&newsql=200" );
		break;
		
	case 2:
		// convert each link in table url
		yourls_update_table_to_14();
		break;
	
	case 3:
		// update table url structure part 2: recreate indexes
		yourls_alter_url_table_to_14_part_two();
		yourls_redirect_javascript( YOURLS_SITE."/admin/upgrade.php?step=4&oldver=1.3&newver=1.4&oldsql=100&newsql=200" );
		break;
	
	case 4:
		// update version & db_version & next_id in the option table
		// attempt to drop YOURLS_DB_TABLE_NEXTDEC
		yourls_update_options_to_14();
	}
}

// Update options to reflect new version
function yourls_update_options_to_14() {
	yourls_update_option( 'version', YOURLS_VERSION );
	yourls_update_option( 'db_version', YOURLS_DB_VERSION );
	
	global $ydb;
	$table = YOURLS_DB_TABLE_NEXTDEC;
	$next_id = $ydb->get_var("SELECT `next_id` FROM `$table`");
	yourls_update_option( 'next_id', $next_id );
	@$ydb->query( "DROP TABLE `$table`" );
}

// Create new tables for YOURLS 1.4: options & log
function yourls_create_tables_for_14() {
	global $ydb;

	$queries = array();

	$queries[YOURLS_DB_TABLE_OPTIONS] = 
		'CREATE TABLE IF NOT EXISTS `'.YOURLS_DB_TABLE_OPTIONS.'` ('.
		'`option_id` int(11) unsigned NOT NULL auto_increment,'.
		'`option_name` varchar(64) NOT NULL default "",'.
		'`option_value` longtext NOT NULL,'.
		'PRIMARY KEY (`option_id`,`option_name`),'.
		'KEY `option_name` (`option_name`)'.
		');';
		
	$queries[YOURLS_DB_TABLE_LOG] = 
		'CREATE TABLE IF NOT EXISTS `'.YOURLS_DB_TABLE_LOG.'` ('.
		'`click_id` int(11) NOT NULL auto_increment,'.
		'`click_time` datetime NOT NULL,'.
		'`shorturl` varchar(200) NOT NULL,'.
		'`referrer` varchar(200) NOT NULL,'.
		'`user_agent` varchar(255) NOT NULL,'.
		'`ip_address` varchar(41) NOT NULL,'.
		'`country_code` char(2) NOT NULL,'.
		'PRIMARY KEY (`click_id`),'.
		'KEY `shorturl` (`shorturl`,`referrer`,`country_code`)'.
		');';
	
	foreach( $queries as $query ) {
		$ydb->query( $query );
	}
	
	echo "<p>New tables created. Please wait...</p>";

}

// Alter table structure, part 1 (change schema, drop index)
function yourls_alter_url_table_to_14() {
	global $ydb;
	$table = YOURLS_DB_TABLE_URL;

	$alters = array();
	$results = array();
	$alters[] = "ALTER TABLE `$table` CHANGE `id` `keyword` VARCHAR( 200 ) NOT NULL";
	$alters[] = "ALTER TABLE `$table` CHANGE `url` `url` TEXT NOT NULL";
	$alters[] = "ALTER TABLE `$table` DROP PRIMARY KEY";
	
	foreach ( $alters as $query ) {
		$ydb->query( $query );
	}
	
	echo "<p>Structure of existing tables updated. Please wait...</p>";
}

// Alter table structure, part 2 (recreate index after the table is up to date)
function yourls_alter_url_table_to_14_part_two() {
	global $ydb;
	$table = YOURLS_DB_TABLE_URL;
	
	$alters = array();
	$alters[] = "ALTER TABLE `$table` ADD PRIMARY KEY ( `keyword` )";
	
	foreach ( $alters as $query ) {
		$ydb->query( $query );
	}

	echo "<p>New table index created</p>";
}

// Convert each link from 1.3 (id) to 1.4 (keyword) structure
function yourls_update_table_to_14() {
	global $ydb;
	$table = YOURLS_DB_TABLE_URL;

	// Modify each link to reflect new structure
	$chunk = 30;
	$from = isset($_GET['from']) ? intval( $_GET['from'] ) : 0 ;
	$total = yourls_get_db_stats();
	$total = $total['total_links'];
	
	$sql = "SELECT `keyword`,`url` FROM `$table` WHERE 1=1 ORDER BY `url` ASC LIMIT $from, $chunk ;";
	
	$rows = $ydb->get_results($sql);
	
	$count = 0;
	foreach( $rows as $row ) {
		$keyword = $row->keyword;
		$url = $row->url;
		$newkeyword = yourls_int2string( $keyword );
		$result = $ydb->query("UPDATE `$table` SET `keyword` = '$newkeyword' WHERE `url` = '$url';");
		$count++;
	}
	
	if ( $count == $chunk ) {
		// there are probably other rows to convert
		$from = $from + $chunk;
		$remain = $total - $from;
		echo "<p>Converted $chunk database rows ($remain remaining). Continuing... Please do not close this window until it's finished!</p>";
		yourls_redirect_javascript( YOURLS_SITE."/admin/upgrade.php?step=2&oldver=1.3&newver=1.4&oldsql=100&newsql=200&from=$from" );
	} else {
		// All done
		echo '<p>All rows converted! Please wait...</p>';
		yourls_redirect_javascript( YOURLS_SITE."/admin/upgrade.php?step=3&oldver=1.3&newver=1.4&oldsql=100&newsql=200" );
	}
	
}

