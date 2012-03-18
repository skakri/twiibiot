<?php

require_once 'lib/t4php/twitter.class.php';
require_once 'conf/config.inc.php';
require_once 'lib/db/db.class.php';

$db = Database::obtain(DB_SERVER, DB_USER, DB_PASS, DB_DATABASE);
$db->connect();
$db->query("SET NAMES 'utf8'");

function getPhrase(){
	// get the already existing instance of the $db object
	$db = Database::obtain();

	$sql = "SELECT `id`,`last_id`,`phrase` FROM `".TABLE_PHRASES."` WHERE `updated` < now() - interval " . ceil(SEARCH_GAP/60) . " minute ORDER BY `updated` ASC";
	$row = $db->query_first($sql);

	if(!empty($row['phrase'])) {
                return array('phrase' => $row['phrase'], 'last_id' => $row['last_id'], 'phrase_id' => $row['id']);
	} else {
		return false;
	}
}

function pullTweets($phrase_info){
	$db = Database::obtain();

	$twitter = new Twitter;

	try {
		$results = $twitter->search(
		        array(
		                'q' => rawurlencode($phrase_info['phrase']),
				'exclude' => 'retweets',
		                'geocode' => SEARCH_LATLONG . ',' . SEARCH_DISTANCE . 'km',
		                'result_type' => 'recent',
		                'since_id' => $phrase_info['last_id']
		        )
		);
	} catch (Exception $e) {
		echo "ERROR: $e\n";
		$results = array();
	}

	if (count($results) > 0) {
		echo "found " . count($results) . " tweets for " . $phrase_info['phrase'] . ". \n";
                $db->update(TABLE_PHRASES, array('last_id' => $results[0]->id, 'updated' => 'NOW()'), "id='".$phrase_info['phrase_id']."'");

	        foreach ($results as $result) {
			if ($result->text[0] != "@" || rand(0, 2) == 0) { // 1/3 possibility
				echo "\033[0;32mValid tweet\033[0m\n";
				$primary_id = $db->insert(TABLE_TWEETS,
					array(
						'posted' => date('Y-m-d H:i:s', strtotime($result->created_at)),
						'tweet_id' => $result->id,
						'author' => htmlspecialchars($result->from_user),
						'text' => $result->text,
						'replied' => '0'
					)
				);
		                echo "    Tweet from " . $result->from_user . "\n";
			} else {
                                echo "Is a reply. Skipping...\n";
			}
	        }
	} else {
		// Update anyway for delayed search
                $db->update(TABLE_PHRASES, array('updated' => 'NOW()'), "id='".$phrase_info['phrase_id']."'");
	}
}

while (1) { // Main loop

	$phrase_info = getPhrase();

	if ($phrase_info){
		//echo "Checking for ".$phrase_info['phrase']." ".$phrase_info['phrase_id']." \n";
		pullTweets($phrase_info);

		$wait = rand(SEARCH_MIN_INTERVAL, SEARCH_MAX_INTERVAL);
		//echo "Waiting $wait seconds 'till next search...\n";
		sleep($wait); // let's not be too hasty
	} else {
		echo "Nothing to do, sleeping for " . ceil(SEARCH_GAP/60) . " minutes...\n";
		sleep(SEARCH_GAP);
	}

}

$db->close();
