<?php

require_once 'lib/t4php/twitter.class.php';
require_once 'conf/config.inc.php';
require_once 'lib/db/db.class.php';

$responses = array(
        'kas tad tā brūķē muti, mamma nav iemācījusi, kā jāuzvedas?',
        'un ar šādu muti Tu mammu bučo?',
        'nu kaut kas traks, manos laikos gan tā nerunāja. Cienīja cilvēkus.',
        'un maizīti Tu arī ar to pašu muti ēd?',
        'kādreiz cilvēki mācēja izteikties bez dranķu spļaušanas.',
        'lasot Jūsu čiepstienus man šķiet ka es rakņājos pa miskasti. Iešu ka nomazgāties.',
        'es necietīšu šādu lamāšanos.',
        'man šķiet ka man nāksies Tevi nopērt un nolikt kaktā. Lamājas kā barons.',
        'lamājas un lamājas, Tev piķis no mutes laukā līst.',
        'kas valodiņu necienī, to arī citi necienīs.',
        'ar šādu valodiņu Tev mīļoto neatrast...',
        'pēc Tavu čiepstienu palasīšanas man jāķer pēc sirdszālēm. Paldies.',
        'ja Tu necieni savu valodu, cieni vismaz citus - vai citiem jālasa Tavas lamas?',
        'nu ko var lamāties, uzdziedi! Labāk ap sirdi paliks.',
        'mute kā laidara vārti.',
	'nu fui, kas tad tādu jifti dzer?',
	'lai tas par visām reizēm ir noklārēts - pēc mutes brūķēšanas ir istaba jāizdvašo, lai velns iet ārā',
	'ja tā mute turpinās tā vervelēt - pastellēšu Kalniņu Juri, lai iet pastāsta tev par vellu un pekli',
	'tie vīterojuma balsieni aiziet vellam ausīs. Arī kad spēlējies ar pēperkokiem, vai ķeģi māmuliņu baksti - vells zina',
	'varbūt ej mācībā nevis sēj rejas tautās?',
	'par šo dūšīgo runu tev goda rakstu nedos, medāļus ar un diližanksu ratos ar 6 zirgiem un kučieri - nevedīs.',
	'tava māmuliņa tagad izraud acis nēzdodziņu sažņaugusi. Kamdēļ viņai gauži dari?',
	'runā braši kā būtu rados ar lielmaņiem. Attopies -  nokļūsi peklē! Vārīsies grāpī ar nabagiem par šiem lāstiem',
	'tavi vārdi - grabastu vērti. Peklē tevi jau gaida!',
	'netīra valodiņa, netīra. Valodiņu jāciena un jāspodrina kā vērtīgāko dzintaru.',
	'valodiņa kā prauls, vai necieni savus senčus? Izkop valodu, lai būtu kā ozolkoks!',
	'krā krā krā, žadzini kā krauklis kokā. Kļūsti par strazdiņu!',
	'kas tā muti brūķē. Saka jau - neskati vīru pēc cepures, bet ja tā hūte dubļiem apmesta...',
	'nu nu nu, vai tad nu vīteris ir īstā vieta lamām...',
	'šādi vīterojumi tikai neslavu citu acīs ceļ...',
	'dūšīga runa, jēga maza. Pastalas ar vārdiem neuzpīsi, viss ar darbiem, viss ar darbiem...'
);

$db = Database::obtain(DB_SERVER, DB_USER, DB_PASS, DB_DATABASE);
$db->connect();

$db->query("SET NAMES 'utf8'");

$twitter = new Twitter(CONSUMER_KEY, CONSUMER_SECRET, ACCESS_TOKEN, ACCESS_SECRET);

function getUnrepliedTweets($interval=7200){
	$db = Database::obtain();
        $sql="SELECT `id`,`tweet_id`,`author`,`text` FROM `".TABLE_TWEETS."` WHERE `replied` = '0' AND `posted` > now() - interval " . $interval . " second ORDER BY `posted` DESC";
        $row = $db->query_first($sql);

        if(!empty($row['tweet_id'])) {
		$db->update(TABLE_TWEETS, array('replied' => '1'), "tweet_id='" . $row['tweet_id'] . "'");
                return array('tweet_id' => $row['tweet_id'], 'author' => $row['author'], 'text' => $row['text']);
        } else {
                return false;
        }

}

while (1) { // Main loop
	$tweet = getUnrepliedTweets(7200); // 3hs
	if ($tweet) {
		echo "Replying to " . $tweet["author"] . "\n";
		echo "Offending text was: " . $tweet["text"] . "\n";
		$status = $twitter->send('@' . $tweet["author"] . ' ' . $responses[array_rand($responses, 1)], $tweet["tweet_id"]);
		if ($status) {
			echo "Tweet has been tweeted\n";
		} else {
			echo "Tweet failed...\n";
		}
		echo "\n";

		$wait = rand(REPLY_MIN_INTERVAL, REPLY_MAX_INTERVAL);
		sleep($wait);
	} else {
		echo "Sleeping " . ceil(REPLY_SLEEP/60) . " minutes\n";
		echo "\n";
		sleep(REPLY_SLEEP);
	}
}
